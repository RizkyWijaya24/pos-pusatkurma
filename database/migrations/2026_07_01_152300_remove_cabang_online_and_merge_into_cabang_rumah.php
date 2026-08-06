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
        // 1. Ubah data branch 'Cabang Online' -> 'Cabang Rumah'
        DB::table('users')->where('branch', 'Cabang Online')->update(['branch' => 'Cabang Rumah']);
        DB::table('transactions')->where('branch', 'Cabang Online')->update(['branch' => 'Cabang Rumah']);
        DB::table('expenses')->where('branch', 'Cabang Online')->update(['branch' => 'Cabang Rumah']);

        // 2. Hubungkan/Pindahkan data stok dari 'Cabang Online' ke 'Cabang Rumah'
        $onlineLoc = StockLocation::where('name', 'Cabang Online')->first();
        $rumahLoc = StockLocation::where('name', 'Cabang Rumah')->first();

        if ($onlineLoc && $rumahLoc) {
            // Pindahkan/gabungkan data stok dari 'Cabang Online' ke 'Cabang Rumah'
            $onlineStocks = ProductStock::where('location_id', $onlineLoc->id)->get();
            foreach ($onlineStocks as $pStock) {
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
                ->where('location_id', $onlineLoc->id)
                ->update(['location_id' => $rumahLoc->id]);

            DB::table('stock_transfers')
                ->where('from_location_id', $onlineLoc->id)
                ->update(['from_location_id' => $rumahLoc->id]);

            DB::table('stock_transfers')
                ->where('to_location_id', $onlineLoc->id)
                ->update(['to_location_id' => $rumahLoc->id]);

            // Hapus lokasi lama 'Cabang Online'
            $onlineLoc->delete();
        }

        // 3. Ubah skema kolom ENUM di tabel users (tanpa 'Cabang Online')
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM('Cabang Rumah', 'Cabang Cianjur', 'Cabang Ciranjang') NOT NULL DEFAULT 'Cabang Rumah'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM('Cabang Rumah', 'Cabang Cianjur', 'Cabang Ciranjang', 'Cabang Online') NOT NULL DEFAULT 'Cabang Rumah'");
        }
    }
};
