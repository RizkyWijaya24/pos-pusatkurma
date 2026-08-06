<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\LiveStockController;

// Semua rute API publik dilindungi rate limiting: max 60 request/menit per IP
Route::middleware(['throttle:60,1'])->group(function () {
    // Rute katalog untuk ditembak dari Web Profil nanti
    Route::get('/products-catalog', [ProductController::class, 'index']);

    // Rute untuk bot WhatsApp lokal
    Route::get('/v1/live-stock', [LiveStockController::class, 'index']);

    // Rute untuk menerima webhook callback pembayaran dari DOKU
    Route::post('/doku/notify', [\App\Http\Controllers\Shop\PaymentCallbackController::class, 'handleDokuNotify']);
});