<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.affiliates.index') }}" 
                   class="p-2 rounded-xl bg-slate-100 dark:bg-dp-700 hover:bg-slate-200 text-slate-600 dark:text-purple-200 transition">
                    &larr; Kembali
                </a>
                <div>
                    <h2 class="font-extrabold text-xl text-slate-800 dark:text-purple-100 leading-tight">
                        Buku Besar Komisi: {{ $affiliate->name }} ({{ $affiliate->code }})
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-purple-300">
                        {{ $affiliate->agency_name ?: 'Mandiri' }} &bull; Rate Aktif: <strong class="text-emerald-600">{{ $affiliate->commission_rate_percentage }}%</strong>
                    </p>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Saldo Komisi Tersedia</span>
                <span class="text-xl font-black text-amber-600 dark:text-amber-400">
                    Rp {{ number_format($affiliate->komisi_tersedia, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-dp-800 rounded-2xl border border-slate-200 dark:border-dp-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-dp-700 font-extrabold text-sm text-slate-800 dark:text-purple-100">
                Mutasi Transaksi & Pencairan (Ledger Audit Trail)
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-dp-900/60 text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-dp-700">
                        <tr>
                            <th class="py-3 px-4">Tanggal & Waktu</th>
                            <th class="py-3 px-4">Tipe Mutasi</th>
                            <th class="py-3 px-4">Deskripsi / No Transaksi</th>
                            <th class="py-3 px-4 text-right">Omset Belanja</th>
                            <th class="py-3 px-4 text-center">Rate Snapshot</th>
                            <th class="py-3 px-4 text-right">Kredit (Komisi Masuk)</th>
                            <th class="py-3 px-4 text-right">Debit (Pencairan)</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dp-700/60 text-slate-700 dark:text-purple-200">
                        @forelse ($ledgers as $ledger)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-dp-700/30">
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    {{ $ledger->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($ledger->type === 'credit')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            + KREDIT
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                            - DEBIT
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-semibold">
                                    <div>{{ $ledger->description }}</div>
                                    @if ($ledger->reference_no)
                                        <div class="text-[10px] text-slate-400 font-mono">Ref: {{ $ledger->reference_no }}</div>
                                    @endif
                                    @if ($ledger->notes)
                                        <div class="text-[10px] text-slate-500 italic mt-0.5">{{ $ledger->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium">
                                    {{ $ledger->transaction_amount > 0 ? 'Rp ' . number_format($ledger->transaction_amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-emerald-600">
                                    {{ $ledger->commission_rate_percentage > 0 ? $ledger->commission_rate_percentage . '%' : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $ledger->type === 'credit' ? '+ Rp ' . number_format($ledger->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                    {{ $ledger->type === 'debit' ? '- Rp ' . number_format($ledger->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($ledger->status === 'paid' || $ledger->status === 'approved')
                                        <span class="text-[10px] font-bold text-emerald-600">✓ Selesai</span>
                                    @elseif($ledger->status === 'pending')
                                        <span class="text-[10px] font-bold text-amber-600 animate-pulse">⏳ Menunggu</span>
                                    @else
                                        <span class="text-[10px] font-bold text-rose-600">✕ Batal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    Belum ada mutasi buku besar tercatat untuk agen ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ledgers->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-dp-700">
                    {{ $ledgers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

