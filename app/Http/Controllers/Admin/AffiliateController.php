<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\KomisiLedger;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Exception;

class AffiliateController extends Controller
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Menampilkan daftar agen B2B / Tour Leader dengan tabel data-heavy dan metrik ringkas
     */
    public function index(Request $request)
    {
        $query = Affiliate::withCount('transactions');

        // Filter Pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('agency_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter Status
        if ($status = $request->input('status')) {
            if (in_array($status, ['active', 'inactive'])) {
                $query->where('status', $status);
            }
        }

        $affiliates = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        // Metrik Ringkas untuk Dashboard Admin
        $totalAgents = Affiliate::count();
        $totalActiveAgents = Affiliate::where('status', 'active')->count();

        // Total akumulasi omset dan komisi dari tabel buku besar komisi_ledger
        $totalOmsetB2B = (float) KomisiLedger::where('type', 'credit')->sum('transaction_amount');
        $totalKomisiEarned = (float) KomisiLedger::where('type', 'credit')->sum('amount');
        $totalKomisiPaid = (float) KomisiLedger::where('type', 'debit')->where('status', 'paid')->sum('amount');
        $totalPendingWithdraw = (float) KomisiLedger::where('type', 'debit')->where('status', 'pending')->sum('amount');

        return view('admin.affiliates.index', compact(
            'affiliates',
            'totalAgents',
            'totalActiveAgents',
            'totalOmsetB2B',
            'totalKomisiEarned',
            'totalKomisiPaid',
            'totalPendingWithdraw'
        ));
    }

    /**
     * Simpan data agen B2B baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'code'                       => 'required|string|max:50|unique:affiliates,code',
            'phone'                      => 'nullable|string|max:30',
            'agency_name'                => 'nullable|string|max:255',
            'commission_rate_percentage' => 'required|numeric|min:0|max:100',
            'bank_name'                  => 'nullable|string|max:100',
            'bank_account_number'        => 'nullable|string|max:100',
            'bank_account_name'          => 'nullable|string|max:255',
            'status'                     => 'required|in:active,inactive',
            'notes'                      => 'nullable|string',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        Affiliate::create($validated);

        return redirect()->route('admin.affiliates.index')
            ->with('success', "Agen B2B Tour Leader '{$validated['name']}' ({$validated['code']}) dengan komisi {$validated['commission_rate_percentage']}% berhasil didaftarkan!");
    }

    /**
     * Update data agen B2B
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'code'                       => 'required|string|max:50|unique:affiliates,code,' . $affiliate->id,
            'phone'                      => 'nullable|string|max:30',
            'agency_name'                => 'nullable|string|max:255',
            'commission_rate_percentage' => 'required|numeric|min:0|max:100',
            'bank_name'                  => 'nullable|string|max:100',
            'bank_account_number'        => 'nullable|string|max:100',
            'bank_account_name'          => 'nullable|string|max:255',
            'status'                     => 'required|in:active,inactive',
            'notes'                      => 'nullable|string',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $affiliate->update($validated);

        return redirect()->route('admin.affiliates.index')
            ->with('success', "Data Agen B2B '{$affiliate->name}' berhasil diperbarui.");
    }

    /**
     * Fitur Edit Cepat (Quick Update) khusus untuk mengubah persentase komisi secara instan
     */
    public function quickUpdateRate(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'commission_rate_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $oldRate = $affiliate->commission_rate_percentage;
        $newRate = (float) $validated['commission_rate_percentage'];

        $affiliate->update([
            'commission_rate_percentage' => $newRate,
        ]);

        $message = "Persentase komisi '{$affiliate->name}' berhasil diubah dari {$oldRate}% menjadi {$newRate}%!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'rate'    => $newRate,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Proses pencairan / penarikan komisi (Payout) dari admin ke agen
     */
    public function payout(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric|min:1000',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $this->affiliateService->processPayout($affiliate, (float) $validated['amount'], [
                'reference_no' => $validated['reference_no'] ?? null,
                'notes'        => $validated['notes'] ?? null,
                'description'  => "Pencairan komisi manual oleh Admin ke rekening {$affiliate->bank_name} - {$affiliate->bank_account_number}",
            ]);

            return redirect()->back()
                ->with('success', "Pencairan komisi Rp " . number_format($validated['amount'], 0, ',', '.') . " untuk '{$affiliate->name}' berhasil dibukukan ke buku besar!");
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', "Gagal mencairkan komisi: " . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail buku besar (Ledger) dan riwayat mutasi agen
     */
    public function ledger(Affiliate $affiliate)
    {
        $ledgers = $affiliate->ledgers()
            ->with('transaction')
            ->paginate(25);

        return view('admin.affiliates.ledger', compact('affiliate', 'ledgers'));
    }

    /**
     * Hapus agen afiliasi (atau nonaktifkan jika memiliki transaksi terkait)
     */
    public function destroy(Affiliate $affiliate)
    {
        if ($affiliate->transactions()->count() > 0 || $affiliate->ledgers()->count() > 0) {
            $affiliate->update(['status' => 'inactive']);
            return redirect()->route('admin.affiliates.index')
                ->with('warning', "Agen '{$affiliate->name}' memiliki catatan transaksi/buku besar, sehingga statusnya diubah menjadi Nonaktif (Soft-disabled).");
        }

        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')
            ->with('success', "Agen '{$affiliate->name}' berhasil dihapus secara permanen.");
    }
}

