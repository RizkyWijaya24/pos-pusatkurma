<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $fillable = [
        'code',
        'owner_name',
        'notes',
        'discount_type',
        'discount_value',
        'min_order',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_order'      => 'integer',
            'used_count'     => 'integer',
            'is_active'      => 'boolean',
        ];
    }

    /**
     * Hitung diskon berdasarkan tipe kode referral.
     */
    public function calculateDiscount(int $subtotal): int
    {
        return match ($this->discount_type) {
            'percent' => (int) ceil($subtotal * $this->discount_value / 100),
            'fixed'   => min($this->discount_value, $subtotal),
            default   => 0,
        };
    }

    /**
     * Validasi apakah kode referral masih aktif dan memenuhi syarat minimum order.
     */
    public function isValid(int $orderSubtotal): bool
    {
        if (!$this->is_active) return false;
        if ($orderSubtotal < $this->min_order) return false;
        return true;
    }

    /** Kode referral aktif saja */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
