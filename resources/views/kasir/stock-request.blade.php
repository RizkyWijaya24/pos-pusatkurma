@extends('layouts.app')

@section('title', 'Request Stok Barang')

@section('content')
<style>
    /* Paksa container parent di layouts/app.blade.php agar melebar maksimal dan memanfaatkan seluruh space layar */
    main > div[class*="max-w-"] {
        max-w: 98% !important;
        width: 98% !important;
    }
    
    .request-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        width: 100%;
    }
    @media (min-width: 1024px) {
        .request-grid {
            grid-template-columns: 3fr 1fr; /* 75% tabel produk, 25% sidebar */
        }
    }
    .request-table-card {
        width: 100% !important;
        max-width: 100% !important;
    }

    @media (max-width: 640px) {
        .responsive-request-table thead {
            display: none !important;
        }
        .responsive-request-table tbody tr {
            display: block !important;
            background: #ffffff !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            border-radius: 16px !important;
            padding: 14px 16px !important;
            margin-bottom: 12px !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .dark .responsive-request-table tbody tr {
            background: rgba(30, 41, 59, 0.4) !important;
            border-color: rgba(51, 65, 85, 0.5) !important;
        }
        .responsive-request-table tbody td {
            display: block !important;
            width: 100% !important;
            padding: 6px 0 !important;
            border: none !important;
            text-align: left !important;
        }
    }
</style>
<div class="py-4 px-1 sm:px-4">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">📬</span> Request Stok Barang
            </h1>
            <p class="text-slate-500 dark:text-purple-300 mt-1 text-sm">
                Kirim permintaan pengiriman barang dari
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Gudang Pusat</span>
                ke
                <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $myLocation?->name ?? $kasir->branch }}</span>
            </p>
        </div>
        @if(!$myLocation)
        <div class="bg-red-100 dark:bg-red-500/20 border border-red-200 dark:border-red-500/30 rounded-xl px-4 py-2.5 text-red-700 dark:text-red-350 text-xs sm:text-sm font-semibold max-w-md shadow-sm">
            ⚠️ Cabang Anda ({{ $kasir->branch }}) tidak terdaftar di lokasi stok sistem. Hubungi Admin.
        </div>
        @endif
    </div>

    @if($myLocation)
    <div class="request-grid items-start">

        {{-- Kolom Kiri (75%): Tabel Daftar Produk & Input Qty --}}
        <div class="request-table-card space-y-5">
            <div class="bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700/60 rounded-2xl p-4 sm:p-6 shadow-sm dark:shadow-dp-950/40">
                
                {{-- Pencarian & Filter Cepat (Stacked Vertikal untuk mencegah Tabrakan/Overlapping) --}}
                <div class="space-y-4 mb-6">
                    {{-- Row 1: Search Bar (Lebar Penuh) --}}
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            🔍
                        </span>
                        <input type="text" id="searchInput" oninput="filterProducts()" placeholder="Cari nama produk atau kategori..."
                            class="w-full bg-slate-50 dark:bg-dp-800 border border-slate-300 dark:border-dp-700 text-slate-900 dark:text-white rounded-xl pl-10 pr-4 py-2.5 text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition-all shadow-inner">
                    </div>
                    
                    {{-- Row 2: Filter Tabs (Baris Mandiri) --}}
                    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-dp-950 border border-slate-200 dark:border-dp-800 rounded-xl w-fit">
                        <button type="button" id="tab-all" onclick="setFilterTab('all')" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-emerald-600 text-white shadow-sm">
                            Semua Produk
                        </button>
                        <button type="button" id="tab-low" onclick="setFilterTab('low')" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 dark:text-purple-300 hover:text-slate-900 dark:hover:text-white">
                            🔴 Stok Rendah / Habis
                        </button>
                        <button type="button" id="tab-inputted" onclick="setFilterTab('inputted')" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 dark:text-purple-300 hover:text-slate-900 dark:hover:text-white flex items-center gap-1.5">
                            ✍️ Sudah Di-input
                            <span id="inputtedBadge" class="hidden bg-emerald-500 text-slate-900 px-1.5 py-0.2 rounded-full font-black text-[9px]">0</span>
                        </button>
                    </div>
                </div>

                {{-- Tabel Produk --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-dp-700/50 bg-slate-50/50 dark:bg-dp-950/20">
                    <table class="w-full text-left border-collapse responsive-request-table">
                        <thead>
                            <tr class="bg-slate-100 dark:bg-dp-950/50 border-b border-slate-200 dark:border-dp-700 text-slate-600 dark:text-purple-300 text-xs font-bold uppercase tracking-wider">
                                <th class="px-4 py-3 text-left w-auto">Nama Produk</th>
                                <th class="px-4 py-3 text-right w-32 sm:w-36">Stok Cabang</th>
                                <th class="px-4 py-3 text-center w-36 sm:w-44">Jumlah Request</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody" class="divide-y divide-slate-150 dark:divide-slate-800 text-sm">
                            @foreach($products as $p)
                            @php
                                $stock = $myStocks[$p->id]->stock ?? 0;
                                $isCritical = $stock <= 5;
                                $isWarning = $stock > 5 && $stock <= 10;
                                
                                $statusClass = $stock <= 0 ? 'bg-rose-100 dark:bg-red-500/20 text-rose-700 dark:text-red-400 border border-rose-200 dark:border-red-500/30' : 
                                               ($isCritical ? 'bg-rose-100 dark:bg-red-500/20 text-rose-700 dark:text-red-400 border border-rose-200 dark:border-red-500/30' : 
                                               ($isWarning ? 'bg-amber-100 dark:bg-yellow-500/20 text-amber-700 dark:text-yellow-400 border border-amber-200 dark:border-yellow-500/30' : 
                                               'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/25'));
                                
                                $statusLabel = $stock <= 0 ? 'Habis' : 
                                               ($isCritical ? 'Kritis' : 
                                               ($isWarning ? 'Hampir Habis' : 'Aman'));
                                                <tr id="row-{{ $p->id }}" data-id="{{ $p->id }}" data-name="{{ strtolower($p->name) }}" data-category="{{ strtolower($p->category) }}" data-stock="{{ $stock }}" class="hover:bg-slate-50 dark:hover:bg-dp-800/40 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm sm:text-base leading-tight">{{ $p->name }}</div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300 border border-slate-200/60 dark:border-dp-700 font-semibold">{{ $p->category ?: 'Umum' }}</span>
                                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-mono tracking-wider">{{ $p->sku }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex sm:block justify-between items-center">
                                        <span class="inline sm:hidden text-xs font-bold text-slate-400 dark:text-purple-400 uppercase tracking-wider">Stok Cabang</span>
                                        <div class="text-right">
                                            <div class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base">
                                                {{ number_format($stock, 2, ',', '.') }}
                                                <span class="text-slate-500 dark:text-purple-300 text-xs font-normal ml-0.5">{{ $p->price_unit }}</span>
                                            </div>
                                            <span class="inline-block text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase mt-1 tracking-wide {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col sm:flex-row items-center justify-between sm:justify-center gap-2">
                                        <span class="inline sm:hidden text-xs font-bold text-slate-400 dark:text-purple-400 uppercase tracking-wider self-start mb-1">Jumlah Request</span>
                                        <div class="flex items-center justify-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                                            <button type="button" onclick="adjustQty({{ $p->id }}, -1)" 
                                                class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-dp-800 hover:bg-slate-200 dark:hover:bg-dp-750 border border-slate-300 dark:border-dp-700 text-slate-800 dark:text-white flex items-center justify-center font-bold text-sm transition-all focus:outline-none select-none active:scale-90">
                                                -
                                            </button>
                                            <input type="number" id="qty-{{ $p->id }}" min="0" step="0.01" placeholder="0"
                                                oninput="updateItemQty({{ $p->id }}, this.value)"
                                                data-name="{{ $p->name }}" data-unit="{{ $p->price_unit }}"
                                                class="w-16 sm:w-20 bg-slate-50 dark:bg-dp-850 border border-slate-300 dark:border-dp-700 text-slate-900 dark:text-white rounded-lg py-1 px-1.5 text-center text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none font-bold">
                                            <button type="button" onclick="adjustQty({{ $p->id }}, 1)" 
                                                class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-dp-800 hover:bg-slate-200 dark:hover:bg-dp-750 border border-slate-300 dark:border-dp-700 text-slate-800 dark:text-white flex items-center justify-center font-bold text-sm transition-all focus:outline-none select-none active:scale-90">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="emptySearch" class="hidden text-center text-slate-500 py-12 bg-white dark:bg-dp-900">
                        <span class="text-3xl block mb-2">🔍</span>
                        Tidak ada produk yang cocok dengan pencarian Anda.
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan (25%): Ringkasan Request & Riwayat (Sticky) --}}
        <div class="space-y-6">
            <div class="sticky top-4 space-y-4">
                
                {{-- Card Ringkasan --}}
                <div class="bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700/60 rounded-2xl p-4 shadow-md">
                    <h2 class="text-slate-900 dark:text-white font-bold text-base mb-3 flex items-center gap-2">
                        <span class="text-emerald-500">📬</span> Ringkasan Permintaan
                    </h2>
                    
                    {{-- Selected items container --}}
                    <div id="summaryItemsList" class="space-y-2 max-h-52 overflow-y-auto pr-1 scrollbar-thin">
                        {{-- Dihasilkan JS --}}
                    </div>
                    <div id="emptySummaryText" class="text-center text-slate-500 py-5 border border-dashed border-slate-200 dark:border-dp-700 rounded-xl bg-slate-50/50 dark:bg-dp-950/20">
                        <p class="text-xs font-medium">Belum ada barang yang di-request.</p>
                        <p class="text-[10px] text-slate-400 mt-1">Masukkan jumlah pada tabel produk.</p>
                    </div>

                    {{-- Form Notes & Keterangan --}}
                    <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
                        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Total Item:</span>
                            <span id="summaryTotalItems" class="font-bold text-slate-900 dark:text-white">0 produk</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Total Kuantitas:</span>
                            <span id="summaryTotalQty" class="font-extrabold text-emerald-600 dark:text-emerald-400">0</span>
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-450 mb-1">Catatan / Keterangan Tambahan</label>
                            <textarea id="requestNotes" rows="2" placeholder="Contoh: Butuh mendesak, stok kurma madu sisa sedikit..."
                                class="w-full bg-slate-50 dark:bg-dp-850 border border-slate-300 dark:border-dp-700 text-slate-900 dark:text-white rounded-xl px-3 py-2 text-xs placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 focus:outline-none resize-none shadow-inner"></textarea>
                        </div>

                        <div class="flex gap-2 pt-1.5">
                            <button onclick="submitRequest()" id="submitRequestBtn" disabled
                                class="flex-1 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-30 disabled:hover:bg-emerald-600 disabled:scale-100 text-white py-2.5 rounded-xl font-bold transition-all hover:scale-[1.02] shadow-sm flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer disabled:cursor-not-allowed text-xs">
                                <span>📬</span> Kirim Admin
                            </button>

                            <button onclick="sendWaReport()" id="waReportBtn" disabled style="background-color: #25D366;"
                                class="flex-1 text-white py-2.5 rounded-xl font-bold transition-all hover:scale-[1.02] hover:opacity-90 disabled:opacity-30 disabled:hover:scale-100 shadow-sm flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer disabled:cursor-not-allowed text-xs">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.504-5.714-1.465L0 24zm6.59-4.846c1.6.95 3.197 1.451 4.793 1.453 5.485.002 9.948-4.41 9.95-9.825.001-2.624-1.013-5.09-2.861-6.942-1.848-1.853-4.309-2.873-6.932-2.874-5.49 0-9.953 4.411-9.956 9.827-.001 1.714.471 3.391 1.364 4.882l-.999 3.65 3.743-.971zm11.367-6.425c-.279-.14-1.651-.814-1.906-.907-.255-.094-.441-.14-.627.14-.186.28-.718.907-.88 1.092-.162.186-.325.21-.604.07-.279-.14-1.18-.435-2.247-1.387-.83-.74-1.39-1.653-1.553-1.932-.162-.28-.017-.43.122-.569.124-.125.279-.325.418-.488.14-.162.186-.28.279-.465.093-.186.046-.349-.023-.488-.069-.14-.627-1.511-.86-2.07-.227-.546-.477-.473-.651-.482-.167-.008-.36-.01-.553-.01-.193 0-.507.073-.772.36-.265.287-1.011.987-1.011 2.406s1.025 2.793 1.168 2.98c.143.19 2.017 3.08 4.886 4.318.682.295 1.214.471 1.629.603.686.218 1.31.187 1.803.114.549-.08 1.651-.676 1.883-1.328.232-.653.232-1.213.162-1.328-.069-.115-.255-.187-.534-.327z"/>
                                </svg>
                                Laporan WA
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Card Riwayat --}}
                <div class="bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700/60 rounded-2xl p-5 shadow-md">
                    <h2 class="text-slate-900 dark:text-white font-bold text-sm mb-4 flex items-center gap-2">
                        <span>📜</span> Riwayat Request Cabang
                    </h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        @forelse($myRequests as $req)
                        <div class="border border-slate-150 dark:border-dp-800/40 bg-slate-50/50 dark:bg-dp-950/30 rounded-xl p-3 hover:bg-slate-100/50 dark:hover:bg-dp-800/30 transition-colors">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <span class="text-slate-800 dark:text-white font-mono text-xs font-bold">{{ $req->transfer_code }}</span>
                                @php
                                    $badgeClasses = match($req->status) {
                                        'requested' => 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-750 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-500/30',
                                        'pending'   => 'bg-blue-100 dark:bg-blue-500/20 text-blue-750 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30',
                                        'approved'  => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-750 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30',
                                        'rejected'  => 'bg-rose-100 dark:bg-red-500/20 text-rose-750 dark:text-red-400 border border-rose-200 dark:border-red-500/30',
                                        default     => 'bg-slate-100 dark:bg-gray-500/20 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-500/30',
                                    };
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $badgeClasses }}">
                                    {{ $req->statusLabel }}
                                </span>
                            </div>
                            <div class="text-slate-600 dark:text-slate-400 text-xs space-y-1 my-2">
                                @foreach($req->items->take(2) as $item)
                                <div>• {{ $item->product->name }} ({{ number_format($item->quantity, 2, ',', '.') }} {{ $item->unit }})</div>
                                @endforeach
                                @if($req->items->count() > 2)
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 italic">+{{ $req->items->count() - 2 }} produk lainnya</div>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-150 dark:border-slate-800">
                                <span class="text-slate-400 dark:text-slate-500 text-[10px]">{{ $req->created_at->diffForHumans() }}</span>
                                @if(in_array($req->status, ['requested']))
                                <button onclick="cancelRequest({{ $req->id }}, '{{ $req->transfer_code }}')"
                                        class="text-rose-600 hover:text-rose-500 dark:text-red-400 dark:hover:text-red-300 text-xs font-semibold">Batalkan</button>
                                @endif
                            </div>
                            @if($req->rejection_reason)
                            <div class="mt-2 bg-rose-50 dark:bg-red-500/10 rounded-lg px-2.5 py-1.5 text-rose-700 dark:text-red-400 text-xs border border-rose-100 dark:border-red-950/20">
                                <span class="font-bold block mb-0.5 text-[9px] uppercase tracking-wide">Alasan Ditolak:</span>
                                {{ $req->rejection_reason }}
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center text-slate-400 py-6 text-xs">
                            Belum ada riwayat request
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>
    @endif
