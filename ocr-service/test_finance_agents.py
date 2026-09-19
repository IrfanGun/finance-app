import json
import os
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

import requests

import app as ai_app
from finance_agents import FinanceAgent, TOOLS
from services.laravel_api import LaravelAPI
from tools.transactions import create_transaction


class FinanceAgentSetupFlowTest(unittest.TestCase):
    def test_model_guessing_account_does_not_skip_asset_selection(self):
        arguments = {
            "transaction_type": "expense",
            "amount": 20000,
            "account": "Cash",
            "category": "Makanan",
            "description": "Makan bakso",
        }
        tool_call = SimpleNamespace(
            id="call-guessed-account",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps(arguments),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(
                        return_value=SimpleNamespace(
                            choices=[SimpleNamespace(message=assistant_message)],
                        ),
                    ),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        create_handler = Mock(return_value={
            "code": "account_selection_required",
            "account_options": [
                {"id": 8, "name": "Cash", "type": "cash"},
            ],
            "missing_resources": [],
        })
        agent.tool_handlers["create_transaction"] = create_handler

        result = agent.chat([
            {"role": "user", "content": "makan bakso 20rb"},
        ], user_id=10)

        self.assertTrue(result["account_selection_required"])
        self.assertNotIn("account", result["pending_transaction"])
        self.assertNotIn("account", create_handler.call_args.kwargs)

    def test_explicit_account_mentioned_by_user_is_preserved(self):
        tool_call = SimpleNamespace(
            id="call-explicit-account",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps({
                    "transaction_type": "expense",
                    "amount": 20000,
                    "account": "Cash",
                }),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(
                        return_value=SimpleNamespace(
                            choices=[SimpleNamespace(message=assistant_message)],
                        ),
                    ),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        create_handler = Mock(return_value={
            "transaction": {"title": "Makan", "amount": 20000},
        })
        agent.tool_handlers["create_transaction"] = create_handler

        agent.chat([
            {"role": "user", "content": "makan bakso 20rb pakai tunai"},
        ], user_id=10)

        self.assertEqual(
            create_handler.call_args.kwargs["account"],
            "Cash",
        )

    def test_missing_account_returns_user_assets_for_modal_selection(self):
        arguments = {
            "transaction_type": "expense",
            "amount": 20000,
            "category": "Makanan",
            "description": "Makan bakso",
        }
        account_options = [
            {"id": 3, "name": "BCA", "type": "bank"},
            {"id": 8, "name": "Cash", "type": "cash"},
        ]
        tool_call = SimpleNamespace(
            id="call-account-selection",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps(arguments),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(
                        return_value=SimpleNamespace(
                            choices=[SimpleNamespace(message=assistant_message)],
                        ),
                    ),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        agent.tool_handlers["create_transaction"] = lambda **_: {
            "code": "account_selection_required",
            "account_options": account_options,
            "missing_resources": [],
        }

        result = agent.chat([
            {"role": "user", "content": "makan bakso 20rb"},
        ], user_id=10)

        self.assertTrue(result["account_selection_required"])
        self.assertEqual(result["pending_transaction"], arguments)
        self.assertEqual(result["account_options"], account_options)
        self.assertEqual(result["missing_resources"], [])
        self.assertEqual(client.chat.completions.create.call_count, 1)

        create_tool = next(
            tool["function"]
            for tool in TOOLS
            if tool["function"]["name"] == "create_transaction"
        )
        self.assertNotIn("account", create_tool["parameters"]["required"])

    def test_create_tool_sends_an_omitted_account_to_laravel(self):
        with patch(
            "tools.transactions.laravel.post",
            return_value={"code": "account_selection_required"},
        ) as post_request:
            create_transaction(
                transaction_type="expense",
                amount=20000,
                category="Makanan",
                description="Makan bakso",
                user_id=10,
            )

        self.assertIsNone(post_request.call_args.args[1]["account"])

    def test_missing_resources_are_returned_to_the_chat_client_for_confirmation(self):
        arguments = {
            "transaction_type": "expense",
            "amount": 20000,
            "account": "Cash",
            "category": "Makanan",
            "description": "Beli bakso",
        }
        missing_resources = [
            {"type": "account", "name": "Cash"},
            {"type": "category", "name": "Makanan"},
        ]
        tool_call = SimpleNamespace(
            id="call-1",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps(arguments),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(
                        return_value=SimpleNamespace(
                            choices=[SimpleNamespace(message=assistant_message)],
                        ),
                    ),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        agent.tool_handlers["create_transaction"] = lambda **_: {
            "code": "missing_resources",
            "missing_resources": missing_resources,
        }

        result = agent.chat([
            {"role": "user", "content": "Beli bakso 20rb pakai Cash"},
        ], user_id=10)

        self.assertEqual(result["pending_transaction"], arguments)
        self.assertEqual(result["missing_resources"], missing_resources)
        self.assertIn("Cash, Makanan", result["response"])
        client.chat.completions.create.assert_called_once()
        self.assertEqual(
            client.chat.completions.create.call_args.kwargs[
                "max_completion_tokens"
            ],
            350,
        )

    def test_successful_create_returns_confirmation_without_followup_ai_request(self):
        arguments = {
            "transaction_type": "expense",
            "amount": 20000,
            "account": "Cash",
            "description": "Beli bakso",
        }
        tool_call = SimpleNamespace(
            id="call-1",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps(arguments),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        first_completion = SimpleNamespace(
            choices=[SimpleNamespace(message=assistant_message)],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(return_value=first_completion),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        agent.tool_handlers["create_transaction"] = lambda **_: {
            "transaction": {
                "title": "Beli bakso",
                "amount": 20000,
            },
        }

        result = agent.chat([
            {"role": "user", "content": "Beli bakso 20rb pakai Cash"},
        ], user_id=10)

        self.assertIn("Transaksi Beli bakso", result["response"])
        self.assertEqual(client.chat.completions.create.call_count, 1)


class GroqStartupConfigurationTest(unittest.TestCase):
    def test_chat_client_has_a_shorter_timeout_than_the_laravel_proxy(self):
        with (
            patch.dict(os.environ, {
                "GROQ_API_KEY": "test-groq-key",
                "GROQ_CHAT_TIMEOUT_SECONDS": "45",
            }),
            patch.object(ai_app, "GROQ_CLIENT", None),
            patch.object(ai_app, "finance_agent", None),
            patch("app.Groq") as groq_client_factory,
            patch("app.FinanceAgent") as finance_agent_factory,
        ):
            ai_app.startup_event()

        self.assertEqual(groq_client_factory.call_count, 2)
        chat_client_options = groq_client_factory.call_args_list[1].kwargs
        self.assertEqual(chat_client_options["timeout"], 45.0)
        self.assertEqual(chat_client_options["max_retries"], 0)
        finance_agent_factory.assert_called_once()
        self.assertIs(
            finance_agent_factory.call_args.kwargs["client"],
            groq_client_factory.return_value,
        )

    def test_write_timeout_reports_ambiguous_status_without_retrying_the_tool(self):
        arguments = {
            "transaction_type": "expense",
            "amount": 20000,
            "account": "Cash",
            "description": "Makan",
        }
        tool_call = SimpleNamespace(
            id="call-timeout",
            function=SimpleNamespace(
                name="create_transaction",
                arguments=json.dumps(arguments),
            ),
        )
        assistant_message = SimpleNamespace(
            content=None,
            tool_calls=[tool_call],
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(
                        return_value=SimpleNamespace(
                            choices=[SimpleNamespace(message=assistant_message)],
                        ),
                    ),
                ),
            ),
        )
        agent = FinanceAgent(
            client=client,
            model="test-model",
            max_completion_tokens=350,
        )
        create_transaction = Mock(side_effect=requests.Timeout("read timed out"))
        agent.tool_handlers["create_transaction"] = create_transaction

        result = agent.chat([], user_id=10)

        self.assertIn("belum dapat dipastikan", result["response"])
        self.assertIn("Jangan kirim ulang", result["response"])
        client.chat.completions.create.assert_called_once()
        create_transaction.assert_called_once()


class LaravelApiMissingResourceTest(unittest.TestCase):
    def test_post_returns_missing_resource_conflict_as_structured_data(self):
        payload = {
            "code": "missing_resources",
            "missing_resources": [{"type": "account", "name": "Cash"}],
        }
        response = Mock(status_code=409)
        response.json.return_value = payload

        with patch.dict(os.environ, {
            "AI_SERVICE_TOKEN": "test-token",
            "LARAVEL_API_URL": "http://laravel.test",
            "LARAVEL_API_TIMEOUT": "18",
        }), patch(
            "services.laravel_api.requests.post",
            return_value=response,
        ) as post_request:
            result = LaravelAPI().post(
                "/api/ai/transactions",
                {"account": "Cash"},
                user_id=10,
            )

        self.assertEqual(result, payload)
        self.assertEqual(
            post_request.call_args.kwargs["timeout"],
            18.0,
        )

    def test_post_returns_account_selection_conflict_as_structured_data(self):
        payload = {
            "code": "account_selection_required",
            "account_options": [
                {"id": 3, "name": "BCA", "type": "bank"},
            ],
            "missing_resources": [],
        }
        response = Mock(status_code=409)
        response.json.return_value = payload

        with patch.dict(os.environ, {
            "AI_SERVICE_TOKEN": "test-token",
            "LARAVEL_API_URL": "http://laravel.test",
        }), patch(
            "services.laravel_api.requests.post",
            return_value=response,
        ):
            result = LaravelAPI().post(
                "/api/ai/transactions",
                {"account": None},
                user_id=10,
            )

        self.assertEqual(result, payload)
