<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    /** Produk paket bundling pemilik */
    public function bundle()
    {
        return $this->belongsTo(Product::class, 'bundle_id');
    }

    /** Produk komponen penyusun */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
