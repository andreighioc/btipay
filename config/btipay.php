<?php

return [

    /*
    |--------------------------------------------------------------------------
    | BT iPay Environment
    |--------------------------------------------------------------------------
    |
    | Determines whether the package uses the sandbox (test) or production
    | environment. Set to "sandbox" for testing, "production" for live.
    |
    */
    'environment' => env('BTIPAY_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | Your merchant API credentials provided by Banca Transilvania.
    | These are different from the GUI (console) credentials.
    |
    */
    'username' => env('BTIPAY_USERNAME', ''),
    'password' => env('BTIPAY_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Authentication Method
    |--------------------------------------------------------------------------
    |
    | The method used for API authentication.
    | "header" - Authorization Basic header (recommended)
    | "body"   - Username/password in request body (legacy, not recommended)
    |
    */
    'auth_method' => env('BTIPAY_AUTH_METHOD', 'header'),

    /*
    |--------------------------------------------------------------------------
    | API Base URLs
    |--------------------------------------------------------------------------
    |
    | The base URLs for sandbox and production environments.
    | These are preconfigured and typically shouldn't be changed.
    |
    */
    'base_urls' => [
        'sandbox'    => 'https://ecclients-sandbox.btrl.ro',
        'production' => 'https://ecclients.btrl.ro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Return URL
    |--------------------------------------------------------------------------
    |
    | The default URL where the customer is redirected after completing
    | or failing a payment. Can be overridden per transaction.
    |
    */
    'return_url' => env('BTIPAY_RETURN_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code (ISO 4217 numeric).
    | 946 = RON, 978 = EUR, 840 = USD
    |
    */
    'currency' => env('BTIPAY_CURRENCY', 946),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language for the payment page (ISO 639-1).
    | Example: "ro", "en"
    |
    */
    'language' => env('BTIPAY_LANGUAGE', 'ro'),

    /*
    |--------------------------------------------------------------------------
    | Default Page View
    |--------------------------------------------------------------------------
    |
    | The default page view for the payment page.
    | "DESKTOP" or "MOBILE"
    |
    */
    'page_view' => env('BTIPAY_PAGE_VIEW', 'DESKTOP'),

    /*
    |--------------------------------------------------------------------------
    | Payment Type
    |--------------------------------------------------------------------------
    |
    | The default payment type:
    | "1phase" - Direct payment (deposit automatic)
    | "2phase" - Pre-authorization (requires manual deposit)
    |
    */
    'payment_type' => env('BTIPAY_PAYMENT_TYPE', '1phase'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the HTTP client used to communicate with the API.
    |
    */
    'http' => [
        'timeout'         => env('BTIPAY_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('BTIPAY_HTTP_CONNECT_TIMEOUT', 10),
        'verify_ssl'      => env('BTIPAY_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging of API requests/responses.
    | Uses Laravel's logging system with a dedicated channel.
    |
    */
    'logging' => [
        'enabled' => env('BTIPAY_LOGGING', false),
        'channel' => env('BTIPAY_LOG_CHANNEL', 'stack'),
    ],

];
