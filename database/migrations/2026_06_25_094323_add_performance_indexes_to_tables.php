<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional Performance Indexes Migration
 *
 * Tambahan index pada tabel product_stocks dan stock_adjustment_logs
 * yang belum ada di migration sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── product_stocks table ───────────────────────────────────────────
        Schema::table('product_stocks', function (Blueprint $table) {
            // Composite: lookup stok produk di lokasi tertentu (saat checkout & dashboard kasir)
            if (!$this->hasIndex('product_stocks', 'ps_product_location_index')) {
                $table->index(['product_id', 'location_id'], 'ps_product_location_index');
            }
        });

        // ── stock_adjustment_logs table ────────────────────────────────────
        Schema::table('stock_adjustment_logs', function (Blueprint $table) {
            // Composite: query laporan log stok per produk berurutan waktu
            if (!$this->hasIndex('stock_adjustment_logs', 'sal_product_date_index')) {
                $table->index(['product_id', 'created_at'], 'sal_product_date_index');
            }
            // Query log berdasarkan lokasi
            if (!$this->hasIndex('stock_adjustment_logs', 'sal_location_index')) {
                $table->index('location_id', 'sal_location_index');
            }
        });

        // ── products table ─────────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            // WHERE category = ? — dipakai oleh filter kategori kasir & admin
            if (!$this->hasIndex('products', 'products_category_index')) {
                $table->index('category', 'products_category_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndexIfExists('ps_product_location_index');
        });

        Schema::table('stock_adjustment_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('sal_product_date_index');
            $table->dropIndexIfExists('sal_location_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndexIfExists('products_category_index');
        });
    }

    /**
     * Check if an index exists on a table (prevents duplicate index errors).
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
