<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order',
        'max_discount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value'        => 'integer',
            'min_order'    => 'integer',
            'max_discount' => 'integer',
            'max_uses'     => 'integer',
            'used_count'   => 'integer',
            'is_active'    => 'boolean',
            'expires_at'   => 'datetime',
        ];
    }

    /**
     * Validasi apakah kupon masih bisa digunakan untuk subtotal tertentu.
     */
    public function isValid(int $orderSubtotal): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) return false;
        if ($orderSubtotal < $this->min_order) return false;
        return true;
    }

    /**
     * Hitung nilai diskon berdasarkan tipe kupon.
     * Untuk free_shipping, gunakan helper yang melibatkan ongkir di controller.
     */
    public function calculateDiscount(int $subtotal, int $shippingCost = 0): int
    {
        return match ($this->type) {
            'percent' => (function () use ($subtotal) {
                $disc = (int) ceil($subtotal * $this->value / 100);
                if ($this->max_discount > 0) {
                    $disc = min($disc, $this->max_discount);
                }
                return $disc;
            })(),
            'fixed'         => min($this->value, $subtotal),
            'free_shipping' => $shippingCost,
            default         => 0,
        };
    }

    /** Kupon aktif saja */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
