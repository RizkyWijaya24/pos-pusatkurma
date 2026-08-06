@php
    $statusColors = [
        'requested' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
        'pending'   => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30',
        'approved'  => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
        'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
        'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-900/20 dark:text-slate-400 dark:border-slate-800/30',
    ];
    $statusIcons = [
        'requested' => '⏳', 'pending' => '🔵', 'approved' => '✅', 'rejected' => '❌', 'cancelled' => '🚫',
    ];
@endphp

@extends('layouts.app')

@section('title', 'Detail Transfer ' . $stockTransfer->transfer_code)

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stock-transfers.index') }}"
               class="text-slate-500 dark:text-purple-400 hover:text-slate-700 dark:hover:text-white transition-colors text-sm font-bold">
                ← Kembali
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-purple-100 font-mono">{{ $stockTransfer->transfer_code }}</h1>
                <p class="text-slate-400 dark:text-purple-400 text-sm font-medium">Detail Transfer Stok</p>
            </div>
        </div>
        @if(in_array($stockTransfer->status, ['requested', 'pending']))
        <div class="flex gap-3">
            <button onclick="approveTransfer()" class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all hover:scale-105 flex items-center gap-2 shadow-sm">
                ✓ Approve Transfer
            </button>
            <button onclick="showRejectModal()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30 px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-colors shadow-sm">
                ✗ Tolak
            </button>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Info Utama --}}
        <div class="lg:col-span-8 xl:col-span-9 space-y-5">

            {{-- Status Card --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="text-4xl">
                            {{ $statusIcons[$stockTransfer->status] ?? '📋' }}
                        </div>
                        <div>
                            <p class="text-slate-400 dark:text-purple-400 text-xs font-bold uppercase tracking-wider mb-1">Status Transfer</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold border {{ $statusColors[$stockTransfer->status] ?? '' }}">
                                {{ $stockTransfer->statusLabel }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-400 dark:text-purple-400 text-xs font-bold uppercase tracking-wider mb-0.5">Dibuat</p>
                        <p class="text-slate-800 dark:text-purple-100 text-sm font-bold">{{ $stockTransfer->created_at->translatedFormat('d F Y - H:i') }}</p>
                        @if($stockTransfer->approved_at)
                        <p class="text-slate-400 dark:text-purple-400 text-xs font-bold uppercase tracking-wider mt-2 mb-0.5">Diproses</p>
                        <p class="text-slate-800 dark:text-purple-100 text-sm font-bold">{{ $stockTransfer->approved_at->translatedFormat('d F Y - H:i') }}</p>
                        @endif
                    </div>
                </div>

                @if($stockTransfer->rejection_reason)
                <div class="mt-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 rounded-2xl p-4">
                    <p class="text-rose-700 dark:text-rose-400 text-sm font-bold mb-1">Alasan Penolakan:</p>
                    <p class="text-rose-600 dark:text-rose-300 text-sm font-semibold">{{ $stockTransfer->rejection_reason }}</p>
                </div>
                @endif
            </div>

            {{-- Rute Transfer --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4 flex items-center gap-2">🗺️ Rute Transfer</h2>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0 bg-slate-50 dark:bg-dp-900 border border-slate-200/50 dark:border-dp-700/50 rounded-2xl p-4 text-center">
                        <div class="text-2xl mb-2">{{ $stockTransfer->fromLocation?->type === 'gudang' ? '🏭' : '🏪' }}</div>
                        <p class="text-slate-400 dark:text-purple-400 text-xs mb-1 font-bold uppercase tracking-wider">Dari</p>
                        <p class="text-slate-800 dark:text-purple-100 font-bold text-sm">{{ $stockTransfer->fromLocation?->name ?? 'Luar Sistem / Pembelian Baru' }}</p>
                    </div>
                    <div class="text-3xl text-slate-400 flex-shrink-0">→</div>
                    <div class="flex-1 min-w-0 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 rounded-2xl p-4 text-center">
                        <div class="text-2xl mb-2">{{ $stockTransfer->toLocation->type === 'gudang' ? '🏭' : ($stockTransfer->toLocation->type === 'online' ? '🌐' : '🏪') }}</div>
                        <p class="text-slate-400 dark:text-purple-400 text-xs mb-1 font-bold uppercase tracking-wider">Ke</p>
                        <p class="text-emerald-800 dark:text-emerald-300 font-bold text-sm">{{ $stockTransfer->toLocation->name }}</p>
                    </div>
                </div>
            </div>

            {{-- Daftar Produk --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl overflow-hidden shadow-md">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-dp-700">
                    <h2 class="text-slate-800 dark:text-purple-100 font-extrabold">🛍️ Daftar Produk yang Ditransfer</h2>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                            <tr class="border-b border-slate-100 dark:border-dp-700">
                                <th class="px-5 py-3">No</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-5 py-3 text-right">Diminta</th>
                                @if($stockTransfer->status === 'approved')
                                <th class="px-5 py-3 text-right">Dikirim</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
                            @foreach($stockTransfer->items as $i => $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150">
                                <td class="px-5 py-3 text-slate-400 text-xs font-bold">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-800 dark:text-purple-100 font-bold">{{ $item->product->name }}</p>
                                    <p class="text-slate-400 dark:text-purple-400 font-mono text-[10px] mt-0.5">{{ $item->product->sku }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-sm">
                                        {{ number_format($item->quantity, 2, ',', '.') }}
                                    </span>
                                    <span class="text-slate-400 dark:text-purple-400 text-xs ml-1 font-bold uppercase">{{ $item->unit }}</span>
                                </td>
                                @if($stockTransfer->status === 'approved')
                                <td class="px-5 py-3 text-right">
                                    @if($item->approved_quantity !== null && $item->approved_quantity != $item->quantity)
                                        <span class="text-amber-600 dark:text-amber-400 font-extrabold text-sm">
                                            {{ number_format($item->approved_quantity, 2, ',', '.') }}
                                        </span>
                                        <span class="text-slate-400 dark:text-purple-400 text-xs ml-1 font-bold uppercase">{{ $item->unit }}</span>
                                        <span class="ml-1 text-[10px] text-amber-500 font-bold">✏️ disesuaikan</span>
                                    @else
                                        <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-sm">
                                            {{ number_format($item->approved_quantity ?? $item->quantity, 2, ',', '.') }}
                                        </span>
                                        <span class="text-slate-400 dark:text-purple-400 text-xs ml-1 font-bold uppercase">{{ $item->unit }}</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($stockTransfer->notes)
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-3">📝 Catatan</h2>
                <p class="text-slate-700 dark:text-purple-200 text-sm leading-relaxed font-medium">{{ $stockTransfer->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-5">
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4 flex items-center gap-2">👤 Informasi</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-slate-400 dark:text-purple-400 text-[10px] font-bold uppercase tracking-wider mb-1">Dibuat Oleh</p>
                        <p class="text-slate-800 dark:text-purple-100 text-sm font-bold">{{ $stockTransfer->requester->name }}</p>
                        <p class="text-slate-400 dark:text-purple-400 text-xs font-semibold">{{ ucfirst($stockTransfer->requester->role) }} — {{ $stockTransfer->requester->branch }}</p>
                    </div>
                    @if($stockTransfer->approver)
                    <div class="border-t border-slate-100 dark:border-dp-700 pt-4">
                        <p class="text-slate-400 dark:text-purple-400 text-[10px] font-bold uppercase tracking-wider mb-1">{{ $stockTransfer->status === 'rejected' ? 'Ditolak Oleh' : 'Disetujui Oleh' }}</p>
                        <p class="text-slate-800 dark:text-purple-100 text-sm font-bold">{{ $stockTransfer->approver->name }}</p>
                        <p class="text-slate-400 dark:text-purple-400 text-xs font-semibold">{{ ucfirst($stockTransfer->approver->role) }}</p>
                    </div>
                    @endif
                    <div class="border-t border-slate-100 dark:border-dp-700 pt-4">
                        <p class="text-slate-400 dark:text-purple-400 text-[10px] font-bold uppercase tracking-wider mb-1">Total Item Produk</p>
                        <p class="text-emerald-700 dark:text-emerald-400 text-3xl font-black">{{ $stockTransfer->items->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all duration-300 scale-95 opacity-0 active-modal-reject">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-purple-100 mb-2">❌ Tolak Transfer</h3>
            <p class="text-slate-500 dark:text-purple-400 text-sm mb-2">Alasan penolakan (wajib):</p>
            <textarea id="rejectionReason" rows="3" placeholder="Alasan penolakan..." required
                class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none resize-none shadow-inner"></textarea>
            <div class="flex gap-3 mt-4">
                <button onclick="closeRejectModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 py-2.5 rounded-xl text-sm font-bold transition-colors">Batal</button>
                <button onclick="confirmReject()" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors">Konfirmasi Tolak</button>
            </div>
        </div>
    </div>
</div>

{{-- Approve Modal (Penyesuaian Jumlah) --}}
<div id="approveModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 active-modal-approve">
        {{-- Modal Header --}}
        <div class="px-6 py-5 border-b border-slate-100 dark:border-dp-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-purple-100">✅ Approve Transfer Stok</h3>
                <p class="text-slate-500 dark:text-purple-400 text-xs mt-0.5">Periksa & sesuaikan jumlah sebelum approve</p>
            </div>
            <button onclick="closeApproveModal()" class="text-slate-400 hover:text-slate-600 dark:text-purple-400 dark:hover:text-white text-xl transition-colors">✕</button>
        </div>

        {{-- Info Jumlah --}}
        <div class="px-6 pt-4">
            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-2xl p-3 flex gap-2 items-start">
                <span class="text-amber-500 text-lg mt-0.5">⚠️</span>
                <p class="text-amber-700 dark:text-amber-300 text-xs font-semibold leading-relaxed">
                    Jika stok di gudang <strong>tidak mencukupi</strong> jumlah yang diminta, ubah jumlah pada kolom
                    <strong>"Disetujui"</strong> menjadi stok yang tersedia. Isi <strong>0</strong> untuk tidak mengirimkan produk tersebut.
                </p>
            </div>
        </div>

        {{-- Tabel Item --}}
        <div class="px-6 py-4 overflow-x-auto max-h-96 overflow-y-auto">
            <table class="min-w-full text-sm" id="approveItemsTable">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-purple-400 border-b border-slate-100 dark:border-dp-700">
                        <th class="text-left pb-3 pr-4">Produk</th>
                        <th class="text-right pb-3 px-3">Stok Gudang</th>
                        <th class="text-right pb-3 px-3">Diminta</th>
                        <th class="text-right pb-3 pl-3">Disetujui ✏️</th>
                    </tr>
                </thead>
                <tbody id="approveItemsBody" class="divide-y divide-slate-100 dark:divide-dp-700">
                    {{-- Diisi via JS --}}
                </tbody>
            </table>
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-5 border-t border-slate-100 dark:border-dp-700 flex gap-3">
            <button onclick="closeApproveModal()"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-dp-700 dark:text-purple-200 hover:dark:bg-dp-600 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors">
                Batal
            </button>
            <button id="confirmApproveBtn" onclick="confirmApproveAdjusted()"
                    class="flex-1 bg-emerald-700 hover:bg-emerald-800 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-md shadow-emerald-700/10">
                ✓ Approve Sesuai Jumlah Ini
            </button>
        </div>
    </div>
</div>

<script>
const rolePrefix = "{{ Auth::user()->role }}";
const transferId = {{ $stockTransfer->id }};

// Data items dari server (PHP → JS)
const transferItems = {!! json_encode($stockTransfer->items->map(fn($item) => [
    'id'         => $item->id,
    'product_id' => $item->product_id,
    'name'       => $item->product->name,
    'sku'        => $item->product->sku,
    'quantity'   => $item->quantity,
    'unit'       => $item->unit,
])) !!};


// Stok gudang per produk: { product_id: stock }
const warehouseStocks = {};

function showToast(msg, type = 'success') {
    if (window.showToast) {
        window.showToast(msg, type);
    } else {
        alert(msg);
    }
}

// Modal Animation Helpers
function showModal(modalId, cardClass) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        const card = modal.querySelector('.' + cardClass);
        if (card) {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
}

function closeModal(modalId, cardClass) {
    const modal = document.getElementById(modalId);
    const card = modal.querySelector('.' + cardClass);
    if (card) {
        card.classList.add('scale-95', 'opacity-0');
        card.classList.remove('scale-100', 'opacity-100');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

// ════════════════════════════════════════
// APPROVE MODAL — dengan penyesuaian jumlah
// ════════════════════════════════════════

async function approveTransfer() {
    // Ambil stok gudang dari server dulu
    try {
        const response = await fetch(`/${rolePrefix}/stock-by-location?location_id={{ $stockTransfer->fromLocation?->id ?? 0 }}`);
        const stocks = await response.json();
        // Susun ke object { product_id: stock }
        stocks.forEach(s => { warehouseStocks[s.product_id] = s.stock; });
    } catch(e) {
        console.warn('Tidak bisa ambil stok gudang:', e);
    }

    // Bangun tabel item di modal
    const tbody = document.getElementById('approveItemsBody');
    tbody.innerHTML = '';

    transferItems.forEach(item => {
        // Lookup stok gudang berdasarkan product_id (bukan item.id)
        const warehouseQty = warehouseStocks[item.product_id] ?? null;
        const suggested    = warehouseQty !== null ? Math.min(item.quantity, warehouseQty) : item.quantity;
        const isShort      = warehouseQty !== null && warehouseQty < item.quantity;

        const warehouseDisplay = warehouseQty !== null
            ? `<span class="font-bold ${isShort ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'}">${formatNum(warehouseQty)}</span>`
            : `<span class="text-slate-400">—</span>`;

        const warningBadge = isShort
            ? `<div class="text-rose-500 text-[10px] font-bold mt-0.5">⚠️ Stok tidak cukup</div>`
            : '';

        tbody.insertAdjacentHTML('beforeend', `
            <tr class="">
                <td class="py-3 pr-4">
                    <p class="font-bold text-slate-800 dark:text-purple-100 text-sm">${item.name}</p>
                    <p class="text-slate-400 font-mono text-[10px] mt-0.5">${item.sku}</p>
                </td>
                <td class="py-3 px-3 text-right">
                    ${warehouseDisplay}
                    <div class="text-slate-400 text-[10px] uppercase font-bold">${item.unit}</div>
                    ${warningBadge}
                </td>
                <td class="py-3 px-3 text-right">
                    <span class="font-extrabold text-slate-700 dark:text-purple-200">${formatNum(item.quantity)}</span>
                    <div class="text-slate-400 text-[10px] uppercase font-bold">${item.unit}</div>
                </td>
                <td class="py-3 pl-3">
                    <input
                        type="number"
                        id="approved_qty_${item.id}"
                        data-item-id="${item.id}"
                        data-warehouse-qty="${warehouseQty ?? 9999999}"
                        data-requested-qty="${item.quantity}"
                        value="${suggested}"
                        min="0"
                        step="0.01"
                        class="w-24 text-right bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-600 text-slate-800 dark:text-purple-100 rounded-xl px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none transition-colors approve-qty-input"
                        oninput="validateQtyInput(this)"
                    />
                    <div class="text-slate-400 text-[10px] uppercase font-bold mt-1 text-center w-24">${item.unit}</div>
                </td>
            </tr>
        `);
    });

    showModal('approveModal', 'active-modal-approve');
}

function validateQtyInput(input) {
    const warehouseQty = parseFloat(input.dataset.warehouseQty);
    const val = parseFloat(input.value);
    if (val > warehouseQty) {
        input.classList.add('border-rose-500', 'bg-rose-50', 'dark:bg-rose-950/20');
        input.classList.remove('border-slate-200', 'border-emerald-500');
    } else if (val >= 0) {
        input.classList.remove('border-rose-500', 'bg-rose-50', 'dark:bg-rose-950/20');
        input.classList.add('border-emerald-500');
    }
}

function closeApproveModal() {
    closeModal('approveModal', 'active-modal-approve');
}

function formatNum(n) {
    if (n === null || n === undefined) return '—';
    return parseFloat(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function confirmApproveAdjusted() {
    // Validasi semua input
    const inputs = document.querySelectorAll('.approve-qty-input');
    let hasError = false;
    let items = [];

    inputs.forEach(input => {
        const val = parseFloat(input.value);
        const warehouseQty = parseFloat(input.dataset.warehouseQty);
        if (isNaN(val) || val < 0) {
            showToast(`Jumlah tidak valid untuk item #${input.dataset.itemId}`, 'error');
            hasError = true;
            return;
        }
        if (val > warehouseQty && warehouseQty < 9999999) {
            showToast(`Jumlah melebihi stok gudang yang tersedia!`, 'error');
            hasError = true;
            return;
        }
        items.push({ item_id: parseInt(input.dataset.itemId), approved_qty: val });
    });

    if (hasError) return;

    const btn = document.getElementById('confirmApproveBtn');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ MEMPROSES...'; }

    fetch(`/${rolePrefix}/stock-transfers/${transferId}/approve-adjusted`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(data => {
        closeApproveModal();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast(data.message, 'error');
            if (btn) { btn.disabled = false; btn.textContent = '✓ Approve Sesuai Jumlah Ini'; }
        }
    })
    .catch(() => {
        closeApproveModal();
        showToast('Terjadi kesalahan koneksi!', 'error');
        if (btn) { btn.disabled = false; btn.textContent = '✓ Approve Sesuai Jumlah Ini'; }
    });
}


// Reject handlers
function showRejectModal() {
    showModal('rejectModal', 'active-modal-reject');
}

function closeRejectModal() {
    closeModal('rejectModal', 'active-modal-reject');
}

function confirmReject() {
    const reason = document.getElementById('rejectionReason').value.trim();
    if (!reason) {
        showToast('Alasan penolakan wajib diisi!', 'error');
        return;
    }
    const btn = document.querySelector('#rejectModal button[onclick="confirmReject()"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ MEMPROSES...';
    }

    fetch(`/${rolePrefix}/stock-transfers/{{ $stockTransfer->id }}/reject`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ rejection_reason: reason })
    })
    .then(r => r.json())
    .then(data => {
        closeRejectModal();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Konfirmasi Tolak';
            }
        }
    })
    .catch(err => {
        closeRejectModal();
        showToast('Terjadi kesalahan koneksi!', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Konfirmasi Tolak';
        }
    });
}
</script>
@endsection
