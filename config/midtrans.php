<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Keys are read from your .env file. Never hardcode them here.
    |
    */

    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    'enabled'       => filter_var(env('MIDTRANS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'snap_url' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN)
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',

    'snap_api_url' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN)
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
];
