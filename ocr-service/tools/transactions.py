# tools/transactions.py

from typing import Optional

from services.laravel_api import LaravelAPI


laravel = LaravelAPI()


def create_transaction(
    transaction_type: str,
    amount: float,
    account: Optional[str] = None,
    category: Optional[str] = None,
    description: Optional[str] = None,
    user_id: int | None = None,
):
    return laravel.post(
        "/api/ai/transactions",
        {
            "transaction_type": transaction_type,
            "amount": amount,
            "account": account,
            "category": category,
            "description": description,
        },
        user_id=user_id,
    )


def get_transactions(
    limit: int = 20,
    user_id: int | None = None,
):
    return laravel.get(
        "/api/ai/transactions",
        params={
            "limit": limit,
        },
        user_id=user_id,
    )


def update_transaction(
    transaction_id: int,
    amount: Optional[float] = None,
    category: Optional[str] = None,
    description: Optional[str] = None,
    user_id: int | None = None,
):
    data = {}

    if amount is not None:
        data["amount"] = amount

    if category is not None:
        data["category"] = category

    if description is not None:
        data["description"] = description

    return laravel.patch(
        f"/api/ai/transactions/{transaction_id}",
        data,
        user_id=user_id,
    )


def delete_transaction(
    transaction_id: int,
    user_id: int | None = None,
):
    return laravel.delete(
        f"/api/ai/transactions/{transaction_id}",
        user_id=user_id,
    )
