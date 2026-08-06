<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * POST /shop/chatbot/query
     * Memproses pertanyaan pelanggan & memberikan saran produk / jawaban FAQ.
     */
    public function query(Request $request)
    {
        $userMessage = trim($request->input('message', ''));

        if (empty($userMessage)) {
            return response()->json([
                'reply' => 'Halo! Ada yang bisa kami bantu? Silakan pilih opsi atau ketik pertanyaan Anda seputar produk toko kami.',
                'products' => [],
                'quick_replies' => $this->getDefaultQuickReplies(),
            ]);
        }

        $lowerMsg = mb_strtolower($userMessage);

        // 1. Cek intent FAQ / Informasi Toko (Lokasi, Pembayaran, Ongkir, Kontak)
        $faqReply = $this->checkFaqIntents($lowerMsg);
        if ($faqReply) {
            return response()->json($faqReply);
        }

        // 2. Analisis Rekomendasi berdasarkan Kebutuhan / Manfaat Kesehatan / Terlaris / Kurma
        $recommendationReply = $this->checkRecommendationIntents($lowerMsg);
        if ($recommendationReply) {
            return response()->json($recommendationReply);
        }

        // 3. Pencarian Kata Kunci Produk di Database
        $productReply = $this->searchProductDatabase($lowerMsg, $userMessage);
        if ($productReply) {
            return response()->json($productReply);
        }

        // 4. Default Fallback Reply
        return response()->json([
            'reply' => 'Terima kasih telah bertanya! Saya dapat memberikan saran produk terlaris, rekomendasi kesehatan/hampers, info lokasi & ongkir. Pilihlah salah satu topik di bawah ini:',
            'products' => $this->formatProductCards($this->getBestSellersFromAllBranches(2)),
            'quick_replies' => $this->getDefaultQuickReplies(),
        ]);
    }

    /**
     * Memeriksa intent umum FAQ
     */
    private function checkFaqIntents(string $msg): ?array
    {
        // 0. Sapaan / Greeting
        if (preg_match('/^(halo|hai|hello|hi|pagi|siang|sore|malam|selamat|assalamualaikum|assalamu\'alaikum|ping|p|spada|permisi)$/i', trim($msg))) {
            return [
                'reply' => "Halo! Selamat datang di **Pusat Kurma Cianjur** 🌿😊.\n\nAda yang bisa kami bantu? Anda dapat menanyakan produk kurma, madu, herbal, rekomendasi kesehatan, lokasi toko, atau estimasi ongkir.",
                'products' => [],
                'quick_replies' => ['🔥 Produk Terlaris', '📍 Lokasi Toko', 'Cek Ongkir', 'Kurma Ajwa'],
            ];
        }

        // 0b. Ucapan Terima Kasih
        if (preg_match('/(terima kasih|makasih|thanks|thx|tengks|suwun|hatur nuhun)/i', $msg)) {
            return [
                'reply' => "Sama-sama! 😊 Senang bisa membantu Anda. Jika ada hal lain yang ingin ditanyakan seputar produk atau pesanan, jangan ragu untuk bertanya ya!",
                'products' => [],
                'quick_replies' => ['🔥 Produk Terlaris', '📍 Lokasi Toko', 'Cek Ongkir'],
            ];
        }

        // Kontak / WA / CS
        if (preg_match('/(wa|whatsapp|admin|cs|customer service|kontak|hubungi|telp|telepon|tanya admin)/i', $msg)) {
            $waNum = Setting::get('shop_whatsapp', '6281234567890');
            $cleanWa = preg_replace('/[^0-9]/', '', $waNum);
            if (!str_starts_with($cleanWa, '62') && str_starts_with($cleanWa, '0')) {
                $cleanWa = '62' . substr($cleanWa, 1);
            }

            return [
                'reply' => "Anda dapat menghubungi Customer Service kami secara langsung melalui WhatsApp untuk konsultasi lebih detail atau pesanan khusus:",
                'action_button' => [
                    'label' => 'Chat Admin via WhatsApp',
                    'url' => "https://wa.me/{$cleanWa}?text=" . urlencode('Halo Admin Pusat Kurma Cianjur, saya ingin konsultasi produk.'),
                    'icon' => 'fa-brands fa-whatsapp',
                ],
                'products' => [],
                'quick_replies' => ['🔥 Produk Terlaris', 'Cek Ongkir', 'Kurma Ajwa', 'Madu Kashmir'],
            ];
        }

        // Lokasi / Alamat / Jam Buka / Shareloc / Gmaps / Peta
        if (preg_match('/(lokasi|alamat|toko|dimana|buka|jam|cianjur|posisi|shareloc|sharelok|sharelock|gmaps|google\s*maps|maps|peta|titik|rute|patokan|ancer|navigasi|gps|denah|cabang|gudang)/i', $msg)) {
            $address = Setting::get('shop_address', 'Jl. Dr. Muwardi No.48, Muka, Kec. Cianjur, Kabupaten Cianjur, Jawa Barat 43215');
            $pusatEmbed = Setting::get('shop_maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.6575885884517!2d107.14293457403386!3d-6.8114380666199486!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68530071717d9f%3A0x82f7d7d1d9e394bd!2sPusat%20kurma%20cianjur!5e0!3m2!1sid!2sid!4v1780114983890!5m2!1sid!2sid');

            $pusatMapsUrl = $this->convertEmbedToPlaceUrl($pusatEmbed, "Cianjur", $address);

            $rawBranches = Setting::get('shop_branches', '[]');
            $branches = json_decode($rawBranches, true) ?: [];

            // Fallback cabang jika belum tersimpan di database
            if (empty($branches)) {
                $branches = [
                    [
                        'name' => 'Ciranjang',
                        'address' => 'Jl. Raya Cianjur Bandung, Ciranjang, Kec. Cianjur, Kabupaten Cianjur, Jawa Barat 43282',
                        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d990.4103651182398!2d107.25104237221375!3d-6.813390599999991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6855007febc6f1%3A0x59a3e368a75250c4!2sPusat%20kurma%20ciranjang!5e0!3m2!1sid!2sid!4v1784791743250!5m2!1sid!2sid'
                    ],
                    [
                        'name' => 'Gudang Tungturunan',
                        'address' => 'Jl. Sukamanah, Hegarmanah, Kec. Sukaluyu, Kabupaten Cianjur, Jawa Barat 43284',
                        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.7532457219695!2d107.23864147403378!3d-6.799845966502754!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6855d09d3b1a5f%3A0x825c9020dfc1af2c!2sPusat%20kurma%20cianjur%20Di%20Khan%20Mart%20(%20Jual%20makanan%20arab%20)!5e0!3m2!1sid!2sid!4v1780115703161!5m2!1sid!2sid'
                    ]
                ];
            }

            $reply = "📍 **Lokasi Toko Pusat:**\n{$address}\n🗺️ [Buka Google Maps Pusat]({$pusatMapsUrl})";

            $actionButtons = [];
            $actionButtons[] = [
                'label' => 'Buka Google Maps Toko Pusat',
                'url' => $pusatMapsUrl,
                'icon' => 'fa-solid fa-map-location-dot',
            ];

            if (!empty($branches)) {
                $reply .= "\n\n🏬 **Lokasi Cabang & Gudang:**";
                foreach ($branches as $index => $branch) {
                    $num = $index + 1;
                    $branchName = $branch['name'] ?? 'Cabang';
                    $branchAddr = $branch['address'] ?? '';
                    $branchEmbed = $branch['maps_embed_url'] ?? '';
                    $branchMapsUrl = $this->convertEmbedToPlaceUrl($branchEmbed, $branchName, $branchAddr);

                    $reply .= "\n{$num}. **{$branchName}:**\n   {$branchAddr}\n   🗺️ [Buka Maps {$branchName}]({$branchMapsUrl})";

                    $actionButtons[] = [
                        'label' => "Buka Google Maps {$branchName}",
                        'url' => $branchMapsUrl,
                        'icon' => 'fa-solid fa-location-dot',
                    ];
                }
            }

            $reply .= "\n\nKlik link di atas atau tombol lokasi di bawah ini untuk langsung membuka kartu tempat di Google Maps!";

            return [
                'reply' => $reply,
                'action_buttons' => $actionButtons,
                'products' => [],
                'quick_replies' => ['🔥 Produk Terlaris', 'Cek Ongkir', 'Pesan via WA'],
            ];
        }

        // Ongkir / Pengiriman / Kurir
        if (preg_match('/(ongkir|ongkos kirim|pengiriman|ekspedisi|jne|jnt|sicepat|pos|kurir|kirim)/i', $msg)) {
            return [
                'reply' => "🚚 **Info Pengiriman & Ongkir:**\n• Pengiriman dilakukan dari **Cianjur, Jawa Barat**.\n• Melayani ekspedisi JNE, J&T, SiCepat, POS, dll.\n• Biaya ongkir dihitung otomatis saat checkout sesuai kota/kecamatan tujuan Anda.\n• Gratis ongkir tersedia untuk produk promo tertentu!",
                'products' => $this->getFreeShippingProducts(),
                'quick_replies' => ['Lihat Semua Produk', '🔥 Produk Terlaris', 'Cara Bayar'],
            ];
        }

        // Pembayaran / Bayar / Metode Bayar
        if (preg_match('/(bayar|pembayaran|transfer|qris|gopay|ovo|dana|va|bank|cod)/i', $msg)) {
            return [
                'reply' => "💳 **Pilihan Pembayaran:**\nKami mendukung berbagai metode pembayaran yang cepat, aman & terverifikasi otomatis:\n• **QRIS** (GoPay, OVO, DANA, ShopeePay, LinkAja, Mobile Banking)\n• **Virtual Account** (BCA, Mandiri, BNI, BRI, Permata)\n• **Transfer Bank**",
                'products' => [],
                'quick_replies' => ['🔥 Produk Terlaris', 'Kurma Ajwa', 'Cek Lokasi'],
            ];
        }

        return null;
    }

    /**
     * Memeriksa intent rekomendasi spesifik (Terlaris, Kurma, Bumil, Diabetes, Hampers, dll)
     */
    private function checkRecommendationIntents(string $msg): ?array
    {
        // 0. Produk Terlaris / Best Seller (Data gabungan dari SEMUA CABANG)
        if (preg_match('/(terlaris|paling laris|best seller|bestseller|populer|terpopuler|paling laku|paling banyak dibeli|favorit)/i', $msg)) {
            $isKurmaSpecific = (bool) preg_match('/(kurma|kurmma|kuram)/i', $msg);
            $bestSellers = $this->getBestSellersFromAllBranches(4, $isKurmaSpecific);

            if ($isKurmaSpecific) {
                return [
                    'reply' => "🌴🔥 **Kurma Terlaris (Best Seller) Rekap Semua Cabang:**\n\nBerikut adalah produk **kurma** yang paling banyak dibeli dan menjadi favorit pelanggan di seluruh cabang toko kami:",
                    'products' => $this->formatProductCards($bestSellers),
                    'quick_replies' => ['Kurma Sukari', 'Kurma Ajwa', 'Kurma Madu', 'Tanya WA Admin'],
                ];
            }

            return [
                'reply' => "🔥 **Produk Terlaris (Best Seller) Rekap Semua Cabang:**\n\nBerikut adalah produk yang paling banyak dibeli dan menjadi favorit pelanggan di seluruh cabang toko kami:",
                'products' => $this->formatProductCards($bestSellers),
                'quick_replies' => ['🌴 Rekomendasi Kurma', '🍯 Madu Kashmir', '🥑 Ibu Hamil & Promil', 'Tanya WA Admin'],
            ];
        }

        // 1. Permintaan Rekomendasi Kurma Umum ("kurma", "dari kurma", "rekomendasi kurma", "jenis kurma")
        if (preg_match('/^(dari kurma|kurma|rekomendasi kurma|pilihan kurma|jenis kurma|aneka kurma|buah kurma|jual kurma)$/i', trim($msg))) {
            $kurmaProducts = $this->getBestSellersFromAllBranches(4, true);

            return [
                'reply' => "🌴 **Pilihan Produk Kurma Premium Terbaik Kami:**\nBerikut adalah aneka pilihan kurma segar dan berkualitas tinggi yang siap dipesan hari ini:",
                'products' => $this->formatProductCards($kurmaProducts),
                'quick_replies' => ['Kurma Ajwa', 'Kurma Sukari', 'Kurma Medjool', 'Kurma per Gram'],
            ];
        }

        // 2. Ibu Hamil / Promil / Menyusui
        if (preg_match('/(hamil|bumil|promil|menyusui|asi|persalinan|kesehatan)/i', $msg)) {
            $products = $this->getGenuineKurmaProducts(3);

            return [
                'reply' => "🥑 **Rekomendasi untuk Ibu Hamil & Promil:**\n• **Kurma Ajwa**: Kaya zat besi, kalium, dan antioksidan untuk stamina & mencegah anemia.\n• **Kurma Sukari**: Membantu mencukupi energi alami menjelang persalinan & melancarkan ASI.\n• **Madu Murni**: Menjaga daya tahan tubuh dan imunitas ibu & janin.\n\nBerikut rekomendasi produk terbaik di toko kami:",
                'products' => $this->formatProductCards($products),
                'quick_replies' => ['Kurma Ajwa', 'Madu Kashmir', 'Kurma Sukari', 'Harga Grosir'],
            ];
        }

        // 3. Diabetes / Rendah Gula / Diet
        if (preg_match('/(diabetes|gula|diet|rendah gula|kadar gula|sehat)/i', $msg)) {
            $products = $this->queryProductsByKeywords(['ajwa', 'tunisia', 'safawi']);

            return [
                'reply' => "🌿 **Rekomendasi untuk Penderita Diabetes / Diet:**\n• **Kurma Ajwa** & **Kurma Tunisia**: Memiliki indeks glikemik yang relatif aman dan manis sedang, cocok dipadukan dengan pola makan sehat.\n\nBerikut pilihan kurma yang disarankan:",
                'products' => $products,
                'quick_replies' => ['Kurma Ajwa', 'Kurma Tunisia', 'Tanya Admin WA'],
            ];
        }

        // 4. Oleh-oleh Haji / Umroh / Hampers / Hadiah
        if (preg_match('/(haji|umroh|oleh-oleh|oleh oleh|hampers|hadiah|souvenir|gift|box|kemasan)/i', $msg)) {
            $products = $this->queryProductsByKeywords(['hampers', 'box', 'ajwa', 'medjool', 'pack', 'oleh']);

            return [
                'reply' => "🎁 **Rekomendasi Oleh-oleh Haji & Hampers:**\nKami menyediakan pilihan kurma kualitas premium, air zam-zam, kismis, kacang arab, serta kemasan rapi yang sangat cocok untuk souvenir haji/umroh maupun hadiah keluarga.",
                'products' => $products,
                'quick_replies' => ['Kurma Medjool', 'Kurma Ajwa', 'Pesan Banyak / Grosir'],
            ];
        }

        // 5. Kurma Manis Legit / Favorit Anak
        if (preg_match('/(manis|legit|lembut|lumer|anak|keluarga)/i', $msg)) {
            $products = $this->queryProductsByKeywords(['sukari', 'sukkari', 'medjool', 'khalas']);

            return [
                'reply' => "🍯 **Rekomendasi Kurma Manis Legit & Basah:**\n• **Kurma Sukari (Kurma Raja)**: Dikenal sangat lembut, manis alami, dan lumer di mulut.\n• **Kurma Medjool**: Daging buah sangat tebal & manis mantap.\n\nBerikut pilihan kurma favorit pelanggan kami:",
                'products' => $products,
                'quick_replies' => ['Kurma Sukari', 'Kurma Medjool', '🔥 Produk Terlaris'],
            ];
        }

        // 6. Ekonomis / Termurah / Grosir
        if (preg_match('/(murah|ekonomis|grosir|terjangkau|harga miring|paket)/i', $msg)) {
            $products = Product::where('is_active', true)
                ->where('is_active_in_shop', true)
                ->orderBy('selling_price', 'asc')
                ->take(3)
                ->get();

            return [
                'reply' => "💰 **Rekomendasi Kurma Paling Ekonomis & Hemat:**\nBerikut adalah pilihan produk dengan harga paling terjangkau di toko kami:",
                'products' => $this->formatProductCards($products),
                'quick_replies' => ['Kurma per Gram', 'Kurma Sukari', 'Oleh-oleh Haji'],
            ];
        }

        return null;
    }

    /**
     * Mencari produk di DB berdasarkan pencarian teks umum
     */
    private function searchProductDatabase(string $lowerMsg, string $originalMsg): ?array
    {
        // 1. Bersihkan tanda baca (?, !, ., ,, #, @, %, dll) dan ubah ke huruf kecil
        $cleanedText = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lowerMsg);

        // 2. Daftar kata sandang & kata tanya umum (stop words) untuk mengekstrak kata kunci asli
        $stopwords = [
            'apakah', 'ada', 'jual', 'stok', 'ready', 'cari', 'rekomendasi', 'saran', 'produk',
            'yang', 'bisa', 'tolong', 'mau', 'beli', 'harga', 'berapa', 'dong', 'kak',
            'min', 'gan', 'sis', 'tanya', 'apa', 'di', 'ke', 'dari', 'ini', 'itu', 'punya', 'tersedia',
            'dijual', 'adakah', 'masih', 'butuh', 'lagi', 'ga', 'gak', 'tidak', 'terlaris', 'laris',
            'best', 'seller', 'bestseller', 'populer', 'terpopuler', 'laku'
        ];

        // Pisahkan string menjadi array kata
        $words = array_values(array_filter(explode(' ', $cleanedText)));
        $filteredWords = array_values(array_filter($words, function ($w) use ($stopwords) {
            $w = trim($w);
            return mb_strlen($w) >= 2 && !in_array($w, $stopwords, true);
        }));

        // Jika kata kunci utamanya hanya "kurma" / "kurmma"
        if (empty($filteredWords) || (count($filteredWords) === 1 && in_array($filteredWords[0], ['kurma', 'kurmma'], true))) {
            if (str_contains($lowerMsg, 'kurma') || str_contains($lowerMsg, 'kurmma')) {
                $genuineKurma = $this->getBestSellersFromAllBranches(4, true);
                return [
                    'reply' => "🌴 **Aneka Produk Kurma Premium Kami:**\nBerikut adalah pilihan kurma segar dan berkualitas tinggi yang siap dipesan hari ini:",
                    'products' => $this->formatProductCards($genuineKurma),
                    'quick_replies' => ['Kurma Ajwa', 'Kurma Sukari', 'Kurma Medjool', 'Kurma per Gram'],
                ];
            }
            return null;
        }

        // Cek apakah pencarian TIDAK secara eksplisit meminta barang non-makanan (peci, tasbih, teko, paket)
        $hasExplicitAccessoryKeyword = false;
        foreach (['peci', 'tasbih', 'teko', 'dus', 'paket', 'paper', 'nampan', 'piring', 'botol'] as $acc) {
            if (str_contains($lowerMsg, $acc)) {
                $hasExplicitAccessoryKeyword = true;
                break;
            }
        }

        $primaryKeyword = $filteredWords[0];

        // 3. Coba pencarian persis gabungan semua kata (AND Search)
        $andQuery = Product::where('is_active', true)
            ->where('is_active_in_shop', true)
            ->where(function ($q) use ($filteredWords) {
                foreach ($filteredWords as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('name', 'LIKE', '%' . $term . '%')
                            ->orWhere('category', 'LIKE', '%' . $term . '%');
                    });
                }
            });

        if (!$hasExplicitAccessoryKeyword) {
            $excludeWords = ['peci', 'tasbih', 'teko', 'dus', 'paket berkah', 'paper bag', 'nampan', 'piring', 'botol', 'plastik'];
            $andQuery->where(function ($q) use ($excludeWords) {
                foreach ($excludeWords as $ex) {
                    $q->where('name', 'NOT LIKE', '%' . $ex . '%');
                }
            });
        }

        $andProducts = $andQuery->take(4)->get();

        if ($andProducts->isNotEmpty()) {
            $displayKeyword = implode(' ', array_map('ucfirst', $filteredWords));
            return [
                'reply' => "Ya, kami menyediakan **{$displayKeyword}**! Berikut pilihan produk yang tersedia di toko kami:",
                'products' => $this->formatProductCards($andProducts),
                'quick_replies' => ['🔥 Produk Terlaris', '🍯 Madu Kashmir', 'Tanya WA Admin', 'Cek Ongkir'],
            ];
        }

        // 4. Coba pencarian kata per kata mulai dari kata pertama
        foreach ($filteredWords as $term) {
            $singleQuery = Product::where('is_active', true)
                ->where('is_active_in_shop', true)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'LIKE', '%' . $term . '%')
                      ->orWhere('category', 'LIKE', '%' . $term . '%');
                });

            if (!$hasExplicitAccessoryKeyword) {
                $excludeWords = ['peci', 'tasbih', 'teko', 'dus', 'paket berkah', 'paper bag', 'nampan', 'piring', 'botol', 'plastik'];
                $singleQuery->where(function ($q) use ($excludeWords) {
                    foreach ($excludeWords as $ex) {
                        $q->where('name', 'NOT LIKE', '%' . $ex . '%');
                    }
                });
            }

            $singleProducts = $singleQuery->take(4)->get();

            if ($singleProducts->isNotEmpty()) {
                $displayKeyword = ucfirst($term);
                return [
                    'reply' => "Ya, kami menyediakan produk terkait **\"{$displayKeyword}\"**! Berikut pilihan produk yang tersedia:",
                    'products' => $this->formatProductCards($singleProducts),
                    'quick_replies' => ['🔥 Produk Terlaris', '🍯 Madu Kashmir', 'Tanya WA Admin', 'Cek Ongkir'],
                ];
            }
        }

        // 5. Cek apakah pesan benar-benar berniat mencari PRODUK atau sekadar kata acak / percakapan umum
        $productIntentKeywords = [
            'ada', 'jual', 'stok', 'ready', 'cari', 'harga', 'beli', 'produk', 'dijual', 'punya', 
            'pesan', 'katalog', 'tersedia', 'butuh', 'dijual', 'adakah', 'merk', 'tipe'
        ];

        $knownProductNouns = [
            'sirup', 'sepatu', 'baju', 'susu', 'minyak', 'kue', 'kismis', 'kacang', 'tasbih', 'peci', 
            'sajadah', 'air', 'zamzam', 'cokelat', 'keju', 'keripik', 'biskuit', 'kapsul', 'salep', 
            'parfum', 'bumbu', 'madu', 'kurma', 'garam', 'teh', 'kopi', 'herbal', 'obat', 'vitamin', 
            'sabun', 'shampoo', 'habbatusauda', 'safron', 'fig', 'tin', 'zaitun', 'olive', 'dates'
        ];

        $hasProductIntent = false;
        foreach ($productIntentKeywords as $pik) {
            if (str_contains($lowerMsg, $pik)) {
                $hasProductIntent = true;
                break;
            }
        }

        $isKnownProductNoun = in_array(mb_strtolower($primaryKeyword), $knownProductNouns, true);

        // Jika pesan TIDAK mengandung kata kunci pencarian produk dan BUKAN nama jenis produk yang umum:
        if (!$hasProductIntent && !$isKnownProductNoun) {
            return [
                'reply' => "Terima kasih telah menghubungi kami! 😊 Saya adalah asisten virtual **Pusat Kurma Cianjur**.\n\nSaya dapat membantu memberikan info lokasi toko, ongkir, rekomendasi kurma terlaris, madu, & produk herbal. Silakan pilih menu di bawah ini atau ketik nama produk yang ingin Anda cari:",
                'products' => $this->formatProductCards($this->getBestSellersFromAllBranches(2, true)),
                'quick_replies' => ['🔥 Produk Terlaris', '📍 Lokasi Toko', 'Cek Ongkir', 'Kurma Ajwa'],
            ];
        }

        // Jika memang mencari produk (misal: "sirup", "ada sepatu?") tetapi TIDAK TERSEDIA di database:
        $waNum = Setting::get('shop_whatsapp', '6281234567890');
        $cleanWa = preg_replace('/[^0-9]/', '', $waNum);
        if (!str_starts_with($cleanWa, '62') && str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }

        $displayKeyword = ucfirst($primaryKeyword);

        return [
            'reply' => "Mohon maaf, produk **\"{$displayKeyword}\"** saat ini belum tersedia di katalog online toko kami 😊.\n\nAnda bisa menanyakan ketersediaan stok khusus via WhatsApp ke Admin atau melihat produk rekomendasi kami di bawah ini:",
            'action_button' => [
                'label' => "Tanya Stok '{$displayKeyword}' via WA",
                'url' => "https://wa.me/{$cleanWa}?text=" . urlencode("Halo Admin Pusat Kurma Cianjur, apakah produk {$displayKeyword} tersedia?"),
                'icon' => 'fa-brands fa-whatsapp',
            ],
            'products' => $this->formatProductCards($this->getBestSellersFromAllBranches(2, true)),
            'quick_replies' => ['🔥 Produk Terlaris', '🍯 Madu Kashmir', '📍 Lokasi Toko'],
        ];
    }

    /**
     * Ambil produk kurma asli (buah kurma) dan abaikan aksesoris/paket yang salah kategori di DB
     */
    private function getGenuineKurmaProducts(int $limit = 4)
    {
        $excludeWords = ['peci', 'tasbih', 'teko', 'dus', 'paket berkah', 'paper bag', 'nampan', 'piring', 'botol', 'plastik'];

        $products = Product::where('is_active', true)
            ->where('is_active_in_shop', true)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%kurma%')
                  ->orWhere('name', 'LIKE', '%sukari%')
                  ->orWhere('name', 'LIKE', '%ajwa%')
                  ->orWhere('name', 'LIKE', '%khalas%')
                  ->orWhere('name', 'LIKE', '%medjool%')
                  ->orWhere('name', 'LIKE', '%ruthob%');
            })
            ->where(function ($q) use ($excludeWords) {
                foreach ($excludeWords as $ex) {
                    $q->where('name', 'NOT LIKE', '%' . $ex . '%');
                }
            })
            ->get();

        return $products->take($limit);
    }

    /**
     * Ambil produk terlaris berdasarkan data transaksi aktual SEMUA CABANG (bisa difilter khusus KURMA)
     */
    private function getBestSellersFromAllBranches(int $limit = 4, bool $onlyKurma = false)
    {
        // 1. Ambil transaksi dari semua cabang
        $transactions = Transaction::select('items_summary')
            ->whereNotNull('items_summary')
            ->where('items_summary', '!=', '')
            ->get();

        $nameCounts = [];
        $excludeWords = ['peci', 'tasbih', 'teko', 'dus', 'paket berkah', 'paper bag', 'nampan', 'piring', 'botol', 'plastik'];

        foreach ($transactions as $trx) {
            $parts = explode(', ', $trx->items_summary);
            foreach ($parts as $part) {
                $clean = trim($part);
                // Hapus format info harga tambahan (misal: "Beras basmati 1kg (1 pack x 35000)")
                $clean = preg_replace('/\s*\(\d+\s*(?:pack|pcs|kg|gram|g|dus)\s*x\s*\d+\)/i', '', $clean);
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)$/', $clean, $m)) {
                    $prodName = trim($m[1]);
                } else {
                    $prodName = $clean;
                }

                if (mb_strlen($prodName) < 2) continue;

                if ($onlyKurma) {
                    $lowerName = mb_strtolower($prodName);
                    $isKurmaName = str_contains($lowerName, 'kurma') || str_contains($lowerName, 'sukari') || str_contains($lowerName, 'ajwa') || str_contains($lowerName, 'khalas') || str_contains($lowerName, 'medjool') || str_contains($lowerName, 'ruthob');
                    if (!$isKurmaName) continue;
                }

                if (!isset($nameCounts[$prodName])) {
                    $nameCounts[$prodName] = 0;
                }
                $nameCounts[$prodName]++;
            }
        }

        arsort($nameCounts);

        // 2. Cocokkan nama produk paling laku dengan model Product di toko
        $matchedProducts = collect();
        $matchedIds = [];

        foreach ($nameCounts as $rawName => $count) {
            $query = Product::where('is_active', true)
                ->where('is_active_in_shop', true)
                ->where(function ($q) use ($rawName) {
                    $q->where('name', 'LIKE', $rawName)
                      ->orWhere('name', 'LIKE', '%' . $rawName . '%');
                });

            if ($onlyKurma) {
                $query->where(function ($q) use ($excludeWords) {
                    foreach ($excludeWords as $ex) {
                        $q->where('name', 'NOT LIKE', '%' . $ex . '%');
                    }
                });
            }

            $prod = $query->first();

            if ($prod && !in_array($prod->id, $matchedIds, true)) {
                $matchedIds[] = $prod->id;
                $matchedProducts->push($prod);
                if ($matchedProducts->count() >= $limit) {
                    break;
                }
            }
        }

        // 3. Fallback jika data transaksi belum mencukupi
        if ($matchedProducts->count() < $limit) {
            if ($onlyKurma) {
                $fallbackKurma = $this->getGenuineKurmaProducts($limit);
                foreach ($fallbackKurma as $fp) {
                    if (!in_array($fp->id, $matchedIds, true)) {
                        $matchedIds[] = $fp->id;
                        $matchedProducts->push($fp);
                        if ($matchedProducts->count() >= $limit) break;
                    }
                }
            } else {
                $fallbackProducts = Product::where('is_active', true)
                    ->where('is_active_in_shop', true)
                    ->whereNotIn('id', $matchedIds)
                    ->orderBy('id', 'desc')
                    ->take($limit - $matchedProducts->count())
                    ->get();

                foreach ($fallbackProducts as $fp) {
                    $matchedProducts->push($fp);
                }
            }
        }

        return $matchedProducts;
    }

    /**
     * Query produk berdasarkan kata kunci alternatif
     */
    private function queryProductsByKeywords(array $keywords): array
    {
        $query = Product::where('is_active', true)->where('is_active_in_shop', true)->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('name', 'LIKE', '%' . $kw . '%')
                  ->orWhere('category', 'LIKE', '%' . $kw . '%');
            }
        });

        $products = $query->take(3)->get();

        if ($products->isEmpty()) {
            $products = $this->getGenuineKurmaProducts(3);
        }

        return $this->formatProductCards($products);
    }

    /**
     * Ambil produk gratis ongkir
     */
    private function getFreeShippingProducts(): array
    {
        $freeShippingIds = json_decode(Setting::get('free_shipping_product_ids', '[]'), true) ?: [];

        if (!empty($freeShippingIds)) {
            $products = Product::whereIn('id', $freeShippingIds)
                ->where('is_active', true)
                ->where('is_active_in_shop', true)
                ->take(3)
                ->get();
            if ($products->isNotEmpty()) {
                return $this->formatProductCards($products);
            }
        }

        return $this->formatProductCards($this->getBestSellersFromAllBranches(2));
    }

    /**
     * Ambil produk unggulan untuk kartu rekomendasi
     */
    private function getFeaturedProductCards(int $limit = 3): array
    {
        return $this->formatProductCards($this->getBestSellersFromAllBranches($limit, true));
    }

    /**
     * Format Eloquent Collection ke array kartu produk JSON
     */
    private function formatProductCards($products): array
    {
        $cards = [];
        foreach ($products as $p) {
            $imageUrl = $p->image_path ? asset('storage/' . $p->image_path) : null;
            $unitLabel = $p->price_unit ?? 'pack';

            $cards[] = [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category ?? 'Kurma',
                'selling_price' => $p->selling_price,
                'formatted_price' => 'Rp ' . number_format($p->selling_price, 0, ',', '.'),
                'price_unit' => $unitLabel,
                'image_url' => $imageUrl,
                'detail_url' => route('shop.product.show', $p->id),
                'stock' => $p->stock,
                'stock_status' => ($p->stock > 0) ? 'Tersedia' : 'Habis',
            ];
        }

        return $cards;
    }

    /**
     * Pilihan quick replies default
     */
    private function getDefaultQuickReplies(): array
    {
        return [
            '🔥 Produk Terlaris',
            '🌴 Rekomendasi Kurma',
            '🍯 Madu Kashmir',
            '🥑 Ibu Hamil & Promil',
            '🎁 Oleh-oleh & Hampers',
            '📍 Lokasi Toko',
            '🚚 Info Ongkir',
        ];
    }

    /**
     * Konversi hex string ke 64-bit unsigned decimal string tanpa kehilangan presisi floating point
     */
    private function hexToDec64(string $hex): string
    {
        $hex = ltrim($hex, '0x');
        $dec = '0';
        for ($i = 0; $i < strlen($hex); $i++) {
            if (function_exists('bcmul') && function_exists('bcadd')) {
                $dec = bcmul($dec, '16');
                $dec = bcadd($dec, (string) hexdec($hex[$i]));
            } else {
                $dec = (string) (floatval($dec) * 16 + hexdec($hex[$i]));
            }
        }
        return $dec;
    }

    /**
     * Konversi URL Google Maps Embed (pb) menjadi URL Google Maps CID / Place Card resmi
     */
    private function convertEmbedToPlaceUrl(string $embedUrl, string $fallbackName = '', string $fallbackAddress = ''): string
    {
        if (!empty($embedUrl)) {
            // Clean HTML trailing attributes if any
            if (preg_match('/src=["\'](https:\/\/[^"\']+)["\']/', $embedUrl, $srcMatch)) {
                $embedUrl = $srcMatch[1];
            }

            // Extract Place Hex CID (!1s0x...%3A0x... or !1s0x...:0x...)
            if (preg_match('/!1s0x[0-9a-fA-F]+(?:%3A|:)(0x[0-9a-fA-F]+)/i', $embedUrl, $m)) {
                $cidHex = $m[1];
                $cidDec = $this->hexToDec64($cidHex);
                if (!empty($cidDec) && $cidDec !== '0') {
                    return "https://www.google.com/maps?cid={$cidDec}";
                }
            }

            // Fallback: extract place name and place id
            if (preg_match('/!1s(0x[0-9a-fA-F]+)(?:%3A|:)(0x[0-9a-fA-F]+)/i', $embedUrl, $m)) {
                $placeId = $m[1] . ':' . $m[2];
                $placeName = '';
                if (preg_match('/!2s([^!]+)/', $embedUrl, $nameMatch)) {
                    $rawName = explode('"', $nameMatch[1])[0];
                    $placeName = urldecode($rawName);
                }
                $query = !empty($placeName) ? $placeName : "Pusat Kurma {$fallbackName}";
                return "https://www.google.com/maps/search/?api=1&query=" . urlencode($query) . "&query_place_id={$placeId}";
            }
        }

        return "https://www.google.com/maps/search/?api=1&query=" . urlencode("Pusat Kurma {$fallbackName} {$fallbackAddress}");
    }
}
