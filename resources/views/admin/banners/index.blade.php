<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 dark:bg-dp-800 rounded-xl">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">Banner Promo</h2>
                    <p class="text-xs text-slate-400 font-medium">Kelola banner iklan di halaman shop</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-600 flex items-center gap-1 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ showForm: false, editBanner: null }">

        @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium flex items-center gap-2">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Add Banner Button --}}
        <div class="flex justify-between items-center mb-6">
            <p class="text-sm text-slate-500">{{ $banners->count() }} banner terdaftar</p>
            <button @click="showForm = !showForm; editBanner = null"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Banner
            </button>
        </div>

        {{-- Add/Edit Form --}}
        <div x-show="showForm" x-transition class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 p-6 mb-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-5 flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span x-text="editBanner ? 'Edit Banner' : 'Tambah Banner Baru'"></span>
            </h3>

            <form id="bannerForm" method="POST" enctype="multipart/form-data" action="{{ route('admin.banners.store') }}">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Judul Banner *</label>
                        <input name="title" type="text" required placeholder="Promo Ramadan Spesial!" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Subjudul</label>
                        <input name="subtitle" type="text" placeholder="Diskon hingga 30% untuk semua kurma premium" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Badge Teks</label>
                        <input name="badge_text" type="text" placeholder="🌙 Ramadan Special" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Teks Tombol</label>
                        <input name="button_text" type="text" required value="Belanja Sekarang" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">URL Tombol</label>
                        <input name="button_url" type="text" required value="/shop" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Urutan</label>
                        <input name="sort_order" type="number" value="0" min="0" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Warna Gradien Mulai</label>
                        <div class="flex gap-2 items-center">
                            <input name="bg_from" type="color" value="#065f46" class="h-10 w-16 rounded-lg border border-slate-200 cursor-pointer">
                            <input type="text" placeholder="#065f46" class="flex-1 border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white" oninput="this.previousElementSibling.value=this.value">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Warna Gradien Akhir</label>
                        <div class="flex gap-2 items-center">
                            <input name="bg_to" type="color" value="#059669" class="h-10 w-16 rounded-lg border border-slate-200 cursor-pointer">
                            <input type="text" placeholder="#059669" class="flex-1 border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white" oninput="this.previousElementSibling.value=this.value">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tanggal Mulai</label>
                        <input name="start_date" type="date" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tanggal Berakhir</label>
                        <input name="end_date" type="date" class="w-full border border-slate-200 dark:border-dp-600 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-dp-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Gambar Banner (Opsional, max 3MB)</label>
                        <input name="image" type="file" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div class="flex items-center gap-2">
                        <input name="is_active" type="checkbox" id="bannerActive" checked value="1" class="rounded">
                        <label for="bannerActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Aktif</label>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">
                        Simpan Banner
                    </button>
                    <button type="button" @click="showForm = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        {{-- Banner List --}}
        <div class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 overflow-hidden shadow-sm">
            @if($banners->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                <p class="font-medium">Belum ada banner. Tambahkan banner pertama!</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-dp-800 border-b border-slate-200 dark:border-dp-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Preview</th>
                            <th class="px-4 py-3 text-left font-bold text-xs text-slate-500 uppercase tracking-wider">Judul</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Periode</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Urutan</th>
                            <th class="px-4 py-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right font-bold text-xs text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dp-700">
                        @foreach($banners as $banner)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dp-800 transition-colors">
                            <td class="px-4 py-3">
                                <div class="w-24 h-14 rounded-lg overflow-hidden flex-shrink-0" style="background:linear-gradient(135deg, {{ $banner->bg_from }}, {{ $banner->bg_to }});">
                                    @if($banner->image_path)
                                        <img src="{{ Storage::url($banner->image_path) }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-xs font-bold px-2 text-center">{{ Str::limit($banner->title, 20) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800 dark:text-slate-100">{{ $banner->title }}</div>
                                @if($banner->subtitle)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ Str::limit($banner->subtitle, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-500">
                                @if($banner->start_date || $banner->end_date)
                                    {{ $banner->start_date?->format('d/m/Y') ?? '∞' }} — {{ $banner->end_date?->format('d/m/Y') ?? '∞' }}
                                @else
                                    <span class="text-slate-400">Selalu aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs font-mono">{{ $banner->sort_order }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($banner->is_active)
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
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Hapus banner ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">Hapus</button>
                                    </form>
                                </div>
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
