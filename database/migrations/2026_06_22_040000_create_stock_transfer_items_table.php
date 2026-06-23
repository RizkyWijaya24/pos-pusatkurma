<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail item produk dalam satu transfer stok.
     * Satu transfer dapat berisi banyak produk.
     */
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);         // Jumlah yang ditransfer
            $table->string('unit')->default('pcs');     // Satuan: gram, kg, pcs, pack, dus
            $table->text('notes')->nullable();           // Catatan per item
            $table->timestamps();

            $table->index(['transfer_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
