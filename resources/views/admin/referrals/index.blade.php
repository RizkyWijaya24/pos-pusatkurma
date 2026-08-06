<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 dark:bg-dp-800 rounded-xl">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">Kode Referral</h2>
                    <p class="text-xs text-slate-400 font-medium">Kelola kode referral untuk mitra & agen toko</p>
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
            <p class="text-sm text-slate-500">{{ $referrals->count() }} kode referral</p>
            <button @click="showForm = !showForm"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Buat Kode Referral
            </button>
        </div>

        {{-- Add Form --}}
        <div x-show="showForm" x-transition class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 p-6 mb-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-5">Buat Kode Referral Baru</h3>
            <form method="POST" action="{{ route('admin.referrals.store') }}">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Kode Referral *</label>
                        <input name="code" type="text" required placeholder="AGEN-BUDI" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm uppercase font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Pemilik / Mitra *</label>
                        <input name="owner_name" type="text" required placeholder="Budi Santoso" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Catatan</label>
                        <input name="notes" type="text" placeholder="Agen wilayah Cianjur" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tipe Diskon *</label>
                        <select name="discount_type" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                            <option value="percent">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Nilai Diskon *</label>
                        <input name="discount_value" type="number" required min="1" placeholder="5" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Min. Belanja (Rp)</label>
                        <input name="min_order" type="number" value="0" min="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div class="flex items-center gap-2">
                        <input name="is_active" type="checkbox" id="refActive" checked value="1" class="rounded">
                        <label for="refActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Aktif</label>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">Buat Kode</button>
                    <button type="button" @click="showForm = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">Batal</button>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 overflow-hidden shadow-sm">
            @if($referrals->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                <p class="font-medium">Belum ada kode referral.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-dp-800 border-b border-slate-200 dark:border-dp-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Kode</th>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Pemilik</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Diskon</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Min. Order</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Dipakai</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right font-bold text-xs text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dp-700">
                        @foreach($referrals as $ref)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dp-800 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono font-bold text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2.5 py-1 rounded-lg text-sm">{{ $ref->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $ref->owner_name }}</div>
                                @if($ref->notes)
                                    <div class="text-xs text-slate-400">{{ $ref->notes }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold">
                                @if($ref->discount_type === 'percent')
                                    <span class="text-amber-600">{{ $ref->discount_value }}%</span>
                                @else
                                    <span class="text-blue-600">Rp {{ number_format($ref->discount_value,0,',','.') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($ref->min_order > 0)
                                    Rp {{ number_format($ref->min_order,0,',','.') }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $ref->used_count }}×</td>
                            <td class="px-4 py-3 text-center">
                                @if($ref->is_active)
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
                                <form method="POST" action="{{ route('admin.referrals.destroy', $ref) }}" onsubmit="return confirm('Hapus kode {{ $ref->code }}?')">
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
