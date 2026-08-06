<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description', 255)->nullable();
            $table->enum('type', ['percent', 'fixed', 'free_shipping'])->default('fixed');
            $table->unsignedInteger('value')->default(0); // persen atau nominal rupiah
            $table->unsignedInteger('min_order')->default(0); // minimum subtotal
            $table->unsignedInteger('max_discount')->default(0); // maks diskon untuk tipe persen (0 = unlimited)
            $table->unsignedInteger('max_uses')->default(0); // 0 = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
