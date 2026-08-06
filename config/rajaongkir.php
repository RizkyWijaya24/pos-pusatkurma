<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RajaOngkir API Configuration
    |--------------------------------------------------------------------------
    | Daftar gratis di: https://collaborator.komerce.id/
    | Masukkan API key ke .env: RAJAONGKIR_API_KEY=...
    */

    'api_key'        => env('RAJAONGKIR_API_KEY'),
    'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY_ID', 82), // 82 = Cianjur

    'base_url' => 'https://api.rajaongkir.com/starter',

    // Kurir yang ditampilkan ke customer
    'couriers' => [
        'jne'      => 'JNE',
        'jnt'      => 'J&T Express',
        'jntcargo' => 'J&T Cargo',
        'sicepat'  => 'SiCepat',
        'pos'      => 'Pos Indonesia',
        'tiki'     => 'TIKI',
        'anteraja' => 'AnterAja',
    ],

    // Berat default (gram) per item jika produk tidak memiliki berat
    'default_weight_per_item' => 500,

    // Cache durasi untuk daftar kota (menit)
    'cities_cache_minutes' => 1440, // 24 jam
];
