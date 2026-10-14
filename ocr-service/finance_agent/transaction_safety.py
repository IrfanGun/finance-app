import re
from typing import Any


def remove_guessed_account(
    arguments: dict[str, Any],
    messages: list[dict[str, Any]],
) -> None:
    if (
        isinstance(arguments.get("account"), str)
        and not _account_was_named_by_user(
            arguments["account"],
            messages,
        )
    ):
        arguments.pop("account")


def normalize_batch_arguments(
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
        remove_guessed_account(
            normalized_transaction,
            messages,
        )
        normalized_transactions.append(normalized_transaction)

    return {
        "transactions": normalized_transactions,
    }


def transaction_success_message(transaction: dict[str, Any]) -> str:
    return (
        "Transaksi "
        f"{transaction.get('title', '')} "
        f"sebesar Rp {transaction.get('amount', '')} "
        "berhasil dicatat."
    )


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
