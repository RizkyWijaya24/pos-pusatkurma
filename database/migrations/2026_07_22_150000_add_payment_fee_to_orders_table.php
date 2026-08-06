<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom payment_fee ke tabel orders untuk mencatat biaya transaksi
     * yang dibebankan ke pembeli (sesuai tarif DOKU).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nominal biaya transaksi DOKU yang ditanggung pembeli (dalam Rupiah)
            $table->unsignedInteger('payment_fee')->default(0)->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_fee');
        });
    }
};
