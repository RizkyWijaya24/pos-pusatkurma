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
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium">
                Saldo stok per produk di setiap lokasi
                @if(Auth::user()->isAdmin())
                <span class="ml-2 inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full">
                    ✏️ Klik angka stok untuk edit
                </span>
                @endif
            </p>
        </div>
        <div class="flex gap-3 flex-wrap">
            @if($pendingRequests > 0)
            <a href="{{ route('admin.stock-transfers.index') }}?status=requested"
               class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-amber-500/20 transition-colors animate-pulse shadow-sm">
                ⏳ {{ $pendingRequests }} Request Kasir
            </a>
            @endif
            <a href="{{ route('owner.stock-report.export', request()->query()) }}"
               class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition duration-150">
                📤 Export Excel
            </a>
            <a href="{{ route('owner.stock-report.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
               class="bg-rose-700 hover:bg-rose-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition duration-150">
                📄 Export PDF
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
            <p class="font-extrabold text-2xl tracking-tight {{ $loc->type === 'gudang' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400' }}" id="loc-total-{{ $loc->id }}">
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
            <p class="text-slate-400 dark:text-purple-400 text-xs font-semibold">
                🔴 Kritis (≤5) &nbsp; 🟡 Rendah (≤10) &nbsp; 🟢 Aman (&gt;10)
                @if(Auth::user()->isAdmin())
                &nbsp;|&nbsp; <span class="text-purple-500 dark:text-purple-400 font-bold">✏️ Klik angka untuk edit</span>
                @endif
            </p>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                    <tr class="border-b border-slate-100 dark:border-dp-700">
                        <th class="px-4 py-3 sticky left-0 bg-white dark:bg-dp-800 z-10 border-r border-slate-100 dark:border-dp-700">Produk</th>
                        <th class="px-3 py-3 w-20">Satuan</th>
                        @foreach($locations as $loc)
                        <th class="text-center px-3 py-3 w-28 whitespace-nowrap {{ $loc->type === 'gudang' ? 'text-blue-600 dark:text-blue-400' : '' }}">
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
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150 group" id="row-product-{{ $product->id }}">
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
                        <td class="px-1 py-2 text-center {{ $cellClass }} relative stock-cell"
                            id="cell-{{ $product->id }}-{{ $loc->id }}"
                            data-product-id="{{ $product->id }}"
                            data-location-id="{{ $loc->id }}"
                            data-product-name="{{ $product->name }}"
                            data-location-name="{{ $loc->name }}"
                            data-stock="{{ $s }}"
                            data-unit="{{ $product->price_unit }}">
                            {{-- Mode tampil --}}
                            <div class="stock-display flex items-center justify-center gap-1 py-1 @if(Auth::user()->isAdmin()) cursor-pointer hover:bg-purple-500/10 rounded-lg @endif"
                                 @if(Auth::user()->isAdmin()) onclick="startEdit({{ $product->id }}, {{ $loc->id }})" @endif>
                                <span class="stock-value">{{ $s > 0 ? $displayVal : '—' }}</span>
                                @if(Auth::user()->isAdmin())
                                <button
                                    class="edit-btn opacity-0 group-hover:opacity-100 transition-opacity duration-150 text-purple-400 hover:text-purple-600 dark:text-purple-500 dark:hover:text-purple-300 ml-0.5 flex-shrink-0"
                                    title="Edit stok">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            {{-- Mode edit (hanya admin) --}}
                            @if(Auth::user()->isAdmin())
                            <div class="stock-edit hidden">
                                <div class="flex flex-col items-center gap-1 py-0.5">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="stock-input w-20 text-center bg-white dark:bg-dp-900 border-2 border-purple-400 dark:border-purple-500 text-slate-800 dark:text-purple-100 rounded-lg px-2 py-1 text-xs font-bold focus:outline-none focus:border-purple-600 shadow-md"
                                        onkeydown="handleEditKey(event, {{ $product->id }}, {{ $loc->id }})"
                                    />
                                    <div class="flex gap-1">
                                        <button onclick="saveEdit({{ $product->id }}, {{ $loc->id }})"
                                            class="save-btn bg-emerald-600 hover:bg-emerald-700 text-white rounded-md px-2 py-0.5 text-[10px] font-bold transition-colors">✓</button>
                                        <button onclick="cancelEdit({{ $product->id }}, {{ $loc->id }})"
                                            class="bg-slate-200 hover:bg-slate-300 dark:bg-dp-700 dark:hover:bg-dp-600 text-slate-600 dark:text-purple-300 rounded-md px-2 py-0.5 text-[10px] font-bold transition-colors">✕</button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @php
                            $displayTotal = number_format($rowTotal, 2, ',', '.');
                            if ($product->price_unit === 'gram' && $rowTotal >= 1000) {
                                $displayTotal = number_format($rowTotal / 1000, 2, ',', '.') . ' kg';
                            }
                        @endphp
                        <td class="px-3 py-3 text-center text-slate-800 dark:text-purple-100 font-black" id="row-total-{{ $product->id }}">
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

@if(Auth::user()->isAdmin())
<style>
.stock-cell { min-width: 6.5rem; }
@keyframes flashSuccess {
    0%   { box-shadow: inset 0 0 0 2px rgb(34 197 94); background-color: rgb(240 253 244); }
    100% { box-shadow: none; background-color: transparent; }
}
@keyframes flashError {
    0%   { box-shadow: inset 0 0 0 2px rgb(239 68 68); background-color: rgb(254 242 242); }
    100% { box-shadow: none; background-color: transparent; }
}
.flash-success { animation: flashSuccess 1.4s ease-out forwards; }
.flash-error   { animation: flashError 1.4s ease-out forwards; }
</style>

