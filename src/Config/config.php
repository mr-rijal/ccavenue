<?php

declare(strict_types=1);

/**
 * CCAvenue package configuration.
 *
 * Publish with: php artisan vendor:publish --tag=ccavenue-config
 * Then set values in .env and reference config key 'payment' (see docs).
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Test mode
    |--------------------------------------------------------------------------
    | When true, uses CCAvenue test endpoints. Set to false for production.
    */
    'testMode' => (bool) env('CCAVENUE_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | CCAvenue credentials and URLs
    |--------------------------------------------------------------------------
    */
    'merchantId' => env('CCAVENUE_MERCHANT_ID', ''),
    'accessCode' => env('CCAVENUE_ACCESS_CODE', ''),
    'workingKey' => env('CCAVENUE_WORKING_KEY', ''),

    // Route names or paths for redirect/cancel (used with url())
    'redirectUrl' => env('CCAVENUE_REDIRECT_URL', 'payment/success'),
    'cancelUrl' => env('CCAVENUE_CANCEL_URL', 'payment/cancel'),

    'currency' => env('CCAVENUE_CURRENCY', 'INR'),
    'language' => env('CCAVENUE_LANGUAGE', 'EN'),

    /*
    |--------------------------------------------------------------------------
    | CSRF exemption for response URL
    |--------------------------------------------------------------------------
    | Add your CCAvenue response callback route here if not using API middleware.
    */
    'remove_csrf_check' => [
        'payment/response',
    ],
];
