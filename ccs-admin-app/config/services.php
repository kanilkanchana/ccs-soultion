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

    'ccs_whatsapp_signup' => [
        'app_id' => env('CCS_WHATSAPP_SIGNUP_APP_ID'),
        'app_secret' => env('CCS_WHATSAPP_SIGNUP_APP_SECRET'),
        'config_id' => env('CCS_WHATSAPP_SIGNUP_CONFIG_ID'),
        'redirect_uri' => env('CCS_WHATSAPP_SIGNUP_REDIRECT_URI'),
        'phone_number_id' => env('CCS_WHATSAPP_SIGNUP_PHONE_NUMBER_ID'),
        'graph_version' => env('CCS_WHATSAPP_SIGNUP_GRAPH_VERSION', 'v25.0'),
    ],

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

];
