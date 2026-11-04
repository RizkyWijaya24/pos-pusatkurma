<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Riwayat Transaksi POS') }}
        </h2>
    </x-slot>

    <div x-data="kasirTransactionsState()" class="flex flex-col gap-6 max-w-full overflow-hidden">

        {{-- ======================================================= --}}
        {{-- TAB NAVIGATION --}}
        {{-- ======================================================= --}}
        <div class="flex items-center gap-2 bg-white border border-slate-100 rounded-2xl p-1.5 shadow-sm w-fit">
            <a href="{{ route('kasir.transactions.index', ['tab' => 'daily']) }}"
               class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200
                      {{ $activeTab === 'daily' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                Harian
            </a>
            <a href="{{ route('kasir.transactions.index', ['tab' => 'weekly']) }}"
               class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200
                      {{ $activeTab === 'weekly' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
                Mingguan
            </a>
            <a href="{{ route('kasir.transactions.index', ['tab' => 'monthly']) }}"
               class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200
                      {{ $activeTab === 'monthly' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                Bulanan
            </a>
        </div>

        {{-- ======================================================= --}}
        {{-- TAB: HARIAN --}}
        {{-- ======================================================= --}}
        @if ($activeTab === 'daily')

            {{-- Summary cards --}}
            <div class="grid grid-cols-1 {{ auth()->user()->isKasir() ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }} gap-4">
                {{-- Omset hari ini --}}
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 mb-1">Omset Hari Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">
                        Rp {{ number_format((int)$todayStats->omset, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-emerald-200 mt-1">{{ $now->translatedFormat('l, d F Y') }}</p>
                </div>
                @if(!auth()->user()->isKasir())
                {{-- Profit hari ini --}}
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Profit Bersih Hari Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">
                        Rp {{ number_format((int)$todayStats->profit, 0, ',', '.') }}
                    </p>
                    @php
                        $marginPct = $todayStats->omset > 0 ? round(($todayStats->profit / $todayStats->omset) * 100, 1) : 0;
                    @endphp
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ $marginPct }}%</p>
                </div>
                @endif
                {{-- Jumlah transaksi --}}
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Transaksi Hari Ini</p>
                    <p class="text-2xl font-extrabold text-slate-700 tracking-tight">
                        {{ number_format((int)$todayStats->count) }}
                        <span class="text-base font-semibold text-slate-400">Trx</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Semua yang Anda catat hari ini</p>
                </div>
            </div>
 
            {{-- Rincian Metode Pembayaran Hari Ini --}}
            <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-md">
                <div class="flex items-center gap-2 mb-4">
                    <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-xl">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0016.5 13.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.75A1.125 1.125 0 002.625 11.625v1.5a1.125 1.125 0 001.125 1.125z" />
                        </svg>
                    </span>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Rincian Metode Pembayaran Hari Ini</h4>
                        <p class="text-xs text-slate-400 font-medium">Pembagian omset hari ini berdasarkan metode pembayaran</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Tunai --}}
                    <div class="bg-emerald-50/40 border border-emerald-100 rounded-2xl p-4 flex flex-col justify-between">
                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-1">💵 Bayar Tunai</span>
                        <p class="text-xl font-black text-slate-800 tracking-tight">
                            Rp {{ number_format((int)$todayStats->cash_total, 0, ',', '.') }}
                        </p>
                    </div>
                    {{-- Non-Tunai QRIS --}}
                    <div class="bg-teal-50/40 border border-teal-100 rounded-2xl p-4 flex flex-col justify-between">
                        <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider mb-1">📱 Non-Tunai (QRIS)</span>
                        <p class="text-xl font-black text-slate-800 tracking-tight">
                            Rp {{ number_format((int)$todayStats->qris_total, 0, ',', '.') }}
                        </p>
                    </div>
                    {{-- Non-Tunai Debit/TF --}}
                    <div class="bg-sky-50/40 border border-sky-100 rounded-2xl p-4 flex flex-col justify-between">
                        <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider mb-1">💳 Non-Tunai (Debit/TF)</span>
                        <p class="text-xl font-black text-slate-800 tracking-tight">
                            Rp {{ number_format((int)$todayStats->debit_total, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
 
            {{-- Header + jumlah --}}
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Daftar Transaksi Hari Ini</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Riwayat pencatatan transaksi yang telah Anda selesaikan hari ini</p>
                </div>
                <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1.5 rounded-full">
                    Total: {{ $transactions->total() }} Transaksi
                </span>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4">Tanggal &amp; Waktu</th>
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
                                        <div class="flex flex-col gap-1.5 max-w-sm py-1">
                                            @forelse($trx->parsed_items as $item)
                                                <div class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-emerald-50/50 border border-slate-100 text-xs transition duration-150">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        <span class="font-bold text-slate-800 truncate" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200/50 whitespace-nowrap">
                                                            {{ $item['display_qty'] }}
                                                        </span>
                                                        @if($item['unit_price'] > 0)
                                                            <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">
                                                                @ Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <span class="text-xs text-slate-400 italic">Tidak ada rincian item</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border
                                            {{ $trx->payment_method === 'Cash'  ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                            {{ $trx->payment_method === 'QRIS'  ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
                                            {{ $trx->payment_method === 'Debit' ? 'bg-sky-50 text-sky-700 border-sky-100' : '' }}
                                            {{ str_starts_with($trx->payment_method, 'SPLIT') ? 'bg-violet-50 text-violet-700 border-violet-100' : '' }}
                                        ">
                                            {{ $trx->payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-emerald-700 font-extrabold whitespace-nowrap">
                                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $trxData = [
                                                'id' => $trx->id,
                                                'transaction_code' => $trx->transaction_code,
                                                'created_at_formatted' => $trx->created_at->translatedFormat('d M Y - H:i'),
                                                'payment_method' => $trx->payment_method,
                                                'payment_status' => $trx->payment_status ?? 'paid',
                                                'branch' => $trx->branch ?? 'Pusat Cianjur',
                                                'cashier_name' => $trx->cashier->name ?? 'Kasir',
                                                'customer_name' => $trx->customer_name ?? '-',
                                                'customer_phone' => $trx->customer_phone ?? '-',
                                                'discount' => (float)$trx->discount,
                                                'total_price' => (float)$trx->total_price,
                                                'items_summary' => $trx->items_summary,
                                                'parsed_items' => $trx->parsed_items,
                                            ];
                                        @endphp
                                        <div class="flex items-center justify-center gap-1.5">
                                            {{-- Detail Modal Button --}}
                                            <button type="button"
                                                    @click="openDetail(@js($trxData))"
                                                    class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl font-bold text-xs border border-slate-200 transition duration-150 inline-flex items-center gap-1 shadow-sm active:scale-95"
                                                    title="Lihat Detail Transaksi">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Detail
                                            </button>

                                            {{-- Direct Bluetooth Thermal Print Button --}}
                                            <button type="button"
                                                    @click="printDirectBluetooth(@js($trxData))"
                                                    class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl font-bold text-xs border border-blue-200 transition duration-150 inline-flex items-center gap-1 shadow-sm active:scale-95"
                                                    title="Cetak Langsung via Bluetooth Thermal">
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75v16.5l6-4.5-6-4.5 6-4.5-6-4.5z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15.75l-6-4.5m6-4.5l-6 4.5"/>
                                                </svg>
                                                Bluetooth
                                            </button>

                                            {{-- Browser / Iframe Print Button --}}
                                            <button type="button"
                                                    @click="printReceipt({{ $trx->id }})"
                                                    class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs border border-slate-200 transition duration-150 inline-flex items-center gap-1 shadow-sm active:scale-95"
                                                    title="Cetak Struk (Dialog Browser)">
                                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                                </svg>
                                                Struk
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat transaksi hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $transactions->appends(['tab' => 'daily'])->links() }}
                    </div>
                @endif
            </div>

        {{-- ======================================================= --}}
        {{-- TAB: MINGGUAN --}}
        {{-- ======================================================= --}}
        @elseif ($activeTab === 'weekly')

            {{-- Header + Export --}}
            <div class="flex justify-between items-start gap-4 flex-wrap">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Ringkasan Penjualan Mingguan</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">
                        Periode: <span class="text-slate-600 font-semibold">{{ $startOfWeek->translatedFormat('d M Y') }} &ndash; {{ $endOfWeek->translatedFormat('d M Y') }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('kasir.transactions.export', ['type' => 'weekly']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-emerald-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Ekspor Excel
                    </a>
                    <a href="{{ route('kasir.transactions.export', ['type' => 'weekly', 'format' => 'pdf']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 bg-rose-500 hover:bg-rose-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-rose-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Ekspor PDF
                    </a>
                </div>
            </div>

            {{-- Weekly summary cards --}}
            <div class="grid grid-cols-1 {{ auth()->user()->isKasir() ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }} gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 mb-1">Total Omset Minggu Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">Rp {{ number_format($weeklyOmset, 0, ',', '.') }}</p>
                    <p class="text-xs text-emerald-200 mt-1">{{ $weeklyCount }} transaksi</p>
                </div>
                @if(!auth()->user()->isKasir())
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Profit Bersih Minggu Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</p>
                    @php $wMargin = $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100, 1) : 0; @endphp
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ $wMargin }}%</p>
                </div>
                @endif
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Rata-rata per Hari</p>
                    @php $daysWithTrx = count(array_filter($weeklyDays, fn($d) => $d['count'] > 0)); $avgDaily = $daysWithTrx > 0 ? (int)round($weeklyOmset / $daysWithTrx) : 0; @endphp
                    <p class="text-2xl font-extrabold text-slate-700 tracking-tight">Rp {{ number_format($avgDaily, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Dari {{ $daysWithTrx }} hari aktif</p>
                </div>
            </div>

            {{-- Weekly table --}}
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4 text-center w-12">No</th>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Hari</th>
                                <th class="px-6 py-4 text-right">Total Omset</th>
                                @if(!auth()->user()->isKasir())
                                <th class="px-6 py-4 text-right">Profit Bersih</th>
                                <th class="px-6 py-4 text-right">Margin</th>
                                @endif
                                <th class="px-6 py-4 text-center">Jml Trx</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($weeklyDays as $dateStr => $day)
                                @php
                                    $isToday  = $dateStr === $now->format('Y-m-d');
                                    $dayMargin = $day['omset'] > 0 ? round(($day['profit'] / $day['omset']) * 100, 1) : 0;
                                @endphp
                                <tr class="{{ $isToday ? 'bg-emerald-50/60' : 'hover:bg-slate-50/50' }} transition duration-150">
                                    <td class="px-6 py-4 text-center text-slate-400 text-xs font-semibold">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-slate-700 text-sm">{{ $day['sub_label'] }}</span>
                                        @if($isToday)
                                            <span class="ml-2 text-[10px] font-extrabold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Hari Ini</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-sm font-semibold">{{ $day['label'] }}</td>
                                    <td class="px-6 py-4 text-right font-extrabold {{ $day['omset'] > 0 ? 'text-emerald-700' : 'text-slate-300' }}">
                                        {{ $day['omset'] > 0 ? 'Rp ' . number_format($day['omset'], 0, ',', '.') : '—' }}
                                    </td>
                                    @if(!auth()->user()->isKasir())
                                    <td class="px-6 py-4 text-right font-extrabold {{ $day['profit'] > 0 ? 'text-teal-600' : 'text-slate-300' }}">
                                        {{ $day['profit'] > 0 ? 'Rp ' . number_format($day['profit'], 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs font-bold text-slate-500">
                                        {{ $day['omset'] > 0 ? $dayMargin . '%' : '—' }}
                                    </td>
                                    @endif
                                    <td class="px-6 py-4 text-center">
                                        @if ($day['count'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">{{ $day['count'] }} Trx</span>
                                        @else
                                            <span class="text-slate-300 text-xs font-semibold">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        {{-- Grand total footer --}}
                        <tfoot class="bg-emerald-50 border-t-2 border-emerald-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 font-extrabold text-emerald-800 text-sm uppercase tracking-wide">Grand Total</td>
                                <td class="px-6 py-4 text-right font-extrabold text-emerald-700 text-base">Rp {{ number_format($weeklyOmset, 0, ',', '.') }}</td>
                                @if(!auth()->user()->isKasir())
                                <td class="px-6 py-4 text-right font-extrabold text-teal-600 text-base">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-500 text-sm">
                                    @php $totalWMargin = $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100, 1) : 0; @endphp
                                    {{ $totalWMargin }}%
                                </td>
                                @endif
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-200 text-emerald-800">{{ $weeklyCount }} Trx</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        {{-- ======================================================= --}}
        {{-- TAB: BULANAN --}}
        {{-- ======================================================= --}}
        @elseif ($activeTab === 'monthly')

            {{-- Header + Export --}}
            <div class="flex justify-between items-start gap-4 flex-wrap">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Ringkasan Penjualan Bulanan</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">
                        Periode: <span class="text-slate-600 font-semibold">{{ $startOfMonth->translatedFormat('F Y') }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('kasir.transactions.export', ['type' => 'monthly']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-emerald-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Ekspor Excel
                    </a>
                    <a href="{{ route('kasir.transactions.export', ['type' => 'monthly', 'format' => 'pdf']) }}"
                       class="flex items-center gap-2 px-4 py-2.5 bg-rose-500 hover:bg-rose-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-rose-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Ekspor PDF
                    </a>
                </div>
            </div>

            {{-- Monthly summary cards --}}
            <div class="grid grid-cols-1 {{ auth()->user()->isKasir() ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }} gap-4">
                <div class="bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-teal-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-teal-100 mb-1">Total Omset Bulan Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">Rp {{ number_format($monthlyOmset, 0, ',', '.') }}</p>
                    <p class="text-xs text-teal-200 mt-1">{{ $monthlyCount }} transaksi</p>
                </div>
                @if(!auth()->user()->isKasir())
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Profit Bersih Bulan Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</p>
                    @php $mMargin = $monthlyOmset > 0 ? round(($monthlyProfit / $monthlyOmset) * 100, 1) : 0; @endphp
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ $mMargin }}%</p>
                </div>
                @endif
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Rata-rata per Minggu</p>
                    @php $weeksWithTrx = count(array_filter($monthlyWeeks, fn($w) => $w['count'] > 0)); $avgWeekly = $weeksWithTrx > 0 ? (int)round($monthlyOmset / $weeksWithTrx) : 0; @endphp
                    <p class="text-2xl font-extrabold text-slate-700 tracking-tight">Rp {{ number_format($avgWeekly, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Dari {{ $weeksWithTrx }} minggu aktif</p>
                </div>
            </div>

            {{-- Monthly table --}}
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4 text-center w-12">No</th>
                                <th class="px-6 py-4">Periode</th>
                                <th class="px-6 py-4">Rentang Tanggal</th>
                                <th class="px-6 py-4 text-right">Total Omset</th>
                                @if(!auth()->user()->isKasir())
                                <th class="px-6 py-4 text-right">Profit Bersih</th>
                                <th class="px-6 py-4 text-right">Margin</th>
                                @endif
                                <th class="px-6 py-4 text-center">Jml Trx</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($monthlyWeeks as $wNum => $w)
                                @php $wMarginPct = $w['omset'] > 0 ? round(($w['profit'] / $w['omset']) * 100, 1) : 0; @endphp
                                <tr class="{{ $loop->even ? 'bg-slate-50/40' : '' }} hover:bg-emerald-50/30 transition duration-150">
                                    <td class="px-6 py-4 text-center text-slate-400 text-xs font-semibold">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 font-extrabold text-slate-700">{{ $w['label'] }}</td>
                                    <td class="px-6 py-4 text-slate-500 font-semibold">{{ $w['sub_label'] }}</td>
                                    <td class="px-6 py-4 text-right font-extrabold {{ $w['omset'] > 0 ? 'text-emerald-700' : 'text-slate-300' }}">
                                        {{ $w['omset'] > 0 ? 'Rp ' . number_format($w['omset'], 0, ',', '.') : '—' }}
                                    </td>
                                    @if(!auth()->user()->isKasir())
                                    <td class="px-6 py-4 text-right font-extrabold {{ $w['profit'] > 0 ? 'text-teal-600' : 'text-slate-300' }}">
                                        {{ $w['profit'] > 0 ? 'Rp ' . number_format($w['profit'], 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs font-bold text-slate-500">
                                        {{ $w['omset'] > 0 ? $wMarginPct . '%' : '—' }}
                                    </td>
                                    @endif
                                    <td class="px-6 py-4 text-center">
                                        @if ($w['count'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">{{ $w['count'] }} Trx</span>
                                        @else
                                            <span class="text-slate-300 text-xs font-semibold">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-emerald-50 border-t-2 border-emerald-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 font-extrabold text-emerald-800 text-sm uppercase tracking-wide">Grand Total</td>
                                <td class="px-6 py-4 text-right font-extrabold text-emerald-700 text-base">Rp {{ number_format($monthlyOmset, 0, ',', '.') }}</td>
                                @if(!auth()->user()->isKasir())
                                <td class="px-6 py-4 text-right font-extrabold text-teal-600 text-base">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-500 text-sm">{{ $mMargin }}%</td>
                                @endif
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-200 text-emerald-800">{{ $monthlyCount }} Trx</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @endif

        {{-- ======================================================= --}}
        {{-- MODAL DETAIL TRANSAKSI --}}
        {{-- ======================================================= --}}
        <div x-show="showDetailModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                 @click="closeDetail()"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transition-all transform"
                     @click.stop>
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100/60">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800">Detail Transaksi</h3>
                                <p class="text-xs font-mono font-bold text-emerald-700" x-text="selectedTrx?.transaction_code"></p>
                            </div>
                        </div>
                        <button type="button"
                                @click="closeDetail()"
                                class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Meta Info Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50/70 border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Tanggal & Waktu</span>
                                <span class="font-bold text-slate-700" x-text="selectedTrx?.created_at_formatted || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Kasir</span>
                                <span class="font-bold text-slate-700" x-text="selectedTrx?.cashier_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Cabang</span>
                                <span class="font-bold text-slate-700" x-text="selectedTrx?.branch || 'Pusat Cianjur'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Metode Bayar</span>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800" x-text="selectedTrx?.payment_method || 'Cash'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Status</span>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-extrabold uppercase"
                                      :class="selectedTrx?.payment_status === 'cancelled' ? 'bg-slate-100 text-slate-700 border border-slate-300' : ((selectedTrx?.payment_status === 'paid' || !selectedTrx?.payment_status) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800')"
                                      x-text="selectedTrx?.payment_status === 'cancelled' ? 'BATAL' : ((selectedTrx?.payment_status === 'paid' || !selectedTrx?.payment_status) ? 'LUNAS' : 'TEMPO')"></span>
                            </div>
                            <div x-show="selectedTrx?.customer_name && selectedTrx?.customer_name !== '-'">
                                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Pelanggan</span>
                                <span class="font-bold text-slate-700" x-text="selectedTrx?.customer_name"></span>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Daftar Produk / Barang</h4>
                            <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50/80 font-bold text-slate-600 border-b border-slate-100">
                                        <tr>
                                            <th class="px-4 py-2.5">Produk</th>
                                            <th class="px-3 py-2.5 text-center">Jumlah</th>
                                            <th class="px-3 py-2.5 text-right">Harga</th>
                                            <th class="px-4 py-2.5 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(item, idx) in (selectedTrx?.parsed_items || [])" :key="idx">
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="px-4 py-3 font-bold text-slate-800" x-text="item.name"></td>
                                                <td class="px-3 py-3 text-center">
                                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-extrabold rounded-lg text-[11px]" x-text="item.display_qty"></span>
                                                </td>
                                                <td class="px-3 py-3 text-right text-slate-500 font-semibold" x-text="formatRupiah(item.unit_price)"></td>
                                                <td class="px-4 py-3 text-right font-extrabold text-slate-700" x-text="formatRupiah(item.total_price)"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!selectedTrx?.parsed_items || selectedTrx.parsed_items.length === 0">
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-slate-400 italic">
                                                    <span x-text="selectedTrx?.items_summary || 'Tidak ada rincian item.'"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal</span>
                                <span class="font-bold" x-text="formatRupiah((selectedTrx?.total_price || 0) + (selectedTrx?.discount || 0))"></span>
                            </div>
                            <template x-if="selectedTrx?.discount > 0">
                                <div class="flex justify-between text-rose-600">
                                    <span>Diskon</span>
                                    <span class="font-bold" x-text="'-' + formatRupiah(selectedTrx?.discount)"></span>
                                </div>
                            </template>
                            <div class="pt-2 border-t border-slate-200/80 flex justify-between items-center">
                                <span class="font-extrabold text-sm text-slate-800">Total Pembayaran</span>
                                <span class="font-black text-lg text-emerald-700" x-text="formatRupiah(selectedTrx?.total_price)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        <button type="button"
                                @click="closeDetail()"
                                class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl border border-slate-200 transition">
                            Tutup
                        </button>
                        <div class="flex items-center gap-2">
                            <!-- Direct Bluetooth -->
                            <button type="button"
                                    @click="printDirectBluetooth(selectedTrx)"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-200 transition active:scale-95 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75v16.5l6-4.5-6-4.5 6-4.5-6-4.5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15.75l-6-4.5m6-4.5l-6 4.5"/>
                                </svg>
                                Cetak Bluetooth
                            </button>
                            <!-- Regular Browser Print -->
                            <button type="button"
                                    @click="printReceipt(selectedTrx?.id)"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-200 transition active:scale-95 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                </svg>
                                Cetak Struk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
                    try { iframe.contentWindow.focus(); iframe.contentWindow.print(); }
                    catch (e) { console.error('Print failed:', e); }
                }, 300);
            };
            iframe.src = '/kasir/transactions/' + transactionId + '/print';
        }

        async function printDirectBluetooth(transaction) {
            if (!transaction) return;

            if (!navigator.bluetooth) {
                const msg = "Browser ini belum mendukung Web Bluetooth. Gunakan browser Google Chrome / Microsoft Edge di PC atau Chrome Android.";
                if (window.showToast) {
                    window.showToast(msg, "error");
                } else {
                    alert(msg);
                }
                return;
            }

            try {
                if (window.showToast) {
                    window.showToast("Mencari printer Bluetooth...", "info");
                }

                // 1. Scan for BLE Bluetooth printers
                const device = await navigator.bluetooth.requestDevice({
                    filters: [
                        { namePrefix: 'Printer' },
                        { namePrefix: 'MTP' },
                        { namePrefix: 'PT-' },
                        { namePrefix: 'POS' },
                        { namePrefix: 'RPP' },
                        { namePrefix: 'RP' },
                        { namePrefix: 'MPT' },
                        { namePrefix: 'InnerPrinter' },
                        { namePrefix: 'Bluetooth' }
                    ],
                    optionalServices: [
                        '000018f0-0000-1000-8000-00805f9b34fb', // Standard BLE printer service UUID
                        '0000e914-0000-1000-8000-00805f9b34fb', // Custom BLE printer service UUID
                        '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent
                        '0000ffe0-0000-1000-8000-00805f9b34fb'  // HM-10 / common BLE
                    ]
                });

                // 2. Connect GATT
                const server = await device.gatt.connect();

                // 3. Find Primary Service
                let service;
                const candidateUuids = [
                    '000018f0-0000-1000-8000-00805f9b34fb',
                    '0000e914-0000-1000-8000-00805f9b34fb',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                    '0000ffe0-0000-1000-8000-00805f9b34fb'
                ];

                for (const uuid of candidateUuids) {
                    try {
                        service = await server.getPrimaryService(uuid);
                        if (service) break;
                    } catch (e) {}
                }

                if (!service) {
                    const services = await server.getPrimaryServices();
                    if (services && services.length > 0) {
                        service = services[0];
                    } else {
                        throw new Error("Layanan printer Bluetooth tidak ditemukan!");
                    }
                }

                // 4. Get Characteristic
                const characteristics = await service.getCharacteristics();
                const writeCharacteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);
                if (!writeCharacteristic) {
                    throw new Error("Karakteristik penulisan data printer tidak ditemukan!");
                }

                // 5. Build raw ESC/POS payload (32 columns standard for 58mm)
                const formatRupiahRaw = (num) => 'Rp ' + Math.round(num || 0).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                const pad2Col = (left, right, width = 32) => {
                    left = String(left || '');
                    right = String(right || '');
                    const spaces = width - left.length - right.length;
                    if (spaces >= 0) {
                        return left + ' '.repeat(spaces) + right + '\n';
                    } else {
                        return left.substring(0, Math.max(0, width - right.length - 1)) + ' ' + right + '\n';
                    }
                };

                const ESC_INIT = '\x1b\x40';
                const ESC_ALIGN_CENTER = '\x1b\x61\x01';
                const ESC_ALIGN_LEFT = '\x1b\x61\x00';
                const ESC_BOLD_ON = '\x1b\x45\x01';
                const ESC_BOLD_OFF = '\x1b\x45\x00';
                const ESC_DOUBLE_SIZE = '\x1b\x21\x30';
                const ESC_NORMAL_SIZE = '\x1b\x21\x00';

                let escpos = '';
                escpos += ESC_INIT;
                escpos += ESC_ALIGN_CENTER;
                escpos += ESC_BOLD_ON + ESC_DOUBLE_SIZE + 'PUSAT KURMA CIANJUR\n' + ESC_NORMAL_SIZE;
                escpos += ESC_BOLD_ON + 'Al karim\n' + ESC_BOLD_OFF;
                escpos += 'Cianjur, Jawa Barat\n';
                escpos += '================================\n';

                escpos += ESC_ALIGN_LEFT;
                escpos += `No. Struk : ${transaction.transaction_code || '-'}\n`;
                escpos += `Waktu     : ${transaction.created_at_formatted || '-'}\n`;
                if (transaction.cashier_name) {
                    escpos += `Kasir     : ${transaction.cashier_name}\n`;
                }
                if (transaction.branch) {
                    escpos += `Cabang    : ${transaction.branch}\n`;
                }
                if (transaction.customer_name && transaction.customer_name !== '-') {
                    escpos += `Pelanggan : ${transaction.customer_name}\n`;
                }
                escpos += `Metode    : ${(transaction.payment_method || 'CASH').toUpperCase()}\n`;
                escpos += '--------------------------------\n';

                // Items
                const parsedItems = transaction.parsed_items && transaction.parsed_items.length > 0
                    ? transaction.parsed_items
                    : null;

                if (parsedItems) {
                    parsedItems.forEach(it => {
                        escpos += ESC_BOLD_ON + (it.name || '-') + '\n' + ESC_BOLD_OFF;
                        const qtyText = `  ${it.display_qty || it.qty} x ${formatRupiahRaw(it.unit_price)}`;
                        const subText = formatRupiahRaw(it.total_price);
                        escpos += pad2Col(qtyText, subText, 32);
                    });
                } else if (transaction.items_summary) {
                    const rawParts = transaction.items_summary.split(', ');
                    rawParts.forEach(part => {
                        escpos += ESC_BOLD_ON + part + '\n' + ESC_BOLD_OFF;
                    });
                }
                escpos += '--------------------------------\n';

                // Financial totals
                const discount = Number(transaction.discount || 0);
                const total = Number(transaction.total_price || 0);
                const subtotal = total + discount;

                escpos += pad2Col('Subtotal', formatRupiahRaw(subtotal), 32);
                if (discount > 0) {
                    escpos += pad2Col('Diskon', '-' + formatRupiahRaw(discount), 32);
                }
                escpos += ESC_BOLD_ON;
                escpos += pad2Col('TOTAL', formatRupiahRaw(total), 32);
                escpos += ESC_BOLD_OFF;
                escpos += '================================\n';

                escpos += ESC_ALIGN_CENTER;
                escpos += '*** TERIMA KASIH ***\n';
                escpos += 'Terima kasih atas kunjungan Anda\n';
                escpos += 'Barang yang dibeli tidak dapat\n';
                escpos += 'ditukar / dikembalikan\n\n\n\n';

                // 6. Encode and write in chunks of 20 bytes
                const encoder = new TextEncoder();
                const dataBytes = encoder.encode(escpos);
                const chunkSize = 20;

                for (let i = 0; i < dataBytes.length; i += chunkSize) {
                    const chunk = dataBytes.slice(i, i + chunkSize);
                    await writeCharacteristic.writeValue(chunk);
                    await new Promise(r => setTimeout(r, 25));
                }

                if (window.showToast) {
                    window.showToast("Cetak struk via Bluetooth berhasil!", "success");
                }
            } catch (error) {
                console.error("Direct Bluetooth printing failed:", error);
                if (window.showToast) {
                    window.showToast("Cetak Bluetooth gagal: " + error.message, "error");
                } else {
                    alert("Cetak Bluetooth gagal: " + error.message);
                }
            }
        }

        function kasirTransactionsState() {
            return {
                showDetailModal: false,
                selectedTrx: null,

                openDetail(trx) {
                    this.selectedTrx = trx;
                    this.showDetailModal = true;
                },

                closeDetail() {
                    this.showDetailModal = false;
                    this.selectedTrx = null;
                },

                formatRupiah(num) {
                    if (num === null || num === undefined) return 'Rp 0';
                    return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                },

                printReceipt(id) {
                    printReceipt(id);
                },

                printDirectBluetooth(trx) {
                    printDirectBluetooth(trx);
                }
            };
        }
    </script>
</x-app-layout>
