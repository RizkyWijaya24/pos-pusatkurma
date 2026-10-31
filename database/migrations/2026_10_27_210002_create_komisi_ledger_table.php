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
        Schema::create('komisi_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Type mutasi:
            // 'credit' = Komisi masuk yang didapat dari transaksi omset belanja jamaah
            // 'debit'  = Pencairan / penarikan komisi yang dibayarkan ke Tour Leader
            $table->enum('type', ['credit', 'debit']);
            
            // Nominal omset transaksi yang menjadi basis perhitungan komisi (0 jika mutasi debit/pencairan)
            $table->decimal('transaction_amount', 15, 2)->default(0);
            
            // Snapshot persentase komisi saat transaksi terjadi (agar data historis audit akurat)
            $table->decimal('commission_rate_percentage', 5, 2)->default(0);
            
            // Nominal komisi yang dicatat di buku besar
            $table->decimal('amount', 15, 2);
            
            // Status ledger:
            // 'approved' = Komisi sah tercatat
            // 'paid'     = Sudah ditransfer / dicairkan (untuk debit)
            // 'pending'  = Pengajuan pencairan komisi menunggu approval admin
            // 'rejected' = Pengajuan pencairan ditolak
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('approved');
            
            $table->string('reference_no')->nullable(); // No bukti transfer / resi penarikan
            $table->string('description')->nullable(); // Penjelasan singkat
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->timestamps();

            $table->index(['affiliate_id', 'type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komisi_ledger');
    }
};

