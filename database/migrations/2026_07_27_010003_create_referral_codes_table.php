<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('owner_name', 100); // nama pemilik kode (agen/mitra)
            $table->string('notes', 255)->nullable();
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->unsignedInteger('discount_value')->default(5); // 5% atau Rp 5.000
            $table->unsignedInteger('min_order')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};
