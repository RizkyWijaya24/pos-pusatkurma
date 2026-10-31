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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('affiliate_id')
                ->nullable()
                ->after('cashier_id')
                ->constrained('affiliates')
                ->nullOnDelete();
            
            // Kolom pelengkap untuk mencatat jumlah jamaah / pax dalam rombongan tour leader
            $table->integer('pax_count')->default(0)->after('affiliate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'pax_count']);
        });
    }
};

