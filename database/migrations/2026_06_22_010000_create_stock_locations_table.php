<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel stock_locations untuk mendefinisikan semua lokasi stok
     * (Gudang Pusat & Cabang-cabang).
     */
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();           // Nama lokasi, e.g. "Pusat Cianjur"
            $table->enum('type', ['gudang', 'cabang', 'online'])->default('cabang');
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
