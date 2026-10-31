<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#064e3b">
    <title>Portal Tour Leader - {{ $affiliate->name }} | Pusat Kurma</title>

    <!-- Google Font: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind & App Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { font-family: 'Outfit', sans-serif; }
        body { -webkit-tap-highlight-color: transparent; background: #0a0a1a !important; color: #e2e8f0; }
        .pb-safe { padding-bottom: calc(5rem + env(safe-area-inset-bottom, 1rem)); }

        /* ── Solid Dark Palette ── */
        .tl-bg-page   { background: #0a0a1a; }
        .tl-bg-header { background: rgba(15, 15, 30, 0.95); backdrop-filter: blur(12px); border-bottom: 1px solid #1e293b; }
        .tl-bg-card   { background: #111827; border: 1px solid #1e293b; }
        .tl-bg-hero   { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); border: 1px solid rgba(16, 185, 129, 0.3); }
        .tl-bg-tab    { background: #111827; border: 1px solid #1e293b; }
        .tl-bg-tab-active { background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35); }
        .tl-bg-item   { background: #111827; border: 1px solid #1e293b; }
        .tl-bg-item:hover { border-color: #334155; }
        .tl-bg-input  { background: #0f172a; border: 1px solid #1e293b; color: #fff; }
        .tl-bg-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.3); }
        .tl-bg-chip   { background: #1e293b; color: #cbd5e1; }
        .tl-bg-chip:hover { background: #334155; }
        .tl-bg-chip:active { background: #059669; color: #fff; }
        .tl-bg-bottom { background: rgba(15, 15, 30, 0.96); backdrop-filter: blur(12px); border-top: 1px solid #1e293b; }
        .tl-bg-cta    { background: linear-gradient(90deg, #059669 0%, #0d9488 50%, #059669 100%); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }
        .tl-bg-cta:active { transform: scale(0.98); }
        .tl-bg-modal  { background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); }
        .tl-bg-sheet  { background: #111827; border-top: 1px solid #1e293b; }
        .tl-bg-saldo  { background: rgba(6, 78, 59, 0.4); border: 1px solid rgba(5, 150, 105, 0.3); }

        /* Flash messages */
        .tl-flash-ok  { background: #059669; border: 2px solid #34d399; }
        .tl-flash-err { background: #be123c; border: 2px solid #fb7185; }
        .tl-flash-warn { background: #d97706; border: 2px solid #fbbf24; }

        /* Pulse dot animation */
        @keyframes tl-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .tl-pulse { animation: tl-pulse 2s infinite; }

        /* Prevent text overflow on small screens */
        .tl-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body class="min-h-full pb-safe antialiased tl-bg-page" x-data="tourLeaderPortal()">

    <!-- ── TOP APP BAR ── -->
    <header class="sticky top-0 z-40 tl-bg-header px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-lg text-white shrink-0" style="background: linear-gradient(135deg, #059669, #14b8a6); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    TL
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h1 class="font-black text-base text-white tracking-tight leading-none tl-truncate">{{ $affiliate->name }}</h1>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-extrabold shrink-0" style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.25);">
                            {{ $affiliate->code }}
                        </span>
                    </div>
                    <p class="text-xs mt-0.5 font-medium tl-truncate" style="color: #94a3b8;">
                        {{ $affiliate->agency_name ?: 'Mitra B2B Umroh & Haji' }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 ml-2">
                <span class="inline-block px-2.5 py-1 rounded-xl text-xs font-black" style="background: #1e293b; color: #34d399; border: 1px solid #334155;">
                    {{ $affiliate->commission_rate_percentage }}% Komisi
                </span>
            </div>
        </div>
    </header>

    <!-- ── MAIN CONTENT ── -->
    <main class="max-w-md mx-auto px-4 pt-5 space-y-4">

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="tl-flash-ok p-4 rounded-2xl text-white shadow-xl flex items-start gap-3">
                <div class="p-1.5 rounded-lg shrink-0 mt-0.5" style="background: rgba(0,0,0,0.2);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div class="flex-1">
                    <div class="font-black text-xs uppercase tracking-wider" style="color: rgba(255,255,255,0.8);">Sukses</div>
                    <div class="text-xs font-bold leading-relaxed text-white mt-0.5">{{ session('success') }}</div>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="tl-flash-err p-4 rounded-2xl text-white shadow-xl flex items-start gap-3">
                <div class="p-1.5 rounded-lg shrink-0 mt-0.5" style="background: rgba(0,0,0,0.2);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                </div>
                <div class="flex-1">
                    <div class="font-black text-xs uppercase tracking-wider" style="color: rgba(255,255,255,0.8);">Perhatian</div>
                    <div class="text-xs font-bold leading-relaxed text-white mt-0.5">{{ session('error') }}</div>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="tl-flash-warn p-4 rounded-2xl text-white shadow-xl">
                <div class="font-black text-xs uppercase tracking-wider">Periksa Kembali:</div>
                <ul class="text-xs font-bold mt-1 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <!-- ═══ HERO CARD 1: KOMISI TERSEDIA ═══ -->
        <div class="relative overflow-hidden rounded-3xl tl-bg-hero p-5 text-white shadow-2xl">
            <div class="absolute -right-6 -bottom-6 text-[80px] select-none pointer-events-none" style="opacity: 0.08;">💎</div>
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest flex items-center gap-1.5" style="color: #a7f3d0;">
                        <span class="w-2 h-2 rounded-full tl-pulse" style="background: #6ee7b7;"></span>
                        Komisi Tersedia
                    </span>
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" style="background: rgba(0,0,0,0.25); color: #a7f3d0; border: 1px solid rgba(167,243,208,0.2);">
                        {{ $affiliate->commission_rate_percentage }}% Dinamis
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-lg font-bold" style="color: #a7f3d0;">Rp</span>
                    <span class="text-4xl font-black tracking-tight text-white">
                        {{ number_format($komisiTersedia, 0, ',', '.') }}
                    </span>
                </div>
                <div class="mt-4 pt-3 flex items-center justify-between text-[11px] font-semibold" style="border-top: 1px solid rgba(167,243,208,0.2); color: #d1fae5;">
                    <div>
                        <span style="color: #a7f3d0;">Dicairkan:</span>
                        <span class="font-bold ml-0.5">Rp {{ number_format($komisiSudahCair, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span style="color: #a7f3d0;">Total Komisi:</span>
                        <span class="font-bold ml-0.5">Rp {{ number_format($totalKomisiEarned, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ HERO CARDS 2 & 3 ═══ -->
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl tl-bg-card p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider" style="color: #64748b;">Total Jamaah</span>
                    <span class="text-lg">👥</span>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-black text-white tracking-tight">{{ number_format($totalJamaah, 0, ',', '.') }}</div>
                    <span class="text-[11px] font-bold" style="color: #34d399;">Pax Rombongan</span>
                </div>
            </div>
            <div class="rounded-2xl tl-bg-card p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider" style="color: #64748b;">Akumulasi Omset</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-white tracking-tight tl-truncate" title="Rp {{ number_format($akumulasiOmset, 0, ',', '.') }}">
                        Rp {{ number_format($akumulasiOmset, 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] font-bold" style="color: #fbbf24;">Total Belanja POS</span>
                </div>
            </div>
        </div>

        <!-- ═══ REKENING BANK ═══ -->
        <div class="rounded-2xl tl-bg-card p-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0" style="background: #1e293b;">🏦</div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-wider" style="color: #64748b;">Rekening Pencairan</div>
                    <div class="text-xs font-black text-white tl-truncate">
                        {{ $affiliate->bank_name ?: 'Belum Diatur' }} — {{ $affiliate->bank_account_number ?: '-' }}
                    </div>
                    <div class="text-[11px] font-medium tl-truncate" style="color: #94a3b8;">
                        a.n {{ $affiliate->bank_account_name ?: $affiliate->name }}
                    </div>
                </div>
            </div>
            <span class="text-[9px] font-extrabold px-2 py-1 rounded-full shrink-0" style="background: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                ✓ Verified
            </span>
        </div>

        <!-- ═══ TAB SWITCHER ═══ -->
        <div class="pt-1 pb-2">
            <div class="flex items-center p-1 rounded-2xl tl-bg-tab">
                <button type="button"
                        @click="activeTab = 'transactions'"
                        :class="activeTab === 'transactions' ? 'tl-bg-tab-active' : ''"
                        :style="activeTab !== 'transactions' ? 'color: #94a3b8;' : ''"
                        class="flex-1 py-2.5 rounded-xl text-xs font-extrabold transition-all duration-200 text-center">
                    Belanja Rombongan ({{ $recentTransactions->count() }})
                </button>
                <button type="button"
                        @click="activeTab = 'ledger'"
                        :class="activeTab === 'ledger' ? 'tl-bg-tab-active' : ''"
                        :style="activeTab !== 'ledger' ? 'color: #94a3b8;' : ''"
                        class="flex-1 py-2.5 rounded-xl text-xs font-extrabold transition-all duration-200 text-center">
                    Buku Mutasi Komisi
                </button>
            </div>

            <!-- Tab: Transaksi Rombongan -->
            <div x-show="activeTab === 'transactions'" class="mt-3 space-y-2.5">
                @forelse ($recentTransactions as $trx)
                    @php
                        $rate = (float) $affiliate->commission_rate_percentage;
                        $earned = round(($trx->total_price * $rate) / 100);
                    @endphp
                    <div class="rounded-2xl tl-bg-item p-3.5 transition">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs" style="color: #34d399;">#{{ $trx->transaction_code }}</span>
                            <span class="text-[11px] font-medium" style="color: #64748b;">{{ $trx->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="text-xs font-medium mt-1.5 line-clamp-1" style="color: #cbd5e1;">{{ $trx->items_summary }}</div>
                        <div class="mt-2.5 pt-2.5 flex items-center justify-between text-xs" style="border-top: 1px solid #1e293b;">
                            <div>
                                <span style="color: #64748b;">Belanja:</span>
                                <strong class="text-white ml-1">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</strong>
                                @if($trx->pax_count > 0)
                                    <span class="ml-1 text-[10px]" style="color: #64748b;">({{ $trx->pax_count }} Pax)</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span style="color: #64748b;">Komisi:</span>
                                <strong class="font-black ml-1" style="color: #34d399;">+ Rp {{ number_format($earned, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center rounded-2xl" style="background: rgba(17,24,39,0.5); border: 1px dashed #1e293b;">
                        <div class="text-3xl mb-2">🛒</div>
                        <div class="font-bold text-xs" style="color: #64748b;">Belum ada transaksi rombongan</div>
                        <p class="text-[11px] mt-1" style="color: #475569;">Saat kasir memasukkan kode <strong style="color: #94a3b8;">{{ $affiliate->code }}</strong>, data akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            <!-- Tab: Buku Mutasi Ledger -->
            <div x-show="activeTab === 'ledger'" style="display: none;" class="mt-3 space-y-2.5">
                @forelse ($ledgerEntries as $entry)
                    <div class="rounded-2xl tl-bg-item p-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
                                 style="{{ $entry->type === 'credit' ? 'background: rgba(6,78,59,0.5); color: #34d399; border: 1px solid #065f46;' : 'background: rgba(136,19,55,0.3); color: #fb7185; border: 1px solid #9f1239;' }}">
                                {{ $entry->type === 'credit' ? '↓' : '↑' }}
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white">
                                    {{ $entry->type === 'credit' ? 'Komisi Belanja Jamaah' : 'Pencairan Komisi' }}
                                </div>
                                <div class="text-[10px]" style="color: #64748b;">
                                    {{ $entry->created_at->format('d/m/Y H:i') }} &bull;
                                    <span style="{{ ($entry->status === 'paid' || $entry->status === 'approved') ? 'color: #34d399;' : 'color: #fbbf24;' }}">
                                        {{ strtoupper($entry->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-black" style="{{ $entry->type === 'credit' ? 'color: #34d399;' : 'color: #fb7185;' }}">
                                {{ $entry->type === 'credit' ? '+' : '-' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                            </div>
                            @if ($entry->commission_rate_percentage > 0)
                                <div class="text-[10px] font-mono" style="color: #64748b;">Rate: {{ $entry->commission_rate_percentage }}%</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center rounded-2xl" style="background: rgba(17,24,39,0.5); border: 1px dashed #1e293b;">
                        <div class="text-3xl mb-2">📑</div>
                        <div class="font-bold text-xs" style="color: #64748b;">Belum ada mutasi komisi</div>
                    </div>
                @endforelse
            </div>
        </div>

    </main>

    <!-- ═══ STICKY BOTTOM BAR ═══ -->
    <div class="fixed bottom-0 inset-x-0 z-40 tl-bg-bottom p-3 sm:p-4">
        <div class="max-w-md mx-auto">
            <button type="button"
                    @click="openWithdrawModal()"
                    class="w-full h-14 rounded-2xl tl-bg-cta text-white font-black text-base flex items-center justify-center gap-3 transition-transform duration-100">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>TARIK KOMISI SEKARANG</span>
            </button>
        </div>
    </div>

    <!-- ═══ BOTTOM-SHEET: PENARIKAN KOMISI ═══ -->
    <div x-show="isWithdrawOpen"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-hidden tl-bg-modal flex items-end sm:items-center justify-center p-0 sm:p-4">

        <div @click.away="isWithdrawOpen = false"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="tl-bg-sheet rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">

            <!-- Handle Bar -->
            <div class="w-12 h-1.5 rounded-full mx-auto -mt-2 mb-2 sm:hidden" style="background: #334155;"></div>

            <div class="flex items-center justify-between pb-3" style="border-bottom: 1px solid #1e293b;">
                <div class="flex items-center gap-2">
                    <span class="text-xl">💸</span>
                    <h3 class="font-black text-base text-white">Tarik Komisi</h3>
                </div>
                <button type="button" @click="isWithdrawOpen = false" class="p-1.5 rounded-lg transition" style="color: #94a3b8;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">✕</button>
            </div>

            <!-- Saldo Info -->
            <div class="rounded-2xl tl-bg-saldo p-3.5 space-y-1.5">
                <div class="text-[11px] font-bold" style="color: #6ee7b7;">Komisi Siap Ditarik:</div>
                <div class="text-2xl font-black text-white">Rp {{ number_format($komisiTersedia, 0, ',', '.') }}</div>
                <div class="text-[11px] pt-1.5 mt-1" style="color: rgba(167,243,208,0.7); border-top: 1px solid rgba(5,150,105,0.2);">
                    Transfer ke: <strong>{{ $affiliate->bank_name ?: 'Belum diatur' }} {{ $affiliate->bank_account_number }}</strong> a.n <strong>{{ $affiliate->bank_account_name ?: $affiliate->name }}</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('tour_leader.withdraw', $affiliate) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold mb-1.5" style="color: #cbd5e1;">Nominal Penarikan (Min. Rp 50.000)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 font-black text-sm" style="color: #64748b;">Rp</span>
                        <input type="number"
                               name="amount"
                               x-model="withdrawAmount"
                               min="50000"
                               max="{{ $komisiTersedia }}"
                               required
                               placeholder="50000"
                               class="w-full pl-11 pr-4 py-3 rounded-2xl font-black text-lg tl-bg-input">
                    </div>
                    <!-- Quick Amount Chips -->
                    <div class="grid grid-cols-3 gap-2 mt-2.5">
                        <button type="button" @click="withdrawAmount = 100000" class="py-2.5 px-2 tl-bg-chip rounded-xl text-xs font-bold text-center transition">100 Rb</button>
                        <button type="button" @click="withdrawAmount = 500000" class="py-2.5 px-2 tl-bg-chip rounded-xl text-xs font-bold text-center transition">500 Rb</button>
                        <button type="button" @click="withdrawAmount = {{ $komisiTersedia }}" class="py-2.5 px-2 rounded-xl text-xs font-bold text-center transition" style="background: rgba(6,78,59,0.5); color: #6ee7b7; border: 1px solid #065f46;">Tarik Semua</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1" style="color: #cbd5e1;">Catatan (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Catatan atau permintaan khusus..."
                              class="w-full p-2.5 rounded-xl text-xs tl-bg-input"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            :disabled="{{ $komisiTersedia }} < 50000"
                            class="w-full h-12 rounded-xl text-white font-extrabold text-sm shadow-lg transition disabled:opacity-40"
                            style="background: #059669;"
                            onmouseover="this.style.background='#047857'"
                            onmouseout="this.style.background='#059669'">
                        Konfirmasi & Ajukan Penarikan
                    </button>
                    <button type="button" @click="isWithdrawOpen = false"
                            class="w-full py-2.5 mt-2 rounded-xl text-xs font-bold transition" style="color: #94a3b8;">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alpine JS -->
    <script>
        function tourLeaderPortal() {
            return {
                activeTab: 'transactions',
                isWithdrawOpen: false,
                withdrawAmount: {{ min(100000, $komisiTersedia) }},
                openWithdrawModal() {
                    if ({{ $komisiTersedia }} < 50000) {
                        alert('Saldo komisi tersedia belum mencukupi batas minimum penarikan (Min. Rp 50.000).');
                        return;
                    }
                    this.isWithdrawOpen = true;
                }
            }
        }
    </script>
</body>
</html>
