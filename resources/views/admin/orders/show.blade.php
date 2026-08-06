<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.index') }}"
                   class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">
                        Detail Pesanan
                    </h2>
                    <p class="text-xs font-mono text-emerald-600 font-bold">{{ $order->order_code }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($order->payment_status === 'paid')
                    <span class="px-3 py-1.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">✅ LUNAS</span>
                @elseif($order->payment_status === 'pending')
                    <span class="px-3 py-1.5 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">⏳ PENDING</span>
                @else
                    <span class="px-3 py-1.5 bg-rose-100 text-rose-800 rounded-full text-xs font-bold">❌ GAGAL</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="{
        showStatusModal: false,
        newStatus: '',
        loading: false,
        checkingPayment: false,
        openStatus(status) { this.newStatus = status; this.showStatusModal = true; },
        executeStatus() {
            this.loading = true;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/admin/orders/{{ $order->id }}/status', {
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
        },
        autoCheckPayment() {
            @if($order->payment_status === 'pending' && config('doku.enabled'))
            this.checkingPayment = true;
            fetch('/admin/orders/{{ $order->id }}/check-payment', {
                headers: { 'Accept': 'application/json' }
            }).then(r => r.json()).then(data => {
                this.checkingPayment = false;
                if (data.updated) {
                    if (window.showToast) window.showToast(data.message, data.payment_status === 'paid' ? 'success' : 'error');
                    setTimeout(() => location.reload(), 1500);
                }
            }).catch(() => { this.checkingPayment = false; });
            @endif
        }
    }" x-init="autoCheckPayment()" class="flex flex-col gap-6 max-w-4xl mx-auto">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Kolom Kiri: Info Utama ── --}}
            <div class="lg:col-span-2 flex flex-col gap-6">

                {{-- Info Pelanggan --}}
                <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-slate-700 dark:text-purple-200 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-blue-50 rounded-lg text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </span>
                        Informasi Pelanggan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Nama</p>
                            <p class="font-bold text-slate-800 dark:text-purple-100">{{ $order->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Nomor HP / WA</p>
                            <p class="font-bold text-slate-800 dark:text-purple-100">{{ $order->customer_phone }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Email</p>
                            <p class="font-semibold text-slate-700 dark:text-purple-200 break-all">{{ $order->customer_email ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Tanggal Pesan</p>
                            <p class="font-semibold text-slate-700 dark:text-purple-200">{{ $order->created_at->format('d M Y, H:i') }} WIB</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Alamat Lengkap</p>
                            <p class="font-semibold text-slate-700 dark:text-purple-200">{{ $order->shipping_address }}</p>
                        </div>
                        @if($order->shipping_notes)
                        <div class="sm:col-span-2">
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Catatan Pengiriman</p>
                            <p class="font-semibold text-slate-600 italic">"{{ $order->shipping_notes }}"</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Info Pengiriman --}}
                <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-slate-700 dark:text-purple-200 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-amber-50 rounded-lg text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        </span>
                        Informasi Pengiriman
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Kurir</p>
                            <p class="font-bold text-slate-800 dark:text-purple-100">{{ $order->shipping_courier }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Layanan</p>
                            <p class="font-bold text-slate-800 dark:text-purple-100">{{ $order->shipping_service }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Estimasi</p>
                            <p class="font-semibold text-slate-700 dark:text-purple-200">{{ $order->shipping_etd ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Kota Tujuan</p>
                            <p class="font-semibold text-slate-700 dark:text-purple-200">{{ $order->destination_city_name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase mb-0.5">Ongkos Kirim</p>
                            <p class="font-bold text-amber-700">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Rincian Produk --}}
                <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-dp-700">
                        <h3 class="font-extrabold text-slate-700 dark:text-purple-200 text-sm uppercase tracking-wide flex items-center gap-2">
                            <span class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            </span>
                            Rincian Produk Dipesan
                        </h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-dp-700 text-xs">
                                <th class="text-left px-5 py-2.5 font-bold text-slate-500 dark:text-purple-300 uppercase">Produk</th>
                                <th class="text-center px-4 py-2.5 font-bold text-slate-500 dark:text-purple-300 uppercase">Qty</th>
                                <th class="text-right px-4 py-2.5 font-bold text-slate-500 dark:text-purple-300 uppercase">Harga</th>
                                <th class="text-right px-5 py-2.5 font-bold text-slate-500 dark:text-purple-300 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-dp-700">
                            @foreach($order->orderItems as $item)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-800 dark:text-purple-100">
                                            {{ $item->product ? $item->product->name : '(Produk dihapus)' }}
                                        </div>
                                        @if($item->product && $item->product->sku)
                                            <div class="text-xs text-slate-400 font-mono">{{ $item->product->sku }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="font-semibold text-slate-700 dark:text-purple-200">
                                            {{ $item->qty }}
                                            @if($item->product) <span class="text-xs text-slate-400">{{ $item->product->price_unit }}</span> @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="font-semibold text-slate-600 dark:text-purple-300">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="font-bold text-slate-800 dark:text-purple-100">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 dark:border-dp-600 bg-slate-50 dark:bg-dp-700">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-slate-500 dark:text-purple-300">Subtotal Produk</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-purple-200">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-5 py-2 text-right text-sm font-semibold text-amber-600">Ongkos Kirim ({{ $order->shipping_courier }} {{ $order->shipping_service }})</td>
                                <td class="px-5 py-2 text-right font-bold text-amber-700">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                            @if(($order->payment_fee ?? 0) > 0)
                            <tr>
                                <td colspan="3" class="px-5 py-2 text-right text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    Biaya Transaksi (DOKU)
                                    @if($order->payment_channel)
                                    <span class="text-xs font-normal ml-1 text-slate-400">
                                        — {{ match($order->payment_channel) {
                                            'QRIS'            => 'QRIS 0,7%',
                                            'VIRTUAL_ACCOUNT' => 'Transfer Bank Rp 4.000',
                                            'EMONEY'          => 'E-Wallet 1,5%',
                                            'RETAIL'          => 'Minimarket Rp 5.000',
                                            default           => $order->payment_channel,
                                        } }} — ditanggung pembeli
                                    </span>
                                    @endif
                                </td>
                                <td class="px-5 py-2 text-right font-bold text-blue-700 dark:text-blue-400">Rp {{ number_format($order->payment_fee, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right text-base font-extrabold text-slate-800 dark:text-purple-100">TOTAL PEMBAYARAN</td>
                                <td class="px-5 py-3 text-right font-black text-lg text-emerald-700 dark:text-emerald-400">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ── Kolom Kanan: Aksi & Status ── --}}
            <div class="flex flex-col gap-4">

                {{-- Progress Status Pesanan & Pembayaran --}}
                <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl p-5 shadow-sm mb-5">
                    <h3 class="font-extrabold text-slate-700 dark:text-purple-200 text-sm uppercase tracking-wide mb-3 flex items-center justify-between">
                        <span>Progress Status Pesanan</span>
                        <span class="text-xs font-mono font-bold text-emerald-600">Step {{ $order->step_number }}/5</span>
                    </h3>

                    {{-- Status Progress Select/Buttons --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Ubah Status Progress Pesanan:</label>
                        <select
                            onchange="window.updateOrderStatus(this.value)"
                            class="w-full bg-slate-50 dark:bg-dp-700 border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>⏳ 1. Menunggu Pembayaran</option>
                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>💳 2. Pembayaran Lunas</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>📦 3. Diproses &amp; Dikemas</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>🚚 4. Dalam Pengiriman (Dikirim)</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>🎉 5. Pesanan Selesai</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>❌ Dibatalkan / Gagal</option>
                        </select>
                    </div>

                    <h4 class="font-extrabold text-slate-700 dark:text-purple-200 text-xs uppercase tracking-wide mb-2">Status Pembayaran</h4>
                    
                    {{-- Indikator auto-check DOKU --}}
                    @if($order->payment_status === 'pending')
                    <div x-show="checkingPayment" x-cloak
                         class="mb-3 flex items-center gap-2 text-xs text-blue-600 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2">
                        <svg class="animate-spin h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Memverifikasi status pembayaran ke DOKU...</span>
                    </div>
                    @endif

                    <div class="mb-4 p-3 rounded-xl text-center
                        {{ $order->payment_status === 'paid' ? 'bg-emerald-50 border border-emerald-200' :
                           ($order->payment_status === 'pending' ? 'bg-amber-50 border border-amber-200' : 'bg-rose-50 border border-rose-200') }}">
                        <div class="text-2xl mb-1">
                            {{ $order->payment_status === 'paid' ? '✅' : ($order->payment_status === 'pending' ? '⏳' : '❌') }}
                        </div>
                        <div class="font-black text-sm
                            {{ $order->payment_status === 'paid' ? 'text-emerald-700' :
                               ($order->payment_status === 'pending' ? 'text-amber-700' : 'text-rose-700') }}">
                            {{ $order->payment_status === 'paid' ? 'LUNAS' : ($order->payment_status === 'pending' ? 'MENUNGGU PEMBAYARAN' : 'GAGAL / BATAL') }}
                        </div>
                    </div>

                    @if($order->payment_status === 'pending')
                        <div class="flex flex-col gap-2">
                            <button @click="openStatus('paid')"
                                    class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                                ✅ Tandai Lunas (Paid)
                            </button>
                            <button @click="openStatus('failed')"
                                    class="w-full py-2.5 bg-rose-500 hover:bg-rose-600 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                                ❌ Tandai Gagal/Batal
                            </button>
                        </div>
                    @elseif($order->payment_status === 'paid')
                        <button @click="openStatus('pending')"
                                class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition">
                            ↩️ Kembalikan ke Pending
                        </button>
                    @endif
                </div>

                {{-- Kontak Pelanggan --}}
                <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-slate-700 dark:text-purple-200 text-sm uppercase tracking-wide mb-4">Kontak Pelanggan</h3>
                    @php
                        $formattedPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
                        if (str_starts_with($formattedPhone, '0')) {
                            $formattedPhone = '62' . substr($formattedPhone, 1);
                        }

                        if ($order->payment_status === 'paid') {
                            $waMessage = 'Halo ' . $order->customer_name . ', kami dari Pusat Kurma ingin mengonfirmasi bahwa pembayaran untuk pesanan Anda dengan kode *' . $order->order_code . '* sebesar Rp ' . number_format($order->total_amount, 0, ',', '.') . ' telah kami terima (Lunas). Pesanan Anda akan segera kami proses dan kirimkan. Terima kasih! 🌴';
                        } else {
                            $waMessage = 'Halo ' . $order->customer_name . ', kami dari Pusat Kurma ingin mengonfirmasi pesanan Anda dengan kode *' . $order->order_code . '*. Total yang perlu dibayar: Rp ' . number_format($order->total_amount, 0, ',', '.') . '. Mohon segera lakukan pembayaran agar dapat kami proses. Terima kasih! 🌴';
                        }
                    @endphp
                    <a href="https://wa.me/{{ $formattedPhone }}?text={{ urlencode($waMessage) }}"
                       target="_blank" rel="noopener"
                       class="w-full py-2.5 bg-green-500 hover:bg-green-600 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Chat WhatsApp
                    </a>
                </div>

                {{-- Info Pesanan --}}
                <div class="bg-slate-50 dark:bg-dp-700 border border-slate-100 dark:border-dp-600 rounded-2xl p-4 text-xs font-semibold text-slate-500 dark:text-purple-400 space-y-2">
                    <div class="flex justify-between">
                        <span>Kode Pesanan</span>
                        <span class="font-mono font-black text-emerald-700 dark:text-emerald-400">{{ $order->order_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Jumlah Item</span>
                        <span class="font-black text-slate-700 dark:text-purple-200">{{ $order->orderItems->count() }} produk</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dibuat</span>
                        <span class="text-slate-600 dark:text-purple-300">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Diperbarui</span>
                        <span class="text-slate-600 dark:text-purple-300">{{ $order->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Modal: Update Status ── --}}
        <div x-show="showStatusModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display:none;">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showStatusModal=false"></div>
            <div class="relative bg-white dark:bg-dp-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                <div class="text-center mb-4">
                    <div class="text-4xl mb-2" x-text="newStatus==='paid' ? '✅' : (newStatus==='failed' ? '❌' : '⏳')"></div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">Ubah Status Pesanan?</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Status pesanan akan diperbarui menjadi
                        <strong x-text="newStatus==='paid' ? 'LUNAS' : (newStatus==='failed' ? 'GAGAL' : 'PENDING')"></strong>.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="showStatusModal=false" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition">Batal</button>
                    <button @click="executeStatus()" :disabled="loading"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': newStatus==='paid',
                                'bg-rose-600 hover:bg-rose-700': newStatus==='failed',
                                'bg-amber-500 hover:bg-amber-600': newStatus==='pending'
                            }"
                            class="flex-1 py-2.5 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition">
                        <span x-show="!loading">Ya, Simpan</span>
                        <span x-show="loading">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    window.updateOrderStatus = function(statusVal) {
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        fetch('/admin/orders/{{ $order->id }}/status', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: statusVal })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                if (window.showToast) window.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                if (window.showToast) window.showToast(data.message || 'Gagal mengubah status', 'error');
            }
        }).catch(err => {
            alert('Terjadi kesalahan jaringan.');
        });
    };
    </script>
</x-app-layout>
