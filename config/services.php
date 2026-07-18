<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'paystack' => [
        'secret' => env('PAYSTACK_SECRET'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'bank_transfer' => [
        'instructions' => env('BANK_TRANSFER_INSTRUCTIONS'),
    ],

    'mollie' => [
        'api_key' => env('MOLLIE_API_KEY'),
    ],

    'flutterwave' => [
        'secret' => env('FLUTTERWAVE_SECRET_KEY'),
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
    ],

    'instamojo' => [
        'api_key' => env('INSTAMOJO_API_KEY'),
        'auth_token' => env('INSTAMOJO_AUTH_TOKEN'),
        'salt' => env('INSTAMOJO_SALT'),
        'mode' => env('INSTAMOJO_MODE', 'test'),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'mode' => env('SSLCOMMERZ_MODE', 'sandbox'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'secret' => env('FACEBOOK_CLIENT_SECRET'),
    ],

    'tawk' => [
        'property_id' => env('TAWK_PROPERTY_ID'),
        'widget_id' => env('TAWK_WIDGET_ID', 'default'),
    ],

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
