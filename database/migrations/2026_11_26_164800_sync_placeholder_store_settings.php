<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace untouched dummy seed values with real store info matching Faktur,
     * without overwriting any customized store settings.
     */
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $placeholderReplacements = [
            'shop_name' => [
                'old' => ['Pusat Kurma'],
                'new' => 'PUSAT KURMA PREMIUM CIANJUR',
            ],
            'shop_address' => [
                'old' => ["Jl. Contoh No. 123,\nCianjur, Jawa Barat 43200", "Jl. Contoh No. 123,\r\nCianjur, Jawa Barat 43200"],
                'new' => 'Jl. Dr. Muwardi No.48, Muka, Kec. Cianjur, Kabupaten Cianjur, Jawa Barat 43215',
            ],
            'shop_whatsapp' => [
                'old' => ['6281234567890'],
                'new' => '082396572250',
            ],
            'shop_phone' => [
                'old' => ['+62 812-3456-7890'],
                'new' => '082396572250',
            ],
        ];

        foreach ($placeholderReplacements as $key => $map) {
            DB::table('settings')
                ->where('key', $key)
                ->whereIn('value', $map['old'])
                ->update([
                    'value' => $map['new'],
                    'updated_at' => now(),
                ]);
        }

        Cache::forget('shop_settings_global');
    }

    public function down(): void
    {
        // Non-destructive data sync; nothing to revert.
    }
};
