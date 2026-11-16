<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambahkan index untuk mempercepat query yang sering digunakan.
     */
    public function up(): void
    {
        // --- TRANSACTIONS ---
        // Index untuk filter berdasarkan transaction_type (wholesale, retail)
        $this->addIndexIfNotExists('transactions', ['transaction_type'], 'idx_transactions_type');
        // Index untuk filter gabungan: type + payment_method + status (Piutang query)
        $this->addIndexIfNotExists('transactions', ['transaction_type', 'payment_method', 'payment_status'], 'idx_transactions_type_method_status');
        // Index untuk filter per branch
        $this->addIndexIfNotExists('transactions', ['branch'], 'idx_transactions_branch');

        // --- PURCHASES ---
        // Index untuk filter per bulan (purchase_date bukan created_at)
        $this->addIndexIfNotExists('purchases', ['purchase_date'], 'idx_purchases_purchase_date');
        // Index untuk filter payment_status (utang aktif)
        $this->addIndexIfNotExists('purchases', ['payment_status'], 'idx_purchases_payment_status');
        // Index untuk filter gabungan: supplier + status (buku utang per supplier)
        $this->addIndexIfNotExists('purchases', ['supplier_name', 'payment_status'], 'idx_purchases_supplier_status');

        // --- PRODUCTS ---
        // Index untuk filter is_active + name (form produk)
        $this->addIndexIfNotExists('products', ['is_active', 'name'], 'idx_products_active_name');
        // Index untuk filter stock rendah
        $this->addIndexIfNotExists('products', ['stock'], 'idx_products_stock');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_transactions_type');
            $this->dropIndexIfExists($table, 'idx_transactions_type_method_status');
            $this->dropIndexIfExists($table, 'idx_transactions_branch');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_purchases_purchase_date');
            $this->dropIndexIfExists($table, 'idx_purchases_payment_status');
            $this->dropIndexIfExists($table, 'idx_purchases_supplier_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_products_active_name');
            $this->dropIndexIfExists($table, 'idx_products_stock');
        });
    }

    /**
     * Helper: tambah index hanya jika belum ada (safe re-run).
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (empty($exists)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        }
    }

    /**
     * Helper: drop index hanya jika ada.
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        $tableName = $table->getTable();
        $exists = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
        if (!empty($exists)) {
            $table->dropIndex($indexName);
        }
    }
};
