<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_product_id',
        'target_product_id',
        'conversion_rate',
    ];

    protected function casts(): array
    {
        return [
            'conversion_rate' => 'float',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    /** Produk bulk / asal (misal: Dus) */
    public function sourceProduct()
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    /** Produk retail / target (misal: Kg / Pack / Pouch) */
    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }
}
