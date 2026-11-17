<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'approved_quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'float',
            'approved_quantity' => 'float',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Dapatkan satuan produk yang akurat.
     * Jika unit yang tersimpan bernilai 'pcs' atau kosong, prioritaskan satuan riil dari produk (misal kg, dus, pack, dll).
     */
    public function getUnitAttribute($value): string
    {
        if (!empty($value) && strtolower(trim($value)) !== 'pcs') {
            return $value;
        }

        $productUnit = $this->product?->price_unit;
        if (!empty($productUnit)) {
            return $productUnit;
        }

        return $value ?: 'pcs';
    }
}
