<?php

// routes/shop.php — Rute publik toko online Pusat Kurma
// Semua rute di sini TIDAK memerlukan autentikasi (publik)

use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\ShippingController;
use Illuminate\Support\Facades\Route;

Route::prefix('shop')->name('shop.')->group(function () {
    // Halaman katalog produk
    Route::get('/', [CatalogController::class, 'index'])->name('index');

    // Halaman detail produk
    Route::get('/product/{product}', [CatalogController::class, 'show'])->name('product.show');

    // Halaman checkout formulir alamat pengiriman
    Route::get('/checkout', [CatalogController::class, 'checkout'])->name('checkout');

    // Proses penyimpanan order dan buat sesi pembayaran iPaymu
    // Throttled: max 10 requests per minute per IP to prevent checkout spam/DoS
    Route::post('/checkout', [CatalogController::class, 'storeOrder'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');

    // Halaman status pembayaran / sukses order
    Route::get('/order/success/{order}', [CatalogController::class, 'orderSuccess'])->name('order.success');

    // ── Order Tracking ──────────────────────────────────────────
    Route::get('/track',  [\App\Http\Controllers\Shop\OrderTrackingController::class, 'show'])->name('track');
    Route::post('/track', [\App\Http\Controllers\Shop\OrderTrackingController::class, 'show'])->name('track.search')->middleware('throttle:20,1');

    // ── Ulasan Produk ───────────────────────────────────────────
    Route::get('/product/{product}/reviews', [\App\Http\Controllers\Shop\ReviewController::class, 'index'])->name('product.reviews');
    Route::post('/review', [\App\Http\Controllers\Shop\ReviewController::class, 'store'])->name('review.store')->middleware('throttle:5,1');

    // ── Kupon & Referral (AJAX) ─────────────────────────────────
    Route::post('/coupon/apply',   [\App\Http\Controllers\Shop\CouponController::class, 'apply'])->name('coupon.apply')->middleware('throttle:10,1');
    Route::post('/referral/apply', [\App\Http\Controllers\Shop\ReferralController::class, 'apply'])->name('referral.apply')->middleware('throttle:10,1');

    // ── Hampers Builder ─────────────────────────────────────────
    Route::get('/hampers', [CatalogController::class, 'hampers'])->name('hampers');

    // Halaman Informasi Statis (syarat legal toko online)
    Route::view('/terms',   'shop.terms')->name('terms');
    Route::view('/privacy', 'shop.privacy')->name('privacy');
    Route::view('/refund',  'shop.refund')->name('refund');
    Route::view('/about',   'shop.about')->name('about');


    // ── AJAX: Shipping / Ongkir ──────────────────────────────────────
    // Autocomplete kota tujuan (60 hit/menit per IP)
    Route::get('/shipping/cities', [ShippingController::class, 'cities'])
        ->middleware('throttle:60,1')
        ->name('shipping.cities');

    // Kalkulasi ongkir (30 hit/menit per IP)
    Route::post('/shipping/cost', [ShippingController::class, 'cost'])
        ->middleware('throttle:30,1')
        ->name('shipping.cost');

    // ── AJAX: Chatbot / Asisten Rekomendasi ──────────────────────────
    Route::post('/chatbot/query', [\App\Http\Controllers\Shop\ChatbotController::class, 'query'])
        ->middleware('throttle:30,1')
        ->name('chatbot.query');
});

// Redirect /shop root langsung
Route::redirect('/', '/shop')->name('home');