</div>

{{-- Success Modal --}}
<div id="successModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-200 dark:border-emerald-500/30 rounded-2xl shadow-2xl w-full max-w-sm text-center p-8">
        <div class="text-5xl mb-4">✅</div>
        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Request Terkirim!</h3>
        <p class="text-slate-600 dark:text-slate-350 text-sm mb-2" id="successCode"></p>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Admin akan segera memproses permintaan Anda.</p>
        <button onclick="location.reload()" class="mt-6 w-full bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 rounded-xl font-bold transition-colors focus:outline-none">
            OK
        </button>
    </div>
</div>

{{-- Cancel Confirm Modal --}}
<div id="cancelConfirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm text-center p-8 transform transition-all duration-300 scale-95 opacity-0 active-modal-cancel">
        <div class="text-5xl mb-4 animate-bounce">🚫</div>
        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Batalkan Request</h3>
        <p class="text-slate-500 dark:text-slate-400 text-xs px-2 mb-6">
            Apakah Anda yakin ingin membatalkan request stok <span id="cancelRequestCode" class="font-mono font-bold text-slate-900 dark:text-white"></span> ini?
        </p>
        <div class="flex gap-3">
            <button onclick="closeCancelModal()" 
                    class="flex-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-250 dark:hover:bg-slate-600 text-slate-750 dark:text-slate-200 py-2.5 rounded-xl text-sm font-bold transition-colors focus:outline-none">
                Batal
            </button>
            <button id="confirmCancelBtn" onclick="confirmCancel()" 
                    class="flex-1 bg-rose-600 hover:bg-rose-505 text-white py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm focus:outline-none">
                Ya, Batalkan
            </button>
        </div>
    </div>
