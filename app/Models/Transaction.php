<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'affiliate_id',
        'pax_count',
        'transaction_code',
        'transaction_type',
        'customer_name',
        'customer_phone',
        'items_summary',
        'total_price',
        'discount',
        'shipping_cost',
        'total_cost',
        'payment_method',
        'branch',
        'payment_status',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Otomatis kembalikan stok saat transaksi dihapus
        static::deleting(function (Transaction $transaction) {
            app(\App\Services\StockService::class)->restoreTransactionStock(
                $transaction,
                "Pengembalian stok: Transaksi #{$transaction->transaction_code} dihapus"
            );

            // Batalkan komisi afiliasi jika ada
            if ($transaction->komisiLedger) {
                $transaction->komisiLedger->delete();
            }
        });

        // Otomatis tangani perubahan status payment_status (misal dibatalkan)
        static::updated(function (Transaction $transaction) {
            if ($transaction->wasChanged('payment_status')) {
                if ($transaction->payment_status === 'cancelled') {
                    app(\App\Services\StockService::class)->restoreTransactionStock(
                        $transaction,
                        "Pengembalian stok: Transaksi #{$transaction->transaction_code} dibatalkan"
                    );

                    if ($transaction->komisiLedger) {
                        $transaction->komisiLedger->delete();
                    }
                } elseif ($transaction->getOriginal('payment_status') === 'cancelled' && in_array($transaction->payment_status, ['paid', 'unpaid'])) {
                    // Jika diaktifkan kembali dari cancelled, potong ulang stoknya
                    app(\App\Services\StockService::class)->reDeductTransactionStock(
                        $transaction,
                        "Potong ulang stok: Transaksi #{$transaction->transaction_code} diaktifkan kembali"
                    );
                }
            }
        });
    }

    /**
     * Get the cashier that owns the transaction.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the affiliate (Tour Leader) that brought this transaction.
     */
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the commission ledger entry associated with this transaction.
     */
    public function komisiLedger()
    {
        return $this->hasOne(KomisiLedger::class);
    }

    /**
     * Get the installment payments for the transaction.
     */
    public function installments()
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    /**
     * Hitung rincian nominal pembayaran per metode (termasuk jika transaksi SPLIT).
     * Mengembalikan array ['cash' => int, 'qris' => int, 'debit' => int, 'is_split' => bool]
     */
    public function getPaymentBreakdownAttribute(): array
    {
        $method = $this->payment_method ?? 'Cash';
        $total  = (float) $this->total_price;

        $breakdown = [
            'cash'     => 0,
            'qris'     => 0,
            'debit'    => 0,
            'is_split' => false,
        ];

        if ($method === 'Cash') {
            $breakdown['cash'] = $total;
        } elseif ($method === 'QRIS') {
            $breakdown['qris'] = $total;
        } elseif ($method === 'Debit') {
            $breakdown['debit'] = $total;
        } elseif (str_starts_with($method, 'SPLIT:')) {
            $breakdown['is_split'] = true;

            // Format tersimpan: "SPLIT: Cash Rp 40.000 + QRIS Rp 60.000"
            if (preg_match('/Cash\s*Rp\s*([\d\.]+)/i', $method, $mCash)) {
                $breakdown['cash'] = (float) str_replace('.', '', $mCash[1]);
            }
            if (preg_match('/QRIS\s*Rp\s*([\d\.]+)/i', $method, $mQris)) {
                $breakdown['qris'] = (float) str_replace('.', '', $mQris[1]);
            }
            if (preg_match('/Debit\s*Rp\s*([\d\.]+)/i', $method, $mDebit)) {
                $breakdown['debit'] = (float) str_replace('.', '', $mDebit[1]);
            }
        } else {
            // Default fallback
            $breakdown['cash'] = $total;
        }

        return $breakdown;
    }

    /**
     * Cek apakah transaksi sudah lunas (berdasarkan payment_status atau total cicilan)
     */
    public function getIsPaidAttribute(): bool
    {
        if (($this->payment_status ?? 'paid') === 'paid') {
            return true;
        }

        if ($this->relationLoaded('installments')) {
            return $this->installments->sum('amount') >= $this->total_price;
        }

        return $this->installments()->sum('amount') >= $this->total_price;
    }

    /**
     * Cek apakah transaksi ini dicatat tanpa pemotongan stok fisik inventori
     * (misal: barang sudah dikeluarkan / didata duluan sebelumnya, atau saldo awal piutang manual).
     */
    public function isStockDeductionSkipped(): bool
    {
        if (str_starts_with($this->transaction_code, 'PTM-') || str_contains($this->transaction_code, 'NOSTK') || str_contains($this->transaction_code, '-NS-')) {
            return true;
        }
        $summary = $this->items_summary ?? '';
        if (str_contains($summary, '[Stok Sudah Didata]') || str_contains($summary, '[Tanpa Potong Stok]') || str_contains($summary, '[Piutang Manual]')) {
            return true;
        }
        return false;
    }

    /**
     * Parse items_summary into structured array of items with formatted quantities and prices.
     */
    public function getParsedItemsAttribute(): array
    {
        $items = [];
        $summary = $this->items_summary;
        if (empty($summary)) {
            return $items;
        }

        $parts = explode(', ', $summary);
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if (in_array($trimmed, ['[Stok Sudah Didata]', '[Tanpa Potong Stok]', '[Piutang Manual]'])) {
                continue;
            }

            if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z\s]+)\s*x\s*(\d+)\)$/', $part, $matches)) {
                $name = trim($matches[1]);
                $qty = floatval($matches[2]);
                $unit = trim($matches[3]);
                $price = intval($matches[4]);

                $displayQty = (round($qty, 2) == round($qty) ? (int)$qty : number_format($qty, 2, ',', '.')) . ' ' . $unit;
                if (strtolower($unit) === 'gram' && $qty >= 1000) {
                    $kg = $qty / 1000;
                    $displayQty = (round($kg, 2) == round($kg) ? (int)$kg : number_format($kg, 2, ',', '.')) . ' kg (' . number_format($qty, 0, ',', '.') . ' g)';
                }

                $items[] = [
                    'name' => $name,
                    'qty' => $qty,
                    'unit' => $unit,
                    'display_qty' => $displayQty,
                    'unit_price' => $price,
                    'total_price' => (float) ($qty * $price),
                ];
            } else {
                $items[] = [
                    'name' => trim($part),
                    'qty' => 1,
                    'unit' => 'item',
                    'display_qty' => '1 item',
                    'unit_price' => 0,
                    'total_price' => 0,
                ];
            }
        }

        return $items;
    }
}
