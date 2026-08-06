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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('id')
                  ->comment('Flag apakah produk ini merupakan paket bundling');
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->comment('Jumlah unit/kg komponen dalam paket');
            $table->timestamps();

            // Cegah duplikasi komponen yang sama dalam satu bundle
            $table->unique(['bundle_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_bundle');
        });
    }
};

