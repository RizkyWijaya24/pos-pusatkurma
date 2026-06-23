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
     * Label status dalam Bahasa Indonesia.
     */
    public function getStatusLabelAttribute(): string
    {
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
     * Generate kode transfer unik.
     */
    public static function generateCode(): string
    {
        return 'TRF-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
