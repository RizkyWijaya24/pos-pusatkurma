<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DOKU Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Keys are read from your .env file.
    |
    */

    'client_id'     => env('DOKU_CLIENT_ID'),
    'secret_key'    => env('DOKU_SECRET_KEY'),
    'is_production' => filter_var(env('DOKU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    'enabled'       => filter_var(env('DOKU_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'api_url' => filter_var(env('DOKU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN)
        ? 'https://api.doku.com'
        : 'https://api-sandbox.doku.com',
];
