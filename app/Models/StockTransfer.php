<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_code',
        'from_location_id',
        'to_location_id',
        'requested_by',
        'approved_by',
        'status',
        'notes',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function fromLocation()
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopePending($query)
    {
        return $query->whereIn('status', ['requested', 'pending']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Apakah transfer ini masih bisa diubah?
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['requested', 'pending']);
    }

    /**
     * Apakah transfer dibuat oleh kasir (status requested)?
     */
    public function isRequest(): bool
    {
        return $this->status === 'requested';
    }

    /**
     * Cek apakah transfer disetujui dengan penyesuaian kuantitas.
     */
    public function hasAdjustment(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(function ($item) {
                return $item->approved_quantity !== null && $item->approved_quantity != $item->quantity;
            });
        }
        return false;
    }

    /**
     * Label status dalam Bahasa Indonesia.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'approved' && $this->hasAdjustment()) {
            return 'Disetujui Sebagian';
        }

        return match ($this->status) {
            'requested' => 'Menunggu Proses',
            'pending'   => 'Diproses Admin',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default     => 'Unknown',
        };
    }

    /**
     * Warna badge status untuk UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'requested' => 'yellow',
            'pending'   => 'blue',
            'approved'  => 'green',
            'rejected'  => 'red',
            'cancelled' => 'gray',
            default     => 'gray',
        };
    }

    /**
     * Payload terformat untuk cetak resi pengiriman antar cabang via Bluetooth ESC/POS.
     */
    public function toBluetoothReceiptData(): array
    {
        $this->loadMissing(['items.product', 'fromLocation', 'toLocation', 'requester', 'approver']);

        $items = $this->items->map(function ($it) {
            $isAdjusted = $this->status === 'approved' && $it->approved_quantity !== null && (float)$it->approved_quantity !== (float)$it->quantity;
            $finalQty = ($this->status === 'approved' && $it->approved_quantity !== null)
                ? (float)$it->approved_quantity
                : (float)$it->quantity;

            return [
                'name'          => $it->product?->name ?? 'Produk',
                'sku'           => $it->product?->sku ?? '-',
                'qty'           => number_format($finalQty, 2, ',', '.'),
                'requested_qty' => number_format((float)$it->quantity, 2, ',', '.'),
                'unit'          => $it->unit ?? 'pcs',
                'is_adjusted'   => $isAdjusted,
            ];
        })->values()->all();

        return [
            'id'            => $this->id,
            'code'          => $this->transfer_code,
            'status'        => $this->statusLabel,
            'created_at'    => $this->created_at->format('d/m/Y H:i') . ' WIB',
            'approved_at'   => $this->approved_at ? $this->approved_at->format('d/m/Y H:i') . ' WIB' : null,
            'from_location' => $this->fromLocation?->name ?? 'Gudang Pusat',
            'to_location'   => $this->toLocation?->name ?? '-',
            'sender_name'   => $this->requester?->name ?? '-',
            'approver_name' => $this->approver?->name,
            'notes'         => $this->notes ?? '-',
            'items'         => $items,
        ];
    }

    /**
     * Generate kode transfer unik.
     */
    public static function generateCode(): string
    {
        return 'TRF-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