<script>
const CSRF = '{{ csrf_token() }}';

function startEdit(productId, locationId) {
    const cell  = document.getElementById('cell-' + productId + '-' + locationId);
    const input = cell.querySelector('.stock-input');
    cell.querySelector('.stock-display').classList.add('hidden');
    cell.querySelector('.stock-edit').classList.remove('hidden');
    input.value = parseFloat(cell.dataset.stock) || 0;
    input.focus();
    input.select();
}

function handleEditKey(e, productId, locationId) {
    if (e.key === 'Enter')  { e.preventDefault(); saveEdit(productId, locationId); }
    if (e.key === 'Escape') { cancelEdit(productId, locationId); }
}

function cancelEdit(productId, locationId) {
    const cell = document.getElementById('cell-' + productId + '-' + locationId);
    cell.querySelector('.stock-display').classList.remove('hidden');
    cell.querySelector('.stock-edit').classList.add('hidden');
}

async function saveEdit(productId, locationId) {
    const cell     = document.getElementById('cell-' + productId + '-' + locationId);
    const input    = cell.querySelector('.stock-input');
    const saveBtn  = cell.querySelector('.save-btn');
    const newStock = parseFloat(input.value);

    if (isNaN(newStock) || newStock < 0) {
        showStockToast('Jumlah stok tidak valid!', 'error');
        input.focus();
        return;
    }

    saveBtn.disabled    = true;
    saveBtn.textContent = '⏳';

    try {
        const res  = await fetch('/admin/stock-adjust', {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                product_id:  productId,
                location_id: locationId,
                new_stock:   newStock,
                reason:      'Koreksi stok via laporan stok',
            }),
        });
        const data = await res.json();

        if (data.success) {
            cell.dataset.stock = newStock;
            updateCellDisplay(cell, newStock);
            updateRowTotal(productId);
            cancelEdit(productId, locationId);
            cell.classList.remove('flash-error', 'flash-success');
            void cell.offsetWidth;
            cell.classList.add('flash-success');
            setTimeout(() => cell.classList.remove('flash-success'), 1400);
            showStockToast('✅ ' + cell.dataset.productName + ' @ ' + cell.dataset.locationName + ' diperbarui → ' + newStock + ' ' + cell.dataset.unit, 'success');
        } else {
            showStockToast(data.message || 'Gagal menyimpan stok!', 'error');
            cell.classList.remove('flash-error', 'flash-success');
            void cell.offsetWidth;
            cell.classList.add('flash-error');
            setTimeout(() => cell.classList.remove('flash-error'), 1400);
        }
    } catch (err) {
        showStockToast('Terjadi kesalahan koneksi!', 'error');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = '✓';
    }
}

function updateCellDisplay(cell, newStock) {
    const unit      = cell.dataset.unit;
    const valueSpan = cell.querySelector('.stock-value');

    let display = '—';
    if (newStock > 0) {
        display = (unit === 'gram' && newStock >= 1000)
            ? (newStock / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg'
            : newStock.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }
    valueSpan.textContent = display;

    const removeList = [
        'text-slate-400','text-rose-700','text-amber-700','text-emerald-700',
        'dark:text-purple-505','dark:text-rose-400','dark:text-amber-400','dark:text-emerald-400',
        'bg-slate-50/50','dark:bg-dp-900/10','bg-rose-50','dark:bg-rose-950/20',
        'bg-amber-50','dark:bg-amber-950/20','font-extrabold','font-bold'
    ];
    removeList.forEach(c => cell.classList.remove(c));

    if (newStock <= 0) {
        cell.classList.add('text-slate-400','dark:text-purple-505','bg-slate-50/50','dark:bg-dp-900/10');
    } else if (newStock <= 5) {
        cell.classList.add('text-rose-700','dark:text-rose-400','font-extrabold','bg-rose-50','dark:bg-rose-950/20');
    } else if (newStock <= 10) {
        cell.classList.add('text-amber-700','dark:text-amber-400','font-extrabold','bg-amber-50','dark:bg-amber-950/20');
    } else {
        cell.classList.add('text-emerald-700','dark:text-emerald-400','font-bold');
    }
}

function updateRowTotal(productId) {
    const totalCell = document.getElementById('row-total-' + productId);
    if (!totalCell) return;
    let rowTotal = 0;
    document.querySelectorAll('[id^="cell-' + productId + '-"]').forEach(function(c) {
        rowTotal += parseFloat(c.dataset.stock) || 0;
    });
    const anyCell = document.querySelector('[id^="cell-' + productId + '-"]');
    const unit    = anyCell ? anyCell.dataset.unit : '';
    totalCell.textContent = (unit === 'gram' && rowTotal >= 1000)
        ? (rowTotal / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg'
        : rowTotal.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function showStockToast(msg, type) {
    if (window.showToast) { window.showToast(msg, type); return; }
    const el = document.createElement('div');
    el.className = 'fixed bottom-6 right-6 z-[9999] '
        + (type === 'success' ? 'bg-emerald-600' : 'bg-rose-600')
        + ' text-white px-5 py-3 rounded-2xl shadow-2xl text-sm font-bold max-w-sm';
    el.style.transition = 'opacity 0.4s';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function() {
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 400);
    }, 3500);
}
</script>
@endif

@endsection
