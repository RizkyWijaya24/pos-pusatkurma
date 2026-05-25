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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->integer('cost_price'); // modal price
            $table->integer('selling_price'); // retail price
            $table->string('price_unit')->default('pcs'); // options: gram, kg, pcs, pack, dus
            $table->string('image_path')->nullable(); // store product photo file path
            $table->decimal('stock', 10, 2)->default(0.00); // support weight/decimal quantity
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
