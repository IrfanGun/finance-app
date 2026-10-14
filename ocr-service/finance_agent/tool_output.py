import re
from decimal import Decimal, InvalidOperation
from typing import Any

def compact_tool_result(
    tool_name: str,
    tool_result: Any,
) -> Any:
    if tool_name != "get_transactions" or not isinstance(tool_result, dict):
        return tool_result

    transactions = tool_result.get("transactions")

    if not isinstance(transactions, list):
        return tool_result

    compact_transactions: list[dict[str, Any]] = []

    for transaction in transactions:
        if not isinstance(transaction, dict):
            continue

        compact_transaction = {
            key: transaction[key]
            for key in (
                "id",
                "date",
                "type",
                "amount",
                "title",
                "note",
            )
            if key in transaction
        }

        for relation in ("category", "account"):
            related_record = transaction.get(relation)

            if isinstance(related_record, dict) and related_record.get("name"):
                compact_transaction[relation] = {
                    "name": related_record["name"],
                }

        compact_transactions.append(compact_transaction)

    return {
        **{
            key: value
            for key, value in tool_result.items()
            if key != "transactions"
        },
        "transactions": compact_transactions,
    }


def is_category_frequency_query(message: str) -> bool:
    normalized_message = message.lower()

    asks_frequency = re.search(
        r"\b(terbanyak|paling banyak|paling sering|frekuensi)\b",
        normalized_message,
    )
    mentions_transactions = re.search(
        r"\b(transaksi|kategori)\b",
        normalized_message,
    )

    return bool(asks_frequency and mentions_transactions)


def format_category_frequency_summary(tool_result: Any) -> str | None:
    if not isinstance(tool_result, dict):
        return None

    transactions = tool_result.get("transactions")

    if not isinstance(transactions, list):
        return None

    category_totals: dict[str, dict[str, Any]] = {}

    for transaction in transactions:
        if not isinstance(transaction, dict):
            continue

        category = transaction.get("category")
        category_name = (
            category.get("name")
            if isinstance(category, dict)
            else category
        )
        category_name = (
            category_name.strip()
            if isinstance(category_name, str) and category_name.strip()
            else "Tanpa kategori"
        )
        amount_value = transaction.get("amount", 0)

        try:
            amount = Decimal(str(amount_value))
        except (InvalidOperation, TypeError, ValueError):
            amount = Decimal("0")

        category_total = category_totals.setdefault(
            category_name,
            {
                "count": 0,
                "amount": Decimal("0"),
            },
        )
        category_total["count"] += 1
        category_total["amount"] += amount

    if not category_totals:
        return "Belum ada transaksi yang tercatat untuk diringkas."

    sorted_categories = sorted(
        category_totals.items(),
        key=lambda item: (
            -item[1]["count"],
            -item[1]["amount"],
            item[0].casefold(),
        ),
    )
    maximum_count = sorted_categories[0][1]["count"]
    leading_categories = [
        category_name
        for category_name, totals in sorted_categories
        if totals["count"] == maximum_count
    ]

    if len(leading_categories) == 1:
        leading_text = f"{leading_categories[0]} ({maximum_count} transaksi)"
    else:
        leading_text = (
            f"{', '.join(leading_categories)} "
            f"(masing-masing {maximum_count} transaksi)"
        )

    lines = [
        f"Kategori terbanyak: **{leading_text}**.",
        f"Rincian {len(transactions)} transaksi (bar menunjukkan jumlah):",
        "",
    ]

    for category_name, totals in sorted_categories:
        bar_length = max(
            1,
            round(totals["count"] / maximum_count * 12),
        )
        bar = "█" * bar_length
        amount_text = f"{int(totals['amount']):,}".replace(",", ".")
        lines.append(
            f"- **{category_name}** — {totals['count']} transaksi · "
            f"total nilai **Rp {amount_text}**"
        )
        lines.append(f"  `{bar}`")

    return "\n".join(lines)


