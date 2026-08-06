<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        // Jika sudah login, lempar ke dashboard sesuai role masing-masing
        return redirect('/' . auth()->user()->role . '/dashboard');
    }
    // Jika belum login, langsung lempar ke halaman login toko kurma
    return redirect()->route('login');
});

// Redirect otomatis dari halaman shop dinonaktifkan agar halaman shop bisa diakses
// Route::any('/shop/{any?}', function () {
//     return redirect('/');
// })->where('any', '.*');

Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->isKasir()) {
        return redirect()->route('kasir.dashboard');
    } elseif ($user->isOwner()) {
        return redirect()->route('owner.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification routes
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');

    // Kasir routes (Accessible by kasir and admin)
    Route::middleware('role:kasir,admin')->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'kasir'])->name('dashboard');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/export', [\App\Http\Controllers\TransactionController::class, 'exportKasir'])->name('transactions.export');
        Route::post('/transactions', [\App\Http\Controllers\TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}/print', [\App\Http\Controllers\TransactionController::class, 'print'])->name('transactions.print');

        // Expense management routes
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // ── Kasir: Request Stok Barang ──────────────────────────
        Route::get('/stock-request', [\App\Http\Controllers\StockTransferController::class, 'kasirRequestPage'])->name('stock-request');
        Route::post('/stock-request', [\App\Http\Controllers\StockTransferController::class, 'kasirRequestStore'])->name('stock-request.store');
        Route::post('/stock-request/{stockTransfer}/cancel', [\App\Http\Controllers\StockTransferController::class, 'cancel'])->name('stock-request.cancel');
    });

    // Admin routes (Accessible only by admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'admin'])->name('dashboard');

        // Wholesale Order Routes
        Route::post('/wholesale-transactions', [\App\Http\Controllers\TransactionController::class, 'storeWholesale'])->name('wholesale-transactions.store');
        Route::get('/wholesale-transactions/{transaction}/print', [\App\Http\Controllers\TransactionController::class, 'printWholesale'])->name('wholesale-transactions.print');
        Route::post('/transactions/{transaction}/mark-as-paid', [\App\Http\Controllers\TransactionController::class, 'markAsPaid'])->name('transactions.mark-as-paid');
        Route::get('/receivables', [\App\Http\Controllers\TransactionController::class, 'getReceivables'])->name('receivables.index');
        Route::post('/transactions/{transaction}/installments', [\App\Http\Controllers\TransactionController::class, 'storeInstallment'])->name('transactions.store-installment');

        // Product CRUD
        Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/toggle-active', [\App\Http\Controllers\ProductController::class, 'toggleActive'])->name('products.toggle-active');
        Route::delete('/products/{product}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');

        // Fast Photo Upload
        Route::get('/products/fast-upload', [\App\Http\Controllers\ProductController::class, 'fastUploadPage'])->name('products.fast-upload');
        Route::post('/products/{product}/upload-photo', [\App\Http\Controllers\ProductController::class, 'uploadPhoto'])->name('products.upload-photo');

        // Category CRUD
        Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Cashier CRUD
        Route::post('/cashiers', [\App\Http\Controllers\CashierController::class, 'store'])->name('cashiers.store');
        Route::put('/cashiers/{user}', [\App\Http\Controllers\CashierController::class, 'update'])->name('cashiers.update');
        Route::delete('/cashiers/{user}', [\App\Http\Controllers\CashierController::class, 'destroy'])->name('cashiers.destroy');
        Route::post('/impersonate/{user}', [\App\Http\Controllers\CashierController::class, 'impersonate'])->name('impersonate');

        // Transaction Management (Admin can view all, edit, delete)
        Route::get('/transactions/export', [\App\Http\Controllers\TransactionController::class, 'export'])->name('transactions.export');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'adminIndex'])->name('transactions.index');
        Route::put('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'destroy'])->name('transactions.destroy');

        // ── Admin: Manajemen Transfer Stok ──────────────────────
        Route::get('/stock-transfers', [\App\Http\Controllers\StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('/stock-transfers/create', [\App\Http\Controllers\StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('/stock-transfers', [\App\Http\Controllers\StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('/stock-transfers/{stockTransfer}', [\App\Http\Controllers\StockTransferController::class, 'show'])->name('stock-transfers.show');
        Route::post('/stock-transfers/{stockTransfer}/approve', [\App\Http\Controllers\StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('/stock-transfers/{stockTransfer}/approve-adjusted', [\App\Http\Controllers\StockTransferController::class, 'approveWithAdjustment'])->name('stock-transfers.approve-adjusted');
        Route::post('/stock-transfers/{stockTransfer}/reject',  [\App\Http\Controllers\StockTransferController::class, 'reject'])->name('stock-transfers.reject');
        Route::post('/stock-transfers/{stockTransfer}/cancel',  [\App\Http\Controllers\StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');

        // AJAX: Stok per lokasi & koreksi stok manual
        Route::get('/stock-by-location', [\App\Http\Controllers\StockTransferController::class, 'getStockByLocation'])->name('stock-by-location');
        Route::post('/stock-adjust', [\App\Http\Controllers\StockTransferController::class, 'adjustStock'])->name('stock-adjust');

        // Repack / Pecah Stok Routes
        Route::get('/products/{product}/conversions', [\App\Http\Controllers\RepackController::class, 'getConversions'])->name('products.conversions');
        Route::resource('repack', \App\Http\Controllers\RepackController::class)->only(['index', 'create', 'store']);
        Route::resource('conversions', \App\Http\Controllers\ProductConversionController::class)->only(['index', 'store', 'destroy']);

        // Shop Settings Management
        Route::post('/settings', [\App\Http\Controllers\ShopSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/sync-profile', [\App\Http\Controllers\ShopSettingController::class, 'syncFromApi'])->name('settings.sync_profile');

        // ── Online Orders Management (Pesanan dari toko online) ──────
        Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/orders/{order}/check-payment', [\App\Http\Controllers\OrderController::class, 'checkPayment'])->name('orders.check-payment');
        Route::delete('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('orders.destroy');

        // ── Banner Promo ──────────────────────────────────────────
        Route::get('/banners', [\App\Http\Controllers\BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [\App\Http\Controllers\BannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}', [\App\Http\Controllers\BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [\App\Http\Controllers\BannerController::class, 'destroy'])->name('banners.destroy');

        // ── Kupon / Kode Promo ────────────────────────────────────
        Route::get('/coupons', [\App\Http\Controllers\CouponAdminController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [\App\Http\Controllers\CouponAdminController::class, 'store'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [\App\Http\Controllers\CouponAdminController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [\App\Http\Controllers\CouponAdminController::class, 'destroy'])->name('coupons.destroy');

        // ── Kode Referral ─────────────────────────────────────────
        Route::get('/referrals', [\App\Http\Controllers\ReferralCodeController::class, 'index'])->name('referrals.index');
        Route::post('/referrals', [\App\Http\Controllers\ReferralCodeController::class, 'store'])->name('referrals.store');
        Route::put('/referrals/{referralCode}', [\App\Http\Controllers\ReferralCodeController::class, 'update'])->name('referrals.update');
        Route::delete('/referrals/{referralCode}', [\App\Http\Controllers\ReferralCodeController::class, 'destroy'])->name('referrals.destroy');

        // ── Ulasan Produk (Moderasi) ──────────────────────────────
        Route::get('/reviews', [\App\Http\Controllers\ProductReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{review}/approve', [\App\Http\Controllers\ProductReviewController::class, 'approve'])->name('reviews.approve');
        Route::delete('/reviews/{review}', [\App\Http\Controllers\ProductReviewController::class, 'destroy'])->name('reviews.destroy');
    });


    // Owner routes (Accessible by owner and admin)
    Route::middleware('role:owner,admin')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'owner'])->name('dashboard');
        Route::get('/performance-analysis', [\App\Http\Controllers\DashboardController::class, 'getPerformanceAnalysis'])->name('performance.analysis');
        Route::get('/performance-analysis/export-pdf', [\App\Http\Controllers\DashboardController::class, 'exportAiAnalysisPdf'])->name('performance.analysis.export-pdf');
        Route::post('/performance-chat', [\App\Http\Controllers\DashboardController::class, 'performanceChat'])->name('performance.chat');

        Route::get('/dashboard/export', [\App\Http\Controllers\DashboardController::class, 'exportOwnerReport'])->name('dashboard.export');
        Route::get('/dashboard/export-best-sellers', [\App\Http\Controllers\DashboardController::class, 'exportBestSellers'])->name('dashboard.export-best-sellers');

        // Transaction History (Owner read-only)
        Route::get('/transactions/export', [\App\Http\Controllers\TransactionController::class, 'export'])->name('transactions.export');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'ownerIndex'])->name('transactions.index');

        // ── Owner: Laporan Stok Multi-Cabang ────────────────────
        Route::get('/stock-report', [\App\Http\Controllers\StockReportController::class, 'index'])->name('stock-report');
        Route::get('/stock-report/export', [\App\Http\Controllers\StockReportController::class, 'export'])->name('stock-report.export');
        Route::get('/stock-adjustment-log', [\App\Http\Controllers\StockReportController::class, 'adjustmentLog'])->name('stock-adjustment-log');

        // Owner juga bisa approve/reject transfer
        Route::post('/stock-transfers/{stockTransfer}/approve', [\App\Http\Controllers\StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('/stock-transfers/{stockTransfer}/approve-adjusted', [\App\Http\Controllers\StockTransferController::class, 'approveWithAdjustment'])->name('stock-transfers.approve-adjusted');
        Route::post('/stock-transfers/{stockTransfer}/reject',  [\App\Http\Controllers\StockTransferController::class, 'reject'])->name('stock-transfers.reject');
        Route::get('/stock-transfers', [\App\Http\Controllers\StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('/stock-transfers/{stockTransfer}', [\App\Http\Controllers\StockTransferController::class, 'show'])->name('stock-transfers.show');
    });

    Route::post('/leave-impersonate', [\App\Http\Controllers\CashierController::class, 'leaveImpersonate'])->name('admin.impersonate.leave');
});

require __DIR__.'/auth.php';

// Fallback untuk memuat gambar produk dari server live jika tidak ada di local storage
// Security: hanya ekstensi file gambar yang diizinkan untuk mencegah open redirect abuse
Route::get('storage/products/{filename}', function ($filename) {
    // Whitelist ekstensi file gambar yang diperbolehkan
    if (!preg_match('/\.(jpg|jpeg|png|webp|gif|avif)$/i', $filename)) {
        abort(404);
    }
    return redirect('https://pos.pusatkurmacianjur.my.id/storage/products/' . rawurlencode(basename($filename)));
});