</div>

<script>
let requestItems = {};
let activeFilterTab = 'all'; // 'all', 'low', 'inputted'

function showToast(msg, type = 'success') {
    if (window.showToast) {
        window.showToast(msg, type);
    } else {
        alert(msg);
    }
}

// Menangani perubahan kuantitas manual melalui input
function updateItemQty(productId, val) {
    const input = document.getElementById(`qty-${productId}`);
    if (!input) return;

    let qty = parseFloat(val) || 0;
    if (qty < 0) qty = 0;
    
    const row = document.getElementById(`row-${productId}`);
    
    // update data
    if (qty > 0) {
        requestItems[productId] = {
            product_id: productId,
            name: input.dataset.name,
            unit: input.dataset.unit,
            quantity: qty
        };
        // Berikan highlight hijau tipis pada row
        if (row) {
            row.classList.add('bg-emerald-50/50', 'dark:bg-emerald-950/15', 'border-l-2', 'border-l-emerald-550');
        }
    } else {
        delete requestItems[productId];
        if (row) {
            row.classList.remove('bg-emerald-50/50', 'dark:bg-emerald-950/15', 'border-l-2', 'border-l-emerald-550');
        }
    }

    input.value = qty > 0 ? qty : ''; // Tampilkan kosong jika 0 untuk kenyamanan mengetik
    
    renderSummary();
    updateInputtedBadge();
    
    // Jika sedang dalam tab 'inputted', saring ulang
    if (activeFilterTab === 'inputted') {
        filterProducts();
    }
}

