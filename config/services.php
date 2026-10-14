<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ocr' => [
        'url' => env('OCR_SERVICE_URL', 'http://127.0.0.1:8001'),
        'token' => env('OCR_SERVICE_TOKEN'),
    ],

    'ai' => [
        'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
        'token' => env('AI_SERVICE_TOKEN'),
        'connect_timeout' => env('AI_SERVICE_CONNECT_TIMEOUT', 3),
        'timeout' => env('AI_SERVICE_TIMEOUT', 90),
        'workflow_ttl_hours' => env('AI_WORKFLOW_TTL_HOURS', 24),
        'context_recent_messages' => env('AI_CONTEXT_RECENT_MESSAGES', 40),
        'lease_seconds' => env('AI_CONVERSATION_LEASE_SECONDS', 120),
        'context_input_budget_tokens' => env('AI_CONTEXT_INPUT_BUDGET_TOKENS', 8000),
    ],

];
