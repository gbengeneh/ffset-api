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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'templates' => [
            'order_received' => env('WHATSAPP_TEMPLATE_ORDER_RECEIVED'), 'payment_confirmed' => env('WHATSAPP_TEMPLATE_PAYMENT_CONFIRMED'),
            'processing' => env('WHATSAPP_TEMPLATE_PROCESSING'), 'ready_for_pickup' => env('WHATSAPP_TEMPLATE_READY'),
            'dispatched' => env('WHATSAPP_TEMPLATE_DISPATCHED'), 'delivered' => env('WHATSAPP_TEMPLATE_DELIVERED'),
            'cancelled' => env('WHATSAPP_TEMPLATE_CANCELLED'), 'refunded' => env('WHATSAPP_TEMPLATE_REFUNDED'),
        ],
    ],

];
