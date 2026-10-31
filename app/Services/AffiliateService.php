<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\KomisiLedger;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class AffiliateService
{
    /**
     * Hitung dan catat komisi transaksi untuk Tour Leader / Agen B2B
     *
     * @param Transaction $transaction
     * @param int|null $affiliateId
     * @param int $paxCount
     * @return KomisiLedger|null
     */
    public function recordCommission(Transaction $transaction, ?int $affiliateId, int $paxCount = 0): ?KomisiLedger
    {
        if (!$affiliateId) {
            return null;
        }

        $affiliate = Affiliate::find($affiliateId);
        if (!$affiliate || $affiliate->status !== 'active') {
            return null;
        }

        // Ambil persentase komisi dinamis saat ini dari profil afiliasi
        $rate = (float) $affiliate->commission_rate_percentage;
        $omset = (float) $transaction->total_price;

        // Hitung nominal potongan komisi dari omset
        $commissionAmount = round(($omset * $rate) / 100);

        return DB::transaction(function () use ($transaction, $affiliate, $rate, $omset, $commissionAmount, $paxCount) {
            // Update data transaksi jika belum tersimpan
            if ($transaction->affiliate_id != $affiliate->id || $transaction->pax_count != $paxCount) {
                $transaction->update([
                    'affiliate_id' => $affiliate->id,
                    'pax_count'    => $paxCount,
                ]);
            }

            // Insert catatan mutasi kredit ke buku besar komisi
            return KomisiLedger::create([
                'affiliate_id'               => $affiliate->id,
                'transaction_id'             => $transaction->id,
                'type'                       => 'credit',
                'transaction_amount'         => $omset,
                'commission_rate_percentage' => $rate,
                'amount'                     => $commissionAmount,
                'status'                     => 'approved',
                'description'                => "Komisi {$rate}% penjualan transaksi #{$transaction->transaction_code}",
                'notes'                      => "B2B Tour Leader: {$affiliate->name} (" . ($affiliate->agency_name ?? 'Mandiri') . ")",
            ]);
        });
    }

    /**
     * Proses pencairan / penarikan komisi (Debit) oleh Admin
     */
    public function processPayout(Affiliate $affiliate, float $amount, array $meta = []): KomisiLedger
    {
        $available = $affiliate->komisi_tersedia;

        if ($amount <= 0) {
            throw new Exception("Nominal penarikan harus lebih dari 0.");
        }

        if ($amount > $available) {
            throw new Exception("Saldo komisi tidak mencukupi! Komisi tersedia: Rp " . number_format($available, 0, ',', '.'));
        }

        return DB::transaction(function () use ($affiliate, $amount, $meta) {
            return KomisiLedger::create([
                'affiliate_id'               => $affiliate->id,
                'transaction_id'             => null,
                'type'                       => 'debit',
                'transaction_amount'         => 0,
                'commission_rate_percentage' => 0,
                'amount'                     => $amount,
                'status'                     => 'paid',
                'reference_no'               => $meta['reference_no'] ?? ('PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5))),
                'description'                => $meta['description'] ?? "Pencairan komisi ke rekening {$affiliate->bank_name} {$affiliate->bank_account_number} a.n {$affiliate->bank_account_name}",
                'notes'                      => $meta['notes'] ?? null,
            ]);
        });
    }

    /**
     * Permintaan penarikan komisi oleh Tour Leader melalui Dashboard Mobile (Pending)
     */
    public function requestWithdrawal(Affiliate $affiliate, float $amount, array $meta = []): KomisiLedger
    {
        $available = $affiliate->komisi_tersedia;

        if ($amount <= 0) {
            throw new Exception("Nominal penarikan harus lebih dari 0.");
        }

        if ($amount > $available) {
            throw new Exception("Saldo komisi Anda tidak mencukupi! Tersedia: Rp " . number_format($available, 0, ',', '.'));
        }

        return KomisiLedger::create([
            'affiliate_id'               => $affiliate->id,
            'transaction_id'             => null,
            'type'                       => 'debit',
            'transaction_amount'         => 0,
            'commission_rate_percentage' => 0,
            'amount'                     => $amount,
            'status'                     => 'pending',
            'reference_no'               => 'WD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'description'                => "Pengajuan pencairan komisi Tour Leader {$affiliate->name}",
            'notes'                      => $meta['notes'] ?? "Rekening: {$affiliate->bank_name} - {$affiliate->bank_account_number} a.n {$affiliate->bank_account_name}",
        ]);
    }
}

