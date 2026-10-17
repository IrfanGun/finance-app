TOOLS = [
    {
        "type": "function",
        "function": {
            "name": "search_meals",
            "description": (
                "Search only the authenticated user's saved meal choices. Use "
                "this for food recommendations. Pass max_price for a user's "
                "budget; every returned price_max is within that cap. Pass "
                "open_only=true only when the user explicitly asks for a place "
                "that is open now or at a particular time. Do not infer this "
                "from being hungry, asking for a general recommendation, or a "
                "chat timestamp. Omit at for now, "
                "or pass an explicit ISO 8601 date and time for a clear intended "
                "time. Ask for a specific hour if a relative time is unclear. "
                "location matches saved address text only; never claim distance "
                "or nearest. Results include saved prices, opening status, and "
                "food budget remaining when configured. Do not invent fields or "
                "menus, and never create an expense just to recommend food."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "keyword": {"type": "string"},
                    "location": {"type": "string"},
                    "type": {"type": "string", "enum": ["buy", "homemade"]},
                    "min_price": {"type": "number", "minimum": 0},
                    "max_price": {"type": "number", "minimum": 0},
                    "at": {
                        "type": "string",
                        "description": "An explicit ISO 8601 date-time when the user gave a clear intended time.",
                    },
                    "open_only": {
                        "type": "boolean",
                        "description": "Set true only when the user explicitly requires places open at the requested time. Otherwise omit it.",
                    },
                },
            },
        },
    },
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
