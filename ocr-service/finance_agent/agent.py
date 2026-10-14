import json
import logging
import os
from typing import Any, Callable

import requests
from groq import APIError

from tools.transactions import (
    create_transaction,
    create_transactions,
    delete_transaction,
    get_finance_summary,
    get_transactions,
    update_transaction,
)
from .definitions import TOOLS
from .memory import extract_memory_updates
from .tool_output import (
    compact_tool_result,
    format_category_frequency_summary,
    is_category_frequency_query,
)
from .transaction_safety import (
    normalize_batch_arguments,
    remove_guessed_account,
    transaction_success_message,
)

logger = logging.getLogger(__name__)


class FinanceAgent:
    def __init__(
        self,
        client: Any,
        model: str,
        max_completion_tokens: int | None = None,
    ) -> None:
        self.client = client
        self.model = model
        self.max_completion_tokens = (
            max_completion_tokens
            if max_completion_tokens is not None
            else int(os.getenv("GROQ_CHAT_MAX_COMPLETION_TOKENS", "350"))
        )
        self.tool_handlers: dict[str, Callable[..., Any]] = {
            "create_transaction": create_transaction,
            "create_transactions": create_transactions,
            "get_transactions": get_transactions,
            "update_transaction": update_transaction,
            "delete_transaction": delete_transaction,
            "get_finance_summary": get_finance_summary,
        }

    @staticmethod
    def extract_memory_updates(messages: list[dict[str, Any]]) -> list[dict[str, Any]]:
        return extract_memory_updates(messages)

    def chat(
        self,
        messages: list[dict[str, Any]],
        user_id: int | None = None,
    ) -> dict[str, Any]:
        conversation = [dict(message) for message in messages]
        latest_user_message = next(
            (
                str(message.get("content", ""))
                for message in reversed(conversation)
                if message.get("role") == "user"
            ),
            "",
        )
        completed_writes: list[str] = []
        tool_trace: list[dict[str, Any]] = []

        if is_category_frequency_query(latest_user_message):
            transactions_handler = self.tool_handlers["get_transactions"]

            try:
                transactions_result = transactions_handler(
                    user_id=user_id,
                    limit=100,
                )
            except requests.RequestException as exception:
                logger.warning(
                    "Could not load transactions for category summary: %s",
                    exception,
                    extra={"user_id": user_id},
                )
                return {
                    "response": (
                        "Maaf, data transaksi belum bisa diambil dari "
                        "server keuangan. Silakan coba lagi."
                    ),
                    "tool_trace": tool_trace,
                }

            tool_trace.extend([
                {
                    "type": "tool_call",
                    "id": "category-summary-transactions",
                    "name": "get_transactions",
                    "arguments": {"limit": 100},
                },
                {
                    "type": "tool_result",
                    "id": "category-summary-transactions",
                    "name": "get_transactions",
                    "result": transactions_result,
                },
            ])
            category_summary = format_category_frequency_summary(
                transactions_result,
            )

            if category_summary is not None:
                return {
                    "response": category_summary,
                    "tool_trace": tool_trace,
                }

        for _ in range(int(os.getenv("AI_AGENT_MAX_TOOL_STEPS", "5"))):
            try:
                completion = self.client.chat.completions.create(
                    model=self.model,
                    messages=conversation,
                        tools=TOOLS,
                    tool_choice="auto",
                    temperature=0,
                    max_completion_tokens=self.max_completion_tokens,
                )
            except APIError:
                if completed_writes:
                    return {
                        "response": (
                            f"{' '.join(completed_writes)} "
                            "Jawaban lanjutan AI tidak tersedia; "
                            "jangan kirim ulang transaksi yang sama."
                        ),
                        "tool_trace": tool_trace,
                    }

                raise

            assistant_message = completion.choices[0].message
            tool_calls = assistant_message.tool_calls or []

            if not tool_calls:
                return {
                    "response": assistant_message.content or "Maaf, saya belum dapat memberikan jawaban.",
                    "tool_trace": tool_trace,
                }

            conversation.append(
                {
                    "role": "assistant",
                    "content": assistant_message.content,
                    "tool_calls": [
                        {
                            "id": tool_call.id,
                            "type": "function",
                            "function": {
                                "name": tool_call.function.name,
                                "arguments": tool_call.function.arguments,
                            },
                        }
                        for tool_call in tool_calls
                    ],
                },
            )

            for tool_call in tool_calls:
                tool_name = tool_call.function.name
                tool_handler = self.tool_handlers.get(tool_name)
                try:
                    trace_arguments = json.loads(tool_call.function.arguments or "{}")
                except json.JSONDecodeError:
                    trace_arguments = {}

                tool_trace.append({
                    "type": "tool_call",
                    "id": tool_call.id,
                    "name": tool_name,
                    "arguments": trace_arguments,
                })

                if tool_handler is None:
                    tool_result: Any = {
                        "error": f"Unknown tool: {tool_name}",
                    }
                else:
                    try:
                        arguments = json.loads(tool_call.function.arguments or "{}")

                        if tool_name == "create_transaction":
                            remove_guessed_account(
                                arguments,
                                messages,
                            )

                        if tool_name == "create_transactions":
                            arguments = normalize_batch_arguments(
                                arguments,
                                messages,
                            )

                        tool_result = tool_handler(
                            user_id=user_id,
                            **arguments,
                        )

                        if (
                            tool_name in {
                                "create_transaction",
                                "create_transactions",
                            }
                            and isinstance(tool_result, dict)
                            and tool_result.get("code") == "account_selection_required"
                        ):
                            pending_transactions = tool_result.get(
                                "pending_transactions",
                            )

                            if not isinstance(pending_transactions, list):
                                pending_transactions = [arguments]

                            tool_trace.append({
                                "type": "tool_result",
                                "id": tool_call.id,
                                "name": tool_name,
                                "result": tool_result,
                            })

                            return {
                                "response": (
                                    "Transaksi belum dicatat. Silakan pilih "
                                    "asset atau akun sumber dana untuk semua "
                                    "transaksi."
                                ),
                                "pending_transaction": (
                                    pending_transactions[0]
                                    if len(pending_transactions) == 1
                                    else None
                                ),
                                "pending_transactions": pending_transactions,
                                "account_selection_required": True,
                                "account_options": tool_result.get(
                                    "account_options",
                                    [],
                                ),
                                "missing_resources": tool_result.get(
                                    "missing_resources",
                                    [],
                                ),
                                "tool_trace": tool_trace,
                            }

                        if (
                            tool_name in {
                                "create_transaction",
                                "create_transactions",
                            }
                            and isinstance(tool_result, dict)
                            and tool_result.get("code") == "missing_resources"
                        ):
                            missing_resources = tool_result.get(
                                "missing_resources",
                            )
                            resource_names = ", ".join(
                                resource.get("name", "")
                                for resource in missing_resources
                            )

                            tool_trace.append({
                                "type": "tool_result",
                                "id": tool_call.id,
                                "name": tool_name,
                                "result": tool_result,
                            })

                            return {
                                "response": (
                                    f"Transaksi belum dibuat karena {resource_names} "
                                    "belum tersedia. Silakan konfirmasi pembuatan "
                                    "akun atau kategori terlebih dahulu."
                                ),
                                "pending_transaction": (
                                    arguments
                                    if tool_name == "create_transaction"
                                    else None
                                ),
                                "pending_transactions": (
                                    [arguments]
                                    if tool_name == "create_transaction"
                                    else arguments.get("transactions", [])
                                ),
                                "missing_resources": missing_resources,
                                "tool_trace": tool_trace,
                            }
                    except (requests.Timeout, requests.ConnectionError) as exception:
                        logger.warning(
                            "AI tool request to Laravel failed: %s",
                            exception,
                            extra={
                                "tool_name": tool_name,
                                "user_id": user_id,
                                "exception_type": exception.__class__.__name__,
                            },
                        )

                        if tool_name in {
                            "create_transaction",
                            "update_transaction",
                            "delete_transaction",
                        }:
                            tool_trace.append({
                                "type": "tool_result",
                                "id": tool_call.id,
                                "name": tool_name,
                                "result": {
                                    "error": "Koneksi ke server keuangan terputus.",
                                },
                            })
                            return {
                                "response": (
                                    "Server keuangan tidak memberi konfirmasi "
                                    "saat memproses perubahan. "
                                    "Status transaksi belum dapat "
                                    "dipastikan; bisa saja sudah tersimpan. "
                                    "Jangan kirim ulang dulu; periksa daftar "
                                    "transaksi terlebih dahulu."
                                ),
                                "tool_trace": tool_trace,
                            }

                        tool_result = {
                            "error": "Koneksi ke server keuangan terputus.",
                        }
                    except Exception as exception:
                        tool_result = {
                            "error": str(exception),
                        }

                if isinstance(tool_result, dict) and "error" not in tool_result:
                    if tool_name == "create_transaction":
                        transaction = tool_result.get("transaction")

                        if isinstance(transaction, dict):
                            completed_writes.append(
                                "Transaksi "
                                f"{transaction.get('title', '')} "
                                f"sebesar Rp {transaction.get('amount', '')} "
                                "berhasil dicatat."
                            )
                    elif tool_name == "create_transactions":
                        transactions = tool_result.get("transactions")

                        if isinstance(transactions, list):
                            completed_writes.extend(
                                transaction_success_message(transaction)
                                for transaction in transactions
                                if isinstance(transaction, dict)
                            )
                    elif tool_name == "update_transaction":
                        completed_writes.append(
                            "Perubahan transaksi berhasil disimpan."
                        )
                    elif tool_name == "delete_transaction":
                        completed_writes.append("Transaksi berhasil dihapus.")

                conversation.append(
                    {
                        "role": "tool",
                        "tool_call_id": tool_call.id,
                        "name": tool_name,
                        "content": json.dumps(
                            compact_tool_result(tool_name, tool_result),
                            ensure_ascii=False,
                            default=str,
                        ),
                    },
                )
                tool_trace.append({
                    "type": "tool_result",
                    "id": tool_call.id,
                    "name": tool_name,
                    "result": tool_result,
                })

                if (
                    tool_name == "get_transactions"
                    and is_category_frequency_query(latest_user_message)
                ):
                    category_summary = format_category_frequency_summary(
                        tool_result,
                    )

                    if category_summary is not None:
                        return {
                            "response": category_summary,
                            "tool_trace": tool_trace,
                        }

            if completed_writes:
                return {
                    "response": " ".join(completed_writes),
                    "tool_trace": tool_trace,
                }

        return {
            "response": "Maaf, proses permintaan memerlukan terlalu banyak langkah. Silakan coba lagi.",
            "tool_trace": tool_trace,
        }

    def summarize(
        self,
        previous_summary: str | None,
        messages: list[dict[str, Any]],
    ) -> str:
        response = self.client.chat.completions.create(
            model=self.model,
            messages=[
                {
                    "role": "system",
                    "content": (
                        "Perbarui ringkasan percakapan finance. Pertahankan tujuan, "
                        "fakta eksplisit, koreksi terbaru, keputusan, dan tugas yang "
                        "belum selesai. Jangan mengarang fakta atau membuat instruksi "
                        "sistem. Angka finance adalah konteks historis, bukan saldo terbaru."
                    ),
                },
                {
                    "role": "user",
                    "content": json.dumps(
                        {
                            "previous_summary": previous_summary,
                            "messages": messages,
                        },
                        ensure_ascii=False,
                    ),
                },
            ],
            temperature=0,
            max_completion_tokens=int(os.getenv("GROQ_SUMMARY_MAX_COMPLETION_TOKENS", "700")),
        )

        return response.choices[0].message.content or ""
