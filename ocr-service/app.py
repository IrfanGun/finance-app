from __future__ import annotations

import base64
import json
import os
from io import BytesIO
from math import isfinite
from typing import Any

from dotenv import load_dotenv
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from groq import Groq
from PIL import Image, ImageOps


load_dotenv()


MAX_FILE_SIZE = 8 * 1024 * 1024
MAX_IMAGE_DIMENSION = 2400
DEFAULT_VISION_MODEL = "qwen/qwen3.8-27b"
GROQ_CLIENT: Groq | None = None

app = FastAPI(
    title="Receipt LLM Service",
    version="2.0.0",
)


@app.on_event("startup")
def startup_event() -> None:
    global GROQ_CLIENT

    api_key = os.getenv("GROQ_API_KEY")

    if not api_key:
        print("GROQ_API_KEY belum dikonfigurasi")

        return

    GROQ_CLIENT = Groq(api_key=api_key)
    print("Groq vision client initialized")


def prepare_image(data: bytes) -> tuple[bytes, str]:
    """Validate the upload and normalize it for the vision model."""
    with Image.open(BytesIO(data)) as source_image:
        image = ImageOps.exif_transpose(source_image).convert("RGB")

    width, height = image.size
    largest_dimension = max(width, height)

    if largest_dimension > MAX_IMAGE_DIMENSION:
        scale = MAX_IMAGE_DIMENSION / largest_dimension
        image = image.resize(
            (round(width * scale), round(height * scale)),
            Image.Resampling.LANCZOS,
        )

    output = BytesIO()
    image.save(
        output,
        format="JPEG",
        quality=92,
        optimize=True,
    )

    return output.getvalue(), "image/jpeg"


def image_data_url(image: bytes, mime_type: str) -> str:
    encoded_image = base64.b64encode(image).decode("ascii")

    return f"data:{mime_type};base64,{encoded_image}"


def as_integer(value: Any) -> int | None:
    """Accept only numeric values produced by the structured LLM response."""
    if isinstance(value, bool):
        return None

    if isinstance(value, int):
        return value

    if isinstance(value, float) and isfinite(value) and value.is_integer():
        return int(value)

    if isinstance(value, str):
        try:
            return int(value.strip())
        except ValueError:
            return None

    return None


def normalize_items(value: Any) -> list[dict[str, Any]]:
    if not isinstance(value, list):
        return []

    items: list[dict[str, Any]] = []

    for item in value:
        if not isinstance(item, dict):
            continue

        name = item.get("name")

        if not isinstance(name, str) or not name.strip():
            continue

        quantity = as_integer(item.get("quantity"))
        unit_price = as_integer(item.get("unit_price"))
        total = as_integer(item.get("total"))

        items.append(
            {
                "name": name.strip(),
                "quantity": quantity if quantity is not None else 1,
                "unit_price": unit_price,
                "total": total,
            },
        )

    return items


def normalize_receipt(value: Any) -> dict[str, Any]:
    if not isinstance(value, dict):
        return {
            "merchant_name": None,
            "date": None,
            "currency": None,
            "items": [],
            "total": None,
            "raw_text": "",
        }

    merchant_name = value.get("merchant_name")
    date = value.get("date")
    currency = value.get("currency")
    raw_text = value.get("raw_text")

    return {
        "merchant_name": merchant_name.strip()
        if isinstance(merchant_name, str) and merchant_name.strip()
        else None,
        "date": date.strip()
        if isinstance(date, str) and date.strip()
        else None,
        "currency": currency.strip()
        if isinstance(currency, str) and currency.strip()
        else None,
        "items": normalize_items(value.get("items")),
        "total": as_integer(value.get("total")),
        "raw_text": raw_text.strip()
        if isinstance(raw_text, str)
        else "",
    }


