<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'cost_price',
        'selling_price',
        'price_unit',
        'image_path',
        'stock',
        'price_tiers',
        'weight_grams',
        'is_bundle',
        'is_active_in_shop',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'float',
            'cost_price' => 'integer',
            'selling_price' => 'integer',
            'price_tiers' => 'array',
            'is_bundle' => 'boolean',
            'is_active_in_shop' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope query untuk hanya mengambil produk aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    /** Item penyusun paket bundling (jika is_bundle = true) */
    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }

    /** Stok produk ini di semua lokasi */
    public function productStocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    /** Log mutasi stok produk ini */
    public function stockLogs()
    {
        return $this->hasMany(StockAdjustmentLog::class);
    }

    /** Hubungan konversi dari produk bulk ini ke eceran/repack */
    public function conversions()
    {
        return $this->hasMany(ProductConversion::class, 'source_product_id');
    }

    /** Hubungan konversi di mana produk ini merupakan target eceran/repack */
    public function targetConversions()
    {
        return $this->hasMany(ProductConversion::class, 'target_product_id');
    }

    /** Ulasan produk yang sudah disetujui */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /** Log repack di mana produk ini menjadi bahan baku */
    public function repackLogs()
    {
        return $this->hasMany(RepackLog::class, 'source_product_id');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    /**
     * Ambil harga modal. Jika bundling dan nilainya kosong/0,
     * hitung otomatis dari penjumlahan harga modal komponen.
     */
    public function getCostPriceAttribute($value): int
    {
        if ($this->is_bundle && ($value === null || $value == 0)) {
            $this->loadMissing('bundleItems.product');
            return (int) $this->bundleItems->sum(function ($item) {
                return $item->product->cost_price * $item->quantity;
            });
        }
        return (int) $value;
    }

    /**
     * Ambil total stok. Perilaku bergantung pada apakah relasi productStocks sudah di-load:
     * - Jika productStocks di-load (eager) → return SUM dari semua cabang (akurat & real-time)
     * - Jika bundle → hitung virtual stok dari komponen di semua lokasi aktif
     * - Fallback → kembalikan kolom stock legacy (untuk kompatibilitas mundur)
     */
    public function getStockAttribute($value)
    {
        if ($this->is_bundle) {
            $locations = StockLocation::where('is_active', true)->get();
            $total = 0.0;
            foreach ($locations as $loc) {
                $total += $this->getStockAtLocation($loc->id);
            }
            return $total;
        }

        // Jika relasi productStocks sudah di-eager-load, gunakan total dari semua cabang
        // Ini dipakai di Admin Dashboard agar stok yang tampil = total semua lokasi
        if ($this->relationLoaded('productStocks')) {
            return (float) $this->productStocks->sum('stock');
        }

        // Fallback ke kolom legacy products.stock (untuk endpoint yang tidak load productStocks)
        return $value;
    }

    // ═══════════════════════════════════════════════════════
    // STOCK HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Ambil stok produk di lokasi tertentu.
     *
     * @param int $locationId
     * @return float
     */
    public function getStockAtLocation(int $locationId): float
    {
        if ($this->is_bundle) {
            $this->loadMissing('bundleItems.product.productStocks');
            if ($this->bundleItems->isEmpty()) {
                return 0.0;
            }

            // Cari nilai minimum dari (stok_komponen / qty_komponen_dalam_bundle)
            $virtualStocks = $this->bundleItems->map(function ($item) use ($locationId) {
                $componentStock = $item->product->getStockAtLocation($locationId);
                return floor($componentStock / $item->quantity);
            });

            return (float) $virtualStocks->min();
        }

        $ps = $this->productStocks()->where('location_id', $locationId)->first();
        if ($ps) {
            return (float) $ps->stock;
        }

        // Fallback: Jika tidak ada data di product_stocks sama sekali untuk produk ini,
        // kembalikan nilai kolom stock legacy pada lokasi utama (gudang/default).
        $hasAnyStockRecord = $this->productStocks()->exists();

        if (!$hasAnyStockRecord) {
            $mainLocation = StockLocation::gudang()->first() ?? StockLocation::first();
            if ($mainLocation && $mainLocation->id === $locationId) {
                return (float) $this->getRawOriginal('stock');
            }
        }

        return 0.0;
    }

    /**
     * Hitung total stok dari semua lokasi (agregat).
     *
     * @return float
     */
    public function getTotalStock(): float
    {
        return (float) $this->productStocks()->sum('stock');
    }

    // ═══════════════════════════════════════════════════════
    // PRICING
    // ═══════════════════════════════════════════════════════

    /**
     * Get the active price based on the purchased quantity.
     * If tiered pricing is empty or invalid, defaults to selling_price.
     *
     * @param float $qty
     * @return int
     */
    public function getPriceForQuantity($qty): int
    {
        if (!empty($this->price_tiers) && is_array($this->price_tiers)) {
            foreach ($this->price_tiers as $tier) {
                $min = isset($tier['min_qty']) && $tier['min_qty'] !== '' ? floatval($tier['min_qty']) : 0;
                $max = isset($tier['max_qty']) && $tier['max_qty'] !== '' ? floatval($tier['max_qty']) : INF;
                if ($qty >= $min && $qty <= $max) {
                    return intval($tier['price']);
                }
            }
        }
        return intval($this->selling_price);
    }
}