// Menangani klik tombol + dan -
function adjustQty(productId, delta) {
    const input = document.getElementById(`qty-${productId}`);
    if (!input) return;

    let current = parseFloat(input.value) || 0;
    let qty = current + delta;
    if (qty < 0) qty = 0;

    // Untuk integer, bulatkan hasil
    if (qty % 1 === 0) qty = parseInt(qty);
    else qty = parseFloat(qty.toFixed(2));

    updateItemQty(productId, qty);
}

// Menghapus request dari panel ringkasan
function removeItemFromSummary(productId) {
    updateItemQty(productId, 0);
}

// Mengupdate badge jumlah produk yang sudah di-input pada tab
function updateInputtedBadge() {
    const count = Object.keys(requestItems).length;
    const badge = document.getElementById('inputtedBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// Render data terpilih ke panel ringkasan
function renderSummary() {
    const container = document.getElementById('summaryItemsList');
    const emptyText = document.getElementById('emptySummaryText');
    const submitBtn = document.getElementById('submitRequestBtn');
    const waReportBtn = document.getElementById('waReportBtn');
    
    const items = Object.values(requestItems);
    const ids = Object.keys(requestItems);

    if (ids.length === 0) {
        container.innerHTML = '';
        emptyText.classList.remove('hidden');
        submitBtn.disabled = true;
        if (waReportBtn) waReportBtn.disabled = true;
        
        document.getElementById('summaryTotalItems').textContent = '0 produk';
        document.getElementById('summaryTotalQty').textContent = '0';
        return;
    }

    emptyText.classList.add('hidden');
    submitBtn.disabled = false;
    if (waReportBtn) waReportBtn.disabled = false;

    let totalQty = 0;
    
    container.innerHTML = items.map(item => {
        totalQty += item.quantity;
        return `
        <div class="flex items-center justify-between bg-slate-50 dark:bg-dp-950/50 border border-slate-200 dark:border-dp-800 rounded-xl px-3.5 py-2">
            <div class="flex-1 min-w-0 pr-2">
                <p class="text-slate-800 dark:text-white text-xs font-bold truncate">${item.name}</p>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">${item.quantity} ${item.unit}</p>
            </div>
            <button onclick="removeItemFromSummary(${item.product_id})" 
                class="text-red-500 hover:text-red-400 font-bold text-lg leading-none p-1 transition-colors focus:outline-none" title="Hapus">
                ×
            </button>
        </div>
        `;
    }).join('');

    document.getElementById('summaryTotalItems').textContent = `${ids.length} produk`;
    document.getElementById('summaryTotalQty').textContent = totalQty % 1 === 0 ? totalQty : totalQty.toFixed(2);
}

// Menangani pergantian Tab Filter Cepat
function setFilterTab(tabName) {
    activeFilterTab = tabName;
    
    // Update styling tab aktif
    const tabs = {
        all: document.getElementById('tab-all'),
        low: document.getElementById('tab-low'),
        inputted: document.getElementById('tab-inputted')
    };

    Object.keys(tabs).forEach(k => {
        if (k === tabName) {
            tabs[k].className = "px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-emerald-600 text-white shadow-sm";
        } else {
            tabs[k].className = "px-4 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 dark:text-purple-300 hover:text-slate-900 dark:hover:text-white";
        }
    });

    filterProducts();
}

// Filter Pencarian & Tab Status secara gabungan
function filterProducts() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#productTableBody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.dataset.name;
        const category = row.dataset.category;
        const stock = parseFloat(row.dataset.stock) || 0;
        const id = parseInt(row.dataset.id);
        
        // Cek filter pencarian teks
        const matchesSearch = name.includes(query) || category.includes(query);
        
        // Cek filter tab status
        let matchesTab = true;
        if (activeFilterTab === 'low') {
            matchesTab = stock <= 10;
        } else if (activeFilterTab === 'inputted') {
            matchesTab = requestItems[id] !== undefined && requestItems[id].quantity > 0;
        }

        if (matchesSearch && matchesTab) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });

    const emptyMsg = document.getElementById('emptySearch');
    if (visibleCount === 0) {
        emptyMsg.classList.remove('hidden');
    } else {
        emptyMsg.classList.add('hidden');
    }
}

