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
            $table->string('transaction_type')->default('retail')->after('transaction_code'); // 'retail' atau 'wholesale'
            $table->string('customer_name')->nullable()->after('transaction_type');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_type', 'customer_name', 'customer_phone', 'shipping_cost']);
        });
    }
};
