<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stok produk per lokasi (menggantikan stok global).
     * Setiap produk punya record stok di setiap lokasi yang aktif.
     */
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('stock_locations')->onDelete('cascade');
            $table->decimal('stock', 10, 2)->default(0.00);
            $table->timestamps();

            // Satu produk hanya boleh memiliki satu record per lokasi
            $table->unique(['product_id', 'location_id']);

            // Index untuk performa query
            $table->index(['product_id']);
            $table->index(['location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
