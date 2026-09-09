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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'orange_sonatel' => [
        'env' => env('ORANGE_SONATEL_ENV', 'sandbox'),
        'client_id' => env('ORANGE_SONATEL_CLIENT_ID', ''),
        'client_secret' => env('ORANGE_SONATEL_CLIENT_SECRET', ''),
        'merchant_code' => env('ORANGE_SONATEL_MERCHANT_CODE', ''),
        'merchant_msisdn' => env('ORANGE_SONATEL_MERCHANT_MSISDN', ''),
        'merchant_pin' => env('ORANGE_SONATEL_MERCHANT_PIN', ''),
    ],

    'wave' => [
        'key' => env('WAVE_API_KEY', ''),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET', ''),
    ],

];
