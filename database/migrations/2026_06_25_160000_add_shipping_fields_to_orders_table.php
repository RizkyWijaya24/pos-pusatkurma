<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kota tujuan pengiriman
            $table->unsignedInteger('destination_city_id')->nullable()->after('shipping_notes');
            $table->string('destination_city_name')->nullable()->after('destination_city_id');

            // Ekspedisi yang dipilih customer
            $table->string('shipping_courier')->nullable()->after('destination_city_name');   // e.g. "jne"
            $table->string('shipping_service')->nullable()->after('shipping_courier');         // e.g. "REG"
            $table->string('shipping_service_name')->nullable()->after('shipping_service');   // e.g. "Reguler"

            // Biaya & estimasi ongkir
            $table->unsignedInteger('shipping_cost')->default(0)->after('shipping_service_name');
            $table->string('shipping_etd')->nullable()->after('shipping_cost');               // e.g. "2-3 hari"

            // Subtotal produk (sebelum ongkir) — agar mudah diaudit
            $table->unsignedInteger('subtotal_amount')->default(0)->after('shipping_etd');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'destination_city_id',
                'destination_city_name',
                'shipping_courier',
                'shipping_service',
                'shipping_service_name',
                'shipping_cost',
                'shipping_etd',
                'subtotal_amount',
            ]);
        });
    }
};
