<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 30)->nullable()->after('shipping_etd');
            $table->unsignedInteger('coupon_discount')->default(0)->after('coupon_code');
            $table->string('referral_code', 30)->nullable()->after('coupon_discount');
            $table->unsignedInteger('referral_discount')->default(0)->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'coupon_discount', 'referral_code', 'referral_discount']);
        });
    }
};
