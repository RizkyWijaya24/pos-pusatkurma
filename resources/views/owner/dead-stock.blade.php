@extends('layouts.app')

@section('title', 'Diagnosa Barang Mati & Slow Moving')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100">
                    Diagnosa Barang Mati
                </h1>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40 whitespace-nowrap">
                    Periode {{ $days }} Hari
                </span>
            </div>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium leading-relaxed">
                Deteksi produk mengendap (dead stock), perputaran lambat, dan modal tertahan di gudang/cabang.
                <span class="inline-block text-xs text-slate-400 whitespace-nowrap ml-1">(Diperbarui: {{ $generatedAt }})</span>
            </p>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 shrink-0">
            <a href="{{ route('owner.dead-stock', array_merge(request()->query(), ['refresh' => 1])) }}"
               class="px-3.5 py-2.5 bg-white hover:bg-slate-50 dark:bg-dp-800 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 border border-slate-200 dark:border-dp-700 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Refresh Data</span>
            </a>
            <a href="{{ route('owner.dead-stock.export', request()->query()) }}"
               class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition whitespace-nowrap">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Excel</span>
            </a>
            <a href="{{ route('owner.dead-stock.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
               class="px-4 py-2.5 bg-rose-700 hover:bg-rose-800 text-white rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition whitespace-nowrap">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span>PDF</span>
            </a>
        </div>
    </div>

    {{-- 4 KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Card 1: Mati Total --}}
        <div class="bg-white dark:bg-dp-800 rounded-3xl p-5 border border-rose-100 dark:border-rose-900/30 shadow-md flex flex-col justify-between gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Mati Total (Dead Stock)</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </span>
            </div>
            <div>
                <div class="text-xl xl:text-2xl font-black text-slate-800 dark:text-white tracking-tight">{{ number_format($summary['dead_count']) }} SKU</div>
                <p class="text-xs font-bold text-rose-600 dark:text-rose-400 mt-0.5">
                    Modal: Rp {{ number_format($summary['dead_capital_total'], 0, ',', '.') }}
                </p>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">0 penjualan selama {{ $days }} hari (&gt;14 hari masuk)</p>
        </div>

        {{-- Card 2: Slow Moving --}}
        <div class="bg-white dark:bg-dp-800 rounded-3xl p-5 border border-amber-100 dark:border-amber-900/30 shadow-md flex flex-col justify-between gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Slow Moving</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div>
                <div class="text-xl xl:text-2xl font-black text-slate-800 dark:text-white tracking-tight">{{ number_format($summary['slow_count']) }} SKU</div>
                <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                    Modal: Rp {{ number_format($summary['slow_capital_total'], 0, ',', '.') }}
                </p>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Estimasi stok habis (DOI) &ge; 60 hari</p>
        </div>

        {{-- Card 3: Total Modal Tertahan --}}
        <div class="text-white rounded-3xl p-5 shadow-md flex flex-col justify-between gap-2"
             style="background: linear-gradient(135deg, #065f46 0%, #022c22 100%);">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-black uppercase tracking-wider text-emerald-200">Total Modal Tertahan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white/15 text-emerald-100 shrink-0">
                    {{ $summary['problem_capital_percent'] }}%
                </span>
            </div>
            <div>
                <div class="text-xl xl:text-2xl font-black text-white tracking-tight whitespace-nowrap">Rp {{ number_format($summary['problem_capital'], 0, ',', '.') }}</div>
                <p class="text-xs font-semibold text-emerald-200 mt-0.5">
                    Dari total Rp {{ number_format($summary['total_inventory_capital'], 0, ',', '.') }} modal stok
                </p>
            </div>
            <p class="text-[10px] text-emerald-200/80 font-medium">Uang yang mengendap di {{ number_format($summary['problem_count']) }} SKU bermasalah</p>
        </div>

        {{-- Card 4: Potensi Omset & Grace Period --}}
        <div class="bg-white dark:bg-dp-800 rounded-3xl p-5 border border-slate-100 dark:border-dp-700/50 shadow-md flex flex-col justify-between gap-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Potensi Omset Tertahan</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </span>
            </div>
            <div>
                <div class="text-xl xl:text-2xl font-black text-slate-800 dark:text-white tracking-tight whitespace-nowrap">Rp {{ number_format($summary['problem_potential_revenue'], 0, ',', '.') }}</div>
                <p class="text-xs font-bold text-sky-600 dark:text-sky-400 mt-0.5">
                    {{ number_format($summary['new_count']) }} SKU Stok Baru (&le;14h)
                </p>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Stok baru dikecualikan dari daftar barang mati</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form method="GET" action="{{ route('owner.dead-stock') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
            {{-- Periode --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Rentang Analisis</label>
                <select name="days" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2.5 text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="60" {{ $days === 60 ? 'selected' : '' }}>60 Hari Terakhir</option>
                    <option value="90" {{ $days === 90 ? 'selected' : '' }}>90 Hari Terakhir</option>
                    <option value="180" {{ $days === 180 ? 'selected' : '' }}>180 Hari Terakhir</option>
                </select>
            </div>

            {{-- Lokasi --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Lokasi / Cabang</label>
                <select name="location_id" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2.5 text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $locationId === $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Status Diagnosa</label>
                <select name="status" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2.5 text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="all_issues" {{ $status === 'all_issues' ? 'selected' : '' }}>Semua Bermasalah (Mati + Lambat)</option>
                    <option value="dead" {{ $status === 'dead' ? 'selected' : '' }}>Mati Total (Dead Stock)</option>
                    <option value="slow" {{ $status === 'slow' ? 'selected' : '' }}>Slow Moving Saja</option>
                    <option value="new" {{ $status === 'new' ? 'selected' : '' }}>Stok Baru (&le;14 Hari)</option>
                </select>
            </div>

            {{-- Kategori --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Kategori</label>
                <select name="category" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2.5 text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->name }}" {{ strcasecmp($category, $cat->name) === 0 ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Urutan --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Urutkan</label>
                <select name="sort" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2.5 text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="capital_desc" {{ $sort === 'capital_desc' ? 'selected' : '' }}>Modal Tertahan Terbesar</option>
                    <option value="idle_desc" {{ $sort === 'idle_desc' ? 'selected' : '' }}>Paling Lama Mengendap</option>
                    <option value="stock_desc" {{ $sort === 'stock_desc' ? 'selected' : '' }}>Stok Terbanyak</option>
                </select>
            </div>

            {{-- Search --}}
            <div>
                <label class="block text-[10px] font-black text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1.5">Cari Produk / SKU</label>
                <div class="flex gap-1.5">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama / SKU..."
                           class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                    <button type="submit" class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition shrink-0">
                        Cari
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-dp-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">Daftar Produk Hasil Diagnosa</h3>
                <p class="text-xs text-slate-400 font-medium">Menampilkan {{ $items->total() }} produk sesuai filter aktif</p>
            </div>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.stock-transfers.create') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <span>Buat Transfer Stok</span>
            </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700/50 text-left text-xs">
                <thead class="bg-slate-50 dark:bg-dp-900/60 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-5 py-3.5">Produk &amp; SKU</th>
                        <th class="px-4 py-3.5 text-right">Sisa Stok</th>
                        <th class="px-4 py-3.5 text-right">Modal Tertahan</th>
                        <th class="px-4 py-3.5 text-center">Terjual ({{ $days }}h)</th>
                        <th class="px-4 py-3.5 text-center">Mengendap</th>
                        <th class="px-4 py-3.5">Status Diagnosa</th>
                        <th class="px-5 py-3.5">Rekomendasi Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dp-700/30 text-slate-700 dark:text-purple-100 font-medium">
                    @forelse($items as $row)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-dp-700/20 transition">
                        <td class="px-5 py-3.5">
                            <div class="font-extrabold text-slate-800 dark:text-white text-sm">{{ $row['name'] }}</div>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px]">
                                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $row['sku'] }}</span>
                                <span class="text-slate-300 dark:text-dp-600">&bull;</span>
                                <span class="text-slate-500 dark:text-purple-300 capitalize">{{ $row['category'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="font-extrabold text-slate-800 dark:text-white">{{ $row['display_stock'] }}</div>
                            <div class="text-[10px] text-slate-400">HPP: Rp {{ number_format($row['cost_price'], 0, ',', '.') }}/{{ $row['price_unit'] }}</div>
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="font-black text-rose-700 dark:text-rose-400 text-sm">Rp {{ number_format($row['dead_capital'], 0, ',', '.') }}</div>
                            <div class="text-[10px] text-slate-400">Potensi: Rp {{ number_format($row['potential_rev'], 0, ',', '.') }}</div>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <div class="font-bold text-slate-800 dark:text-white">{{ $row['display_sold'] }}</div>
                            <div class="text-[10px] text-slate-400">
                                {{ $row['sale_count'] }}x trx
                                @if($row['doi_days'] !== null)
                                    &bull; DOI ~{{ number_format($row['doi_days']) }}h
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <span class="font-extrabold text-slate-800 dark:text-white">{{ $row['idle_days'] }} hari</span>
                            <div class="text-[10px] text-slate-400">Akhir: {{ $row['last_sold_at'] }}</div>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($row['status'] === 'dead')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800/40">
                                    Mati Total
                                </span>
                            @elseif($row['status'] === 'critical_slow')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-orange-100 text-orange-800 border border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800/40">
                                    Sangat Lambat
                                </span>
                            @elseif($row['status'] === 'slow')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/40">
                                    Slow Moving
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-sky-100 text-sky-800 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800/40">
                                    Stok Baru (&le;14h)
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 max-w-xs">
                            <p class="text-xs text-slate-600 dark:text-purple-200 leading-snug">{{ $row['recommendation'] }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center gap-2 text-slate-400">
                                <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-bold text-slate-600 dark:text-purple-200 text-sm">Tidak ada barang mati atau slow moving pada kriteria filter ini.</p>
                                <p class="text-xs text-slate-400">Seluruh produk berputar dengan sehat dalam rentang {{ $days }} hari.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-dp-700/50">
            {{ $items->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
