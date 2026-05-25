<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Riwayat Transaksi POS') }}
        </h2>
    </x-slot>

    <div class="flex flex-col gap-6 max-w-full overflow-hidden">
        
        <!-- Header description -->
        <div class="flex justify-between items-center gap-4">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Daftar Transaksi Kasir</h3>
                <p class="text-sm text-slate-400 font-medium mt-1">Seluruh riwayat pencatatan transaksi yang telah diselesaikan oleh Anda</p>
            </div>
            <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1.5 rounded-full">
                Total: {{ $transactions->total() }} Transaksi
            </span>
        </div>

        <!-- Read-Only Transaction History Table -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
            <div class="overflow-x-auto w-full max-w-full">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal & Waktu</th>
                            <th class="px-6 py-4">Kode Transaksi</th>
                            <th class="px-6 py-4">Ringkasan Item</th>
                            <th class="px-6 py-4">Metode Bayar</th>
                            <th class="px-6 py-4 text-right">Total Tagihan</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                        @forelse ($transactions as $trx)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    {{ $trx->created_at->translatedFormat('d M Y - H:i') }}
                                </td>
                                <td class="px-6 py-4 text-emerald-700 font-mono text-xs">
                                    {{ $trx->transaction_code }}
                                </td>
                                <td class="px-6 py-4 text-slate-700 text-xs">
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        @foreach(explode(', ', $trx->items_summary) as $itemStr)
                                            @if(trim($itemStr))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50/60 text-emerald-800 border border-emerald-100/50">
                                                    {{ trim($itemStr) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border
                                        {{ $trx->payment_method === 'Cash' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                        {{ $trx->payment_method === 'QRIS' ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
                                        {{ $trx->payment_method === 'Debit' ? 'bg-sky-50 text-sky-700 border-sky-100' : '' }}
                                    ">
                                        {{ $trx->payment_method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-emerald-700 font-extrabold">
                                    Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" 
                                            onclick="printReceipt({{ $trx->id }})"
                                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs border border-slate-200 transition duration-150 inline-flex items-center gap-1.5 shadow-sm active:scale-95">
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                        </svg>
                                        Cetak
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                    Belum ada catatan riwayat transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls (Laravel Styled) -->
            @if ($transactions->hasPages())
                <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function printReceipt(transactionId) {
            if (!transactionId) return;
            const iframeId = 'receipt-print-iframe';
            let iframe = document.getElementById(iframeId);
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = iframeId;
                iframe.style.position = 'fixed';
                iframe.style.right = '-1000px';
                iframe.style.bottom = '-1000px';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = 'none';
                document.body.appendChild(iframe);
            }
            
            iframe.onload = function() {
                setTimeout(() => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    } catch (e) {
                        console.error('Print failed:', e);
                    }
                }, 300);
            };
            
            iframe.src = '/kasir/transactions/' + transactionId + '/print';
        }
    </script>
</x-app-layout>
