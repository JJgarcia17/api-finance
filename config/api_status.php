<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Status Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the API Status Dashboard functionality
    |
    */

    'auth' => [
        'email' => env('API_STATUS_EMAIL', 'admin@example.com'),
        'password' => env('API_STATUS_PASSWORD', 'password'),
    ],

    'cache' => [
        'token_ttl' => env('API_STATUS_TOKEN_TTL', 50 * 60), // 50 minutes
    ],

    'timeout' => [
        'login' => env('API_STATUS_LOGIN_TIMEOUT', 10),
        'endpoint_check' => env('API_STATUS_ENDPOINT_TIMEOUT', 10),
    ],
];
