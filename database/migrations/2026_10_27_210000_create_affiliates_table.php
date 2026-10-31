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
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Lengkap Tour Leader / Agen B2B
            $table->string('code')->unique(); // Kode Referensi / Afiliasi Unik (e.g., TL-001)
            $table->string('phone')->nullable(); // No WhatsApp aktif
            $table->string('agency_name')->nullable(); // Nama Biro Travel Umroh / Tour Agency
            $table->decimal('commission_rate_percentage', 5, 2)->default(5.00); // Persentase komisi dinamis (e.g. 5.00%, 7.50%)
            $table->string('bank_name')->nullable(); // Nama Bank untuk pencairan komisi
            $table->string('bank_account_number')->nullable(); // Nomor Rekening
            $table->string('bank_account_name')->nullable(); // Nama Pemilik Rekening
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Opsional jika punya akun login
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};

