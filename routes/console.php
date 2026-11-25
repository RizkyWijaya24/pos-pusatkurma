<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:clear-unpaid', function () {
    $count = Order::where('payment_status', 'pending')
        ->where('created_at', '<', \Carbon\Carbon::now()->subHours(24))
        ->delete();

    $this->info("Successfully deleted {$count} unpaid orders older than 24 hours.");
})->purpose('Clear unpaid orders older than 24 hours');

Schedule::command('orders:clear-unpaid')->hourly();

Artisan::command('purchases:fix-unit-conversions', function () {
    $this->info("Scanning purchase items for mismatched units vs product base units...");
    $items = \App\Models\PurchaseItem::with(['product', 'purchase'])->get();
    $fixedCount = 0;

    foreach ($items as $item) {
        $prod = $item->product;
        if (!$prod) continue;

        $inputUnit = strtolower(trim($item->unit ?? ''));
        $masterUnit = strtolower(trim($prod->price_unit ?? ''));

        $isInputKg = in_array($inputUnit, ['kg', 'kilogram']);
        $isMasterGram = in_array($masterUnit, ['gram', 'gr', 'g']);

        if ($isInputKg && $isMasterGram) {
            // User input 5 kg pada barang master gram
            // Seharusnya stok yang masuk: 5000 gram (sebelumnya hanya masuk 5 gram)
            $expectedStock = (float) $item->quantity * 1000;
            $missingStock = $expectedStock - (float) $item->quantity;

            // Periksa apakah HPP master produk keliru tersimpan harga per kg (misal >= 10.000)
            $oldHpp = (float) $prod->cost_price;
            $newHpp = $oldHpp;
            if ($oldHpp >= 10000 && (float) $item->unit_cost == $oldHpp) {
                $newHpp = (int) round($oldHpp / 1000);
            }

            $this->warn("Ditemukan item: {$prod->name} pada PO {$item->purchase?->purchase_code}");
            $this->line("  Input: {$item->quantity} {$item->unit} @ Rp " . number_format($item->unit_cost));
            $this->line("  Stok sebelum penyesuaian: {$prod->stock} {$prod->price_unit}");
            $this->line("  Penambahan stok yang kurang: +{$missingStock} {$prod->price_unit}");
            $this->line("  HPP produk: Rp {$oldHpp} -> Rp {$newHpp} / {$prod->price_unit}");

            // Perbaiki stok produk
            $prod->stock = (float) $prod->stock + $missingStock;
            $prod->cost_price = $newHpp;
            $prod->save();

            // Perbaiki ProductStock lokasi
            if ($item->purchase && $item->purchase->location_id) {
                $ps = \App\Models\ProductStock::where('product_id', $prod->id)
                    ->where('location_id', $item->purchase->location_id)
                    ->first();
                if ($ps) {
                    $ps->stock = (float) $ps->stock + $missingStock;
                    $ps->save();
                }
            }

            // Catat log
            \App\Models\StockAdjustmentLog::create([
                'product_id'      => $prod->id,
                'location_id'     => $item->purchase?->location_id ?? 1,
                'type'            => 'adjustment',
                'quantity_before' => (float)$prod->stock - $missingStock,
                'quantity_change' => $missingStock,
                'quantity_after'  => (float)$prod->stock,
                'reference_type'  => 'unit_conversion_fix',
                'reference_id'    => $item->purchase_id,
                'created_by'      => 1,
                'notes'           => "Koreksi otomatis konversi satuan pembelian ({$item->quantity} kg -> {$expectedStock} gram)",
                'created_at'      => now(),
            ]);

            $fixedCount++;
        }
    }

    $this->info("Selesai! Berhasil mengoreksi {$fixedCount} item pembelian.");
})->purpose('Koreksi otomatis stok dan HPP untuk barang masuk dengan satuan kg pada master gram');
