<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepackLog extends Model
{
    use HasFactory;

    // Log ini hanya dibuat, tidak diupdate → gunakan created_at saja
    public $timestamps = false;

    protected $fillable = [
        'repack_code',
        'location_id',
        'source_product_id',
        'source_quantity',
        'created_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'source_quantity' => 'float',
            'created_at'      => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function sourceProduct()
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RepackLogItem::class, 'repack_log_id');
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    public static function generateCode(): string
    {
        $prefix = 'RPK-' . date('Ymd') . '-';
        $latest = self::where('repack_code', 'like', $prefix . '%')
                      ->orderBy('repack_code', 'desc')
                      ->first();

        if ($latest) {
            $num = (int) substr($latest->repack_code, -4);
            $next = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return $prefix . $next;
    }
}
