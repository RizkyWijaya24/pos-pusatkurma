<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    /**
     * Update the shop settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $settings = $request->input('settings');

        // Update visibilitas produk di shop
        if (isset($settings['active_product_ids_submitted'])) {
            $activeIds = $request->input('settings.active_product_ids', []);

            // Set is_active_in_shop = true untuk produk yang dicentang
            Product::whereIn('id', $activeIds)->update(['is_active_in_shop' => true]);
            // Set is_active_in_shop = false untuk produk yang tidak dicentang
            Product::whereNotIn('id', $activeIds)->update(['is_active_in_shop' => false]);

            // Hapus keys ini agar tidak tersimpan di tabel settings
            unset($settings['active_product_ids_submitted']);
            unset($settings['active_product_ids']);
        }

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            Setting::set($key, $value);
        }

        // Checkbox tidak terkirim kalau tidak dicentang — set eksplisit ke '0'
        if (!isset($settings['payment_fee_enabled'])) {
            Setting::set('payment_fee_enabled', '0');
        }

        return redirect()->back()->with('success', 'Pengaturan shop berhasil diperbarui!');
    }

    /**
     * Sync shop settings from external profile API.
     */
    public function syncFromApi(Request $request)
    {
        try {
            $apiUrl = 'https://pusatkurmacianjur.my.id/api/profile';
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($apiUrl);

            if ($response->successful()) {
                $json = $response->json();
                $store = $json['data']['store'] ?? null;

                if ($store) {
                    if (!empty($store['name'])) {
                        Setting::set('shop_name', $store['name']);
                    }
                    if (!empty($store['logo'])) {
                        Setting::set('shop_logo', $store['logo']);
                    }
                    if (!empty($store['wa_number'])) {
                        Setting::set('shop_whatsapp', $store['wa_number']);
                        Setting::set('shop_phone', $store['wa_number']);
                    }
                    if (!empty($store['address'])) {
                        Setting::set('shop_address', $store['address']);
                    }
                    if (!empty($store['opening_hours'])) {
                        Setting::set('shop_operational_hours', $store['opening_hours']);
                    }
                    if (!empty($store['shipping_info'])) {
                        Setting::set('shop_shipping_info', $store['shipping_info']);
                    }
                    if (!empty($store['maps_embed_url'])) {
                        Setting::set('shop_maps_embed_url', $store['maps_embed_url']);
                    }
                    if (isset($store['social_media']['instagram']) && !empty($store['social_media']['instagram'])) {
                        Setting::set('shop_social_instagram', $store['social_media']['instagram']);
                    }
                    if (isset($store['social_media']['facebook']) && !empty($store['social_media']['facebook'])) {
                        Setting::set('shop_social_facebook', $store['social_media']['facebook']);
                    }
                    if (!empty($store['branches']) && is_array($store['branches'])) {
                        Setting::set('shop_branches', json_encode($store['branches']));
                    }

                    return redirect()->back()->with('success', 'Berhasil melakukan sinkronisasi profil toko dari API web profil!');
                }
            }

            return redirect()->back()->with('error', 'Gagal mengambil data dari API profil toko.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat koneksi ke API: ' . $e->getMessage());
        }
    }
}
