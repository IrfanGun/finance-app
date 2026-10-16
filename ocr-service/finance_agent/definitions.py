TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "get_finance_summary",
            "description": (
                "Get authoritative income, expense, balance, account balances, "
                "and budget availability for a date range. Totals are grouped by "
                "currency; never combine different currencies. "
                "Use this for balance, spending, budget, or planning questions."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "from_date": {"type": "string"},
                    "to_date": {"type": "string"},
                },
            },
        },
    },

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
