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
        // 1. Master Data Supplier
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        // 2. Faktur Pembelian / Stok Masuk
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code')->unique();
            $table->string('invoice_number')->nullable()->comment('Nomor surat jalan / faktur fisik supplier');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_phone')->nullable();
            $table->foreignId('location_id')->constrained('stock_locations');
            $table->foreignId('created_by')->constrained('users');
            $table->date('purchase_date');
            $table->text('items_summary')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('payment_method')->default('Tunai'); // Tunai, Transfer Bank, Tempo / Utang
            $table->string('payment_status')->default('unpaid'); // paid, partial, unpaid
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_date', 'payment_status']);
            $table->index('supplier_name');
        });

        // 3. Item Rincian Pembelian
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 12, 2);
            $table->string('unit')->default('pcs');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->boolean('update_product_cost')->default(true);
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('product_id');
        });

        // 4. Log Pembayaran & Cicilan Utang Supplier
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('payment_code')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('Tunai');
            $table->string('reference_number')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};

