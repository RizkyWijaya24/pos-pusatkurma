<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel stock_transfers menyimpan header setiap transfer/permintaan stok.
     * - Admin/Owner membuat transfer langsung (status: pending → approved)
     * - Kasir membuat permintaan (status: requested → pending → approved)
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code')->unique();  // e.g. TRF-20260622-A1B2C3

            // Lokasi asal (null = dari luar sistem / pembelian baru)
            $table->foreignId('from_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            // Lokasi tujuan (wajib)
            $table->foreignId('to_location_id')->constrained('stock_locations')->onDelete('cascade');

            // User yang membuat request/transfer
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            // User yang approve (bisa null jika belum diproses)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Alur status:
            // requested = dibuat kasir | pending = siap diproses admin | approved = stok sudah pindah
            // rejected = ditolak | cancelled = dibatalkan
            $table->enum('status', ['requested', 'pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending');

            $table->text('notes')->nullable();           // Catatan transfer
            $table->text('rejection_reason')->nullable(); // Alasan jika ditolak
            $table->timestamp('approved_at')->nullable(); // Waktu approval
            $table->timestamps();

            // Index untuk performa query
            $table->index(['status']);
            $table->index(['from_location_id']);
            $table->index(['to_location_id']);
            $table->index(['requested_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