// Mengirim request stok
function submitRequest() {
    const items = Object.values(requestItems).filter(i => i.quantity > 0);
    if (items.length === 0) { 
        showToast('Kuantitas request belum diisi!', 'warning'); 
        return; 
    }

    const btn = document.getElementById('submitRequestBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Mengirim...';

    fetch('/kasir/stock-request', {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
            'Content-Type': 'application/json', 
            'Accept': 'application/json' 
        },
        body: JSON.stringify({ 
            notes: document.getElementById('requestNotes').value, 
            items: items.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successCode').textContent = `Kode Transfer: ${data.transfer.code}`;
            document.getElementById('successModal').classList.remove('hidden');
            requestItems = {};
            renderSummary();
        } else {
            showToast('Gagal: ' + data.message, 'error');
            btn.disabled = false;
            btn.textContent = '📬 Kirim Permintaan ke Admin';
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan koneksi!', 'error');
        btn.disabled = false;
        btn.textContent = '📬 Kirim Permintaan ke Admin';
    });
}

// Batal Request Logika Modal
let currentCancelId = null;
let currentCancelCode = null;

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

function cancelRequest(id, code) {
    currentCancelId = id;
    currentCancelCode = code;
    document.getElementById('cancelRequestCode').textContent = code;
    showModal('cancelConfirmModal', 'active-modal-cancel');
}

function closeCancelModal() {
    closeModal('cancelConfirmModal', 'active-modal-cancel');
    currentCancelId = null;
    currentCancelCode = null;
}

function confirmCancel() {
    if (!currentCancelId) return;
    const btn = document.getElementById('confirmCancelBtn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Memproses...';
    }

    fetch(`/kasir/stock-request/${currentCancelId}/cancel`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
            'Accept': 'application/json' 
        }
    })
    .then(r => r.json())
    .then(data => {
        closeCancelModal();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Ya, Batalkan';
            }
        }
    })
    .catch(err => {
        closeCancelModal();
        showToast('Terjadi kesalahan koneksi!', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Ya, Batalkan';
        }
    });
}

