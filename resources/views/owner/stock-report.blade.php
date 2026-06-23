@extends('layouts.app')

@section('title', 'Laporan Stok Per Cabang')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-3">
                <span class="text-3xl">📊</span> Laporan Stok Antar Cabang
            </h1>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium">Saldo stok per produk di setiap lokasi</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            @if($pendingRequests > 0)
            <a href="{{ route('admin.stock-transfers.index') }}?status=requested"
               class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-amber-500/20 transition-colors animate-pulse shadow-sm">
                ⏳ {{ $pendingRequests }} Request Kasir
            </a>
            @endif
            <a href="{{ route('owner.stock-report.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition duration-150">
                📤 Export Excel
            </a>
        </div>
    </div>

    {{-- Alert Stok Kritis --}}
    @if(!empty($lowStockAlerts))
    <div class="bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 rounded-3xl p-5 shadow-sm">
        <h3 class="text-rose-700 dark:text-rose-400 font-extrabold mb-3 flex items-center gap-2 text-sm uppercase tracking-wider">
            <span>🚨</span> Peringatan Stok Kritis (≤ 10)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lowStockAlerts as $locName => $alerts)
            <div class="bg-rose-500/5 dark:bg-rose-950/40 border border-rose-100/50 dark:border-rose-900/20 rounded-2xl p-4 shadow-inner">
                <p class="text-rose-800 dark:text-rose-300 font-bold text-xs mb-2 uppercase tracking-wide">📍 {{ $locName }}</p>
                @foreach($alerts as $alert)
                @php
                    $displayAlertStock = $alert['stock'];
                    $displayAlertUnit = $alert['unit'];
                    if ($alert['unit'] === 'gram' && $alert['stock'] >= 1000) {
                        $displayAlertStock = $alert['stock'] / 1000;
                        $displayAlertUnit = 'kg';
                    }
                @endphp
                <div class="flex justify-between text-xs text-rose-700/80 dark:text-rose-300/80 py-1 border-b border-rose-100/30 dark:border-rose-900/10 last:border-0">
                    <span class="truncate mr-2 font-medium text-left">{{ $alert['product'] }}</span>
                    <span class="font-black text-rose-600 dark:text-rose-400 whitespace-nowrap">{{ number_format($displayAlertStock, 2, ',', '.') }} {{ $displayAlertUnit }}</span>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Kategori</label>
                <select name="category" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-inner">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->name }}" {{ request('category') == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Cari Produk</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama produk..."
                       class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-inner w-48">
            </div>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150">🔍 Filter</button>
            @if(request()->hasAny(['category','search']))
            <a href="{{ route('owner.stock-report') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 text-xs font-bold rounded-xl transition duration-150">✕ Reset</a>
            @endif
        </form>
    </div>

    {{-- Total Per Lokasi Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($locations as $loc)
        @php $total = $locationTotals[$loc->id] ?? 0; @endphp
        <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 text-center shadow-md hover:scale-[1.02] transition duration-150">
            <div class="text-2xl mb-2">{{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}</div>
            <p class="text-slate-500 dark:text-purple-400 text-xs font-bold uppercase tracking-wider mb-1">{{ $loc->name }}</p>
            <p class="font-extrabold text-2xl tracking-tight {{ $loc->type === 'gudang' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ number_format($total, 0, ',', '.') }}
            </p>
            <p class="text-slate-400 dark:text-purple-505 text-[10px] uppercase font-bold tracking-widest mt-1">unit total</p>
        </div>
        @endforeach
    </div>

    {{-- Matrix Stok --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-dp-700 flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-slate-800 dark:text-purple-100 font-extrabold text-md">Matriks Stok Produk × Lokasi</h2>
            <p class="text-slate-400 dark:text-purple-400 text-xs font-semibold">🔴 Kritis (≤5) &nbsp; 🟡 Rendah (≤10) &nbsp; 🟢 Aman (&gt;10)</p>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                    <tr class="border-b border-slate-100 dark:border-dp-700">
                        <th class="px-4 py-3 sticky left-0 bg-white dark:bg-dp-800 z-10 border-r border-slate-100 dark:border-dp-700">Produk</th>
                        <th class="px-3 py-3 w-20">Satuan</th>
                        @foreach($locations as $loc)
                        <th class="text-center px-3 py-3 w-24 whitespace-nowrap {{ $loc->type === 'gudang' ? 'text-blue-600 dark:text-blue-400' : '' }}">
                            {{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}
                            <br>{{ Str::limit($loc->name, 12) }}
                        </th>
                        @endforeach
                        <th class="text-center px-3 py-3 w-24">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
                    @foreach($products as $product)
                    @php
                        $stockByLoc = $product->productStocks->keyBy('location_id');
                        $rowTotal   = $product->productStocks->sum('stock');
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150 group">
                        <td class="px-4 py-3 sticky left-0 bg-white dark:bg-dp-800 border-r border-slate-100 dark:border-dp-700 z-10 group-hover:bg-slate-50 dark:group-hover:bg-dp-700">
                            <p class="text-slate-900 dark:text-purple-100 font-bold leading-tight">{{ $product->name }}</p>
                            <p class="text-slate-400 dark:text-purple-400 font-mono text-[10px] mt-0.5">{{ $product->sku }}</p>
                        </td>
                        <td class="px-3 py-3 text-slate-500 dark:text-purple-400 text-xs font-semibold uppercase tracking-wider">{{ $product->price_unit }}</td>
                        @foreach($locations as $loc)
                        @php
                            $s = isset($stockByLoc[$loc->id]) ? (float) $stockByLoc[$loc->id]->stock : 0;
                            $cellClass = $s <= 0 ? 'text-slate-400 dark:text-purple-505 bg-slate-50/50 dark:bg-dp-900/10' :
                                        ($s <= 5  ? 'text-rose-700 dark:text-rose-400 font-extrabold bg-rose-50 dark:bg-rose-950/20' :
                                        ($s <= 10 ? 'text-amber-700 dark:text-amber-400 font-extrabold bg-amber-50 dark:bg-amber-950/20' :
                                                    'text-emerald-700 dark:text-emerald-400 font-bold'));
                            $displayVal = number_format($s, 2, ',', '.');
                            if ($product->price_unit === 'gram' && $s >= 1000) {
                                $displayVal = number_format($s / 1000, 2, ',', '.') . ' kg';
                            }
                        @endphp
                        <td class="px-3 py-3 text-center {{ $cellClass }}">
                            {{ $s > 0 ? $displayVal : '—' }}
                        </td>
                        @endforeach
                        @php
                            $displayTotal = number_format($rowTotal, 2, ',', '.');
                            if ($product->price_unit === 'gram' && $rowTotal >= 1000) {
                                $displayTotal = number_format($rowTotal / 1000, 2, ',', '.') . ' kg';
                            }
                        @endphp
                        <td class="px-3 py-3 text-center text-slate-800 dark:text-purple-100 font-black">
                            {{ $displayTotal }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="bg-slate-50/50 dark:bg-dp-900/50 border-t border-slate-100 dark:border-dp-700 px-5 py-4">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    {{-- Link ke log mutasi --}}
    <div class="mt-4 text-center">
        <a href="{{ route('owner.stock-adjustment-log') }}"
           class="text-purple-700 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 text-sm font-bold underline underline-offset-2 transition duration-150">
            📜 Lihat Histori Log Mutasi Stok →
        </a>
    </div>
</div>
@endsection
