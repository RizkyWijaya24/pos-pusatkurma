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
        Schema::create('repack_logs', function (Blueprint $table) {
            $table->id();
            $table->string('repack_code')->unique();
            $table->foreignId('location_id')->constrained('stock_locations')->onDelete('cascade');
            $table->foreignId('source_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('source_quantity', 10, 2);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repack_logs');
    }
};
