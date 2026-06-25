<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repack_log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repack_log_id')->constrained('repack_logs')->onDelete('cascade');
            $table->foreignId('target_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('target_quantity', 10, 2);
            $table->integer('additional_packaging_cost')->default(0);
            $table->integer('calculated_cost_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repack_log_items');
    }
};
