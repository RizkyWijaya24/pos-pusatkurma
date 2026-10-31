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

    {{-- Filter (Live Search & Kategori) --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form id="filter-form" onsubmit="event.preventDefault();" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Kategori</label>
                <select id="category-filter" name="category" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-inner">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->name }}" {{ request('category') == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative flex-1 min-w-[260px] max-w-md">
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Cari Produk (Live Search)</label>
                <div class="relative">
                    <input type="text" id="live-search-input" name="search" value="{{ request('search') }}" autocomplete="off" placeholder="Ketik nama produk atau SKU..."
                           class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl pl-10 pr-9 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-inner">
                    <span class="absolute left-3.5 top-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <button type="button" id="clear-search-btn" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 hidden text-sm font-bold" title="Hapus pencarian">✕</button>
                    <span id="search-spinner" class="absolute right-3 top-3 hidden">
                        <svg class="animate-spin w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </div>
            </div>
            <button type="button" id="reset-filter-btn" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-dp-700 dark:hover:bg-dp-600 text-slate-600 dark:text-purple-300 text-xs font-bold rounded-xl transition duration-150">
                ✕ Reset
            </button>
            <div class="ml-auto self-center text-xs font-bold text-slate-500 dark:text-purple-300" id="search-result-count">
                Total: {{ number_format($products->total(), 0, ',', '.') }} Produk
            </div>
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

    {{-- Matrix Stok (Dapat di-refresh secara realtime via Live Search) --}}
    <div id="stock-matrix-container" class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden transition-opacity duration-200 relative">
        @include('owner.partials.stock-matrix-table')
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

{{-- ── LIVE SEARCH & AJAX FILTER SCRIPT ── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('live-search-input');
    const categorySelect = document.getElementById('category-filter');
    const clearBtn = document.getElementById('clear-search-btn');
    const spinner = document.getElementById('search-spinner');
    const resetBtn = document.getElementById('reset-filter-btn');
    const container = document.getElementById('stock-matrix-container');
    const resultCount = document.getElementById('search-result-count');

    let debounceTimer = null;
    let abortController = null;

    function toggleClearBtn() {
        if (!clearBtn || !searchInput) return;
        if (searchInput.value.trim().length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }

    toggleClearBtn();

    async function fetchFilteredStock(url = null) {
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        const search = searchInput ? searchInput.value.trim() : '';
        const category = categorySelect ? categorySelect.value : '';

        let targetUrl;
        if (url) {
            try {
                const parsed = new URL(url, window.location.origin);
                targetUrl = new URL(parsed.pathname + parsed.search, window.location.origin);
            } catch (e) {
                targetUrl = new URL(url, window.location.origin);
            }
        } else {
            targetUrl = new URL(window.location.pathname, window.location.origin);
            if (search) targetUrl.searchParams.set('search', search);
            if (category) targetUrl.searchParams.set('category', category);
        }

        // Show spinner & dim container slightly
        if (spinner) spinner.classList.remove('hidden');
        if (container) container.classList.add('opacity-60');

        try {
            const response = await fetch(targetUrl.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: abortController.signal,
            });

            if (!response.ok) throw new Error('Network response not ok');
            const data = await response.json();

            if (container && data.html) {
                container.innerHTML = data.html;
            }

            if (resultCount && typeof data.total !== 'undefined') {
                resultCount.textContent = `Total: ${new Intl.NumberFormat('id-ID').format(data.total)} Produk`;
            }

            // Update browser URL without reloading
            window.history.replaceState({}, '', targetUrl.toString());

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Error fetching live stock data:', err);
                if (window.showToast) {
                    window.showToast('Gagal memuat data live search: ' + err.message, 'error');
                }
            }
        } finally {
            if (spinner) spinner.classList.add('hidden');
            if (container) container.classList.remove('opacity-60');
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            toggleClearBtn();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchFilteredStock();
            }, 250);
        });

        // Android soft keyboard autocomplete / composition trigger
        searchInput.addEventListener('change', function () {
            toggleClearBtn();
            clearTimeout(debounceTimer);
            fetchFilteredStock();
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                searchInput.blur(); // Tutup virtual keyboard di Android
                fetchFilteredStock();
            }
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            fetchFilteredStock();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            toggleClearBtn();
            searchInput.focus();
            fetchFilteredStock();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (categorySelect) categorySelect.value = '';
            toggleClearBtn();
            fetchFilteredStock();
        });
    }

    // Intercept pagination clicks inside container
    if (container) {
        container.addEventListener('click', function (e) {
            const link = e.target.closest('.stock-pagination a');
            if (link && link.href) {
                e.preventDefault();
                fetchFilteredStock(link.href);
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
});
</script>

@endsection
