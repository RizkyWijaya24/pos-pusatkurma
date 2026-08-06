<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'shipping_notes',
        // Shipping / Ongkir fields
        'destination_city_id',
        'destination_city_name',
        'shipping_courier',
        'shipping_service',
        'shipping_service_name',
        'shipping_cost',
        'shipping_etd',
        'subtotal_amount',
        // Totals & payment
        'payment_fee',
        'payment_channel',
        'total_amount',
        'payment_status',
        'status',
        'snap_token',
        // Diskon
        'coupon_code',
        'coupon_discount',
        'referral_code',
        'referral_discount',
    ];

    /** Ambil nomor langkah timeline tracking (1-5) */
    public function getStepNumberAttribute(): int
    {
        if (in_array($this->payment_status, ['failed', 'expired', 'cancelled']) || $this->status === 'cancelled') {
            return 0;
        }

        switch ($this->status) {
            case 'completed':
                return 5;
            case 'shipped':
                return 4;
            case 'processing':
                return 3;
            case 'paid':
                return 2;
            case 'pending':
            default:
                return $this->payment_status === 'paid' ? 2 : 1;
        }
    }

    /** Relasi ke item detail pesanan */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Relasi ke ulasan produk yang terkait dengan pesanan ini */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'order_code', 'order_code');
    }

    /** Helper untuk membuat kode pesanan unik secara otomatis */
    public static function generateOrderCode(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return 'PK-ORD-' . $date . '-' . $random;
    }

    /** Boot method to hook model events */
    protected static function booted()
    {
        static::updated(function ($order) {
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                app(\App\Services\StockService::class)->deductOrderStock($order);
            }
        });
    }
}
