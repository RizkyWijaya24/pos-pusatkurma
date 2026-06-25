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
        Schema::create('product_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('target_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('conversion_rate', 10, 4); // support very precise conversion rates
            $table->timestamps();

            $table->unique(['source_product_id', 'target_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_conversions');
    }
};
