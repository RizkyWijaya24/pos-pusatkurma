<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'branch' column to transactions table safely.
     * Existing transactions will be back-filled from their cashier's branch.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('branch')->default('Pusat Cianjur')->after('cashier_id');
        });

        // Back-fill existing transactions with their cashier's branch value
        DB::statement('UPDATE transactions t JOIN users u ON t.cashier_id = u.id SET t.branch = u.branch');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('branch');
        });
    }
};
