@extends('layouts.app')

@section('title', 'Riwayat Repack & Pecah Stok')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-3">
                <span class="text-3xl">🔄</span> Riwayat Repack & Pecah Stok
            </h1>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium">Log pemrosesan pemecahan unit produk bulk ke eceran/repack</p>
        </div>
        <div>
            <a href="{{ route('admin.repack.create') }}"
               class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition duration-150">
                <span>+ Proses Repack Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Lokasi</label>
                <select name="location_id" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
            </div>
            <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150">
                🔍 Filter
            </button>
            @if(request()->hasAny(['location_id','date']))
            <a href="{{ route('admin.repack.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 text-xs font-bold rounded-xl transition duration-150">
                ✕ Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Repack Logs Table --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden">
        @if($repacks->isEmpty())
        <div class="text-center py-16">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-slate-400 dark:text-purple-400 text-lg font-semibold">Belum ada riwayat repack</p>
            <a href="{{ route('admin.repack.create') }}" class="mt-4 inline-block text-emerald-600 hover:text-emerald-500 font-bold underline">
                Mulai proses repack pertama
            </a>
        </div>
        @else
        <div class="overflow-x-auto w-full max-w-full">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                    <tr class="border-b border-slate-100 dark:border-dp-700">
                        <th class="px-5 py-4">Kode Repack</th>
                        <th class="px-4 py-4">Lokasi</th>
                        <th class="px-4 py-4">Produk Asal (Bahan)</th>
                        <th class="px-4 py-4">Produk Hasil (Yield)</th>
                        <th class="px-4 py-4">Oleh</th>
                        <th class="px-4 py-4">Tanggal</th>
                        <th class="px-5 py-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
                    @foreach($repacks as $repack)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150">
                        <td class="px-5 py-4">
                            <span class="text-emerald-800 dark:text-emerald-300 font-mono font-bold text-xs bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg border border-emerald-100 dark:border-emerald-900/30 shadow-inner">
                                {{ $repack->repack_code }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-700 dark:text-purple-200">
                            <span class="bg-slate-100 text-slate-700 dark:bg-dp-700 dark:text-purple-300 px-2.5 py-1 rounded-lg border border-slate-200/50 dark:border-dp-600/40">
                                {{ $repack->location->name }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-slate-800 dark:text-purple-100 text-xs">
                                <div class="font-bold">{{ $repack->sourceProduct->name }}</div>
                                <div class="text-rose-600 font-extrabold mt-1">
                                    -{{ number_format($repack->source_quantity, 2, ',', '.') }} {{ $repack->sourceProduct->price_unit }}
                                </div>
                                <div class="text-slate-400 text-[10px] mt-0.5">Modal: Rp {{ number_format($repack->sourceProduct->cost_price, 0, ',', '.') }}/{{ $repack->sourceProduct->price_unit }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-2">
                                @foreach($repack->items as $item)
                                <div class="p-2 bg-slate-50 dark:bg-dp-900 border border-slate-100 dark:border-dp-700/50 rounded-xl text-xs">
                                    <div class="font-bold text-slate-800 dark:text-purple-200">{{ $item->targetProduct->name }}</div>
                                    <div class="flex flex-wrap gap-x-2 text-[10px] text-slate-500 mt-1">
                                        <span class="text-emerald-600 font-extrabold">+{{ number_format($item->target_quantity, 2, ',', '.') }} {{ $item->targetProduct->price_unit }}</span>
                                        <span>|</span>
                                        <span>B. Kemasan: Rp {{ number_format($item->additional_packaging_cost, 0, ',', '.') }}</span>
                                        <span>|</span>
                                        <span class="text-emerald-700 font-bold">HPP Baru: Rp {{ number_format($item->calculated_cost_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-4 text-xs">
                            <div class="text-slate-800 dark:text-purple-100 font-semibold">{{ $repack->creator->name }}</div>
                            <div class="text-slate-400 dark:text-purple-400 text-[9px] uppercase font-bold tracking-wider mt-0.5">{{ $repack->creator->role }}</div>
                        </td>
                        <td class="px-4 py-4 text-slate-500 dark:text-purple-400 text-xs">
                            <div>{{ $repack->created_at->translatedFormat('d M Y') }}</div>
                            <div class="text-slate-400 dark:text-purple-500 text-[10px] font-semibold mt-0.5">{{ $repack->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-500 dark:text-purple-400 text-xs font-normal max-w-xs truncate" title="{{ $repack->notes }}">
                            {{ $repack->notes ?: '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($repacks->hasPages())
        <div class="bg-slate-50/50 dark:bg-dp-900/50 border-t border-slate-100 dark:border-dp-700 px-5 py-4">
            {{ $repacks->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
