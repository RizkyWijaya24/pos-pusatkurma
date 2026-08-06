<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom payment_channel ke tabel orders untuk mencatat
     * metode pembayaran yang dipilih pembeli (QRIS, VIRTUAL_ACCOUNT, EMONEY, RETAIL).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_channel', 30)->nullable()->after('payment_fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_channel');
        });
    }
};
