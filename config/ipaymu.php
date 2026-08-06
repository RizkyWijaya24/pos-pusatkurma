<?php

return [
    /*
    |--------------------------------------------------------------------------
    | iPaymu Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Keys are read from your .env file. Never hardcode them here.
    | Daftar di sandbox.ipaymu.com untuk mendapatkan API Key & VA.
    |
    */

    'api_key'       => env('IPAYMU_API_KEY'),
    'va'            => env('IPAYMU_VA'),
    'is_production' => filter_var(env('IPAYMU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    'enabled'       => filter_var(env('IPAYMU_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'api_url' => filter_var(env('IPAYMU_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN)
        ? 'https://my.ipaymu.com/api/v2/payment'
        : 'https://sandbox.ipaymu.com/api/v2/payment',
];
