<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambah kolom approved_quantity:
     *  - null  → admin approve dengan jumlah asli dari kasir
     *  - angka → admin menyesuaikan jumlah (bisa lebih kecil dari quantity)
     */
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('approved_quantity', 10, 2)->nullable()->after('quantity')
                  ->comment('Jumlah yang disetujui admin. Null = jumlah asli, angka = jumlah disesuaikan.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('approved_quantity');
        });
    }
};
