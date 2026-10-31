<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_code',
        'invoice_number',
        'supplier_id',
        'supplier_name',
        'supplier_phone',
        'location_id',
        'created_by',
        'purchase_date',
        'items_summary',
        'total_amount',
        'paid_amount',
        'payment_method',
        'payment_status',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'due_date'      => 'date',
            'total_amount'  => 'float',
            'paid_amount'   => 'float',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class)->latest('payment_date');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS & HELPERS
    // ═══════════════════════════════════════════════════════

    public function getRemainingDebtAttribute(): float
    {
        return (float) max(0, $this->total_amount - $this->paid_amount);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid' || $this->paid_amount >= $this->total_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_paid || empty($this->due_date)) {
            return false;
        }
        return Carbon::parse($this->due_date)->endOfDay()->isPast();
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (empty($this->due_date)) {
            return null;
        }
        $today = Carbon::today();
        $due = Carbon::parse($this->due_date)->startOfDay();
        return (int) $today->diffInDays($due, false);
    }
}

