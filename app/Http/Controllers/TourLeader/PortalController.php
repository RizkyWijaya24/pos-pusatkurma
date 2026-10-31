<?php

namespace App\Http\Controllers\TourLeader;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Exception;

class PortalController extends Controller
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Dashboard Mobile-First khusus Tour Leader / B2B Partner
     */
    public function dashboard(Request $request, ?string $code = null)
    {
        // 1. Prioritas cari afiliasi berdasarkan parameter code di URL
        $affiliate = null;
        if ($code) {
            $affiliate = Affiliate::where('code', strtoupper($code))->first();
        }

        // 2. Jika tidak ada code di URL, periksa user login jika terhubung
        if (!$affiliate && auth()->check()) {
            $affiliate = Affiliate::where('user_id', auth()->id())->first();
        }

        // 3. Fallback jika testing belum ada afiliasi yang dipilih (ambil agen pertama yang aktif)
        if (!$affiliate) {
            $affiliate = Affiliate::where('status', 'active')->first();
        }

        if (!$affiliate) {
            abort(404, 'Data Agen Tour Leader tidak ditemukan atau belum aktif.');
        }

        // 3 HERO CARDS METRICS
        $totalJamaah = $affiliate->total_jamaah;
        $akumulasiOmset = $affiliate->total_omset;
        $komisiTersedia = $affiliate->komisi_tersedia;

        // Metrik pelengkap
        $komisiSudahCair = $affiliate->total_komisi_withdrawn;
        $totalKomisiEarned = $affiliate->total_komisi_earned;

        // Riwayat Transaksi Rombongan Terbaru (10 transaksi terakhir)
        $recentTransactions = $affiliate->transactions()
            ->latest()
            ->take(10)
            ->get();

        // Riwayat Buku Besar Mutasi Komisi (15 mutasi terakhir)
        $ledgerEntries = $affiliate->ledgers()
            ->latest()
            ->take(15)
            ->get();

        return view('tour_leader.dashboard', compact(
            'affiliate',
            'totalJamaah',
            'akumulasiOmset',
            'komisiTersedia',
            'komisiSudahCair',
            'totalKomisiEarned',
            'recentTransactions',
            'ledgerEntries'
        ));
    }

    /**
     * Permintaan Penarikan Komisi (Tarik Komisi) dari antarmuka mobile Tour Leader
     */
    public function withdraw(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50000',
            'notes'  => 'nullable|string|max:255',
        ], [
            'amount.min' => 'Batas minimum penarikan komisi adalah Rp 50.000.',
        ]);

        try {
            $amount = (float) $validated['amount'];

            $this->affiliateService->requestWithdrawal($affiliate, $amount, [
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->back()
                ->with('success', "Permintaan penarikan komisi sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dikirim! Admin akan segera memproses transfer ke rekening {$affiliate->bank_name} Anda.");
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}

