<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 dark:bg-dp-800 rounded-xl">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">Pesanan Online</h2>
                    <p class="text-xs text-slate-400 font-medium">Kelola semua pesanan dari toko online</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-600 flex items-center gap-1 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Dashboard
            </a>
        </div>
    </x-slot>

    <div x-data="{
        showDeleteModal: false,
        showStatusModal: false,
        selectedOrderId: null,
        selectedOrderCode: '',
        newStatus: '',
        loading: false,

        openDelete(id, code) {
            this.selectedOrderId = id;
            this.selectedOrderCode = code;
            this.showDeleteModal = true;
        },
        openStatus(id, code, status) {
            this.selectedOrderId = id;
            this.selectedOrderCode = code;
            this.newStatus = status;
            this.showStatusModal = true;
        },
        executeDelete() {
            this.loading = true;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/admin/orders/' + this.selectedOrderId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(data => {
                this.loading = false;
                this.showDeleteModal = false;
                if (data.success) {
                    if (window.showToast) window.showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (window.showToast) window.showToast(data.message, 'error');
                }
            }).catch(() => { this.loading = false; });
        },
        executeStatusUpdate() {
            this.loading = true;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/admin/orders/' + this.selectedOrderId + '/status', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: this.newStatus })
            }).then(r => r.json()).then(data => {
                this.loading = false;
                this.showStatusModal = false;
                if (data.success) {
                    if (window.showToast) window.showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (window.showToast) window.showToast(data.message, 'error');
                }
            }).catch(() => { this.loading = false; });
        }
    }" class="flex flex-col gap-6 max-w-full">

        {{-- ── Stats Cards ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $statusCards = [
                    ['key'=>'all',     'label'=>'Total Pesanan',  'icon'=>'📋', 'bg'=>'bg-slate-50',   'text'=>'text-slate-700',   'border'=>'border-slate-200'],
                    ['key'=>'pending', 'label'=>'Menunggu Bayar', 'icon'=>'⏳', 'bg'=>'bg-amber-50',   'text'=>'text-amber-700',   'border'=>'border-amber-200'],
                    ['key'=>'paid',    'label'=>'Sudah Lunas',    'icon'=>'✅', 'bg'=>'bg-emerald-50', 'text'=>'text-emerald-700', 'border'=>'border-emerald-200'],
                    ['key'=>'failed',  'label'=>'Gagal/Batal',   'icon'=>'❌', 'bg'=>'bg-rose-50',    'text'=>'text-rose-700',    'border'=>'border-rose-200'],
                ];
            @endphp
            @foreach($statusCards as $card)
                <a href="{{ route('admin.orders.index', ['status'=>$card['key'], 'search'=>$search]) }}"
                   class="block {{ $card['bg'] }} border {{ $card['border'] }} rounded-2xl p-4 shadow-sm transition hover:shadow-md {{ $status === $card['key'] ? 'ring-2 ring-offset-1 ring-emerald-400' : '' }}">
                    <div class="text-2xl mb-1">{{ $card['icon'] }}</div>
                    <div class="font-black text-2xl {{ $card['text'] }}">{{ $counts[$card['key']] }}</div>
                    <div class="text-xs font-semibold text-slate-500 mt-0.5">{{ $card['label'] }}</div>
                </a>
            @endforeach
        </div>

        {{-- ── Search & Filter Bar ── --}}
        <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari kode pesanan, nama, atau nomor HP..."
                           class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:border-emerald-400 focus:ring-emerald-400 bg-slate-50 dark:bg-dp-700 dark:border-dp-600 dark:text-purple-100">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow transition whitespace-nowrap">
                    🔍 Cari
                </button>
                @if($search || $status !== 'all')
                    <a href="{{ route('admin.orders.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold rounded-xl transition text-center whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- ── Status Filter Tabs ── --}}
        <div class="flex gap-2 flex-wrap">
            @foreach([['all','Semua','slate'],['pending','Pending','amber'],['paid','Paid','emerald'],['failed','Gagal','rose']] as [$key,$label,$color])
                <a href="{{ route('admin.orders.index', ['status'=>$key, 'search'=>$search]) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-bold border transition
                       {{ $status === $key
                           ? 'bg-'.($key==='all'?'slate':$color).'-600 text-white border-transparent shadow'
                           : 'bg-white text-slate-600 border-slate-200 hover:border-'.($key==='all'?'slate':$color).'-300' }}">
                    {{ $label }} ({{ $counts[$key] }})
                </a>
            @endforeach
        </div>

        {{-- ── Orders Table ── --}}
        <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl shadow-sm overflow-hidden">
            @if($orders->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="text-5xl mb-4">📭</div>
                    <h3 class="font-bold text-slate-600 dark:text-purple-200 text-base">Tidak ada pesanan ditemukan</h3>
                    <p class="text-sm text-slate-400 mt-1">Coba ubah filter atau kata kunci pencarian</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-dp-700 border-b border-slate-100 dark:border-dp-600">
                                <th class="text-left px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide">Kode Pesanan</th>
                                <th class="text-left px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide">Pelanggan</th>
                                <th class="text-left px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide hidden md:table-cell">Kurir</th>
                                <th class="text-right px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide">Total</th>
                                <th class="text-center px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide">Status</th>
                                <th class="text-left px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide hidden lg:table-cell">Tanggal</th>
                                <th class="text-center px-4 py-3 font-bold text-slate-600 dark:text-purple-300 text-xs uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-dp-700">
                            @foreach($orders as $order)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-dp-700/50 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 text-xs">{{ $order->order_code }}</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-slate-800 dark:text-purple-100 text-sm">{{ $order->customer_name }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">{{ $order->customer_phone }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 hidden md:table-cell">
                                        <span class="text-xs font-semibold text-slate-600 dark:text-purple-300">{{ $order->shipping_courier }} {{ $order->shipping_service }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="font-black text-slate-800 dark:text-purple-100 text-sm">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($order->payment_status === 'paid')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">✅ LUNAS</span>
                                        @elseif($order->payment_status === 'pending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">⏳ PENDING</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-bold">❌ GAGAL</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 hidden lg:table-cell text-xs text-slate-400 font-medium">
                                        {{ $order->created_at->format('d M Y') }}<br>
                                        <span class="text-slate-300">{{ $order->created_at->format('H:i') }} WIB</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center justify-center gap-1.5">
                                            {{-- Detail --}}
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition" title="Lihat Detail">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                            {{-- Tandai Lunas (jika pending) --}}
                                            @if($order->payment_status === 'pending')
                                                <button @click="openStatus({{ $order->id }}, '{{ $order->order_code }}', 'paid')"
                                                        class="p-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg transition" title="Tandai Lunas">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- Tandai Gagal (jika pending) --}}
                                            @if($order->payment_status === 'pending')
                                                <button @click="openStatus({{ $order->id }}, '{{ $order->order_code }}', 'failed')"
                                                        class="p-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition" title="Tandai Gagal">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- Hapus --}}
                                            @if($order->payment_status !== 'paid')
                                                <button @click="openDelete({{ $order->id }}, '{{ $order->order_code }}')"
                                                        class="p-1.5 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg transition" title="Hapus Pesanan">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
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
                @if($orders->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- ── Modal: Konfirmasi Hapus ── --}}
        <div x-show="showDeleteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display:none;">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal=false"></div>
            <div class="relative bg-white dark:bg-dp-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                <div class="text-center mb-4">
                    <div class="text-4xl mb-2">🗑️</div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">Hapus Pesanan?</h3>
                    <p class="text-sm text-slate-500 mt-1">Pesanan <strong x-text="selectedOrderCode"></strong> akan dihapus permanen.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showDeleteModal=false" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition">Batal</button>
                    <button @click="executeDelete()" :disabled="loading"
                            class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition">
                        <span x-show="!loading">Ya, Hapus</span>
                        <span x-show="loading">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Modal: Konfirmasi Update Status ── --}}
        <div x-show="showStatusModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display:none;">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showStatusModal=false"></div>
            <div class="relative bg-white dark:bg-dp-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                <div class="text-center mb-4">
                    <div class="text-4xl mb-2" x-text="newStatus==='paid' ? '✅' : '❌'"></div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">
                        <span x-text="newStatus==='paid' ? 'Tandai Pesanan Lunas?' : 'Tandai Pesanan Gagal?'"></span>
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">Pesanan <strong x-text="selectedOrderCode"></strong> akan diperbarui statusnya.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showStatusModal=false" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition">Batal</button>
                    <button @click="executeStatusUpdate()" :disabled="loading"
                            :class="newStatus==='paid' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                            class="flex-1 py-2.5 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition">
                        <span x-show="!loading" x-text="newStatus==='paid' ? 'Ya, Tandai Lunas' : 'Ya, Tandai Gagal'"></span>
                        <span x-show="loading">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
