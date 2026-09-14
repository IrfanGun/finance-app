from __future__ import annotations

import json
import os
import re
from io import BytesIO
from threading import Lock
from typing import Any

import numpy as np
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image, ImageEnhance, ImageFilter


app = FastAPI(
    title="Receipt OCR Service",
    version="1.0.0",
)

MAX_FILE_SIZE = 8 * 1024 * 1024
OCR_LOCK = Lock()
OCR_PIPELINE: Any = None


def get_ocr_pipeline() -> Any:
    global OCR_PIPELINE

    if OCR_PIPELINE is None:
        with OCR_LOCK:
            if OCR_PIPELINE is None:
                from paddleocr import PaddleOCR

                OCR_PIPELINE = PaddleOCR(
                    lang="en",
                    use_doc_orientation_classify=False,
                    use_doc_unwarping=False,
                    use_textline_orientation=False,
                    engine="paddle",
                )

    return OCR_PIPELINE


def prepare_image(data: bytes) -> np.ndarray:
    image = Image.open(BytesIO(data)).convert("RGB")
    width, height = image.size
    scale = min(1.5, 1800 / max(width, height))

    if scale > 1:
        image = image.resize(
            (round(width * scale), round(height * scale)),
            Image.Resampling.LANCZOS,
        )

    image = ImageEnhance.Contrast(image).enhance(1.15)
    image = image.filter(ImageFilter.SHARPEN)

    return np.asarray(image)


def result_payload(result: Any) -> dict[str, Any]:
    if isinstance(result, dict):
        payload = result
    else:
        value = getattr(result, "json", None)

        if callable(value):
            value = value()

        if isinstance(value, str):
            value = json.loads(value)

        payload = value if isinstance(value, dict) else {}

    nested = payload.get("res")

    return nested if isinstance(nested, dict) else payload


def run_ocr(image: np.ndarray) -> tuple[str, float]:
    results = get_ocr_pipeline().predict(image)
    lines: list[tuple[float, str, float]] = []

    for result in results:
        payload = result_payload(result)
        texts = payload.get("rec_texts", [])
        scores = payload.get("rec_scores", [])
        boxes = payload.get("rec_boxes", payload.get("rec_polys", []))

        if isinstance(texts, str):
            texts = [texts]

        for index, text in enumerate(texts):
            text_value = str(text).strip()

            if not text_value:
                continue

            score = float(scores[index]) if index < len(scores) else 0.0
            box = boxes[index] if index < len(boxes) else []
            y_position = float(box[1]) if len(box) >= 2 else float(index)
            lines.append((y_position, text_value, score))

    lines.sort(key=lambda line: line[0])
    text = "\n".join(line[1] for line in lines)
    confidence = (
        sum(line[2] for line in lines) / len(lines)
        if lines
        else 0.0
    )

    return text, confidence


def extract_amount(text: str) -> int | None:
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    label_pattern = re.compile(
        r"\b(t[o0]ta[l1]|jumlah|j[uy]mlah|bayar|bayer|dibayar|tunai|cash)\b",
        re.IGNORECASE,
    )
    number_pattern = re.compile(
        r"(?:rp\.?\s*)?\d{1,3}(?:[.,]\d{3})+|"
        r"(?:rp\.?\s*)?\d{3,}",
        re.IGNORECASE,
    )

    def numbers(value: str) -> list[int]:
        return [
            int(re.sub(r"\D", "", match))
            for match in number_pattern.findall(value)
            if len(re.sub(r"\D", "", match)) >= 3
        ]

    for index, line in enumerate(lines):
        if label_pattern.search(line):
            amounts = numbers(" ".join(lines[max(0, index - 1):index + 4]))

            if amounts:
                return max(amounts, key=lambda amount: len(str(amount)))

    all_amounts = numbers(" ".join(lines))

    return max(all_amounts, key=lambda amount: len(str(amount))) if all_amounts else None


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
        image = prepare_image(data)
        text, confidence = run_ocr(image)
        amount = extract_amount(text)

        if amount is None:
            raise HTTPException(
                status_code=422,
                detail=(
                    "Total struk tidak terlihat. Pastikan bagian total "
                    "terlihat jelas dan foto tidak terlalu jauh."
                ),
            )

        return {
            "text": text,
            "amount": amount,
            "confidence": confidence,
            "detection": None,
        }
    except HTTPException:
        raise
    except Exception as exception:
        print(f"OCR processing error: {exception}")
        raise HTTPException(
            status_code=500,
            detail="Gambar gagal diproses oleh OCR",
        ) from exception
