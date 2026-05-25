<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Analisis Kinerja Penjualan') }}
        </h2>
    </x-slot>

    <!-- Alpine.js Owner State (Optional for interactivity) -->
    <div x-data="{
        revenue: {{ (int) $activeRevenue }},
        transactions: {{ (int) $activeTransactionsCount }},
        expenses: {{ (int) $activeExpenses }},
        lowStockCount: {{ (int) $lowStockCount }},
        timeframe: 'Mingguan',
        showTable: localStorage.getItem('owner_active_card') !== null,
        activeCard: localStorage.getItem('owner_active_card'),
        formatRupiah(num) {
            const val = parseFloat(num);
            return 'Rp ' + (isNaN(val) ? '0' : val.toLocaleString('id-ID'));
        },
        toggleCard(card) {
            if (this.activeCard === card) {
                this.activeCard = null;
                this.showTable = false;
                localStorage.removeItem('owner_active_card');
            } else {
                this.activeCard = card;
                this.showTable = true;
                localStorage.setItem('owner_active_card', card);
            }
        }
    }" class="flex flex-col gap-8 max-w-full overflow-hidden">

        <!-- Filter Toggles (Forest Green Brand Theme) -->
        <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-100 shadow-md">
            <!-- Left Side: Title and badge -->
            <div class="flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">
                        Laporan Penjualan — @if($activeFilter === 'today') Hari Ini @elseif($activeFilter === 'weekly') Pekan Ini @else Bulan Ini @endif
                    </h3>
                    @if($selectedBranch)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                            📍 {{ $selectedBranch }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 font-medium">Gunakan tab filter dan filter cabang untuk menganalisis performa bisnis Anda</p>
            </div>
            
            <!-- Right Side: Dropdowns and Filters Controls -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <!-- Single Merged GET Form for both Filters -->
                <form method="GET" action="{{ route('owner.dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <!-- Dropdown Filter Cabang -->
                    <select name="branch" onchange="this.form.submit()" 
                            class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">
                        <option value="">🌐 Semua Cabang</option>
                        @foreach($branches as $branchName)
                            @if($branchName)
                                <option value="{{ $branchName }}" {{ $selectedBranch === $branchName ? 'selected' : '' }}>
                                    📍 {{ $branchName }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Dropdown Filter Waktu (Timeframe) -->
                    <select name="filter" onchange="this.form.submit()" 
                            class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">
                        <option value="today" {{ $activeFilter === 'today' ? 'selected' : '' }}>📅 Hari Ini</option>
                        <option value="weekly" {{ $activeFilter === 'weekly' ? 'selected' : '' }}>📅 Minggu Ini</option>
                        <option value="monthly" {{ $activeFilter === 'monthly' ? 'selected' : '' }}>📅 Bulan Ini</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Hint Banner to encourage clicking cards -->
        <div class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white px-6 py-3.5 rounded-2xl shadow-sm flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                    <span class="animate-bounce text-emerald-300 font-extrabold text-sm">👇</span>
                </div>
                <div>
                    <h4 class="text-xs font-black tracking-wider uppercase text-emerald-300">Tips Analisis Performa</h4>
                    <p class="text-xs text-emerald-50 font-medium">Klik salah satu kartu metrik utama di bawah ini untuk menampilkan detail laporan dan menganalisis rincian kolom secara interaktif!</p>
                </div>
            </div>
        </div>

        <!-- 1. TOP ROW: Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Card 1: Total Omset -->
            <div id="card-omset"
                 x-on:click="toggleCard('omset')"
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;"
                 :class="activeCard === 'omset' ? 'ring-4 ring-emerald-500/40 scale-[1.03] shadow-xl border-emerald-500' : 'hover:scale-[1.02] shadow-md border-emerald-200/50'"
                 class="p-5 sm:p-6 rounded-3xl flex flex-col justify-between gap-4 group transition duration-300 cursor-pointer select-none relative overflow-hidden">
                <!-- Soft Glow Backdrop Decoration -->
                <div class="absolute -right-10 -top-10 w-24 h-24 rounded-full bg-emerald-500/10 blur-2xl group-hover:bg-emerald-500/20 transition duration-300"></div>
                <div class="flex flex-col gap-1 min-w-0 flex-1 z-10 pr-10">
                    <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest pr-10">Total Omset</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight mt-0.5 whitespace-nowrap" x-text="formatRupiah({{ (int) $activeRevenue }})">Rp {{ number_format($activeRevenue, 0, ',', '.') }}</h3>
                    <div class="flex items-center gap-1 select-none whitespace-nowrap bg-emerald-100/50 border border-emerald-200/40 rounded-lg px-2 py-0.5 mt-1 self-start text-[10px] font-bold text-emerald-800">
                        <span>🛡️ Kas Bersih:</span>
                        <span class="font-extrabold" x-text="formatRupiah({{ (int) ($activeRevenue - $activeExpenses) }})">Rp {{ number_format($activeRevenue - $activeExpenses, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-[11px] font-extrabold {{ $revenueGrowthPercent >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                        @if($revenueGrowthPercent > 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="truncate">+{{ number_format($revenueGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @elseif($revenueGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($revenueGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 shrink-0 bg-white/80 text-emerald-600 border border-emerald-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition duration-300 absolute top-5 right-5 z-10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Card 2: Total Profit Bersih -->
            <div id="card-profit"
                 x-on:click="toggleCard('profit')"
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #ccfbf1 100%); border: 1px solid #99f6e4;"
                 :class="activeCard === 'profit' ? 'ring-4 ring-teal-500/40 scale-[1.03] shadow-xl border-teal-500' : 'hover:scale-[1.02] shadow-md border-teal-200/50'"
                 class="p-5 sm:p-6 rounded-3xl flex flex-col justify-between gap-4 group transition duration-300 cursor-pointer select-none relative overflow-hidden">
                <!-- Soft Glow Backdrop Decoration -->
                <div class="absolute -right-10 -top-10 w-24 h-24 rounded-full bg-teal-500/10 blur-2xl group-hover:bg-teal-500/20 transition duration-300"></div>

                <div class="flex flex-col gap-1 min-w-0 flex-1 z-10 pr-10">
                    <div class="flex items-center gap-1.5 pr-10">
                        <span class="text-[10px] font-black text-teal-700 uppercase tracking-widest">Total Profit Bersih</span>
                        <span class="text-[8px] font-black text-teal-700 leading-none bg-teal-100/60 border border-teal-200 px-1 py-0.5 rounded tracking-wide shrink-0">NET</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight mt-0.5 whitespace-nowrap" x-text="formatRupiah({{ (int) $activeProfit }})">Rp {{ number_format($activeProfit, 0, ',', '.') }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-extrabold {{ $profitGrowthPercent >= 0 ? 'text-teal-600' : 'text-rose-600' }} mt-1">
                        @if($profitGrowthPercent > 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="truncate">+{{ number_format($profitGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @elseif($profitGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($profitGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 shrink-0 bg-white/80 text-teal-600 border border-teal-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white transition duration-300 absolute top-5 right-5 z-10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
            </div>

            <!-- Card 3: Total Transaksi -->
            <div id="card-transactions"
                 x-on:click="toggleCard('transactions')"
                 style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1px solid #fecdd3;"
                 :class="activeCard === 'transactions' ? 'ring-4 ring-rose-500/40 scale-[1.03] shadow-xl border-rose-500' : 'hover:scale-[1.02] shadow-md border-rose-200/50'"
                 class="p-5 sm:p-6 rounded-3xl flex flex-col justify-between gap-4 group transition duration-300 cursor-pointer select-none relative overflow-hidden">
                <!-- Soft Glow Backdrop Decoration -->
                <div class="absolute -right-10 -top-10 w-24 h-24 rounded-full bg-rose-500/10 blur-2xl group-hover:bg-rose-500/20 transition duration-300"></div>

                <div class="flex flex-col gap-1 min-w-0 flex-1 z-10 pr-10">
                    <span class="text-[10px] font-black text-rose-700 uppercase tracking-widest pr-10">Total Transaksi</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight mt-0.5 whitespace-nowrap" x-text="transactions + ' Transaksi'">{{ $activeTransactionsCount }} Transaksi</h3>
                    <div class="flex items-center gap-1 text-[11px] font-extrabold {{ $transactionGrowthPercent >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                        @if($transactionGrowthPercent > 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="truncate">+{{ number_format($transactionGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @elseif($transactionGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($transactionGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 shrink-0 bg-white/80 text-rose-600 border border-rose-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 group-hover:bg-rose-600 group-hover:text-white transition duration-300 absolute top-5 right-5 z-10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5h6.75M8.625 12.75h6.75" />
                    </svg>
                </div>
            </div>

            <!-- Card 4: Total Pengeluaran -->
            <div id="card-expenses"
                 x-on:click="toggleCard('expenses')"
                 style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;"
                 :class="activeCard === 'expenses' ? 'ring-4 ring-amber-500/40 scale-[1.03] shadow-xl border-amber-500' : 'hover:scale-[1.02] shadow-md border-amber-200/50'"
                 class="p-5 sm:p-6 rounded-3xl flex flex-col justify-between gap-4 group transition duration-300 cursor-pointer select-none relative overflow-hidden">
                <!-- Soft Glow Backdrop Decoration -->
                <div class="absolute -right-10 -top-10 w-24 h-24 rounded-full bg-amber-500/10 blur-2xl group-hover:bg-amber-500/20 transition duration-300"></div>

                <div class="flex flex-col gap-1 min-w-0 flex-1 z-10 pr-10">
                    <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest pr-10">Total Pengeluaran</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight mt-0.5 whitespace-nowrap" x-text="formatRupiah({{ (int) $activeExpenses }})">Rp {{ number_format($activeExpenses, 0, ',', '.') }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-extrabold {{ $expenseGrowthPercent >= 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                        @if($expenseGrowthPercent > 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="truncate">+{{ number_format($expenseGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @elseif($expenseGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($expenseGrowthPercent, 1) }}% @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil @if($activeFilter === 'today') vs kemarin @elseif($activeFilter === 'weekly') vs pekan lalu @else vs bulan lalu @endif</span>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 shrink-0 bg-white/80 text-amber-600 border border-amber-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white transition duration-300 absolute top-5 right-5 z-10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. MIDDLE ROW: Sales Trends Bar Graph & Low Stock Table -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Beautiful Sales Trend Bar Graph (HTML/CSS) -->
            <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col justify-between gap-6">
                <!-- Chart Header -->
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-base leading-tight" x-text="timeframe === 'Mingguan' ? 'Tren Omset Mingguan' : 'Tren Omset Bulanan'">Tren Omset Mingguan</h4>
                        <p class="text-xs text-slate-400 font-medium mt-1" x-text="timeframe === 'Mingguan' ? 'Laporan omset harian dalam minggu ini' : 'Laporan omset mingguan dalam bulan ini'">Laporan omset harian dalam minggu ini</p>
                    </div>
                    <div class="flex gap-1.5 p-1 bg-slate-50 border border-slate-100 rounded-xl">
                        <button type="button" x-on:click="timeframe = 'Mingguan'" :class="timeframe === 'Mingguan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150">Mingguan</button>
                        <button type="button" x-on:click="timeframe = 'Bulanan'" :class="timeframe === 'Bulanan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150">Bulanan</button>
                    </div>
                </div>

                <!-- Pure CSS/HTML Responsive Bar Chart -->
                <div class="relative pt-4">
                    <!-- Chart body -->
                    <div class="flex items-end justify-between h-56 gap-2 sm:gap-4 md:gap-6 border-b border-slate-100 pb-1 px-2 relative z-10">
                        
                        <!-- Back gridlines -->
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none -z-10">
                            <div class="w-full border-t border-slate-100"></div>
                            <div class="w-full border-t border-slate-100"></div>
                            <div class="w-full border-t border-slate-100"></div>
                            <div class="w-full border-t border-slate-100"></div>
                        </div>

                        <!-- 1. WEEKLY TREND BARS -->
                        <div x-show="timeframe === 'Mingguan'" class="flex items-end justify-between w-full h-full gap-2 sm:gap-4 md:gap-6 relative">
                            @foreach($weeklyTrend as $day)
                            <div class="flex-1 flex flex-col items-center gap-2 group">
                                <!-- Tooltip showing the exact Omset -->
                                <div class="text-[10px] font-bold opacity-0 group-hover:opacity-100 transition duration-150 bg-slate-900 text-white py-0.5 px-1.5 rounded shadow whitespace-nowrap z-30 pointer-events-none">
                                    Rp {{ number_format($day['omset'], 0, ',', '.') }}
                                </div>
                                <!-- Bar -->
                                <div class="w-full sm:w-10 rounded-t-lg transition-all duration-300 shadow-inner 
                                    {{ $day['is_today'] ? 'bg-emerald-600 group-hover:bg-emerald-700 shadow-md shadow-emerald-500/20' : 'bg-emerald-100 group-hover:bg-emerald-600' }}" 
                                    style="height: {{ max(6, $day['height_percent']) }}%">
                                </div>
                                <!-- Day Name -->
                                <span class="text-xs {{ $day['is_today'] ? 'text-slate-800 font-extrabold' : 'text-slate-400 font-bold' }} mt-1" title="{{ $day['full_day_name'] }} ({{ $day['date'] }})">
                                    {{ $day['day_name'] }}
                                </span>
                            </div>
                            @endforeach
                        </div>

                        <!-- 2. MONTHLY TREND BARS -->
                        <div x-show="timeframe === 'Bulanan'" class="flex items-end justify-between w-full h-full gap-2 sm:gap-4 md:gap-6 relative" style="display: none;">
                            @foreach($monthlyTrend as $week)
                            <div class="flex-1 flex flex-col items-center gap-2 group">
                                <!-- Tooltip showing the exact Omset -->
                                <div class="text-[10px] font-bold opacity-0 group-hover:opacity-100 transition duration-150 bg-slate-900 text-white py-0.5 px-1.5 rounded shadow whitespace-nowrap z-30 pointer-events-none">
                                    Rp {{ number_format($week['omset'], 0, ',', '.') }}
                                </div>
                                <!-- Bar -->
                                <div class="w-full sm:w-10 rounded-t-lg transition-all duration-300 shadow-inner 
                                    {{ $week['is_today'] ? 'bg-emerald-600 group-hover:bg-emerald-700 shadow-md shadow-emerald-500/20' : 'bg-emerald-100 group-hover:bg-emerald-600' }}" 
                                    style="height: {{ max(6, $week['height_percent']) }}%">
                                </div>
                                <!-- Week Name -->
                                <span class="text-xs {{ $week['is_today'] ? 'text-slate-800 font-extrabold' : 'text-slate-400 font-bold' }} mt-1" title="{{ $week['full_name'] }} (Tanggal {{ $week['date'] }})">
                                    {{ $week['label'] }}
                                </span>
                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>

            <!-- Side alert section: Low Stock products detailed listing -->
            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                <div>
                    <h4 class="font-extrabold text-slate-800 text-base leading-tight">Detail Peringatan Stok</h4>
                    <p class="text-xs text-slate-400 font-medium mt-1">Segera restok produk yang hampir habis:</p>
                </div>
                
                <div class="flex flex-col gap-3 overflow-y-auto max-h-[220px] pr-1">
                    @forelse($lowStockProducts as $product)
                        <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100 flex flex-col gap-2">
                            <div class="flex justify-between items-start gap-2">
                                <h5 class="font-extrabold text-sm text-slate-800 truncate" title="{{ $product->name }}">{{ $product->name }}</h5>
                                <span class="bg-rose-100 text-rose-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-rose-200 shrink-0">
                                    Sisa {{ $product->stock }} {{ $product->price_unit }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500 font-semibold">
                                <span>SKU: {{ $product->sku }}</span>
                                <span>Kategori: {{ $product->category }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-sm font-semibold">
                            Tidak ada produk dengan stok menipis.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- 3. BOTTOM ROW: Dynamic Financial Breakdown Table (Collapsible on Card Click) -->
        <div id="breakdown-table-container" x-show="showTable" x-transition style="display: none;"
             class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h4 class="font-extrabold text-slate-800 text-base leading-tight">
                        @if($activeFilter === 'today')
                            Daftar Transaksi Terkini 
                        @elseif($activeFilter === 'weekly')
                            Rincian Pendapatan Harian Pekan Ini
                        @else
                            Rincian Pendapatan Mingguan Bulan Ini
                        @endif
                        <span id="table-focus-badge" class="text-xs font-bold px-2.5 py-0.5 rounded-full uppercase ml-1.5 border"
                            x-show="activeCard !== null"
                            :class="activeCard === 'omset' ? 'bg-emerald-50 text-emerald-800 border-emerald-100' : (activeCard === 'profit' ? 'bg-teal-50 text-teal-800 border-teal-100' : 'bg-rose-50 text-rose-800 border-rose-100')"
                            x-text="activeCard === 'omset' ? 'Fokus Omset' : (activeCard === 'profit' ? 'Fokus Profit' : 'Fokus Transaksi')">
                        </span>
                    </h4>
                    <p class="text-xs text-slate-400 font-medium mt-1">
                        @if($activeFilter === 'today')
                            Daftar transaksi kasir yang tercatat pada hari ini
                        @elseif($activeFilter === 'weekly')
                            Laporan analisis kinerja keuangan harian dari hari Senin sampai Minggu
                        @else
                            Laporan analisis kinerja keuangan mingguan dalam bulan berjalan
                        @endif
                    </p>
                </div>
                
                <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto justify-end">
                    <!-- Button Ekspor Excel (Visible on all filters) -->
                    <a href="{{ route('owner.dashboard.export', ['filter' => $activeFilter, 'branch' => $selectedBranch]) }}" 
                       class="px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span>Ekspor Excel</span>
                    </a>

                    @if($activeFilter === 'today')
                        <a href="{{ route('owner.transactions.index') }}" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow transition duration-150 flex items-center gap-1.5">
                            <span>Lihat Semua Riwayat</span>
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto w-full max-w-full rounded-2xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                    @if($activeFilter === 'today')
                        <!-- Headers for Today's Transactions -->
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Waktu</th>
                                <th class="px-5 py-4">Kode Transaksi</th>
                                <th class="px-5 py-4">Kasir</th>
                                <th class="px-5 py-4">Ringkasan Item</th>
                                <th class="px-5 py-4">Metode</th>
                                <th data-focus="omset" class="px-5 py-4 text-right transition-colors duration-150" :class="activeCard === 'omset' || activeCard === 'transactions' ? 'bg-emerald-50/50 text-emerald-800' : ''">Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($breakdownData as $trx)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                        {{ $trx->created_at->translatedFormat('d M Y - H:i') }}
                                    </td>
                                    <td class="px-5 py-4 text-emerald-700 font-mono text-xs">
                                        {{ $trx->transaction_code }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-extrabold shrink-0">
                                                {{ strtoupper(substr($trx->cashier->name ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="text-slate-700">{{ $trx->cashier->name ?? 'N/A' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs">
                                        <div class="flex flex-wrap gap-1 max-w-[280px]">
                                            @foreach(explode(', ', $trx->items_summary) as $itemStr)
                                                @if(trim($itemStr))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50/60 text-emerald-800 border border-emerald-100/50">
                                                        {{ trim($itemStr) }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border
                                            {{ $trx->payment_method === 'Cash' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                            {{ $trx->payment_method === 'QRIS' ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
                                            {{ $trx->payment_method === 'Debit' ? 'bg-sky-50 text-sky-700 border-sky-100' : '' }}
                                        ">
                                            {{ $trx->payment_method }}
                                        </span>
                                    </td>
                                    <td data-focus="omset" class="px-5 py-4 text-right font-extrabold whitespace-nowrap transition-colors duration-150 text-emerald-700" :class="activeCard === 'omset' || activeCard === 'transactions' ? 'bg-emerald-50/30 text-emerald-800' : 'text-emerald-700'">
                                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat transaksi hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    @else
                        <!-- Headers for Weekly / Monthly Breakdown Laporan Financial -->
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Periode</th>
                                <th data-focus="omset" class="px-5 py-4 text-right transition-colors duration-150" :class="activeCard === 'omset' ? 'bg-emerald-50/50 text-emerald-800' : ''">Total Omset</th>
                                <th data-focus="profit" class="px-5 py-4 text-right transition-colors duration-150" :class="activeCard === 'profit' ? 'bg-teal-50/50 text-teal-800' : ''">Profit Bersih</th>
                                <th class="px-5 py-4 text-center">Margin Keuntungan</th>
                                <th data-focus="transactions" class="px-5 py-4 text-center transition-colors duration-150" :class="activeCard === 'transactions' ? 'bg-rose-50/50 text-rose-800' : ''">Jumlah Transaksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($breakdownData as $row)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800">{{ $row['label'] }}</span>
                                            <span class="text-xs text-slate-400 font-semibold">{{ $row['sub_label'] }}</span>
                                        </div>
                                    </td>
                                    <td data-focus="omset" class="px-5 py-4 text-right font-extrabold whitespace-nowrap transition-colors duration-150 text-emerald-700" :class="activeCard === 'omset' ? 'bg-emerald-50/30 text-emerald-800' : 'text-emerald-700'">
                                        Rp {{ number_format($row['omset'], 0, ',', '.') }}
                                    </td>
                                    <td data-focus="profit" class="px-5 py-4 text-right font-extrabold whitespace-nowrap transition-colors duration-150 text-teal-700" :class="activeCard === 'profit' ? 'bg-teal-50/30 text-teal-800' : 'text-teal-700'">
                                        Rp {{ number_format($row['profit'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold border
                                            {{ $row['omset'] > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-100' }}
                                        ">
                                            {{ $row['omset'] > 0 ? round(($row['profit'] / $row['omset']) * 100) : 0 }}%
                                        </span>
                                    </td>
                                    <td data-focus="transactions" class="px-5 py-4 text-center font-bold whitespace-nowrap transition-colors duration-150 text-slate-600" :class="activeCard === 'transactions' ? 'bg-rose-50/30 text-rose-800' : 'text-slate-600'">
                                        {{ $row['count'] }} Transaksi
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan rincian keuangan untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    @endif
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
