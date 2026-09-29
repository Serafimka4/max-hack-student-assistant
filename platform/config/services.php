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

    'max' => [
        'bot_token' => env('MAX_BOT_TOKEN', ''),
        'api_url' => env('MAX_API_URL', 'https://platform-api2.max.ru'),
        'init_data_ttl' => (int) env('MAX_INIT_DATA_TTL', 3600),
        // Имя бота для диплинков вида https://max.ru/<bot>?startapp=offer_42
        'bot_name' => env('MAX_BOT_NAME', ''),
        // Секрет вебхука: приходит в заголовке X-Max-Bot-Api-Secret.
        'webhook_secret' => env('MAX_WEBHOOK_SECRET', ''),
        'notifications' => (bool) env('MAX_NOTIFICATIONS', true),
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

];
