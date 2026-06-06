<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\LiveStockController;

// Rute katalog untuk ditembak dari Web Profil nanti
Route::get('/products-catalog', [ProductController::class, 'index']);

// Rute untuk bot WhatsApp lokal
Route::get('/v1/live-stock', [LiveStockController::class, 'index']);

// Rute tes database langsung
Route::get('/v1/test-db', function () {
    return response()->json([
        'status' => 'success',
        'count' => \App\Models\Product::count()
    ]);
});