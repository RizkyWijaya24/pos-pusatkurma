<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Riwayat Transaksi — Owner') }}
        </h2>
    </x-slot>

    <div x-data="{
        activeTab: '{{ request()->has('expense_page') ? 'expenses' : 'sales' }}'
    }" class="flex flex-col gap-6 max-w-full overflow-hidden">

        <!-- Header & Date Filter -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Semua Transaksi</h3>
                <p class="text-sm text-slate-400 font-medium mt-1">Laporan riwayat transaksi dari seluruh kasir (Read-Only)</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto shrink-0">
                <form method="GET" action="{{ route('owner.transactions.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <!-- Dropdown Filter Cabang -->
                    <select name="branch" 
                            class="text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-3.5 py-2.5 bg-slate-50 text-slate-700 font-medium">
                        <option value="">🌐 Semua Cabang</option>
                        @foreach($branches as $branchName)
                            @if($branchName)
                                <option value="{{ $branchName }}" {{ request('branch') === $branchName ? 'selected' : '' }}>
                                    📍 {{ $branchName }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <div class="flex items-center gap-2">
                        <input type="date" name="date" value="{{ request()->has('date') ? request('date') : \Carbon\Carbon::today()->toDateString() }}"
                            class="text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-3 py-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow transition duration-150 py-2.5">Filter</button>
                        @if(request()->has('date') || request('branch'))
                            <a href="{{ route('owner.transactions.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition duration-150 py-2.5">Reset</a>
                        @endif
                    </div>
                </form>

                <!-- Button Ekspor Excel -->
                <a href="{{ route('owner.transactions.export', ['date' => request('date'), 'branch' => request('branch')]) }}" 
                   class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center gap-1.5 py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Ekspor Excel</span>
                </a>

                <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1.5 rounded-full whitespace-nowrap" x-show="activeTab === 'sales'">
                    Total: {{ $transactions->total() }} Penjualan
                </span>
                <span class="bg-rose-100 text-rose-800 font-bold text-xs px-3 py-1.5 rounded-full whitespace-nowrap" x-show="activeTab === 'expenses'" style="display: none;">
                    Total: {{ $expenses->total() }} Pengeluaran
                </span>
            </div>
        </div>

        <!-- Laporan Omset & Profit Cards (Today, Weekly & Monthly) -->
        <!-- Row 1: Hari Ini -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Today Omset Card (Gold/Amber Gradient) -->
            <div style="background: linear-gradient(135deg, #92400e 0%, #78350f 60%, #451a03 100%);" class="text-white rounded-3xl p-6 shadow-xl border border-amber-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20 text-amber-300 pointer-events-none transform rotate-12">
                    <svg class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-300">☀ Omset {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</span>
                    <h3 class="text-3xl font-black mt-2 leading-none text-white">Rp {{ number_format($todayOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(120,53,15,0.6); border: 1px solid rgba(217,119,6,0.3);" class="text-amber-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $targetDate->translatedFormat('l, d F Y') }}
                    </span>
                </div>
            </div>

            <!-- Today Profit Card (Deep Violet/Indigo Gradient) -->
            <div style="background: linear-gradient(135deg, #4c1d95 0%, #3b0764 60%, #1e1b4b 100%);" class="text-white rounded-3xl p-6 shadow-xl border border-violet-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20 text-violet-300 pointer-events-none transform rotate-12">
                    <svg class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-300">⚡ Profit {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</span>
                    <h3 class="text-3xl font-black mt-2 leading-none text-white">Rp {{ number_format($todayProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(76,29,149,0.6); border: 1px solid rgba(167,139,250,0.3);" class="text-violet-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $todayOmset > 0 ? round(($todayProfit / $todayOmset) * 100) : 0 }}%
                    </span>
                </div>
        </div>

        {{-- Rincian Metode Pembayaran Tanggal Terpilih --}}
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-md">
            <div class="flex items-center gap-2 mb-4">
                <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-xl">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0016.5 13.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.75A1.125 1.125 0 002.625 11.625v1.5a1.125 1.125 0 001.125 1.125z" />
                    </svg>
                </span>
                <div>
                    <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Rincian Metode Pembayaran {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</h4>
                    <p class="text-xs text-slate-400 font-medium">Pembagian omset berdasarkan metode pembayaran untuk tanggal {{ $targetDate->translatedFormat('d M Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Tunai --}}
                <div class="bg-emerald-50/40 border border-emerald-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-1">💵 Bayar Tunai</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayCash, 0, ',', '.') }}
                    </p>
                </div>
                {{-- Non-Tunai QRIS --}}
                <div class="bg-teal-50/40 border border-teal-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider mb-1">📱 Non-Tunai (QRIS)</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayQris, 0, ',', '.') }}
                    </p>
                </div>
                {{-- Non-Tunai Debit/TF --}}
                <div class="bg-sky-50/40 border border-sky-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider mb-1">💳 Non-Tunai (Debit/TF)</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayDebit, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Row 2: Pekan & Bulan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Weekly Omset Card (Dark Forest Green Gradient) -->
            <div style="background: linear-gradient(135deg, #1b4332 0%, #081c15 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-emerald-800/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Omset Pekan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($weeklyOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(27,67,50,0.6); border: 1px solid rgba(52,211,153,0.3);" class="text-emerald-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $startOfWeek->translatedFormat('d M') }} - {{ $endOfWeek->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>

            <!-- Weekly Profit Card (Vibrant Jade/Emerald Green Gradient) -->
            <div style="background: linear-gradient(135deg, #2d6a4f 0%, #0d3b25 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-emerald-600/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-200">Profit Pekan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(13,59,37,0.6); border: 1px solid rgba(52,211,153,0.3);" class="text-emerald-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100) : 0 }}%
                    </span>
                </div>
            </div>

            <!-- Monthly Omset Card (Midnight Slate Gradient) -->
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-slate-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Omset Bulan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($monthlyOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(30,41,59,0.6); border: 1px solid rgba(148,163,184,0.3);" class="text-slate-200 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $targetDate->translatedFormat('F Y') }}
                    </span>
                </div>
            </div>

            <!-- Monthly Profit Card (Teal/Emerald Gradient) -->
            <div style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-teal-600/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-200">Profit Bulan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(15,118,110,0.6); border: 1px solid rgba(45,212,191,0.3);" class="text-teal-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $monthlyOmset > 0 ? round(($monthlyProfit / $monthlyOmset) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Transactions & Expenses Card -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
            
            <!-- Navigation Tabs -->
            <div class="flex border-b border-slate-100 bg-slate-50/50 p-2 gap-2">
                <button type="button"
                        @click="activeTab = 'sales'"
                        :class="activeTab === 'sales' 
                            ? 'bg-white text-emerald-800 shadow-sm border-slate-200/60 font-bold' 
                            : 'text-slate-400 hover:text-slate-600 font-semibold'"
                        class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs uppercase tracking-wider transition border border-transparent">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                    <span>Riwayat Penjualan</span>
                </button>
                <button type="button"
                        @click="activeTab = 'expenses'"
                        :class="activeTab === 'expenses' 
                            ? 'bg-white text-rose-800 shadow-sm border-slate-200/60 font-bold' 
                            : 'text-slate-400 hover:text-slate-600 font-semibold'"
                        class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs uppercase tracking-wider transition border border-transparent">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>Riwayat Pengeluaran</span>
                </button>
            </div>

            <!-- TAB 1: SALES TRANSACTIONS -->
            <div x-show="activeTab === 'sales'" class="w-full">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal & Waktu</th>
                                <th class="px-5 py-4">Kode Transaksi</th>
                                <th class="px-5 py-4">Kasir</th>
                                <th class="px-5 py-4">Cabang</th>
                                <th class="px-5 py-4">Ringkasan Item</th>
                                <th class="px-5 py-4">Metode</th>
                                <th class="px-5 py-4 text-right">Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($transactions as $trx)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                        {{ $trx->created_at->translatedFormat('d M Y - H:i') }}
                                    </td>
                                    <td class="px-5 py-4 text-emerald-700 font-mono text-xs">
                                        {{ $trx->transaction_code }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-extrabold shrink-0">
                                                {{ strtoupper(substr($trx->cashier->name ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="text-slate-700">{{ $trx->cashier->name ?? 'N/A' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200">
                                            {{ $trx->branch ?? 'Pusat Cianjur' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs">
                                        <div class="flex flex-wrap gap-1 max-w-[280px]">
                                            @foreach(explode(', ', $trx->items_summary) as $itemStr)
                                                @if(trim($itemStr))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50/60 text-emerald-800 border border-emerald-100/50">
                                                        {{ trim($itemStr) }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border
                                            {{ $trx->payment_method === 'Cash' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                            {{ $trx->payment_method === 'QRIS' ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
                                            {{ $trx->payment_method === 'Debit' ? 'bg-sky-50 text-sky-700 border-sky-100' : '' }}
                                        ">
                                            {{ $trx->payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-emerald-700 font-extrabold whitespace-nowrap">
                                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat transaksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sales Pagination -->
                @if ($transactions->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            <!-- TAB 2: OPERATIONAL EXPENSES -->
            <div x-show="activeTab === 'expenses'" class="w-full" style="display: none;">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal & Waktu</th>
                                <th class="px-5 py-4">Kasir</th>
                                <th class="px-5 py-4">Cabang</th>
                                <th class="px-5 py-4">Kategori</th>
                                <th class="px-5 py-4">Keterangan</th>
                                <th class="px-5 py-4 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($expenses as $exp)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                        {{ $exp->created_at->translatedFormat('d M Y - H:i') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-800 flex items-center justify-center text-[10px] font-extrabold shrink-0">
                                                {{ strtoupper(substr($exp->cashier->name ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="text-slate-700">{{ $exp->cashier->name ?? 'N/A' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200">
                                            {{ $exp->branch ?? 'Pusat Cianjur' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-50 text-rose-700 border border-rose-100">
                                            {{ $exp->category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs truncate max-w-[250px]" title="{{ $exp->description }}">
                                        {{ $exp->description }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-rose-700 font-extrabold whitespace-nowrap">
                                        - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat pengeluaran.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Expenses Pagination -->
                @if ($expenses->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $expenses->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
