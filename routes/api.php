<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\LiveStockController;

// Rute katalog untuk ditembak dari Web Profil nanti
Route::get('/products-catalog', [ProductController::class, 'index']);

// Rute untuk bot WhatsApp lokal
Route::get('/v1/live-stock', [LiveStockController::class, 'index']);