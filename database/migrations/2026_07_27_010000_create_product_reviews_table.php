<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('order_code', 40)->nullable()->index(); // verifikasi pembeli nyata (opsional)
            $table->string('reviewer_name', 100);
            $table->tinyInteger('rating')->unsigned(); // 1–5
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
