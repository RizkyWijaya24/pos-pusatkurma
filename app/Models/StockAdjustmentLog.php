<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLog extends Model
{
    // Log ini hanya dibuat, tidak diupdate → pakai created_at saja
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'location_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'float',
            'quantity_change' => 'float',
            'quantity_after'  => 'float',
            'created_at'      => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Label tipe mutasi dalam Bahasa Indonesia.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'initial'      => 'Stok Awal',
            'sale'         => 'Penjualan',
            'transfer_in'  => 'Transfer Masuk',
            'transfer_out' => 'Transfer Keluar',
            'adjustment'   => 'Koreksi Manual',
            'return'       => 'Retur Barang',
            default        => 'Lainnya',
        };
    }

    /**
     * Ikon dan warna untuk UI log.
     */
    public function getTypeIconAttribute(): array
    {
        return match ($this->type) {
            'initial'      => ['icon' => '🏭', 'color' => 'gray'],
            'sale'         => ['icon' => '🛒', 'color' => 'red'],
            'transfer_in'  => ['icon' => '📦', 'color' => 'green'],
            'transfer_out' => ['icon' => '🚚', 'color' => 'blue'],
            'adjustment'   => ['icon' => '✏️', 'color' => 'yellow'],
            'return'       => ['icon' => '↩️', 'color' => 'purple'],
            default        => ['icon' => '📋', 'color' => 'gray'],
        };
    }
}
