<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Riwayat Transaksi — Admin') }}
        </h2>
    </x-slot>

    <div x-data="{
        activeTab: '{{ request()->has('expense_page') ? 'expenses' : 'sales' }}',
        showEditModal: false,
        showDeleteModal: false,
        showCancelModal: false,
        showDeleteExpenseModal: false,
        editForm: { id: null, items_summary: '', total_price: 0, payment_method: 'Cash', payment_status: 'paid' },
        deleteId: null,
        cancelId: null,
        cancelCode: '',
        deleteExpenseId: null,
        successMsg: '',

        confirmCancel(id, code) {
            this.cancelId = id;
            this.cancelCode = code;
            this.showCancelModal = true;
        },

        executeCancel() {
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            fetch('/admin/transactions/' + this.cancelId + '/cancel', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showCancelModal = false;
                    if (window.showToast) {
                        window.showToast(data.message, 'success');
                    } else {
                        this.successMsg = data.message;
                    }
                    setTimeout(() => location.reload(), 1400);
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'Gagal membatalkan transaksi.', 'error');
                    } else {
                        alert(data.message || 'Gagal membatalkan transaksi.');
                    }
                }
            })
            .catch(e => {
                if (window.showToast) {
                    window.showToast('Gagal membatalkan: ' + e, 'error');
                } else {
                    alert('Gagal membatalkan: ' + e);
                }
            });
        },

        openEdit(trx) {
            this.editForm = {
                id: trx.id,
                items_summary: trx.items_summary,
                total_price: trx.total_price,
                payment_method: trx.payment_method,
                payment_status: trx.payment_status || 'paid'
            };
            this.showEditModal = true;
        },

        submitEdit() {
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            fetch('/admin/transactions/' + this.editForm.id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    items_summary: this.editForm.items_summary,
                    total_price: parseInt(this.editForm.total_price),
                    payment_method: this.editForm.payment_method,
                    payment_status: this.editForm.payment_status
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showEditModal = false;
                    if (window.showToast) {
                        window.showToast(data.message || 'Transaksi berhasil diperbarui!', 'success');
                    } else {
                        this.successMsg = data.message;
                    }
                    setTimeout(() => location.reload(), 1400);
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'Gagal memperbarui transaksi.', 'error');
                    } else {
                        alert(data.message || 'Gagal memperbarui transaksi.');
                    }
                }
            })
            .catch(e => {
                if (window.showToast) {
                    window.showToast('Gagal memperbarui: ' + e, 'error');
                } else {
                    alert('Gagal memperbarui: ' + e);
                }
            });
        },

        confirmDelete(id) {
            this.deleteId = id;
            this.showDeleteModal = true;
        },

        executeDelete() {
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            fetch('/admin/transactions/' + this.deleteId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showDeleteModal = false;
                    if (window.showToast) {
                        window.showToast(data.message || 'Transaksi berhasil dihapus!', 'success');
                    } else {
                        this.successMsg = data.message;
                    }
                    setTimeout(() => location.reload(), 1400);
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'Gagal menghapus transaksi.', 'error');
                    } else {
                        alert(data.message || 'Gagal menghapus transaksi.');
                    }
                }
            })
            .catch(e => {
                if (window.showToast) {
                    window.showToast('Gagal menghapus: ' + e, 'error');
                } else {
                    alert('Gagal menghapus: ' + e);
                }
            });
        },

        confirmDeleteExpense(id) {
            this.deleteExpenseId = id;
            this.showDeleteExpenseModal = true;
        },

        executeDeleteExpense() {
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            fetch('/kasir/expenses/' + this.deleteExpenseId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showDeleteExpenseModal = false;
                    if (window.showToast) {
                        window.showToast(data.message || 'Pengeluaran berhasil dihapus!', 'success');
                    } else {
                        this.successMsg = data.message;
                    }
                    setTimeout(() => location.reload(), 1400);
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'Gagal menghapus pengeluaran.', 'error');
                    } else {
                        alert(data.message || 'Gagal menghapus pengeluaran.');
                    }
                }
            })
            .catch(e => {
                if (window.showToast) {
                    window.showToast('Gagal menghapus: ' + e, 'error');
                } else {
                    alert('Gagal menghapus: ' + e);
                }
            });
        },

        formatRupiah(num) {
            if (num === null || num === undefined) return 'Rp 0';
            return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        },

        markPaid(trxId) {
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            fetch(`/admin/transactions/${trxId}/mark-as-paid`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.showToast) {
                        window.showToast(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message);
                }
            })
            .catch(e => alert(e));
        }
    }" class="flex flex-col gap-6 max-w-full overflow-hidden">

        <!-- Header & Date Filter -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Semua Transaksi</h3>
                <p class="text-sm text-slate-400 font-medium mt-1">Kelola seluruh riwayat transaksi dari semua kasir</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto shrink-0">
                <form method="GET" action="{{ route('admin.transactions.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <!-- Dropdown Filter Cabang -->
                    <select name="branch" 
                            class="text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-3.5 py-2.5 bg-slate-50 text-slate-700 font-medium">
                        <option value="">🌐 Semua Cabang</option>
                        @foreach($branches as $branchName)
                            @if($branchName)
                                <option value="{{ $branchName }}" {{ request('branch') === $branchName ? 'selected' : '' }}>
                                    📍 {{ $branchName }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Dropdown Filter Periode -->
                    <select name="filter_type" 
                            class="text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-3.5 py-2.5 bg-slate-50 text-slate-700 font-medium">
                        <option value="harian" {{ request('filter_type') === 'harian' ? 'selected' : '' }}>📅 Harian</option>
                        <option value="mingguan" {{ request('filter_type') === 'mingguan' ? 'selected' : '' }}>📅 Mingguan</option>
                        <option value="bulanan" {{ request('filter_type') === 'bulanan' ? 'selected' : '' }}>📅 Bulanan</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <input type="date" name="date" value="{{ request()->has('date') ? request('date') : \Carbon\Carbon::today()->toDateString() }}"
                            class="text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-3 py-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow transition duration-150 py-2.5">Filter</button>
                        @if(request()->has('date') || request('branch') || request('filter_type'))
                            <a href="{{ route('admin.transactions.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition duration-150 py-2.5">Reset</a>
                        @endif
                    </div>
                </form>

                <!-- Button Ekspor Excel -->
                <a href="{{ route('admin.transactions.export', ['date' => request('date'), 'branch' => request('branch'), 'filter_type' => request('filter_type')]) }}" 
                   class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center gap-1.5 py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Ekspor Excel</span>
                </a>

                <!-- Button Ekspor PDF -->
                <a href="{{ route('admin.transactions.export', ['date' => request('date'), 'branch' => request('branch'), 'filter_type' => request('filter_type'), 'format' => 'pdf']) }}" 
                   class="px-4 py-2 bg-rose-50 border border-rose-200 text-rose-800 hover:bg-rose-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center gap-1.5 py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Ekspor PDF</span>
                </a>

                <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1.5 rounded-full whitespace-nowrap" x-show="activeTab === 'sales'">
                    Total: {{ $transactions->total() }} Penjualan
                </span>
                <span class="bg-rose-100 text-rose-800 font-bold text-xs px-3 py-1.5 rounded-full whitespace-nowrap" x-show="activeTab === 'expenses'" style="display: none;">
                    Total: {{ $expenses->total() }} Pengeluaran
                </span>
            </div>
        </div>

        <!-- Laporan Omset & Profit Cards (Today, Weekly & Monthly) -->
        <!-- Row 1: Hari Ini -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Today Omset Card (Gold/Amber Gradient) -->
            <div style="background: linear-gradient(135deg, #92400e 0%, #78350f 60%, #451a03 100%);" class="text-white rounded-3xl p-6 shadow-xl border border-amber-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20 text-amber-300 pointer-events-none transform rotate-12">
                    <svg class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-300">☀ Omset {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</span>
                    <h3 class="text-3xl font-black mt-2 leading-none text-white">Rp {{ number_format($todayOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(120,53,15,0.6); border: 1px solid rgba(217,119,6,0.3);" class="text-amber-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $targetDate->translatedFormat('l, d F Y') }}
                    </span>
                </div>
            </div>

            <!-- Today Profit Card (Deep Violet/Indigo Gradient) -->
            <div style="background: linear-gradient(135deg, #4c1d95 0%, #3b0764 60%, #1e1b4b 100%);" class="text-white rounded-3xl p-6 shadow-xl border border-violet-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20 text-violet-300 pointer-events-none transform rotate-12">
                    <svg class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-300">⚡ Profit {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</span>
                    <h3 class="text-3xl font-black mt-2 leading-none text-white">Rp {{ number_format($todayProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(76,29,149,0.6); border: 1px solid rgba(167,139,250,0.3);" class="text-violet-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $todayOmset > 0 ? round(($todayProfit / $todayOmset) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Rincian Metode Pembayaran Tanggal Terpilih --}}
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-md">
            <div class="flex items-center gap-2 mb-4">
                <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-xl">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0016.5 13.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.75A1.125 1.125 0 002.625 11.625v1.5a1.125 1.125 0 001.125 1.125z" />
                    </svg>
                </span>
                <div>
                    <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Rincian Metode Pembayaran {{ $targetDate->isToday() ? 'Hari Ini' : 'Terfilter' }}</h4>
                    <p class="text-xs text-slate-400 font-medium">Pembagian omset berdasarkan metode pembayaran untuk tanggal {{ $targetDate->translatedFormat('d M Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Tunai --}}
                <div class="bg-emerald-50/40 border border-emerald-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-1">💵 Bayar Tunai</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayCash, 0, ',', '.') }}
                    </p>
                </div>
                {{-- Non-Tunai QRIS --}}
                <div class="bg-teal-50/40 border border-teal-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider mb-1">📱 Non-Tunai (QRIS)</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayQris, 0, ',', '.') }}
                    </p>
                </div>
                {{-- Non-Tunai Debit/TF --}}
                <div class="bg-sky-50/40 border border-sky-100 rounded-2xl p-4 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider mb-1">💳 Non-Tunai (Debit/TF)</span>
                    <p class="text-xl font-black text-slate-800 tracking-tight">
                        Rp {{ number_format((int)$todayDebit, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Row 2: Pekan & Bulan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Weekly Omset Card (Dark Forest Green Gradient) -->
            <div style="background: linear-gradient(135deg, #1b4332 0%, #081c15 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-emerald-800/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Omset Pekan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($weeklyOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(27,67,50,0.6); border: 1px solid rgba(52,211,153,0.3);" class="text-emerald-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $startOfWeek->translatedFormat('d M') }} - {{ $endOfWeek->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>

            <!-- Weekly Profit Card (Vibrant Jade/Emerald Green Gradient) -->
            <div style="background: linear-gradient(135deg, #2d6a4f 0%, #0d3b25 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-emerald-600/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-200">Profit Pekan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($weeklyProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(13,59,37,0.6); border: 1px solid rgba(52,211,153,0.3);" class="text-emerald-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $weeklyOmset > 0 ? round(($weeklyProfit / $weeklyOmset) * 100) : 0 }}%
                    </span>
                </div>
            </div>

            <!-- Monthly Omset Card (Midnight Slate Gradient) -->
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-slate-700/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Omset Bulan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($monthlyOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(30,41,59,0.6); border: 1px solid rgba(148,163,184,0.3);" class="text-slate-200 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $targetDate->translatedFormat('F Y') }}
                    </span>
                </div>
            </div>

            <!-- Monthly Profit Card (Teal/Emerald Gradient) -->
            <div style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);" class="text-white rounded-3xl p-6 shadow-lg border border-teal-600/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-15 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-200">Profit Bulan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white">Rp {{ number_format($monthlyProfit, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(15,118,110,0.6); border: 1px solid rgba(45,212,191,0.3);" class="text-teal-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Margin: {{ $monthlyOmset > 0 ? round(($monthlyProfit / $monthlyOmset) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Transactions & Expenses Card -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
            
            <!-- Navigation Tabs -->
            <div class="flex border-b border-slate-100 bg-slate-50/50 p-2 gap-2">
                <button type="button"
                        @click="activeTab = 'sales'"
                        :class="activeTab === 'sales' 
                            ? 'bg-white text-emerald-800 shadow-sm border-slate-200/60 font-bold' 
                            : 'text-slate-400 hover:text-slate-600 font-semibold'"
                        class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs uppercase tracking-wider transition border border-transparent">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M3.75 20.25h16.5M3.75 16.5h16.5m-16.5-12h16.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                    <span>Riwayat Penjualan</span>
                </button>
                <button type="button"
                        @click="activeTab = 'expenses'"
                        :class="activeTab === 'expenses' 
                            ? 'bg-white text-rose-800 shadow-sm border-slate-200/60 font-bold' 
                            : 'text-slate-400 hover:text-slate-600 font-semibold'"
                        class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs uppercase tracking-wider transition border border-transparent">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>Riwayat Pengeluaran</span>
                </button>
            </div>

            <!-- TAB 1: SALES TRANSACTIONS -->
            <div x-show="activeTab === 'sales'" class="w-full">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal & Waktu</th>
                                <th class="px-5 py-4">Kode Transaksi</th>
                                <th class="px-5 py-4">Kasir</th>
                                <th class="px-5 py-4">Cabang</th>
                                <th class="px-5 py-4">Ringkasan Item</th>
                                <th class="px-5 py-4">Metode</th>
                                <th class="px-5 py-4 text-center">Status</th>
                                <th class="px-5 py-4 text-right">Total</th>
                                <th class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($transactions as $trx)
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
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200">
                                            {{ $trx->branch ?? 'Pusat Cianjur' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs">
                                        <div class="flex flex-col gap-1.5 max-w-[320px] py-1">
                                            @forelse($trx->parsed_items as $item)
                                                <div class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-emerald-50/50 border border-slate-100 text-xs transition duration-150">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        <span class="font-bold text-slate-800 truncate" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200/50 whitespace-nowrap">
                                                            {{ $item['display_qty'] }}
                                                        </span>
                                                        @if($item['unit_price'] > 0)
                                                            <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">
                                                                @ Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <span class="text-xs text-slate-400 italic">Tidak ada rincian item</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border
                                            {{ $trx->payment_method === 'Cash' || $trx->payment_method === 'Tunai' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                            {{ $trx->payment_method === 'QRIS' || $trx->payment_method === 'Transfer Bank' ? 'bg-teal-50 text-teal-700 border-teal-100' : '' }}
                                            {{ $trx->payment_method === 'Debit' ? 'bg-sky-50 text-sky-700 border-sky-100' : '' }}
                                            {{ $trx->payment_method === 'Piutang / Tempo' ? 'bg-rose-50 text-rose-700 border-rose-100' : '' }}
                                        ">
                                            {{ $trx->payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        @if(($trx->payment_status ?? 'paid') === 'paid')
                                            <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">Lunas</span>
                                        @elseif(($trx->payment_status ?? 'paid') === 'cancelled')
                                            <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700 border border-slate-300">Dibatalkan</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200">Tempo</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-emerald-700 font-extrabold whitespace-nowrap">
                                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Wholesale Print/PDF Actions -->
                                            @if($trx->transaction_type === 'wholesale')
                                                <a href="{{ route('admin.wholesale-transactions.print', $trx) }}" 
                                                   target="_blank"
                                                   class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition duration-150 text-xs text-center inline-flex items-center"
                                                   title="Cetak Nota (A4)" style="text-decoration: none;">
                                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.wholesale-transactions.print', [$trx, 'format' => 'pdf']) }}" 
                                                   target="_blank"
                                                   class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition duration-150 text-xs text-center inline-flex items-center"
                                                   title="Unduh PDF" style="text-decoration: none;">
                                                    <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                                    </svg>
                                                </a>
                                                @if(($trx->payment_status ?? 'paid') === 'unpaid')
                                                    <button type="button"
                                                        @click="markPaid({{ $trx->id }})"
                                                        class="px-2.5 py-1.5 text-[10px] font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg transition duration-150 shadow-sm"
                                                        title="Tandai Lunas">
                                                        Lunas
                                                    </button>
                                                @endif
                                            @else
                                                <!-- Retail Print Actions -->
                                                @php
                                                    $adminTrxData = [
                                                        'id' => $trx->id,
                                                        'transaction_code' => $trx->transaction_code,
                                                        'created_at_formatted' => $trx->created_at->translatedFormat('d M Y - H:i'),
                                                        'payment_method' => $trx->payment_method,
                                                        'payment_status' => $trx->payment_status ?? 'paid',
                                                        'branch' => $trx->branch ?? 'Pusat Cianjur',
                                                        'cashier_name' => $trx->cashier->name ?? 'Kasir',
                                                        'customer_name' => $trx->customer_name ?? '-',
                                                        'customer_phone' => $trx->customer_phone ?? '-',
                                                        'discount' => (float)$trx->discount,
                                                        'total_price' => (float)$trx->total_price,
                                                        'items_summary' => $trx->items_summary,
                                                        'parsed_items' => $trx->parsed_items,
                                                    ];
                                                @endphp
                                                <button type="button"
                                                    @click="printDirectBluetooth(@js($adminTrxData))"
                                                    class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 transition duration-150 inline-flex items-center"
                                                    title="Cetak Bluetooth Thermal">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75v16.5l6-4.5-6-4.5 6-4.5-6-4.5z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15.75l-6-4.5m6-4.5l-6 4.5"/>
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                    onclick="printReceipt({{ $trx->id }})"
                                                    class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition duration-150 inline-flex items-center"
                                                    title="Cetak Struk Browser">
                                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                                    </svg>
                                                </button>
                                            @endif
                                            
                                            <!-- Edit Button -->
                                            <button type="button"
                                                @click="openEdit({
                                                    id: {{ $trx->id }},
                                                    items_summary: {{ json_encode($trx->items_summary) }},
                                                    total_price: {{ $trx->total_price }},
                                                    payment_method: '{{ $trx->payment_method }}',
                                                    payment_status: '{{ $trx->payment_status ?? 'paid' }}'
                                                })"
                                                class="p-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-100 transition duration-150"
                                                title="Edit Transaksi">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </button>

                                            <!-- Cancel Button (Kembalikan Stok) -->
                                            @if(($trx->payment_status ?? 'paid') !== 'cancelled')
                                                <button type="button"
                                                    @click="confirmCancel({{ $trx->id }}, '{{ $trx->transaction_code }}')"
                                                    class="p-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-600 border border-orange-200 transition duration-150 inline-flex items-center"
                                                    title="Batalkan Transaksi (Kembalikan Stok)">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @endif

                                            <!-- Delete Button -->
                                            <button type="button"
                                                @click="confirmDelete({{ $trx->id }})"
                                                class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition duration-150"
                                                title="Hapus Transaksi (Kembalikan Stok)">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat transaksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sales Pagination -->
                @if ($transactions->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            <!-- TAB 2: OPERATIONAL EXPENSES -->
            <div x-show="activeTab === 'expenses'" class="w-full" style="display: none;">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal & Waktu</th>
                                <th class="px-5 py-4">Kasir</th>
                                <th class="px-5 py-4">Cabang</th>
                                <th class="px-5 py-4">Kategori</th>
                                <th class="px-5 py-4">Keterangan</th>
                                <th class="px-5 py-4 text-right">Jumlah</th>
                                <th class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($expenses as $exp)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                        {{ $exp->created_at->translatedFormat('d M Y - H:i') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-800 flex items-center justify-center text-[10px] font-extrabold shrink-0">
                                                {{ strtoupper(substr($exp->cashier->name ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="text-slate-700">{{ $exp->cashier->name ?? 'N/A' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200">
                                            {{ $exp->branch ?? 'Pusat Cianjur' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-50 text-rose-700 border border-rose-100">
                                            {{ $exp->category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs truncate max-w-[250px]" title="{{ $exp->description }}">
                                        {{ $exp->description }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-rose-700 font-extrabold whitespace-nowrap">
                                        - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center">
                                            <!-- Delete Button -->
                                            <button type="button"
                                                @click="confirmDeleteExpense({{ $exp->id }})"
                                                class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition duration-150"
                                                title="Hapus Pengeluaran">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat pengeluaran.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Expenses Pagination -->
                @if ($expenses->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $expenses->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- EDIT MODAL -->
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="showEditModal = false">

            <div class="bg-white rounded-3xl p-6 w-full max-w-lg border border-slate-100 shadow-2xl flex flex-col gap-5"
                 @click.away="showEditModal = false">

                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-slate-800 text-lg">Edit Transaksi</h3>
                    <button type="button" @click="showEditModal = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Ringkasan Item -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Ringkasan Item</label>
                    <textarea x-model="editForm.items_summary" rows="3"
                        class="w-full border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner"></textarea>
                </div>

                <!-- Total Harga -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Total Harga (Rp)</label>
                    <input type="number" x-model="editForm.total_price" min="0"
                        class="w-full border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                    <select x-model="editForm.payment_method"
                        class="w-full border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                        <option value="Cash">Cash</option>
                        <option value="QRIS">QRIS</option>
                        <option value="Debit">Debit</option>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Piutang / Tempo">Piutang / Tempo</option>
                    </select>
                </div>

                <!-- Status Pembayaran -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Pembayaran</label>
                    <select x-model="editForm.payment_status"
                        class="w-full border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                        <option value="paid">Lunas (Paid)</option>
                        <option value="unpaid">Belum Lunas (Unpaid)</option>
                        <option value="cancelled">Dibatalkan (Kembalikan Stok)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button type="button" @click="showEditModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                    <button type="button" @click="submitEdit()" class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold shadow-md shadow-emerald-700/10 transition duration-150">Simpan</button>
                </div>
            </div>
        </div>

        <!-- CANCEL TRANSACTION CONFIRMATION MODAL -->
        <div x-show="showCancelModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="showCancelModal = false">

            <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col items-center gap-5 text-center"
                 @click.away="showCancelModal = false">

                <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center">
                    <svg class="h-7 w-7 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg">Batalkan Transaksi?</h3>
                    <p class="text-sm text-slate-500 font-medium mt-1">
                        Transaksi <span class="font-bold text-slate-700 font-mono" x-text="cancelCode"></span> akan dibatalkan dan seluruh stok produk di dalamnya akan <span class="text-emerald-700 font-bold">otomatis dikembalikan ke inventori</span>.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 w-full pt-2">
                    <button type="button" @click="showCancelModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Tutup</button>
                    <button type="button" @click="executeCancel()" class="py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl font-bold shadow-md transition duration-150">Ya, Batalkan</button>
                </div>
            </div>
        </div>

        <!-- DELETE CONFIRMATION MODAL -->
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="showDeleteModal = false">

            <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col items-center gap-5 text-center"
                 @click.away="showDeleteModal = false">

                <div class="w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center">
                    <svg class="h-7 w-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg">Hapus Transaksi?</h3>
                    <p class="text-sm text-slate-500 font-medium mt-1">Data transaksi akan dihapus secara permanen dan seluruh stok produk akan <span class="text-emerald-700 font-bold">otomatis dikembalikan ke inventori</span>.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 w-full pt-2">
                    <button type="button" @click="showDeleteModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                    <button type="button" @click="executeDelete()" class="py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-bold shadow-md transition duration-150">Hapus</button>
                </div>
            </div>
        </div>

        <!-- DELETE EXPENSE CONFIRMATION MODAL -->
        <div x-show="showDeleteExpenseModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="showDeleteExpenseModal = false">

            <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col items-center gap-5 text-center"
                 @click.away="showDeleteExpenseModal = false">

                <div class="w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center">
                    <svg class="h-7 w-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg">Hapus Pengeluaran?</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Tindakan ini tidak dapat dibatalkan. Catatan pengeluaran operasional ini akan dihapus secara permanen.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 w-full pt-2">
                    <button type="button" @click="showDeleteExpenseModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                    <button type="button" @click="executeDeleteExpense()" class="py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-bold shadow-md transition duration-150">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printReceipt(transactionId) {
            if (!transactionId) return;
            const iframeId = 'receipt-print-iframe';
            let iframe = document.getElementById(iframeId);
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = iframeId;
                iframe.style.position = 'fixed';
                iframe.style.right = '-1000px';
                iframe.style.bottom = '-1000px';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = 'none';
                document.body.appendChild(iframe);
            }
            iframe.onload = function() {
                setTimeout(() => {
                    try { iframe.contentWindow.focus(); iframe.contentWindow.print(); }
                    catch (e) { console.error('Print failed:', e); }
                }, 300);
            };
            iframe.src = '/kasir/transactions/' + transactionId + '/print';
        }

        async function printDirectBluetooth(transaction) {
            if (!transaction) return;

            if (!navigator.bluetooth) {
                const msg = "Browser ini belum mendukung Web Bluetooth. Gunakan browser Google Chrome / Microsoft Edge di PC atau Chrome Android.";
                if (window.showToast) {
                    window.showToast(msg, "error");
                } else {
                    alert(msg);
                }
                return;
            }

            try {
                if (window.showToast) {
                    window.showToast("Mencari printer Bluetooth...", "info");
                }

                // 1. Scan for BLE Bluetooth printers
                const device = await navigator.bluetooth.requestDevice({
                    filters: [
                        { namePrefix: 'Printer' },
                        { namePrefix: 'MTP' },
                        { namePrefix: 'PT-' },
                        { namePrefix: 'POS' },
                        { namePrefix: 'RPP' },
                        { namePrefix: 'RP' },
                        { namePrefix: 'MPT' },
                        { namePrefix: 'InnerPrinter' },
                        { namePrefix: 'Bluetooth' }
                    ],
                    optionalServices: [
                        '000018f0-0000-1000-8000-00805f9b34fb', // Standard BLE printer service UUID
                        '0000e914-0000-1000-8000-00805f9b34fb', // Custom BLE printer service UUID
                        '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent
                        '0000ffe0-0000-1000-8000-00805f9b34fb'  // HM-10 / common BLE
                    ]
                });

                // 2. Connect GATT
                const server = await device.gatt.connect();

                // 3. Find Primary Service
                let service;
                const candidateUuids = [
                    '000018f0-0000-1000-8000-00805f9b34fb',
                    '0000e914-0000-1000-8000-00805f9b34fb',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                    '0000ffe0-0000-1000-8000-00805f9b34fb'
                ];

                for (const uuid of candidateUuids) {
                    try {
                        service = await server.getPrimaryService(uuid);
                        if (service) break;
                    } catch (e) {}
                }

                if (!service) {
                    const services = await server.getPrimaryServices();
                    if (services && services.length > 0) {
                        service = services[0];
                    } else {
                        throw new Error("Layanan printer Bluetooth tidak ditemukan!");
                    }
                }

                // 4. Get Characteristic
                const characteristics = await service.getCharacteristics();
                const writeCharacteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);
                if (!writeCharacteristic) {
                    throw new Error("Karakteristik penulisan data printer tidak ditemukan!");
                }

                // 5. Build raw ESC/POS payload (32 columns standard for 58mm)
                const formatRupiahRaw = (num) => 'Rp ' + Math.round(num || 0).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                const pad2Col = (left, right, width = 32) => {
                    left = String(left || '');
                    right = String(right || '');
                    const spaces = width - left.length - right.length;
                    if (spaces >= 0) {
                        return left + ' '.repeat(spaces) + right + '\n';
                    } else {
                        return left.substring(0, Math.max(0, width - right.length - 1)) + ' ' + right + '\n';
                    }
                };

                const ESC_INIT = '\x1b\x40';
                const ESC_ALIGN_CENTER = '\x1b\x61\x01';
                const ESC_ALIGN_LEFT = '\x1b\x61\x00';
                const ESC_BOLD_ON = '\x1b\x45\x01';
                const ESC_BOLD_OFF = '\x1b\x45\x00';
                const ESC_DOUBLE_SIZE = '\x1b\x21\x30';
                const ESC_NORMAL_SIZE = '\x1b\x21\x00';

                let escpos = '';
                escpos += ESC_INIT;
                escpos += ESC_ALIGN_CENTER;
                escpos += ESC_BOLD_ON + ESC_DOUBLE_SIZE + 'PUSAT KURMA CIANJUR\n' + ESC_NORMAL_SIZE;
                escpos += ESC_BOLD_ON + 'Al karim\n' + ESC_BOLD_OFF;
                escpos += 'Cianjur, Jawa Barat\n';
                escpos += '================================\n';

                escpos += ESC_ALIGN_LEFT;
                escpos += `No. Struk : ${transaction.transaction_code || '-'}\n`;
                escpos += `Waktu     : ${transaction.created_at_formatted || '-'}\n`;
                if (transaction.cashier_name) {
                    escpos += `Kasir     : ${transaction.cashier_name}\n`;
                }
                if (transaction.branch) {
                    escpos += `Cabang    : ${transaction.branch}\n`;
                }
                if (transaction.customer_name && transaction.customer_name !== '-') {
                    escpos += `Pelanggan : ${transaction.customer_name}\n`;
                }
                escpos += `Metode    : ${(transaction.payment_method || 'CASH').toUpperCase()}\n`;
                escpos += '--------------------------------\n';

                // Items
                const parsedItems = transaction.parsed_items && transaction.parsed_items.length > 0
                    ? transaction.parsed_items
                    : null;

                if (parsedItems) {
                    parsedItems.forEach(it => {
                        escpos += ESC_BOLD_ON + (it.name || '-') + '\n' + ESC_BOLD_OFF;
                        const qtyText = `  ${it.display_qty || it.qty} x ${formatRupiahRaw(it.unit_price)}`;
                        const subText = formatRupiahRaw(it.total_price);
                        escpos += pad2Col(qtyText, subText, 32);
                    });
                } else if (transaction.items_summary) {
                    const rawParts = transaction.items_summary.split(', ');
                    rawParts.forEach(part => {
                        escpos += ESC_BOLD_ON + part + '\n' + ESC_BOLD_OFF;
                    });
                }
                escpos += '--------------------------------\n';

                // Financial totals
                const discount = Number(transaction.discount || 0);
                const total = Number(transaction.total_price || 0);
                const subtotal = total + discount;

                escpos += pad2Col('Subtotal', formatRupiahRaw(subtotal), 32);
                if (discount > 0) {
                    escpos += pad2Col('Diskon', '-' + formatRupiahRaw(discount), 32);
                }
                escpos += ESC_BOLD_ON;
                escpos += pad2Col('TOTAL', formatRupiahRaw(total), 32);
                escpos += ESC_BOLD_OFF;
                escpos += '================================\n';

                escpos += ESC_ALIGN_CENTER;
                escpos += '*** TERIMA KASIH ***\n';
                escpos += 'Terima kasih atas kunjungan Anda\n';
                escpos += 'Barang yang dibeli tidak dapat\n';
                escpos += 'ditukar / dikembalikan\n\n\n\n';

                // 6. Encode and write in chunks of 20 bytes
                const encoder = new TextEncoder();
                const dataBytes = encoder.encode(escpos);
                const chunkSize = 20;

                for (let i = 0; i < dataBytes.length; i += chunkSize) {
                    const chunk = dataBytes.slice(i, i + chunkSize);
                    await writeCharacteristic.writeValue(chunk);
                    await new Promise(r => setTimeout(r, 25));
                }

                if (window.showToast) {
                    window.showToast("Cetak struk via Bluetooth berhasil!", "success");
                }
            } catch (error) {
                console.error("Direct Bluetooth printing failed:", error);
                if (window.showToast) {
                    window.showToast("Cetak Bluetooth gagal: " + error.message, "error");
                } else {
                    alert("Cetak Bluetooth gagal: " + error.message);
                }
            }
        }
    </script>
</x-app-layout>
