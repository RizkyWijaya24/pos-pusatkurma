@extends('layouts.app')

@section('title', 'Buat Transfer Stok Baru')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.stock-transfers.index') }}"
           class="text-slate-500 dark:text-purple-400 hover:text-slate-700 dark:hover:text-white transition-colors text-sm font-bold">
            ← Kembali
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-2">
                📦 Buat Transfer Stok
            </h1>
            <p class="text-slate-400 dark:text-purple-400 text-sm font-medium">Pindahkan stok dari satu lokasi ke lokasi lain</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Form Panel (kiri) --}}
        <div class="lg:col-span-8 xl:col-span-9 space-y-5">

            {{-- Lokasi --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4 flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">🗺️</span> Rute Transfer
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📤 Dari Lokasi <span class="text-slate-400">(sumber stok)</span></label>
                        <select id="fromLocation" name="from_location_id"
                                class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner">
                            <option value="">-- Pilih Lokasi Asal --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                    data-type="{{ $loc->type }}"
                                    {{ $loc->type === 'gudang' ? 'selected' : '' }}>
                                {{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}
                                {{ $loc->name }}
                                @if($loc->type === 'gudang') (Gudang Utama) @endif
                            </option>
                            @endforeach
                        </select>
                        <p class="text-slate-400 dark:text-purple-505 text-xs mt-1 font-medium">Kosongkan jika stok berasal dari pembelian baru</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📥 Ke Lokasi <span class="text-rose-500">*</span></label>
                        <select id="toLocation" name="to_location_id" required
                                class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner">
                            <option value="">-- Pilih Lokasi Tujuan --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">
                                {{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}
                                {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Produk yang ditransfer --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-slate-800 dark:text-purple-100 font-extrabold flex items-center gap-2">
                        <span class="text-emerald-600 dark:text-emerald-400">🛍️</span> Daftar Produk
                    </h2>
                    <button type="button" onclick="addProductRow()"
                            class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-sm flex items-center gap-1">
                        + Tambah Produk
                    </button>
                </div>

                {{-- Tabel item --}}
                <div class="w-full overflow-visible">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-dp-700">
                                <th class="text-left text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider text-xs pb-3 pr-4">Produk</th>
                                <th class="text-left text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider text-xs pb-3 pr-4 w-40">Stok Tersedia</th>
                                <th class="text-left text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider text-xs pb-3 pr-4 w-36">Jumlah Transfer</th>
                                <th class="text-left text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider text-xs pb-3 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTable" class="divide-y divide-slate-100 dark:divide-dp-700">
                            {{-- Baris item akan di-generate JS --}}
                        </tbody>
                    </table>
                </div>
                <p id="noItemsMsg" class="text-center text-slate-400 dark:text-purple-400 py-8 text-sm font-medium">
                    Pilih lokasi asal terlebih dahulu, lalu klik "+ Tambah Produk"
                </p>
            </div>

            {{-- Catatan --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md">
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📝 Catatan Transfer</label>
                <textarea id="notes" rows="3" placeholder="Opsional: catatan alasan transfer, instruksi khusus, dll."
                    class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none resize-none shadow-inner"></textarea>
            </div>
        </div>

        {{-- Summary Panel (kanan) --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-5">
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md sticky top-6">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4">📋 Ringkasan Transfer</h2>

                <div class="space-y-3 mb-5 border-b border-slate-100 dark:border-dp-700 pb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400 dark:text-purple-400 font-medium">Dari</span>
                        <span id="summaryFrom" class="text-slate-800 dark:text-purple-100 font-bold text-right">-</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400 dark:text-purple-400 font-medium">Ke</span>
                        <span id="summaryTo" class="text-slate-800 dark:text-purple-100 font-bold text-right">-</span>
                    </div>
                    <div class="flex justify-between text-sm pt-1">
                        <span class="text-slate-400 dark:text-purple-400 font-medium">Total Produk</span>
                        <span id="summaryItemCount" class="text-emerald-700 dark:text-emerald-300 font-extrabold">0 item</span>
                    </div>
                </div>

                {{-- Daftar item summary --}}
                <div id="summaryItems" class="space-y-2 mb-5 max-h-60 overflow-y-auto text-xs text-slate-700 dark:text-purple-200"></div>

                <button id="submitBtn" onclick="submitTransfer()"
                        class="w-full bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3.5 rounded-xl font-bold uppercase tracking-wider text-xs transition duration-150 shadow-sm">
                    🚀 Buat Transfer Sekarang
                </button>
                <p class="text-slate-400 dark:text-purple-500 text-xs text-center mt-2.5 font-medium">
                    Status akan "Pending" — stok berpindah setelah di-approve
                </p>
            </div>
        </div>

    </div>
</div>

<script>
const allProducts = {!! json_encode($products->map(function($p) {
    return [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
        'unit' => $p->price_unit
    ];
})) !!};

let stockByLocation = {}; // [locationId][productId] = stock
let rowCount = 0;

// Load stok saat lokasi asal berubah
document.getElementById('fromLocation').addEventListener('change', function() {
    updateSummary();
    const locId = this.value;
    if (!locId) {
        stockByLocation = {};
        updateAllRows();
        return;
    }

    fetch(`/admin/stock-by-location?location_id=${locId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        stockByLocation = {};
        data.forEach(s => { stockByLocation[s.product_id] = s.stock; });
        updateAllRows();
    });
});

document.getElementById('toLocation').addEventListener('change', updateSummary);

// Load stok awal jika lokasi asal sudah terpilih secara default pada page load
if (document.getElementById('fromLocation').value) {
    document.getElementById('fromLocation').dispatchEvent(new Event('change'));
}

function addProductRow() {
    document.getElementById('noItemsMsg').classList.add('hidden');
    rowCount++;
    const tr = document.createElement('tr');
    tr.id = `row_${rowCount}`;
    tr.dataset.rowId = rowCount;
    tr.className = 'group';
    tr.innerHTML = `
        <td class="py-3 pr-4">
            <div class="relative z-30">
                <input type="hidden" id="product_${rowCount}" value="">
                <input type="text" 
                       id="product_search_${rowCount}" 
                       placeholder="🔍 Ketik nama atau SKU produk..." 
                       onfocus="showDropdown(${rowCount})" 
                       oninput="filterDropdown(${rowCount})" 
                       onclick="event.stopPropagation()"
                       class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-3.5 py-2.5 text-xs focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner"
                       autocomplete="off">
                <div id="product_dropdown_${rowCount}" 
                     class="hidden absolute z-50 left-0 right-0 bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700 shadow-xl rounded-xl mt-1 max-h-60 overflow-y-auto">
                    <!-- Dropdown items will be inserted dynamically -->
                </div>
            </div>
        </td>
        <td class="py-3 pr-4">
            <div id="available_${rowCount}" class="text-slate-400 dark:text-purple-400 text-xs px-2">—</div>
        </td>
        <td class="py-3 pr-4">
            <input type="number" min="0.01" step="0.01" placeholder="0"
                   id="qty_${rowCount}"
                   oninput="onQtyChange(${rowCount})"
                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-3.5 py-2.5 text-xs focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner">
        </td>
        <td class="py-3">
            <button onclick="removeRow(${rowCount})" class="text-slate-400 dark:text-purple-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors text-xl font-bold px-2">×</button>
        </td>
    `;
    document.getElementById('itemsTable').appendChild(tr);
    updateSummary();
}

function removeRow(id) {
    document.getElementById(`row_${id}`)?.remove();
    const rows = document.getElementById('itemsTable').rows;
    if (rows.length === 0) {
        document.getElementById('noItemsMsg').classList.remove('hidden');
    }
    updateSummary();
}

// Autocomplete Functions
function formatStock(stock, unit) {
    if (unit === 'gram' && stock >= 1000) {
        return `${(stock / 1000).toFixed(2).replace(/\.?0+$/, '')} kg`;
    }
    return `${parseFloat(stock).toFixed(2).replace(/\.?0+$/, '')} ${unit}`;
}

function showDropdown(rowId) {
    closeAllDropdowns();
    const dropdown = document.getElementById(`product_dropdown_${rowId}`);
    if (!dropdown) return;
    dropdown.classList.remove('hidden');
    filterDropdown(rowId);
}

function filterDropdown(rowId) {
    const searchVal = document.getElementById(`product_search_${rowId}`).value.toLowerCase();
    const dropdown = document.getElementById(`product_dropdown_${rowId}`);
    if (!dropdown) return;

    const filtered = allProducts.filter(p => {
        return p.name.toLowerCase().includes(searchVal) || p.sku.toLowerCase().includes(searchVal);
    });

    if (filtered.length === 0) {
        dropdown.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400 dark:text-purple-400 text-center font-medium">Produk tidak ditemukan</div>`;
        return;
    }

    const currentLocId = document.getElementById('fromLocation').value;

    dropdown.innerHTML = filtered.map(p => {
        const stock = stockByLocation[p.id] ?? null;
        let stockText = '';
        if (currentLocId && stock !== null) {
            const formatted = formatStock(stock, p.unit);
            const isLow = p.unit === 'gram' ? (stock <= 10000) : (stock <= 10);
            stockText = `<span class="text-[10px] ${isLow ? 'bg-rose-100 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300' : 'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300'} px-2 py-0.5 rounded-lg font-bold">${formatted}</span>`;
        }
        return `
            <button type="button" 
                    onclick="selectProduct(${rowId}, ${p.id}, '${escapeHtml(p.name)}', '${p.sku}', '${p.unit}')"
                    class="w-full text-left px-4 py-2.5 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-slate-700 dark:text-purple-200 hover:text-emerald-800 dark:hover:text-emerald-300 border-b border-slate-100 dark:border-dp-700/50 font-bold transition flex justify-between items-center">
                <span class="truncate mr-2 text-slate-700 dark:text-purple-200 font-bold">${p.name} <span class="text-slate-400 dark:text-purple-400 font-normal">(${p.sku})</span></span>
                ${stockText}
            </button>
        `;
    }).join('');
}

function selectProduct(rowId, productId, name, sku, unit) {
    const inputHidden = document.getElementById(`product_${rowId}`);
    const inputSearch = document.getElementById(`product_search_${rowId}`);
    const dropdown = document.getElementById(`product_dropdown_${rowId}`);

    inputHidden.value = productId;
    inputSearch.value = `${name} (${sku})`;
    dropdown.classList.add('hidden');

    onProductChange(rowId);
}

function closeAllDropdowns() {
    document.querySelectorAll('[id^="product_dropdown_"]').forEach(el => {
        el.classList.add('hidden');
    });
}

function escapeHtml(text) {
    return text
        .replace(/'/g, "\\'")
        .replace(/"/g, '&quot;');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="product_dropdown_"]') && !e.target.closest('[id^="product_search_"]')) {
        closeAllDropdowns();
    }
});

function onProductChange(id) {
    const productId = parseInt(document.getElementById(`product_${id}`).value);
    const avail = document.getElementById(`available_${id}`);

    if (!productId) { 
        avail.textContent = '—'; 
        avail.className = 'text-slate-400 dark:text-purple-400 text-xs px-2'; 
        return; 
    }

    const product = allProducts.find(p => p.id === productId);
    const unit = product ? product.unit : 'pcs';
    const stock = stockByLocation[productId] ?? null;
    const fromLocId = document.getElementById('fromLocation').value;

    if (fromLocId && stock !== null) {
        avail.textContent = formatStock(stock, unit);
        const isLow = unit === 'gram' ? (stock <= 10000) : (stock <= 10);
        avail.className = isLow ? 'text-rose-600 dark:text-rose-400 text-xs font-bold px-2' : 'text-emerald-700 dark:text-emerald-400 text-xs font-bold px-2';
    } else {
        avail.textContent = fromLocId ? '0' : 'Pilih lokasi asal';
        avail.className = 'text-slate-400 dark:text-purple-400 text-xs px-2';
    }
    updateSummary();
}

// override global browser alerts with customized premium global toast
function showToast(msg, type = 'success') {
    if (window.showToast) {
        window.showToast(msg, type);
    } else {
        alert(msg);
    }
}

function onQtyChange(id) {
    const sel = document.getElementById(`product_${id}`);
    const qtyEl = document.getElementById(`qty_${id}`);
    const productId = parseInt(sel.value);
    const qty = parseFloat(qtyEl.value) || 0;
    const fromLocId = document.getElementById('fromLocation').value;

    if (fromLocId && productId && stockByLocation[productId] !== undefined) {
        const available = stockByLocation[productId];
        if (qty > available) {
            qtyEl.classList.add('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
        } else {
            qtyEl.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
        }
    }
    updateSummary();
}

function updateAllRows() {
    document.querySelectorAll('input[type="hidden"][id^="product_"]').forEach(sel => {
        const id = sel.id.replace('product_', '');
        onProductChange(parseInt(id));
    });
}

function updateSummary() {
    const fromSel = document.getElementById('fromLocation');
    const toSel   = document.getElementById('toLocation');
    const fromText = fromSel.options[fromSel.selectedIndex]?.text ?? '-';
    const toText   = toSel.options[toSel.selectedIndex]?.text ?? '-';

    document.getElementById('summaryFrom').textContent = fromSel.value ? fromText : '-';
    document.getElementById('summaryTo').textContent   = toSel.value ? toText : '-';

    const items = getValidItems();
    document.getElementById('summaryItemCount').textContent = `${items.length} item`;

    const summaryEl = document.getElementById('summaryItems');
    summaryEl.innerHTML = items.map(item => `
        <div class="flex justify-between bg-slate-50 dark:bg-dp-900 border border-slate-200/50 dark:border-dp-700/50 rounded-xl px-3.5 py-2">
            <span class="text-slate-700 dark:text-purple-200 truncate mr-2 font-medium">${item.name}</span>
            <span class="text-emerald-700 dark:text-emerald-400 font-extrabold whitespace-nowrap">${item.qty} ${item.unit}</span>
        </div>
    `).join('');
}

function getValidItems() {
    const items = [];
    document.querySelectorAll('[id^="row_"]').forEach(row => {
        const id = row.dataset.rowId;
        const sel = document.getElementById(`product_${id}`);
        const qty = parseFloat(document.getElementById(`qty_${id}`)?.value) || 0;
        if (sel?.value && qty > 0) {
            const productId = parseInt(sel.value);
            const product = allProducts.find(p => p.id === productId);
            items.push({
                product_id: productId,
                name: product ? product.name : '',
                quantity: qty,
                qty: qty.toFixed(2),
                unit: product ? product.unit : 'pcs',
            });
        }
    });
    return items;
}

function submitTransfer() {
    const fromId = document.getElementById('fromLocation').value;
    const toId   = document.getElementById('toLocation').value;
    const notes  = document.getElementById('notes').value;
    const items  = getValidItems();

    if (!toId) { showToast('Pilih lokasi tujuan terlebih dahulu!', 'warning'); return; }
    if (items.length === 0) { showToast('Tambahkan minimal 1 produk untuk ditransfer!', 'warning'); return; }
    if (fromId && fromId === toId) { showToast('Lokasi asal dan tujuan tidak boleh sama!', 'warning'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';

    fetch('/admin/stock-transfers', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ from_location_id: fromId || null, to_location_id: toId, notes, items })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            showToast('Gagal: ' + data.message, 'error');
            btn.disabled = false;
            btn.textContent = '🚀 Buat Transfer Sekarang';
        }
    });
}
</script>
@endsection
