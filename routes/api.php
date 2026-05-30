<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;

// Rute katalog untuk ditembak dari Web Profil nanti
Route::get('/products-catalog', [ProductController::class, 'index']);