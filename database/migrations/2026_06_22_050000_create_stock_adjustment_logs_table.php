<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log audit setiap perubahan stok:
     * penjualan, transfer masuk, transfer keluar, koreksi manual, stok awal.
     */
    public function up(): void
    {
        Schema::create('stock_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('stock_locations')->onDelete('cascade');

            // Tipe mutasi stok
            $table->enum('type', [
                'initial',       // Stok awal saat setup sistem
                'sale',          // Penjualan (stok berkurang)
                'transfer_in',   // Transfer masuk (stok bertambah)
                'transfer_out',  // Transfer keluar (stok berkurang)
                'adjustment',    // Koreksi manual
                'return',        // Retur barang (stok bertambah)
            ]);

            $table->decimal('quantity_before', 10, 2);  // Stok sebelum perubahan
            $table->decimal('quantity_change', 10, 2);  // Perubahan (positif/negatif)
            $table->decimal('quantity_after', 10, 2);   // Stok setelah perubahan

            // Referensi ke sumber perubahan (polymorphic-style)
            $table->string('reference_type')->nullable();  // 'App\Models\Transaction', 'App\Models\StockTransfer'
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Index untuk query performa
            $table->index(['product_id', 'location_id']);
            $table->index(['type']);
            $table->index(['created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_logs');
    }
};
