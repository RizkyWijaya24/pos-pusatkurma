<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold text-sm shadow-sm">
                        B2B
                    </span>
                    <h2 class="font-extrabold text-xl text-slate-800 dark:text-purple-100 leading-tight">
                        {{ __('Pelacakan Komisi Afiliasi (Tour Leader)') }}
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-purple-300 mt-1">
                    Kelola mitra travel agen, rombongan umroh/haji, dan penyesuaian persentase komisi dinamis.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="document.getElementById('modalCreateAgent').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition duration-150 transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Agen / TL Baru</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="affiliateManager()">

        {{-- ── UX FLASH MESSAGES YANG KONTRAS (HIGH-CONTRAST ALERTS) ── --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
                 class="relative flex items-center justify-between p-4 rounded-xl bg-emerald-600 text-white shadow-lg border-2 border-emerald-400">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-800/80 rounded-lg shrink-0">
                        <svg class="w-6 h-6 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-sm uppercase tracking-wider text-emerald-100">Berhasil!</div>
                        <div class="text-sm font-semibold text-white">{{ session('success') }}</div>
                    </div>
                </div>
                <button @click="show = false" class="p-1.5 hover:bg-emerald-700 rounded-lg text-emerald-100 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
                 class="relative flex items-center justify-between p-4 rounded-xl bg-rose-700 text-white shadow-lg border-2 border-rose-400">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-rose-900/80 rounded-lg shrink-0">
                        <svg class="w-6 h-6 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-sm uppercase tracking-wider text-rose-100">Perhatian / Gagal!</div>
                        <div class="text-sm font-semibold text-white">{{ session('error') }}</div>
                    </div>
                </div>
                <button @click="show = false" class="p-1.5 hover:bg-rose-800 rounded-lg text-rose-100 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-amber-600 text-white shadow-lg border-2 border-amber-300">
                <div class="font-extrabold text-sm uppercase tracking-wider mb-1">Terdapat kesalahan pengisian data:</div>
                <ul class="list-disc list-inside text-xs font-semibold space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── 4 SUMMARY METRIC CARDS ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Agen --}}
            <div class="bg-white dark:bg-dp-800 rounded-2xl p-4 border border-slate-200 dark:border-dp-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 dark:text-purple-300 uppercase tracking-wider">Total Mitra Tour Leader</span>
                    <div class="text-2xl font-black text-slate-800 dark:text-purple-100 mt-1">
                        {{ $totalAgents }} <span class="text-xs font-semibold text-emerald-600">({{ $totalActiveAgents }} Aktif)</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                    👥
                </div>
            </div>

            {{-- Card 2: Total Omset B2B --}}
            <div class="bg-white dark:bg-dp-800 rounded-2xl p-4 border border-slate-200 dark:border-dp-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 dark:text-purple-300 uppercase tracking-wider">Total Omset Afiliasi</span>
                    <div class="text-xl font-black text-slate-800 dark:text-purple-100 mt-1">
                        Rp {{ number_format($totalOmsetB2B, 0, ',', '.') }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                    🛍️
                </div>
            </div>

            {{-- Card 3: Komisi Disalurkan --}}
            <div class="bg-white dark:bg-dp-800 rounded-2xl p-4 border border-slate-200 dark:border-dp-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 dark:text-purple-300 uppercase tracking-wider">Komisi Telah Dicairkan</span>
                    <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                        Rp {{ number_format($totalKomisiPaid, 0, ',', '.') }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl">
                    💸
                </div>
            </div>

            {{-- Card 4: Komisi Pending / Siap Cair --}}
            <div class="bg-white dark:bg-dp-800 rounded-2xl p-4 border border-amber-200 dark:border-amber-900/50 bg-gradient-to-br from-amber-50/50 to-white dark:from-dp-800 dark:to-dp-800 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Komisi Siap Cair</span>
                    <div class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1">
                        Rp {{ number_format(max(0, $totalKomisiEarned - $totalKomisiPaid), 0, ',', '.') }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 flex items-center justify-center text-xl">
                    ⏳
                </div>
            </div>
        </div>

        {{-- ── FILTER & SEARCH BAR (DATATABLES STYLE HEADER) ── --}}
        <div class="bg-white dark:bg-dp-800 rounded-2xl border border-slate-200 dark:border-dp-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-dp-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.affiliates.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                    <div class="relative flex-1 min-w-[240px]">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Cari nama TL, kode (TL-001), travel, atau no HP..." 
                               class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 text-slate-800 dark:text-purple-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select name="status" onchange="this.form.submit()" 
                            class="py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 text-slate-800 dark:text-purple-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif Saja</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                    
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.affiliates.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-dp-700 hover:bg-slate-200 dark:hover:bg-dp-600 text-slate-600 dark:text-purple-200 rounded-xl text-xs font-semibold">
                            Reset
                        </a>
                    @endif
                </form>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <span class="text-xs text-slate-400 font-medium">Total: {{ $affiliates->total() }} Agen</span>
                </div>
            </div>

            {{-- ── TABEL DATA-HEAVY (DESKTOP-OPTIMIZED — CONSOLIDATED 7 COLS) ── --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700/60 text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-dp-900/60 text-slate-500 dark:text-purple-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-dp-700">
                        <tr>
                            <th class="py-3.5 px-4 whitespace-nowrap">Agen / Tour Leader</th>
                            <th class="py-3.5 px-4 whitespace-nowrap text-center">Kontak</th>
                            <th class="py-3.5 px-3 whitespace-nowrap text-center bg-emerald-50/50 dark:bg-emerald-950/15">Rate Komisi (%)</th>
                            <th class="py-3.5 px-4 whitespace-nowrap text-center">Jamaah</th>
                            <th class="py-3.5 px-4 whitespace-nowrap text-right">Omset & Komisi</th>
                            <th class="py-3.5 px-3 whitespace-nowrap text-center">Status</th>
                            <th class="py-3.5 px-3 whitespace-nowrap text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dp-700/60 text-slate-700 dark:text-purple-200 font-medium">
                        @forelse ($affiliates as $affiliate)
                            @php
                                $komisiTersedia = $affiliate->komisi_tersedia;
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-dp-700/30 transition duration-150 group">
                                {{-- 1. Agen / Tour Leader (Kode + Nama + Biro Travel + Rekening) --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-start gap-2.5">
                                        <span class="inline-flex items-center justify-center px-2 py-1 rounded-lg bg-slate-800 dark:bg-purple-900 text-white font-mono font-bold text-[10px] tracking-wider leading-none shrink-0 mt-0.5">
                                            {{ $affiliate->code }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-extrabold text-slate-800 dark:text-purple-100 text-[13px] leading-tight">
                                                {{ $affiliate->name }}
                                            </div>
                                            @if($affiliate->agency_name)
                                                <div class="text-[11px] text-slate-500 dark:text-purple-300 font-semibold mt-0.5">
                                                    {{ $affiliate->agency_name }}
                                                </div>
                                            @endif
                                            @if($affiliate->bank_name)
                                                <div class="text-[10px] text-slate-400 dark:text-purple-400 mt-0.5 font-medium">
                                                    🏦 {{ $affiliate->bank_name }} {{ $affiliate->bank_account_number }} <span class="text-slate-300 dark:text-purple-500">({{ $affiliate->bank_account_name }})</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Kontak (WhatsApp — icon compact) --}}
                                <td class="py-3.5 px-4 text-center">
                                    @if ($affiliate->phone)
                                        @php
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $affiliate->phone);
                                            $waNumber = str_starts_with($cleanPhone, '0') ? '62' . substr($cleanPhone, 1) : $cleanPhone;
                                        @endphp
                                        <a href="https://wa.me/{{ $waNumber }}?text=Halo%20{{ urlencode($affiliate->name) }},%20update%20komisi%20Pusat%20Kurma" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition text-[11px] font-bold border border-emerald-200/50 dark:border-emerald-800/40">
                                            💬 {{ $affiliate->phone }}
                                        </a>
                                    @else
                                        <span class="text-slate-300 dark:text-purple-500">—</span>
                                    @endif
                                </td>

                                {{-- 3. EDIT CEPAT RATE KOMISI (Inline) --}}
                                <td class="py-3.5 px-3 text-center bg-emerald-50/30 dark:bg-emerald-950/10">
                                    <form method="POST" action="{{ route('admin.affiliates.quick-rate', $affiliate) }}"
                                          @submit.prevent="quickUpdateRate($el, {{ $affiliate->id }})"
                                          class="inline-flex items-center gap-1.5 justify-center">
                                        @csrf
                                        @method('PATCH')
                                        <div class="relative w-[76px]">
                                            <input type="number" 
                                                   step="0.1" min="0" max="100" 
                                                   name="commission_rate_percentage" 
                                                   value="{{ number_format($affiliate->commission_rate_percentage, 1, '.', '') }}" 
                                                   class="w-full text-center font-black text-xs pr-5 py-1.5 px-2 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-dp-900 text-emerald-800 dark:text-emerald-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none shadow-xs"
                                                   title="Ubah lalu tekan Simpan">
                                            <span class="absolute right-2 top-1.5 font-bold text-[11px] text-emerald-500 pointer-events-none">%</span>
                                        </div>
                                        <button type="submit" 
                                                title="Simpan Perubahan Rate"
                                                class="p-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-sm transition flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </form>
                                </td>

                                {{-- 4. Total Jamaah --}}
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-dp-900 text-slate-700 dark:text-purple-200 font-extrabold text-[11px]">
                                        {{ number_format($affiliate->total_jamaah, 0, ',', '.') }} Pax
                                    </span>
                                </td>

                                {{-- 5. Omset & Komisi (Stacked) --}}
                                <td class="py-3.5 px-4 text-right">
                                    <div class="font-bold text-slate-800 dark:text-purple-100 text-[12px]">
                                        Rp {{ number_format($affiliate->total_omset, 0, ',', '.') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 dark:text-purple-400 mt-0.5">
                                        {{ $affiliate->transactions_count }}x trx
                                    </div>
                                    @if ($komisiTersedia > 0)
                                        <div class="mt-1 pt-1 border-t border-slate-100 dark:border-dp-700/50">
                                            <span class="font-extrabold text-[12px] text-amber-600 dark:text-amber-400">
                                                Rp {{ number_format($komisiTersedia, 0, ',', '.') }}
                                            </span>
                                            <button type="button" 
                                                    @click="openPayoutModal({{ json_encode($affiliate) }}, {{ $komisiTersedia }})"
                                                    class="ml-1 text-[9px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 underline underline-offset-2">
                                                Cairkan
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-300 dark:text-purple-500 mt-0.5">Komisi: Rp 0</div>
                                    @endif
                                </td>

                                {{-- 6. Status --}}
                                <td class="py-3.5 px-3 text-center">
                                    @if($affiliate->status === 'active')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-dp-900 text-slate-400 dark:text-purple-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300 inline-block"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                {{-- 7. Aksi (Solid icon buttons) --}}
                                <td class="py-3.5 px-3 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.affiliates.ledger', $affiliate) }}" 
                                           title="Buku Besar Komisi"
                                           class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dp-700 dark:hover:bg-dp-600 text-slate-600 dark:text-purple-200 transition shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </a>
                                        <a href="{{ route('tour_leader.dashboard', $affiliate->code) }}" 
                                           target="_blank"
                                           title="Portal Mobile Tour Leader"
                                           class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/50 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-300 transition shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        </a>
                                        <button type="button" 
                                                @click="openEditModal({{ json_encode($affiliate) }})"
                                                title="Edit Profil Agen"
                                                class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300 transition shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <form method="POST" action="{{ route('admin.affiliates.destroy', $affiliate) }}" 
                                              onsubmit="return confirm('Hapus / nonaktifkan agen \'{{ $affiliate->name }}\'?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    title="Hapus / Nonaktifkan"
                                                    class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 transition shadow-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🕌</div>
                                    <div class="font-bold text-sm">Belum ada data Agen Tour Leader (B2B)</div>
                                    <p class="text-xs mt-1">Daftarkan Tour Leader pertama Anda untuk mulai melacak komisi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($affiliates->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-dp-700">
                    {{ $affiliates->links() }}
                </div>
            @endif
        </div>

        {{-- ── MODAL: TAMBAH AGEN B2B BARU ── --}}
        <div id="modalCreateAgent" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
            <div class="bg-white dark:bg-dp-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 dark:border-dp-700">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-dp-700">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-purple-100">
                        Tambah Mitra Agen B2B / Tour Leader
                    </h3>
                    <button type="button" onclick="document.getElementById('modalCreateAgent').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.affiliates.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nama Tour Leader / PIC *</label>
                            <input type="text" name="name" required placeholder="Contoh: Ustadz Ahmad Fauzi" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Kode Referral / TL Unik *</label>
                            <input type="text" name="code" required placeholder="Contoh: TL-001" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 uppercase font-mono font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nama Biro Travel / Umroh</label>
                            <input type="text" name="agency_name" placeholder="Contoh: Arminareka / Mandiri" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">No WhatsApp</label>
                            <input type="text" name="phone" placeholder="081234567890" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-emerald-800 dark:text-emerald-300 mb-1">Persentase Komisi Dinamis (%) *</label>
                            <div class="relative">
                                <input type="number" step="0.1" min="0" max="100" name="commission_rate_percentage" value="5.0" required
                                       class="w-full text-xs font-black rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/20 p-2.5 pr-8 text-emerald-900 dark:text-emerald-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <span class="absolute right-3 top-2.5 font-bold text-xs text-emerald-600">%</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Status Keaktifan</label>
                            <select name="status" class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="active">Aktif (Menerima Komisi)</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    {{-- Informasi Rekening Bank --}}
                    <div class="pt-3 border-t border-slate-100 dark:border-dp-700">
                        <span class="block text-xs font-extrabold text-slate-800 dark:text-purple-200 mb-2">Informasi Rekening Bank (Tujuan Pencairan)</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <input type="text" name="bank_name" placeholder="Nama Bank (BCA/BSI)" 
                                       class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 text-slate-800 dark:text-purple-100">
                            </div>
                            <div>
                                <input type="text" name="bank_account_number" placeholder="Nomor Rekening" 
                                       class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 text-slate-800 dark:text-purple-100 font-mono">
                            </div>
                            <div>
                                <input type="text" name="bank_account_name" placeholder="Atas Nama Rekening" 
                                       class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 text-slate-800 dark:text-purple-100">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-dp-700">
                        <button type="button" onclick="document.getElementById('modalCreateAgent').classList.add('hidden')"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-purple-300 hover:bg-slate-100 dark:hover:bg-dp-700">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md">
                            Simpan Agen B2B
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── MODAL: EDIT DATA AGEN ── --}}
        <div x-show="isEditModalOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="isEditModalOpen = false" class="bg-white dark:bg-dp-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 dark:border-dp-700">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-dp-700">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-purple-100">
                        Edit Profil Mitra: <span x-text="editForm.name"></span>
                    </h3>
                    <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form :action="'{{ url('/admin/affiliates') }}/' + editForm.id" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nama Tour Leader / PIC *</label>
                            <input type="text" name="name" x-model="editForm.name" required 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Kode Referral / TL Unik *</label>
                            <input type="text" name="code" x-model="editForm.code" required 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 uppercase font-mono font-bold">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nama Biro Travel / Umroh</label>
                            <input type="text" name="agency_name" x-model="editForm.agency_name" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">No WhatsApp</label>
                            <input type="text" name="phone" x-model="editForm.phone" 
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-emerald-800 dark:text-emerald-300 mb-1">Persentase Komisi Dinamis (%) *</label>
                            <div class="relative">
                                <input type="number" step="0.1" min="0" max="100" name="commission_rate_percentage" x-model="editForm.commission_rate_percentage" required
                                       class="w-full text-xs font-black rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/20 p-2.5 pr-8 text-emerald-900 dark:text-emerald-200">
                                <span class="absolute right-3 top-2.5 font-bold text-xs text-emerald-600">%</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Status Keaktifan</label>
                            <select name="status" x-model="editForm.status" class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-dp-700">
                        <span class="block text-xs font-extrabold text-slate-800 dark:text-purple-200 mb-2">Informasi Rekening Bank</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <input type="text" name="bank_name" x-model="editForm.bank_name" placeholder="Bank" class="text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2">
                            <input type="text" name="bank_account_number" x-model="editForm.bank_account_number" placeholder="No Rek" class="text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 font-mono">
                            <input type="text" name="bank_account_name" x-model="editForm.bank_account_name" placeholder="Atas Nama" class="text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-dp-700">
                        <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-purple-300">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── MODAL: BAYAR KOMISI (PAYOUT MANUAL OLEH ADMIN) ── --}}
        <div x-show="isPayoutModalOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="isPayoutModalOpen = false" class="bg-white dark:bg-dp-800 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-dp-700">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-dp-700">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-purple-100">
                        Proses Pembayaran Komisi
                    </h3>
                    <button type="button" @click="isPayoutModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="my-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50">
                    <div class="text-xs text-amber-800 dark:text-amber-300">
                        Penerima: <strong x-text="payoutTarget.name"></strong> (<span x-text="payoutTarget.agency_name"></span>)<br>
                        Rekening: <strong x-text="payoutTarget.bank_name"></strong> - <strong x-text="payoutTarget.bank_account_number"></strong> a.n <strong x-text="payoutTarget.bank_account_name"></strong><br>
                        Saldo Komisi Tersedia: <strong class="text-amber-900 dark:text-amber-200">Rp <span x-text="new Intl.NumberFormat('id-ID').format(payoutMaxAmount)"></span></strong>
                    </div>
                </div>

                <form :action="'{{ url('/admin/affiliates') }}/' + payoutTarget.id + '/payout'" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nominal yang Dicairkan (Rp) *</label>
                        <input type="number" name="amount" :max="payoutMaxAmount" min="1000" x-model="payoutAmount" required
                               class="w-full text-sm font-black rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2.5 text-slate-800 dark:text-purple-100 focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Nomor Referensi Transfer / Resi</label>
                        <input type="text" name="reference_no" placeholder="Contoh: TRF-BCA-987654" 
                               class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 text-slate-800 dark:text-purple-100">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-purple-200 mb-1">Catatan Tambahan</label>
                        <textarea name="notes" rows="2" placeholder="Catatan pembayaran..." 
                                  class="w-full text-xs rounded-xl border border-slate-200 dark:border-dp-700 bg-slate-50 dark:bg-dp-900 p-2 text-slate-800 dark:text-purple-100"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-dp-700">
                        <button type="button" @click="isPayoutModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-purple-300">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-md">Catat Pencairan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- ── INLINE ALPINE JS CONTROLLER ── --}}
    <script>
        function affiliateManager() {
            return {
                isEditModalOpen: false,
                editForm: {},
                isPayoutModalOpen: false,
                payoutTarget: {},
                payoutMaxAmount: 0,
                payoutAmount: 0,

                openEditModal(affiliate) {
                    this.editForm = Object.assign({}, affiliate);
                    this.isEditModalOpen = true;
                },

                openPayoutModal(affiliate, availableAmount) {
                    this.payoutTarget = affiliate;
                    this.payoutMaxAmount = availableAmount;
                    this.payoutAmount = availableAmount;
                    this.isPayoutModalOpen = true;
                },

                quickUpdateRate(formEl, affiliateId) {
                    const formData = new FormData(formEl);
                    const btn = formEl.querySelector('button[type="submit"]');
                    const originalBtnContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = `<span class="animate-spin text-xs">↻</span>`;

                    fetch(formEl.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerHTML = originalBtnContent;
                        if (data.success) {
                            // Flash notification indicator
                            const input = formEl.querySelector('input[name="commission_rate_percentage"]');
                            input.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-100');
                            setTimeout(() => {
                                input.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-100');
                            }, 1500);

                            if (window.showToast) {
                                window.showToast(data.message, 'success');
                            } else {
                                alert(data.message);
                            }
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = originalBtnContent;
                        alert('Gagal mengubah persentase komisi.');
                    });
                }
            }
        }
    </script>
</x-app-layout>

