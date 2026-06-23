<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    /** Stok produk di lokasi ini */
    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'location_id');
    }

    /** Transfer yang berasal dari lokasi ini */
    public function transfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_location_id');
    }

    /** Transfer yang menuju ke lokasi ini */
    public function transfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_location_id');
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGudang($query)
    {
        return $query->where('type', 'gudang');
    }

    public function scopeCabang($query)
    {
        return $query->where('type', '!=', 'gudang');
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Temukan atau buat lokasi berdasarkan nama cabang (dari field user.branch).
     */
    public static function findByBranchName(string $branchName): ?self
    {
        return self::where('name', $branchName)->first();
    }

    /**
     * Ambil semua nama lokasi aktif sebagai array.
     */
    public static function getActiveNames(): array
    {
        return self::active()->pluck('name', 'id')->toArray();
    }
}
