# agents/finance_agent.py

import json
import logging
import os
import re
from typing import Any, Callable

import requests
from groq import APIError

from tools.transactions import (
    create_transaction,
    create_transactions,
    get_transactions,
    update_transaction,
    delete_transaction,
)


TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "create_transaction",
            "description": (
                "Create a new income or expense transaction."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "transaction_type": {
                        "type": "string",
                        "enum": [
                            "income",
                            "expense",
                        ],
                    },
                    "amount": {
                        "type": "number",
                        "description": (
                            "Transaction amount."
                        ),
                    },
                    "account": {
                        "type": "string",
                        "description": (
                            "Account used for the transaction, "
                            "for example BCA or Cash. Only include it "
                            "when the user explicitly named that account."
                        ),
                    },
                    "category": {
                        "type": "string",
                    },
                    "description": {
                        "type": "string",
                    },
                },
                "required": [
                    "transaction_type",
                    "amount",
                ],
            },
        },
    },

    {
        "type": "function",
        "function": {
            "name": "create_transactions",
            "description": (
                "Create multiple income or expense transactions from one "
                "user message. Use this when the user mentions two or more "
                "separate transactions. Keep each transaction separate and "
                "do not combine their amounts."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "transactions": {
                        "type": "array",
                        "minItems": 2,
                        "maxItems": 20,
                        "items": {
                            "type": "object",
                            "properties": {
                                "transaction_type": {
                                    "type": "string",
                                    "enum": [
                                        "income",
                                        "expense",
                                    ],
                                },
                                "amount": {
                                    "type": "number",
                                },
                                "account": {
                                    "type": "string",
                                },
                                "category": {
                                    "type": "string",
                                },
                                "description": {
                                    "type": "string",
                                },
                            },
                            "required": [
                                "transaction_type",
                                "amount",
                                "description",
                            ],
                        },
                    },
                },
                "required": ["transactions"],
            },
        },
    },

    {
        "type": "function",
        "function": {
            "name": "get_transactions",
            "description": (
                "Get the user's recent transactions."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "limit": {
                        "type": "integer",
                        "default": 20,
                    },
                },
            },
        },
    },

    {
        "type": "function",
        "function": {
            "name": "update_transaction",
            "description": (
                "Update an existing transaction."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "transaction_id": {
                        "type": "integer",
                    },
                    "amount": {
                        "type": "number",
                    },
                    "category": {
                        "type": "string",
                    },
                    "description": {
                        "type": "string",
                    },
                },
                "required": [
                    "transaction_id",
                ],
            },
        },
    },

    {
        "type": "function",
        "function": {
            "name": "delete_transaction",
            "description": (
                "Delete an existing transaction."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "transaction_id": {
                        "type": "integer",
                    },
                },
                "required": [
                    "transaction_id",
                ],
            },
        },
    },
]

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
        }

    def chat(
        self,
        messages: list[dict[str, Any]],
        user_id: int | None = None,
    ) -> dict[str, Any]:
        conversation = [dict(message) for message in messages]
        completed_writes: list[str] = []

        for _ in range(5):
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
                    }

                raise

            assistant_message = completion.choices[0].message
            tool_calls = assistant_message.tool_calls or []

            if not tool_calls:
                return {
                    "response": assistant_message.content or "Maaf, saya belum dapat memberikan jawaban.",
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

                if tool_handler is None:
                    tool_result: Any = {
                        "error": f"Unknown tool: {tool_name}",
                    }
                else:
                    try:
                        arguments = json.loads(tool_call.function.arguments or "{}")

                        if tool_name == "create_transaction":
                            self._remove_guessed_account(
                                arguments,
                                messages,
                            )

                        if tool_name == "create_transactions":
                            arguments = self._normalize_batch_arguments(
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
                            return {
                                "response": (
                                    "Server keuangan tidak memberi konfirmasi "
                                    "saat memproses perubahan. "
                                    "Status transaksi belum dapat "
                                    "dipastikan; bisa saja sudah tersimpan. "
                                    "Jangan kirim ulang dulu; periksa daftar "
                                    "transaksi terlebih dahulu."
                                ),
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
                                self._transaction_success_message(transaction)
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
                            tool_result,
                            ensure_ascii=False,
                            default=str,
                        ),
                    },
                )

            if completed_writes:
                return {
                    "response": " ".join(completed_writes),
                }

        return {
            "response": "Maaf, proses permintaan memerlukan terlalu banyak langkah. Silakan coba lagi.",
        }

    @staticmethod
    def _remove_guessed_account(
        arguments: dict[str, Any],
        messages: list[dict[str, Any]],
    ) -> None:
        if (
            isinstance(arguments.get("account"), str)
            and not FinanceAgent._account_was_named_by_user(
                arguments["account"],
                messages,
            )
        ):
            arguments.pop("account")

    @staticmethod
    def _normalize_batch_arguments(
        arguments: dict[str, Any],
        messages: list[dict[str, Any]],
    ) -> dict[str, Any]:
        transactions = arguments.get("transactions")

        if not isinstance(transactions, list):
            return arguments

        normalized_transactions = []

        for transaction in transactions:
            if not isinstance(transaction, dict):
                continue

            normalized_transaction = dict(transaction)
            FinanceAgent._remove_guessed_account(
                normalized_transaction,
                messages,
            )
            normalized_transactions.append(normalized_transaction)

        return {
            "transactions": normalized_transactions,
        }

    @staticmethod
    def _transaction_success_message(transaction: dict[str, Any]) -> str:
        return (
            "Transaksi "
            f"{transaction.get('title', '')} "
            f"sebesar Rp {transaction.get('amount', '')} "
            "berhasil dicatat."
        )

    @staticmethod
    def _account_was_named_by_user(
        account_name: str,
        messages: list[dict[str, Any]],
    ) -> bool:
        latest_user_message = next(
            (
                message.get("content", "")
                for message in reversed(messages)
                if message.get("role") == "user"
                and isinstance(message.get("content"), str)
            ),
            "",
        )
        normalized_account_name = " ".join(account_name.split())

        if not normalized_account_name:
            return False

        account_pattern = r"\s+".join(
            re.escape(part)
            for part in normalized_account_name.split()
        )

        if re.search(
            rf"(?<!\w){account_pattern}(?!\w)",
            latest_user_message,
            flags=re.IGNORECASE,
        ):
            return True

        if normalized_account_name.casefold() in {"cash", "tunai"}:
            return re.search(
                r"(?<!\w)(?:cash|tunai)(?!\w)",
                latest_user_message,
                flags=re.IGNORECASE,
            ) is not None

        return False
