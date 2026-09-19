# services/laravel_api.py

import os
import requests


class LaravelAPI:

    def timeout_seconds(self) -> float:
        try:
            return max(1.0, float(os.getenv("LARAVEL_API_TIMEOUT", "30")))
        except ValueError:
            return 30.0

    def base_url(self) -> str:
        return os.getenv(
            "LARAVEL_API_URL",
            "http://app",
        ).rstrip("/")

    def headers(self, user_id: int | None) -> dict[str, str]:
        service_token = os.getenv("AI_SERVICE_TOKEN", "")

        if not service_token or user_id is None:
            raise RuntimeError("AI service authentication is not configured")

        return {
            "Accept": "application/json",
            "X-AI-Service-Token": service_token,
            "X-AI-User-ID": str(user_id),
        }

    def get(self, endpoint: str, params=None, user_id: int | None = None):
        response = requests.get(
            self.base_url() + endpoint,
            params=params,
            headers=self.headers(user_id),
            timeout=self.timeout_seconds(),
        )

        response.raise_for_status()

        return response.json()

    def post(self, endpoint: str, data=None, user_id: int | None = None):
        response = requests.post(
            self.base_url() + endpoint,
            json=data,
            headers=self.headers(user_id),
            timeout=self.timeout_seconds(),
        )

        if response.status_code == 409:
            payload = response.json()

            if payload.get("code") in {
                "missing_resources",
                "account_selection_required",
            }:
                return payload

        response.raise_for_status()

        return response.json()

    def patch(self, endpoint: str, data=None, user_id: int | None = None):
        response = requests.patch(
            self.base_url() + endpoint,
            json=data,
            headers=self.headers(user_id),
            timeout=self.timeout_seconds(),
        )

        response.raise_for_status()

        return response.json()

    def delete(self, endpoint: str, user_id: int | None = None):
        response = requests.delete(
            self.base_url() + endpoint,
            headers=self.headers(user_id),
            timeout=self.timeout_seconds(),
        )

        response.raise_for_status()

        if not response.content:
            return {"success": True}

        return response.json()