function sendWaReport() {
    const items = Object.values(requestItems).filter(i => i.quantity > 0);
    if (items.length === 0) {
        showToast('Kuantitas request belum diisi!', 'warning');
        return;
    }

    const branchName = @json($myLocation?->name ?? $kasir->branch);
    const today = new Date();
    const dateStr = String(today.getDate()).padStart(2, '0') + '-' + 
                    String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                    today.getFullYear();

    let msg = `*LAPORAN REQUEST STOK*\n`;
    msg += `*Cabang:* ${branchName}\n`;
    msg += `*Tanggal:* ${dateStr}\n\n`;
    msg += `*Daftar Barang:*\n`;
    items.forEach((item, idx) => {
        const qtyStr = Number(item.quantity).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        msg += `${idx + 1}. *${item.name}* - ${qtyStr} ${item.unit}\n`;
    });

    const notes = document.getElementById('requestNotes').value.trim();
    msg += `\n*Catatan:* ${notes ? notes : '-'}\n\n`;
    msg += `_Laporan ini dibuat otomatis melalui sistem POS Pusat Kurma._`;

    const waNumber = @json(config('app.whatsapp_report_number', ''));
    let url = '';
    if (waNumber) {
        let number = waNumber.replace(/[^0-9]/g, '');
        if (number.startsWith('0')) {
            number = '62' + number.slice(1);
        }
        url = `https://api.whatsapp.com/send?phone=${number}&text=${encodeURIComponent(msg)}`;
    } else {
        url = `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    }
    window.open(url, '_blank');
}
</script>
@endsection
