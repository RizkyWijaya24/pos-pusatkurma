<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\StockLocation;
use App\Models\ProductStock;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah data branch 'Pusat Cianjur' -> 'Cabang Rumah'
        DB::table('users')->where('branch', 'Pusat Cianjur')->update(['branch' => 'Cabang Rumah']);
        DB::table('transactions')->where('branch', 'Pusat Cianjur')->update(['branch' => 'Cabang Rumah']);
        DB::table('expenses')->where('branch', 'Pusat Cianjur')->update(['branch' => 'Cabang Rumah']);

        // 2. Hubungkan/Pindahkan data stok dari 'Pusat Cianjur' ke 'Cabang Rumah'
        $pusatLoc = StockLocation::where('name', 'Pusat Cianjur')->first();
        $rumahLoc = StockLocation::where('name', 'Cabang Rumah')->first();

        if ($pusatLoc) {
            // Jika belum ada Cabang Rumah, buat terlebih dahulu dengan tipe 'gudang'
            if (!$rumahLoc) {
                $rumahLoc = StockLocation::create([
                    'name' => 'Cabang Rumah',
                    'type' => 'gudang',
                    'is_active' => true,
                    'description' => 'Gudang utama sekaligus Toko Pusat'
                ]);
            } else {
                // Jika Cabang Rumah sudah ada, pastikan tipenya 'gudang' karena sekarang menjadi pusat
                $rumahLoc->update(['type' => 'gudang']);
            }

            // Pindahkan/gabungkan data stok dari 'Pusat Cianjur' ke 'Cabang Rumah'
            $pusatStocks = ProductStock::where('location_id', $pusatLoc->id)->get();
            foreach ($pusatStocks as $pStock) {
                $rumahStock = ProductStock::where('product_id', $pStock->product_id)
                    ->where('location_id', $rumahLoc->id)
                    ->first();

                if ($rumahStock) {
                    // Gabungkan stok
                    $rumahStock->stock += $pStock->stock;
                    $rumahStock->save();
                } else {
                    // Pindahkan lokasi
                    $pStock->location_id = $rumahLoc->id;
                    $pStock->save();
                }
            }

            // Update referensi ID di tabel logs
            DB::table('stock_adjustment_logs')
                ->where('location_id', $pusatLoc->id)
                ->update(['location_id' => $rumahLoc->id]);

            DB::table('stock_transfers')
                ->where('from_location_id', $pusatLoc->id)
                ->update(['from_location_id' => $rumahLoc->id]);

            DB::table('stock_transfers')
                ->where('to_location_id', $pusatLoc->id)
                ->update(['to_location_id' => $rumahLoc->id]);

            // Hapus lokasi lama 'Pusat Cianjur'
            $pusatLoc->delete();
        } else {
            // Jika Pusat Cianjur tidak ada tapi Cabang Rumah ada, pastikan tipenya 'gudang'
            if ($rumahLoc) {
                $rumahLoc->update(['type' => 'gudang']);
            }
        }

        // 3. Ubah skema kolom ENUM di tabel users (tanpa 'Pusat Cianjur' & default 'Cabang Rumah')
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM('Cabang Rumah', 'Cabang Cianjur', 'Cabang Ciranjang', 'Cabang Online') NOT NULL DEFAULT 'Cabang Rumah'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN branch VARCHAR(255) NOT NULL DEFAULT 'Cabang Rumah'");
            DB::statement("ALTER TABLE expenses MODIFY COLUMN branch VARCHAR(255) NOT NULL DEFAULT 'Cabang Rumah'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan default ke 'Pusat Cianjur'
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM('Pusat Cianjur', 'Cabang Rumah', 'Cabang Cianjur', 'Cabang Ciranjang', 'Cabang Online') NOT NULL DEFAULT 'Pusat Cianjur'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN branch VARCHAR(255) NOT NULL DEFAULT 'Pusat Cianjur'");
            DB::statement("ALTER TABLE expenses MODIFY COLUMN branch VARCHAR(255) NOT NULL DEFAULT 'Pusat Cianjur'");
        }
    }
};
