<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default values
        $defaults = [
            'shop_name' => 'Pusat Kurma',
            'shop_tagline' => 'Cianjur ✦ Premium Dates',
            'shop_whatsapp' => '6281234567890',
            'shop_description' => 'Distributor kurma premium terpercaya sejak 2010. Langsung dari importir, kualitas terjamin, harga bersaing untuk ritel dan grosir.',
            'shop_address' => "Jl. Contoh No. 123,\nCianjur, Jawa Barat 43200",
            'shop_phone' => '+62 812-3456-7890',
            'shop_operational_hours' => 'Senin–Sabtu: 08.00–17.00',
            'shop_social_instagram' => '#',
            'shop_social_facebook' => '#',
            'shop_social_tiktok' => '#',
            'shop_copyright' => 'Pusat Kurma Cianjur. Semua hak dilindungi.',
            'shop_hero_badge' => 'Kurma Premium Berkualitas Tinggi',
            'shop_hero_title' => 'Temukan <span class="highlight">Kurma Terbaik</span><br>Langsung dari Sumbernya',
            'shop_hero_desc' => 'Pilihan kurma premium pilihan untuk keluarga Anda — dari Madinah, Irak, hingga Tunisia. Kualitas terjamin, harga transparan, pengiriman ke seluruh Indonesia.',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
