<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'payment_code',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'float',
            'payment_date' => 'date',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

