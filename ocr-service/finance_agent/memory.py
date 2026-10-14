import re
from typing import Any


def extract_memory_updates(messages: list[dict[str, Any]]) -> list[dict[str, Any]]:
    latest_user_message = next(
        (
            message.get("content", "")
            for message in reversed(messages)
            if message.get("role") == "user"
            and isinstance(message.get("content"), str)
        ),
        "",
    )
    text = latest_user_message.casefold()
    updates: list[dict[str, Any]] = []

    buy_match = None
    cook_match = re.search(
        r"\b(suka|lebih suka|prefer)\b.{0,30}\b(masak|memasak)\b(?:.{0,20}\bkarena\b\s*(?P<reason>[^.!?]+))?",
        text,
    )
    if cook_match:
        value = {"mode": "cook"}
        if cook_match.group("reason"):
            value["reason"] = cook_match.group("reason").strip()[:500]
        updates.append({
            "memory_key": "food.preparation_preference",
            "value": value,
        })
    else:
        buy_match = re.search(
            r"\b(suka|lebih suka|prefer)\b.{0,30}\b(beli|membeli)\b(?:.{0,20}\bkarena\b\s*(?P<reason>[^.!?]+))?",
            text,
        )
    if not cook_match and buy_match:
        value = {"mode": "buy"}
        if buy_match.group("reason"):
            value["reason"] = buy_match.group("reason").strip()[:500]
        updates.append({
            "memory_key": "food.preparation_preference",
            "value": value,
        })

    if re.search(r"\b(jawaban|saran).{0,30}\b(detail|rinci)\b", text):
        updates.append({
            "memory_key": "finance.advice_style",
            "value": {"style": "detailed"},
        })
    elif re.search(r"\b(jawaban|saran).{0,30}\b(praktis|singkat)\b", text):
        updates.append({
            "memory_key": "finance.advice_style",
            "value": {"style": "practical"},
        })

    if re.search(r"\b(bahasa|gunakan).{0,20}\bindonesia\b", text):
        updates.append({
            "memory_key": "communication.language",
            "value": {"language": "id"},
        })
    elif re.search(r"\b(bahasa|gunakan).{0,20}\binggris\b", text):
        updates.append({
            "memory_key": "communication.language",
            "value": {"language": "en"},
        })

    transport_match = re.search(
        r"\b(?:preferensi|prefer|lebih suka|biasanya|gunakan)\b.{0,40}\b(?:naik|pakai|menggunakan)?\s*"
        r"(motor|mobil|sepeda|jalan kaki|transportasi umum|ojek|tak?si)\b",
        text,
    )
    if transport_match:
        updates.append({
            "memory_key": "transport.preferred_mode",
            "value": {"mode": transport_match.group(1).strip()},
        })

    priority_match = re.search(
        r"\b(?:prioritas|utamakan|fokus(?:kan)?(?: pengeluaran)?)\b\s*(?:pengeluaran|belanja)?\s*[:=]?\s*(.+?)(?:[.!?]|$)",
        text,
    )
    if priority_match:
        items = [
            item.strip(" .")
            for item in re.split(r",|\bdan\b|/", priority_match.group(1))
            if item.strip(" .")
        ][:20]
        if items and all(len(item) <= 100 for item in items):
            updates.append({
                "memory_key": "finance.spending_priority",
                "value": {"items": items},
            })

    return updates