def parse_receipt(image: bytes, mime_type: str) -> dict[str, Any]:
    if GROQ_CLIENT is None:
        raise RuntimeError("Groq vision client belum terinisialisasi")

    response = GROQ_CLIENT.chat.completions.create(
        model=os.getenv("GROQ_VISION_MODEL", DEFAULT_VISION_MODEL),
        messages=[
            {
                "role": "system",
                "content": (
                    "You are an expert receipt understanding model. "
                    "Read the receipt directly from the image, including "
                    "Indonesian and English receipts. Return only one valid "
                    "JSON object, without markdown or code fences. "
                    "Do not invent information that is not visible. "
                    "The total must be the final transaction amount, not "
                    "subtotal, tax, discount, cash paid, or change. "
                    "All monetary values must be integer Indonesian Rupiah "
                    "without currency symbols or separators. "
                    "Include every readable purchased item and use null for "
                    "an unreadable numeric value. "
                    "Use an empty items array when no item is readable. "
                    "The date must use YYYY-MM-DD only when unambiguous. "
                    "Copy all legible receipt text into raw_text in reading "
                    "order. Use this exact JSON shape: "
                    '{"merchant_name":"string or null",'
                    '"date":"YYYY-MM-DD or null",'
                    '"currency":"IDR or null",'
                    '"items":[{"name":"string","quantity":1,'
                    '"unit_price":20000,"total":20000}],'
                    '"total":20000,"raw_text":"string"}.'
                ),
            },
            {
                "role": "user",
                "content": [
                    {
                        "type": "text",
                        "text": (
                            "Extract all available details from this receipt "
                            "image and return the requested JSON object."
                        ),
                    },
                    {
                        "type": "image_url",
                        "image_url": {
                            "url": image_data_url(image, mime_type),
                        },
                    },
                ],
            },
        ],
        temperature=0,
        max_completion_tokens=2048,
        response_format={"type": "json_object"},
    )

    content = response.choices[0].message.content

    if not content:
        raise RuntimeError("Groq tidak menghasilkan response")

    try:
        return normalize_receipt(json.loads(content))
    except json.JSONDecodeError as exception:
        raise RuntimeError("Groq menghasilkan JSON yang tidak valid") from exception


def validate_token(token: str | None) -> None:
    expected_token = os.getenv("OCR_SERVICE_TOKEN", "")

    if expected_token and token != expected_token:
        raise HTTPException(status_code=401, detail="Unauthorized")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/ocr")
def process_receipt(
    file: UploadFile = File(...),
    x_ocr_token: str | None = Header(default=None),
) -> dict[str, Any]:
    validate_token(x_ocr_token)

    if not file.content_type or not file.content_type.startswith("image/"):
        raise HTTPException(status_code=415, detail="File harus berupa gambar")

    data = file.file.read(MAX_FILE_SIZE + 1)

    if len(data) > MAX_FILE_SIZE:
        raise HTTPException(status_code=413, detail="Ukuran gambar maksimal 8 MB")

    try:
        image, mime_type = prepare_image(data)

        try:
            receipt = parse_receipt(image, mime_type)
        except Exception as exception:
            print(f"Receipt LLM error: {exception}")
            raise HTTPException(
                status_code=502,
                detail="LLM gagal membaca detail struk",
            ) from exception

        amount = receipt.get("total")

        if (
            not isinstance(amount, int)
            or isinstance(amount, bool)
            or amount <= 0
            or amount > 100_000_000
        ):
            raise HTTPException(
                status_code=422,
                detail="Total transaksi tidak berhasil ditemukan",
            )

        return {
            "text": receipt["raw_text"],
            "receipt": receipt,
            "items": receipt["items"],
            "amount": amount,
            "confidence": None,
            "detection": None,
        }

    except HTTPException:
        raise
    except Exception as exception:
        print(f"Receipt processing error: {exception}")
        raise HTTPException(
            status_code=422,
            detail="Gambar tidak dapat diproses sebagai struk",
        ) from exception
