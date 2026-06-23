@extends('layouts.app')

@section('title', 'Log Mutasi Stok')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-3">
                <span class="text-3xl">📜</span> Log Mutasi Stok
            </h1>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium">Histori setiap perubahan stok di semua lokasi</p>
        </div>
        <a href="{{ route('owner.stock-report') }}" class="text-slate-500 dark:text-purple-400 hover:text-slate-700 dark:hover:text-white text-sm font-bold flex items-center gap-1 transition duration-150">
            ← Kembali ke Laporan Stok
        </a>
    </div>

    {{-- Filter --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Produk</label>
                <select name="product_id" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-inner">
                    <option value="">Semua Produk</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Lokasi</label>
                <select name="location_id" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-inner">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Tipe</label>
                <select name="type" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-inner">
                    <option value="">Semua Tipe</option>
                    @foreach(['initial' => '🏭 Stok Awal', 'sale' => '🛒 Penjualan', 'transfer_in' => '📦 Transfer Masuk', 'transfer_out' => '🚚 Transfer Keluar', 'adjustment' => '✏️ Koreksi Manual', 'return' => '↩️ Retur'] as $val => $label)
                    <option value="{{ $val }}" {{ request('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-inner">
            </div>
            <button type="submit" class="bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150">🔍 Filter</button>
            @if(request()->hasAny(['product_id','location_id','type','date']))
            <a href="{{ route('owner.stock-adjustment-log') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 text-xs font-bold rounded-xl transition duration-150">✕ Reset</a>
            @endif
        </form>
    </div>

    {{-- Log Table --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden">
        @if($logs->isEmpty())
        <div class="text-center py-16">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-slate-400 dark:text-purple-400 text-lg font-semibold">Belum ada log mutasi stok</p>
        </div>
        @else
        <div class="overflow-x-auto w-full max-w-full">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                    <tr class="border-b border-slate-100 dark:border-dp-700">
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="text-center px-4 py-3">Sebelum</th>
                        <th class="text-center px-4 py-3">Perubahan</th>
                        <th class="text-center px-4 py-3">Sesudah</th>
                        <th class="px-4 py-3">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
                    @foreach($logs as $log)
                    @php
                        $typeIcons = [
                            'initial'      => ['icon' => '🏭', 'color' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-900/40 dark:text-slate-400 dark:border-slate-800/30'],
                            'sale'         => ['icon' => '🛒', 'color' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30'],
                            'transfer_in'  => ['icon' => '📦', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30'],
                            'transfer_out' => ['icon' => '🚚', 'color' => 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30'],
                            'adjustment'   => ['icon' => '✏️', 'color' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30'],
                            'return'       => ['icon' => '↩️', 'color' => 'bg-purple-50 text-purple-700 border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30'],
                        ];
                        $typeInfo = $typeIcons[$log->type] ?? ['icon' => '📋', 'color' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-900/40 dark:text-slate-400 dark:border-slate-800/30'];
                        $changePositive = $log->quantity_change >= 0;
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150">
                        <td class="px-4 py-3 text-slate-500 dark:text-purple-400 text-xs whitespace-nowrap">
                            <div>{{ $log->created_at->translatedFormat('d M Y') }}</div>
                            <div class="text-slate-400 dark:text-purple-505 text-[10px] font-semibold mt-0.5">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $typeInfo['color'] }}">
                                {{ $typeInfo['icon'] }} {{ $log->typeLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-900 dark:text-purple-100 font-bold">{{ $log->product->name }}</p>
                            <p class="text-slate-400 dark:text-purple-400 font-mono text-[10px] mt-0.5">{{ $log->product->sku }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-800 dark:text-purple-100 font-bold text-sm">{{ $log->location->name }}</p>
                            <p class="text-slate-400 dark:text-purple-400 text-[10px] uppercase font-bold tracking-wider mt-0.5">{{ $log->location->type }}</p>
                        </td>
                        @php
                            $unit = $log->product->price_unit;
                            
                            $beforeVal = number_format($log->quantity_before, 2, ',', '.');
                            if ($unit === 'gram' && abs($log->quantity_before) >= 1000) {
                                $beforeVal = number_format($log->quantity_before / 1000, 2, ',', '.') . ' kg';
                            }
                            
                            $changeVal = ($changePositive ? '+' : '') . number_format($log->quantity_change, 2, ',', '.');
                            if ($unit === 'gram' && abs($log->quantity_change) >= 1000) {
                                $changeVal = ($changePositive ? '+' : '') . number_format($log->quantity_change / 1000, 2, ',', '.') . ' kg';
                            }
                            
                            $afterVal = number_format($log->quantity_after, 2, ',', '.');
                            if ($unit === 'gram' && abs($log->quantity_after) >= 1000) {
                                $afterVal = number_format($log->quantity_after / 1000, 2, ',', '.') . ' kg';
                            }
                        @endphp
                        <td class="px-4 py-3 text-center text-slate-500 dark:text-purple-400 text-sm">
                            {{ $beforeVal }}
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-sm {{ $changePositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                            {{ $changeVal }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-800 dark:text-purple-100 font-black text-sm">
                            {{ $afterVal }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-800 dark:text-purple-100 font-bold text-xs">{{ $log->creator->name }}</p>
                            <p class="text-slate-400 dark:text-purple-400 text-[9px] uppercase font-bold tracking-wider mt-0.5">{{ $log->creator->role }}</p>
                            @if($log->notes)
                            <p class="text-slate-400 dark:text-purple-500 text-[10px] italic mt-1.5 leading-normal max-w-[200px] break-words" title="{{ $log->notes }}">{{ Str::limit($log->notes, 40) }}</p>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="bg-slate-50/50 dark:bg-dp-900/50 border-t border-slate-100 dark:border-dp-700 px-5 py-4">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
