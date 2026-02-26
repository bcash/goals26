<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', '/google/callback'),
        'scopes' => 'https://www.googleapis.com/auth/calendar.readonly',
    ],

    'granola' => [
        'mcp_url' => env('GRANOLA_MCP_URL', 'https://mcp.granola.ai/mcp'),
        'auth_url' => env('GRANOLA_AUTH_URL', 'https://mcp-auth.granola.ai'),
        'redirect_uri' => env('GRANOLA_REDIRECT_URI', '/granola/callback'),
        'scopes' => 'offline_access openid',
    ],

    'freescout' => [
        'url' => env('FREESCOUT_URL', 'https://help.alpine.io'),
        'api_key' => env('FREESCOUT_API_KEY'),
        'webhook_secret' => env('FREESCOUT_WEBHOOK_SECRET'),
    ],

    'cloudways' => [
        'email' => env('CLOUDWAYS_EMAIL'),
        'api_key' => env('CLOUDWAYS_API_KEY'),
        'base_url' => env('CLOUDWAYS_BASE_URL', 'https://api.cloudways.com/api/v1'),
        'source_server_id' => (int) env('CLOUDWAYS_SOURCE_SERVER_ID', 743387),
        'source_server_ip' => env('CLOUDWAYS_SOURCE_IP', '144.202.98.245'),
        'target_server_id' => (int) env('CLOUDWAYS_TARGET_SERVER_ID'),
        'target_server_ip' => env('CLOUDWAYS_TARGET_IP'),
        'token_ttl' => 3300, // 55 minutes (tokens valid 60 min, refresh with margin)
    ],

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'base_url' => env('CLOUDFLARE_BASE_URL', 'https://api.cloudflare.com/client/v4'),
    ],

];
