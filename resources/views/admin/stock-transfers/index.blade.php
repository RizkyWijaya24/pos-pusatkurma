@php
    $statusColors = [
        'requested' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
        'pending'   => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30',
        'approved'  => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
        'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
        'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-900/20 dark:text-slate-400 dark:border-slate-800/30',
    ];
    $statusLabels = [
        'requested' => '⏳ Menunggu Proses',
        'pending'   => '🔵 Diproses Admin',
        'approved'  => '✅ Disetujui',
        'rejected'  => '❌ Ditolak',
        'cancelled' => '🚫 Dibatalkan',
    ];
@endphp

@extends('layouts.app')

@section('title', 'Transfer Stok Antar Cabang')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-3">
                <span class="text-3xl">📦</span> Manajemen Transfer Stok
            </h1>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium">Distribusi stok dari gudang ke cabang-cabang</p>
        </div>
        <div class="flex gap-3">
            @if($pendingCount > 0)
            <div class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 px-4 py-2 rounded-xl text-sm font-semibold animate-pulse">
                <span>⏳</span> {{ $pendingCount }} Menunggu Proses
            </div>
            @endif
            <a href="{{ route('admin.stock-transfers.create') }}"
               class="bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition duration-150">
                <span>+ Buat Transfer Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-5 shadow-md">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Status</label>
                <select name="status" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                    <option value="">Semua Status</option>
                    @foreach(['requested' => '⏳ Menunggu', 'pending' => '🔵 Diproses', 'approved' => '✅ Disetujui', 'rejected' => '❌ Ditolak', 'cancelled' => '🚫 Dibatalkan'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Dari Lokasi</label>
                <select name="from" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                    <option value="">Semua</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('from') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">Ke Lokasi</label>
                <select name="to" class="bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                    <option value="">Semua</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('to') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
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
            @if(request()->hasAny(['status','from','to','date']))
            <a href="{{ route('admin.stock-transfers.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 text-xs font-bold rounded-xl transition duration-150">
                ✕ Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Transfer Table --}}
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl shadow-md overflow-hidden">
        @if($transfers->isEmpty())
        <div class="text-center py-16">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-slate-400 dark:text-purple-400 text-lg font-semibold">Belum ada transfer stok</p>
            <a href="{{ route('admin.stock-transfers.create') }}" class="mt-4 inline-block text-emerald-600 hover:text-emerald-500 font-bold underline">
                Buat transfer pertama
            </a>
        </div>
        @else
        <div class="overflow-x-auto w-full max-w-full">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
                <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
                    <tr class="border-b border-slate-100 dark:border-dp-700">
                        <th class="px-5 py-4">Kode Transfer</th>
                        <th class="px-4 py-4">Dari → Ke</th>
                        <th class="px-4 py-4">Produk</th>
                        <th class="px-4 py-4">Dibuat Oleh</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Tanggal</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
                    @foreach($transfers as $transfer)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150">
                        <td class="px-5 py-4">
                            <span class="text-emerald-800 dark:text-emerald-300 font-mono font-bold text-xs bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg border border-emerald-100 dark:border-emerald-900/30 shadow-inner">
                                {{ $transfer->transfer_code }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-700 dark:text-purple-200">
                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span class="bg-slate-100 text-slate-700 dark:bg-dp-700 dark:text-purple-300 px-2.5 py-1 rounded-lg border border-slate-200/50 dark:border-dp-600/40">
                                    {{ $transfer->fromLocation?->name ?? 'Pembelian Baru' }}
                                </span>
                                <span class="text-slate-400">→</span>
                                <span class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300 px-2.5 py-1 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                                    {{ $transfer->toLocation->name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-slate-700 dark:text-purple-200 text-xs space-y-1">
                                @foreach($transfer->items->take(2) as $item)
                                <div class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 border border-emerald-100/50 dark:border-emerald-900/20 mr-1 mb-1">
                                    {{ $item->product->name }} ({{ number_format($item->quantity, 2, ',', '.') }} {{ $item->unit }})
                                </div>
                                @endforeach
                                @if($transfer->items->count() > 2)
                                <div class="text-slate-400 dark:text-purple-400 text-[10px] italic">+{{ $transfer->items->count() - 2 }} produk lainnya</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-xs">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 flex items-center justify-center text-[10px] font-extrabold shrink-0 shadow-inner">
                                    {{ strtoupper(substr($transfer->requester->name, 0, 1)) }}
                                </span>
                                <span>
                                    <div class="text-slate-800 dark:text-purple-100 font-semibold leading-tight">{{ $transfer->requester->name }}</div>
                                    <div class="text-slate-400 dark:text-purple-400 text-[9px] uppercase font-bold tracking-wider mt-0.5">{{ $transfer->requester->role }}</div>
                                </span>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusColors[$transfer->status] ?? '' }}">
                                {{ $statusLabels[$transfer->status] ?? $transfer->status }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-500 dark:text-purple-400 text-xs">
                            <div>{{ $transfer->created_at->translatedFormat('d M Y') }}</div>
                            <div class="text-slate-400 dark:text-purple-500 text-[10px] font-semibold mt-0.5">{{ $transfer->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.stock-transfers.show', $transfer->id) }}"
                                   class="text-slate-600 dark:text-purple-300 hover:text-slate-900 dark:hover:text-white transition-colors text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-dp-700 hover:bg-slate-200 dark:hover:bg-dp-600 shadow-sm border border-slate-200/50 dark:border-dp-600/30">
                                    Detail
                                </a>
                                @if(in_array($transfer->status, ['requested', 'pending']))
                                <button onclick="approveTransfer({{ $transfer->id }}, '{{ $transfer->transfer_code }}')"
                                        class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-200 transition-colors text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-100 dark:border-emerald-900/30 shadow-sm">
                                    ✓ Approve
                                </button>
                                <button onclick="rejectTransfer({{ $transfer->id }}, '{{ $transfer->transfer_code }}')"
                                        class="text-rose-700 dark:text-rose-400 hover:text-rose-900 dark:hover:text-rose-300 transition-colors text-xs font-bold px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-100 dark:border-rose-900/30 shadow-sm">
                                    ✗ Tolak
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transfers->hasPages())
        <div class="bg-slate-50/50 dark:bg-dp-900/50 border-t border-slate-100 dark:border-dp-700 px-5 py-4">
            {{ $transfers->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all duration-300 scale-95 opacity-0 active-modal-reject">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-purple-100 mb-2">❌ Tolak Transfer</h3>
            <p class="text-slate-500 dark:text-purple-400 text-sm mb-4">Alasan penolakan (wajib):</p>
            <textarea id="rejectionReason" rows="3" placeholder="Contoh: Stok gudang tidak mencukupi untuk saat ini..." required
                class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none"></textarea>
            <div class="flex gap-3 mt-4">
                <button onclick="closeRejectModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:text-purple-300 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                    Batal
                </button>
                <button onclick="confirmReject()" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white py-2.5 rounded-xl text-sm font-semibold transition-colors">
                    Konfirmasi Tolak
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Approve Modal --}}
<div id="approveModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all duration-300 scale-95 opacity-0 active-modal-approve">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 text-3xl mb-4 animate-bounce">
                ✅
            </div>
            <h3 class="text-xl font-black text-slate-800 dark:text-purple-100 mb-2">Konfirmasi Approve</h3>
            <p class="text-slate-500 dark:text-purple-300 text-xs font-semibold leading-relaxed px-2">
                Apakah Anda yakin ingin menyetujui transfer stok <span id="approveTransferCode" class="font-mono font-bold text-slate-800 dark:text-purple-100"></span>? <br>Stok produk akan segera dipindahkan antar lokasi secara otomatis.
            </p>
            <div class="flex gap-3 mt-6">
                <button onclick="closeApproveModal()" 
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-dp-700 dark:text-purple-200 hover:dark:bg-dp-600 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors">
                    Batal
                </button>
                <button id="confirmApproveBtn" onclick="confirmApprove()" 
                        class="flex-1 bg-emerald-700 hover:bg-emerald-800 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-md shadow-emerald-700/10">
                    Setujui
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const rolePrefix = "{{ Auth::user()->role }}";
let currentTransferId = null;
let currentTransferCode = null;

function showToast(msg, type = 'success') {
    if (window.showToast) {
        window.showToast(msg, type);
    } else {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-xl text-white font-semibold shadow-2xl transition-all text-sm max-w-sm
            ${type === 'success' ? 'bg-emerald-600' : 'bg-red-600'}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
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

// Approve functions
function approveTransfer(id, code) {
    currentTransferId = id;
    currentTransferCode = code;
    document.getElementById('approveTransferCode').textContent = code;
    showModal('approveModal', 'active-modal-approve');
}

function closeApproveModal() {
    closeModal('approveModal', 'active-modal-approve');
    currentTransferId = null;
    currentTransferCode = null;
}

function confirmApprove() {
    if (!currentTransferId) return;
    const btn = document.getElementById('confirmApproveBtn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ MEMPROSES...';
    }

    fetch(`/${rolePrefix}/stock-transfers/${currentTransferId}/approve`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        closeApproveModal();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'SETUJUI';
            }
        }
    })
    .catch(err => {
        closeApproveModal();
        showToast('Terjadi kesalahan koneksi!', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'SETUJUI';
        }
    });
}

// Reject functions
function rejectTransfer(id, code) {
    currentTransferId = id;
    document.getElementById('rejectionReason').value = '';
    showModal('rejectModal', 'active-modal-reject');
}

function closeRejectModal() {
    closeModal('rejectModal', 'active-modal-reject');
    currentTransferId = null;
}

function confirmReject() {
    if (!currentTransferId) return;
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

    fetch(`/${rolePrefix}/stock-transfers/${currentTransferId}/reject`, {
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
