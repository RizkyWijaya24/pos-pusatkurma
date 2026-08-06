<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('subtitle', 255)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('badge_text', 80)->nullable();
            $table->string('button_text', 60)->default('Belanja Sekarang');
            $table->string('button_url', 255)->default('/shop');
            $table->string('bg_from', 30)->default('#065f46'); // CSS color / tailwind start
            $table->string('bg_to', 30)->default('#059669');   // CSS color / tailwind end
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
