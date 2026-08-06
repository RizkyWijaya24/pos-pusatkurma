<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 dark:bg-dp-800 rounded-xl">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">Kupon / Kode Promo</h2>
                    <p class="text-xs text-slate-400 font-medium">Kelola kode diskon untuk pelanggan toko online</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-600 flex items-center gap-1 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ showForm: false }">

        @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium flex items-center gap-2">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <p class="text-sm text-slate-500">{{ $coupons->count() }} kupon terdaftar</p>
            <button @click="showForm = !showForm"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Buat Kupon
            </button>
        </div>

        {{-- Add Form --}}
        <div x-show="showForm" x-transition class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 p-6 mb-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-5">Buat Kupon Baru</h3>
            <form method="POST" action="{{ route('admin.coupons.store') }}" x-data="{ couponType: 'fixed' }">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Kode Kupon *</label>
                        <input name="code" type="text" required placeholder="KURMA20" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm uppercase font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Deskripsi</label>
                        <input name="description" type="text" placeholder="Diskon 20% untuk pembelian pertama" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tipe Diskon *</label>
                        <select name="type" x-model="couponType" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                            <option value="fixed">Nominal Tetap (Rp)</option>
                            <option value="percent">Persentase (%)</option>
                            <option value="free_shipping">Gratis Ongkir</option>
                        </select>
                    </div>
                    <div x-show="couponType !== 'free_shipping'">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider" x-text="couponType === 'percent' ? 'Nilai (%)' : 'Nilai (Rp)'"></label>
                        <input name="value" type="number" min="0" placeholder="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div x-show="couponType === 'percent'">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Maks. Diskon (Rp, 0 = unlimited)</label>
                        <input name="max_discount" type="number" min="0" value="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Min. Belanja (Rp)</label>
                        <input name="min_order" type="number" value="0" min="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Maks. Penggunaan (0 = unlimited)</label>
                        <input name="max_uses" type="number" value="0" min="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Kedaluwarsa</label>
                        <input name="expires_at" type="datetime-local" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div class="flex items-center gap-2">
                        <input name="is_active" type="checkbox" id="couponActive" checked value="1" class="rounded">
                        <label for="couponActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Aktif</label>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">Buat Kupon</button>
                    <button type="button" @click="showForm = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">Batal</button>
                </div>
            </form>
        </div>

        {{-- Coupon Table --}}
        <div class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 overflow-hidden shadow-sm">
            @if($coupons->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/></svg>
                <p class="font-medium">Belum ada kupon. Buat kupon pertama!</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-dp-800 border-b border-slate-200 dark:border-dp-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Kode</th>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Tipe & Nilai</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Min. Belanja</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Penggunaan</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Kedaluwarsa</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right font-bold text-xs text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dp-700">
                        @foreach($coupons as $coupon)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dp-800 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1 rounded-lg text-sm">{{ $coupon->code }}</span>
                                @if($coupon->description)
                                    <div class="text-xs text-slate-400 mt-1">{{ $coupon->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->type === 'percent')
                                    <span class="text-amber-600 font-bold">{{ $coupon->value }}% off</span>
                                    @if($coupon->max_discount > 0)
                                        <span class="text-xs text-slate-400"> (maks Rp {{ number_format($coupon->max_discount,0,',','.') }})</span>
                                    @endif
                                @elseif($coupon->type === 'fixed')
                                    <span class="text-blue-600 font-bold">Rp {{ number_format($coupon->value,0,',','.') }}</span>
                                @else
                                    <span class="text-green-600 font-bold">Gratis Ongkir</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($coupon->min_order > 0)
                                    Rp {{ number_format($coupon->min_order,0,',','.') }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                {{ $coupon->used_count }} / {{ $coupon->max_uses ?: '∞' }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($coupon->expires_at)
                                    <span class="{{ $coupon->expires_at->isPast() ? 'text-red-500' : 'text-slate-500' }}">
                                        {{ $coupon->expires_at->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Tidak kedaluwarsa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($coupon->is_active && (!$coupon->expires_at || !$coupon->expires_at->isPast()))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Hapus kupon {{ $coupon->code }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
