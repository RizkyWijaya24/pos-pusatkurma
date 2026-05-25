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

    // Kasir routes (Accessible by kasir and admin)
    Route::middleware('role:kasir,admin')->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'kasir'])->name('dashboard');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transactions', [\App\Http\Controllers\TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}/print', [\App\Http\Controllers\TransactionController::class, 'print'])->name('transactions.print');
        
        // Expense management routes
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    // Admin routes (Accessible only by admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'admin'])->name('dashboard');
        
        // Product CRUD
        Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');

        // Cashier CRUD
        Route::post('/cashiers', [\App\Http\Controllers\CashierController::class, 'store'])->name('cashiers.store');
        Route::delete('/cashiers/{user}', [\App\Http\Controllers\CashierController::class, 'destroy'])->name('cashiers.destroy');

        // Transaction Management (Admin can view all, edit, delete)
        Route::get('/transactions/export', [\App\Http\Controllers\TransactionController::class, 'export'])->name('transactions.export');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'adminIndex'])->name('transactions.index');
        Route::put('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'destroy'])->name('transactions.destroy');
    });

    // Owner routes (Accessible by owner and admin)
    Route::middleware('role:owner,admin')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'owner'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\DashboardController::class, 'exportOwnerReport'])->name('dashboard.export');

        // Transaction History (Owner read-only)
        Route::get('/transactions/export', [\App\Http\Controllers\TransactionController::class, 'export'])->name('transactions.export');
        Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'ownerIndex'])->name('transactions.index');
    });
});

require __DIR__.'/auth.php';
