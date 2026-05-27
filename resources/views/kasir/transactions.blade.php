<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Riwayat Transaksi POS') }}
        </h2>
    </x-slot>

    <div class="flex flex-col gap-6 max-w-full overflow-hidden">

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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Omset hari ini --}}
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 mb-1">Omset Hari Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">
                        Rp {{ number_format((int)$todayStats->omset, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-emerald-200 mt-1">{{ $now->translatedFormat('l, d F Y') }}</p>
                </div>
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
                                            {{ $trx->payment_method === 'Cash'  ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                            {{ $trx->payment_method === 'QRIS'  ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
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
                <a href="{{ route('kasir.transactions.export', ['type' => 'weekly']) }}"
                   class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-emerald-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Ekspor Excel
                </a>
            </div>

            {{-- Weekly summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 mb-1">Total Omset Minggu Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">Rp {{ number_format($weeklyOmset, 0, ',', '.') }}</p>
                    <p class="text-xs text-emerald-200 mt-1">{{ $weeklyCount }} transaksi</p>
                </div>
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Profit Bersih Minggu Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</p>
                    @php $wMargin = $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100, 1) : 0; @endphp
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ $wMargin }}%</p>
                </div>
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
                                <th class="px-6 py-4 text-right">Profit Bersih</th>
                                <th class="px-6 py-4 text-right">Margin</th>
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
                                    <td class="px-6 py-4 text-right font-extrabold {{ $day['profit'] > 0 ? 'text-teal-600' : 'text-slate-300' }}">
                                        {{ $day['profit'] > 0 ? 'Rp ' . number_format($day['profit'], 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs font-bold text-slate-500">
                                        {{ $day['omset'] > 0 ? $dayMargin . '%' : '—' }}
                                    </td>
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
                                <td class="px-6 py-4 text-right font-extrabold text-teal-600 text-base">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-500 text-sm">
                                    @php $totalWMargin = $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100, 1) : 0; @endphp
                                    {{ $totalWMargin }}%
                                </td>
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
                <a href="{{ route('kasir.transactions.export', ['type' => 'monthly']) }}"
                   class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md shadow-emerald-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Ekspor Excel
                </a>
            </div>

            {{-- Monthly summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-teal-200/50">
                    <p class="text-xs font-bold uppercase tracking-wider text-teal-100 mb-1">Total Omset Bulan Ini</p>
                    <p class="text-2xl font-extrabold tracking-tight">Rp {{ number_format($monthlyOmset, 0, ',', '.') }}</p>
                    <p class="text-xs text-teal-200 mt-1">{{ $monthlyCount }} transaksi</p>
                </div>
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Profit Bersih Bulan Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</p>
                    @php $mMargin = $monthlyOmset > 0 ? round(($monthlyProfit / $monthlyOmset) * 100, 1) : 0; @endphp
                    <p class="text-xs text-slate-400 mt-1">Margin: {{ $mMargin }}%</p>
                </div>
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
                                <th class="px-6 py-4 text-right">Profit Bersih</th>
                                <th class="px-6 py-4 text-right">Margin</th>
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
                                    <td class="px-6 py-4 text-right font-extrabold {{ $w['profit'] > 0 ? 'text-teal-600' : 'text-slate-300' }}">
                                        {{ $w['profit'] > 0 ? 'Rp ' . number_format($w['profit'], 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs font-bold text-slate-500">
                                        {{ $w['omset'] > 0 ? $wMarginPct . '%' : '—' }}
                                    </td>
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
                                <td class="px-6 py-4 text-right font-extrabold text-teal-600 text-base">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-500 text-sm">{{ $mMargin }}%</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-200 text-emerald-800">{{ $monthlyCount }} Trx</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @endif
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
    </script>
</x-app-layout>
