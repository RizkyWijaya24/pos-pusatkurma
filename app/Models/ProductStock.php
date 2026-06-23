<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'float',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Tambah stok di lokasi ini. Pastikan tidak negatif.
     */
    public function incrementStock(float $qty): void
    {
        $this->stock = max(0, $this->stock + $qty);
        $this->save();
    }

    /**
     * Kurangi stok di lokasi ini. Pastikan tidak negatif.
     *
     * @throws \Exception jika stok tidak mencukupi
     */
    public function decrementStock(float $qty): void
    {
        if ($this->stock < $qty) {
            throw new \Exception("Stok tidak mencukupi. Tersedia: {$this->stock}, diminta: {$qty}");
        }
        $this->stock = max(0, $this->stock - $qty);
        $this->save();
    }

    /**
     * Ambil atau buat record stok untuk produk & lokasi tertentu.
     */
    public static function getOrCreate(int $productId, int $locationId): self
    {
        return self::firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId],
            ['stock' => 0.00]
        );
    }
}
