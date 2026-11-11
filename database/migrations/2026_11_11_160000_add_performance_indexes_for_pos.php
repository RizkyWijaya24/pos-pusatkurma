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
            $table->index(['cashier_id', 'created_at'], 'idx_transactions_cashier_created');
            $table->index(['created_at'], 'idx_transactions_created_at');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['cashier_id', 'created_at'], 'idx_expenses_cashier_created');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['name'], 'idx_products_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_cashier_created');
            $table->dropIndex('idx_transactions_created_at');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_expenses_cashier_created');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
        });
    }
};

