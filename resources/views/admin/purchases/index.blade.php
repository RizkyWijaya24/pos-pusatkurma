<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Stok Masuk, Pembelian & Kelola Utang Piutang') }}
        </h2>
    </x-slot>

    <div x-data="purchaseManagementState({{ json_encode($products) }}, {{ json_encode($locations) }}, {{ json_encode($suppliers) }}, {{ json_encode($categories) }})"
         class="flex flex-col gap-6 max-w-full overflow-hidden">

        <!-- ======================================================= -->
        <!-- TOP STATS CARDS (EXECUTIVE DASHBOARD STYLE) -->
        <!-- ======================================================= -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Belanja Bulan Ini (Dark Forest Emerald) -->
            <div @click="setTab('history')"
                 style="background: linear-gradient(135deg, #1b4332 0%, #081c15 100%);"
                 class="text-white rounded-3xl p-6 shadow-lg border border-emerald-800/30 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:shadow-xl transition-all duration-200 active:scale-[0.99]">
                <div style="opacity: 0.12;" class="absolute -right-4 -bottom-4 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.948c0-.621-.504-1.125-1.125-1.125H4.125C3.504 5.5 3 6.004 3 6.625v7.5c0 .621.504 1.125 1.125 1.125h1.5" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Belanja Bulan Ini</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white tracking-tight">Rp {{ number_format($monthlyTotal, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(27,67,50,0.7); border: 1px solid rgba(52,211,153,0.3);" class="text-emerald-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $monthlyCount }} Faktur Masuk • {{ now()->translatedFormat('F Y') }}
                    </span>
                </div>
            </div>

            <!-- Card 2: Total Faktur Pembelian (Teal / Ocean) -->
            <div @click="setTab('history')"
                 style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);"
                 class="text-white rounded-3xl p-6 shadow-lg border border-teal-600/30 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:shadow-xl transition-all duration-200 active:scale-[0.99]">
                <div style="opacity: 0.12;" class="absolute -right-4 -bottom-4 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-200">Total Faktur Tercatat</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white tracking-tight">{{ number_format($purchases->total()) }} <span class="text-sm font-semibold text-teal-200">Faktur</span></h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(15,118,110,0.7); border: 1px solid rgba(45,212,191,0.3);" class="text-teal-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        Seluruh Riwayat Pengadaan
                    </span>
                </div>
            </div>

            <!-- Card 3: Sisa Utang Supplier (Deep Wine Crimson) -->
            <div @click="setTab('payables')"
                 style="background: linear-gradient(135deg, #881337 0%, #4c0519 100%);"
                 class="text-white rounded-3xl p-6 shadow-lg border border-rose-800/30 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:shadow-xl transition-all duration-200 active:scale-[0.99]">
                <div style="opacity: 0.12;" class="absolute -right-4 -bottom-4 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-300">Sisa Utang ke Supplier</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white tracking-tight">Rp {{ number_format($totalOutstandingDebt, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(136,19,55,0.7); border: 1px solid rgba(251,113,133,0.3);" class="text-rose-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $payables->count() }} Faktur Tempo Aktif
                    </span>
                </div>
            </div>

            <!-- Card 4: Piutang Usaha Pelanggan (Royal Sapphire Blue) -->
            <div @click="setTab('receivables')"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);"
                 class="text-white rounded-3xl p-6 shadow-lg border border-blue-700/30 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:shadow-xl transition-all duration-200 active:scale-[0.99]">
                <div style="opacity: 0.12;" class="absolute -right-4 -bottom-4 text-white pointer-events-none transform rotate-12">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                    </svg>
                </div>
                <div class="z-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-300">Piutang Pelanggan (Tagihan)</span>
                    <h3 class="text-2xl font-black mt-2 leading-none text-white tracking-tight">Rp {{ number_format($totalReceivablesAmount, 0, ',', '.') }}</h3>
                </div>
                <div class="mt-4 flex items-center z-10">
                    <span style="background: rgba(30,58,138,0.7); border: 1px solid rgba(147,197,253,0.3);" class="text-blue-100 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                        {{ $receivablesCustomerCount }} Pelanggan • {{ $receivablesInvoicesCount }} Nota Bon
                    </span>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- NAVIGATION TABS -->
        <!-- ======================================================= -->
        <div class="flex items-center flex-wrap gap-2 bg-white border border-slate-100 rounded-2xl p-1.5 shadow-sm w-fit">
            <button type="button"
                    @click="setTab('input')"
                    :class="activeTab === 'input' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Input Stok Masuk</span>
            </button>
            <button type="button"
                    @click="setTab('history')"
                    :class="activeTab === 'history' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.03 0 1.9.732 2.076 1.714m-5.8 0a2.251 2.251 0 00-2.338-.043M9 6.75h-.75a2.25 2.25 0 00-2.25 2.25v10.5a2.25 2.25 0 002.25 2.25h3.75a2.25 2.25 0 002.25-2.25V9A2.25 2.25 0 0012 6.75h-.75M9 6.75c0-.414.336-.75.75-.75h1.5a.75.75 0 01.75.75M9 6.75h3" />
                </svg>
                <span>Riwayat Pembelian</span>
            </button>
            <button type="button"
                    @click="setTab('payables')"
                    :class="activeTab === 'payables' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Buku Utang Supplier</span>
                @if($payables->count() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="activeTab === 'payables' ? 'bg-white text-emerald-800' : 'bg-amber-100 text-amber-800'">
                        {{ $payables->count() }}
                    </span>
                @endif
            </button>
            <button type="button"
                    @click="setTab('receivables')"
                    :class="activeTab === 'receivables' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                </svg>
                <span>Kelola Piutang Pelanggan</span>
                @if($receivablesCustomerCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="activeTab === 'receivables' ? 'bg-white text-emerald-800' : 'bg-blue-100 text-blue-800'">
                        {{ $receivablesCustomerCount }}
                    </span>
                @endif
            </button>
        </div>

        <!-- ======================================================= -->
        <!-- TAB 1: FORM INPUT STOK MASUK (FAKTUR PEMBELIAN) -->
        <!-- ======================================================= -->
        <div x-show="activeTab === 'input'" class="space-y-6">
            <form @submit.prevent="submitPurchase()" class="bg-white rounded-3xl border border-slate-100 shadow-md p-6 lg:p-8 space-y-8">
                
                <!-- Section 1: Header Info Supplier & Lokasi -->
                <div>
                    <h3 class="text-base font-extrabold text-slate-800 mb-1 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Informasi Faktur & Pemasok (Supplier)
                    </h3>
                    <p class="text-xs text-slate-400 font-medium mb-5">Lengkapi identitas supplier dan lokasi penyimpanan fisik stok yang diterima.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Nama Supplier -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Nama Supplier / Toko Pemasok <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   list="supplier-suggestions"
                                   x-model="form.supplier_name"
                                   @input="onSupplierSelected()"
                                   required
                                   placeholder="Contoh: PT Kurma Barokah / H. Ahmad"
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 px-3.5 py-2.5">
                            <datalist id="supplier-suggestions">
                                <template x-for="s in suppliersList" :key="s.id">
                                    <option :value="s.name"></option>
                                </template>
                            </datalist>
                        </div>

                        <!-- No. WhatsApp / Telepon -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">No. Telp / WhatsApp Supplier</label>
                            <input type="text"
                                   x-model="form.supplier_phone"
                                   placeholder="Contoh: 081234567890"
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 px-3.5 py-2.5">
                        </div>

                        <!-- No. Faktur / Surat Jalan Supplier -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">No. Faktur / Surat Jalan Fisik</label>
                            <input type="text"
                                   x-model="form.invoice_number"
                                   placeholder="Contoh: INV/2026/098"
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 px-3.5 py-2.5">
                        </div>

                        <!-- Lokasi Stok Masuk -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Lokasi Penerimaan Stok <span class="text-rose-500">*</span></label>
                            <select x-model="form.location_id"
                                    required
                                    class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 px-3.5 py-2.5 bg-slate-50">
                                <template x-for="loc in locationsList" :key="loc.id">
                                    <option :value="loc.id" x-text="loc.name + (loc.type === 'gudang' ? ' (Gudang)' : ' (Toko)')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Section 2: Items Builder -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Daftar Barang / Produk Masuk
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Pilih produk via pencarian nama/SKU, masukkan kuantitas, dan harga modal (HPP).</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="openQuickProductModal(null)"
                                    class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs border border-slate-200 transition flex items-center gap-1.5 shadow-sm active:scale-95"
                                    title="Daftarkan SKU / master produk baru langsung tanpa meninggalkan halaman ini">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>+ SKU Baru</span>
                            </button>
                            <button type="button"
                                    @click="addItemRow()"
                                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md shadow-emerald-200 transition flex items-center gap-1.5 active:scale-95">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>Tambah Baris Produk</span>
                            </button>
                        </div>
                    </div>

                    <!-- Items Table with Searchable Selector -->
                    <div class="border border-slate-100 rounded-2xl shadow-sm bg-white" style="overflow: visible; position: relative; z-index: 20;">
                        <div style="overflow: visible;">
                            <table class="w-full text-left text-xs" style="overflow: visible;">
                                <thead class="bg-slate-50 font-bold text-slate-600 uppercase text-[10px] tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="px-4 py-3" style="width: 360px; min-width: 340px;">Produk / SKU</th>
                                        <th class="px-3 py-3 w-32 text-center">Jumlah (Qty)</th>
                                        <th class="px-3 py-3 w-28 text-center">Satuan</th>
                                        <th class="px-4 py-3 min-w-[150px] text-right">Harga Modal Satuan (Rp)</th>
                                        <th class="px-4 py-3 min-w-[150px] text-right">Subtotal (Rp)</th>
                                        <th class="px-3 py-3 w-28 text-center" title="Perbarui harga modal produk di master data">Update HPP</th>
                                        <th class="px-3 py-3 w-12 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" style="overflow: visible;">
                                    <template x-for="(item, index) in form.items" :key="index">
                                        <tr class="hover:bg-slate-50/50" style="overflow: visible;">
                                            <!-- Product Searchable Combobox -->
                                            <td class="px-4 py-3 align-top" style="overflow: visible; position: relative;">
                                                <div class="relative" style="overflow: visible;" @click.away="item.is_open = false">
                                                    <!-- Input Box with Search Icon -->
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                        </div>
                                                        <input type="text"
                                                               x-model="item.search_query"
                                                               @focus="openSearch(index)"
                                                               @click="openSearch(index)"
                                                               @input="onSearchInput(index)"
                                                               @keydown.escape="item.is_open = false"
                                                               placeholder="Ketik nama produk, SKU, kategori..."
                                                               autocomplete="off"
                                                               class="w-full text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 pl-9 pr-14 transition-all"
                                                               :class="item.product_id ? 'bg-emerald-50/40 text-emerald-950 border-emerald-300 ring-1 ring-emerald-200' : 'bg-white text-slate-700'">

                                                        <!-- Action Buttons: Clear & Dropdown Toggle -->
                                                        <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-0.5">
                                                            <button type="button"
                                                                    x-show="item.product_id || item.search_query"
                                                                    @click="clearProduct(index)"
                                                                    class="p-1 text-slate-400 hover:text-rose-500 rounded-lg transition"
                                                                    title="Reset pilihan produk">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                            <button type="button"
                                                                    @click="toggleSearch(index)"
                                                                    class="p-1 text-slate-400 hover:text-slate-700 rounded-lg transition">
                                                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="item.is_open ? 'rotate-180 text-emerald-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Selected Product Mini Badge Indicator -->
                                                    <template x-if="item.product_id">
                                                        <div class="flex items-center gap-2 mt-1.5 px-1 text-[11px] text-slate-400">
                                                            <span class="font-mono text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.2 rounded" x-text="getSelectedProduct(item.product_id)?.sku || '-'"></span>
                                                            <span class="font-bold text-slate-600" x-text="'Stok: ' + (getSelectedProduct(item.product_id)?.stock ?? 0) + ' ' + (getSelectedProduct(item.product_id)?.price_unit ?? '')"></span>
                                                            <span>•</span>
                                                            <span class="text-slate-500" x-text="'HPP: ' + formatRupiah(getSelectedProduct(item.product_id)?.cost_price ?? 0)"></span>
                                                        </div>
                                                    </template>

                                                    <!-- Dropdown Results Menu (Inline CSS for absolute guarantee of visibility) -->
                                                    <div x-show="item.is_open"
                                                         style="position: absolute; top: calc(100% + 6px); left: 0; width: 100%; min-width: 380px; max-width: 480px; z-index: 99999; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1); display: flex; flex-direction: column;"
                                                         class="overflow-hidden text-left">
                                                        
                                                        <!-- Results List -->
                                                        <div style="max-height: 260px; overflow-y: auto;" class="divide-y divide-slate-100">
                                                            <template x-for="p in getFilteredProducts(item.search_query)" :key="p.id">
                                                                <div @click="selectProduct(index, p)"
                                                                     class="p-2.5 hover:bg-emerald-50 transition cursor-pointer flex items-center justify-between gap-3 group">
                                                                    <div class="min-w-0 flex-1">
                                                                        <div class="font-extrabold text-xs text-slate-800 group-hover:text-emerald-900 truncate" x-text="p.name"></div>
                                                                        <div class="flex items-center gap-1.5 mt-0.5 text-[10px]">
                                                                            <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-1 py-0.2 rounded" x-text="p.sku || '-'"></span>
                                                                            <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium capitalize" x-text="p.category || 'Umum'"></span>
                                                                            <span class="text-slate-400 font-bold" x-text="'• Stok: ' + p.stock + ' ' + (p.price_unit || '')"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right shrink-0">
                                                                        <div class="text-[10px] text-slate-400 font-medium">HPP Terakhir</div>
                                                                        <div class="font-black text-slate-700 text-xs" x-text="formatRupiah(p.cost_price)"></div>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <!-- Empty Search State -->
                                                            <template x-if="getFilteredProducts(item.search_query).length === 0">
                                                                <div class="p-4 text-center text-slate-400">
                                                                    <p class="text-xs font-semibold text-slate-600">Produk tidak ditemukan</p>
                                                                    <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada produk dengan kata kunci "<span x-text="item.search_query" class="font-bold text-slate-700"></span>"</p>
                                                                </div>
                                                            </template>
                                                        </div>

                                                        <!-- Footer: + Buat SKU Baru -->
                                                        <div class="p-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                                                            <span class="text-[10px] text-slate-500 font-medium">SKU belum terdaftar?</span>
                                                            <button type="button"
                                                                    @click="openQuickProductModal(index, item.search_query)"
                                                                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-[11px] shadow-sm transition flex items-center gap-1 active:scale-95">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                                                </svg>
                                                                <span>+ Buat SKU Baru</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Qty -->
                                            <td class="px-3 py-3">
                                                <input type="number"
                                                       step="any"
                                                       min="0.001"
                                                       x-model.number="item.quantity"
                                                       @input="calculateTotals()"
                                                       required
                                                       placeholder="0"
                                                       class="w-full text-center text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                            </td>

                                            <!-- Unit -->
                                            <td class="px-3 py-3">
                                                <input type="text"
                                                       x-model="item.unit"
                                                       required
                                                       placeholder="dus / kg / pcs"
                                                       class="w-full text-center text-xs font-semibold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                            </td>

                                            <!-- Unit Cost -->
                                            <td class="px-4 py-3">
                                                <input type="number"
                                                       min="0"
                                                       x-model.number="item.unit_cost"
                                                       @input="calculateTotals()"
                                                       required
                                                       placeholder="0"
                                                       class="w-full text-right text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                            </td>

                                            <!-- Subtotal -->
                                            <td class="px-4 py-3 text-right font-extrabold text-slate-800">
                                                <span x-text="formatRupiah(item.quantity * item.unit_cost)"></span>
                                            </td>

                                            <!-- Update HPP Checkbox -->
                                            <td class="px-3 py-3 text-center">
                                                <input type="checkbox"
                                                       x-model="item.update_product_cost"
                                                       class="rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                                            </td>

                                            <!-- Remove Row Button -->
                                            <td class="px-3 py-3 text-center">
                                                <button type="button"
                                                        @click="removeItemRow(index)"
                                                        :disabled="form.items.length <= 1"
                                                        class="p-1.5 text-slate-300 hover:text-rose-600 disabled:opacity-30 disabled:hover:text-slate-300 transition rounded-lg">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Section 3: Pembayaran & Total Tagihan -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    
                    <!-- Kiri: Opsi Pembayaran (Lunas vs Tempo) -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Ketentuan Pembayaran</h4>

                        <!-- Radio Mode Bayar -->
                        <div class="grid grid-cols-2 gap-3">
                            <label @click="form.is_tempo = false"
                                   class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3 transition"
                                   :class="!form.is_tempo ? 'bg-emerald-50/70 border-emerald-300 text-emerald-900 shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                <input type="radio" name="payment_term" :checked="!form.is_tempo" @change="form.is_tempo = false" class="text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="block text-xs font-extrabold">Lunas Seketika</span>
                                    <span class="block text-[11px] text-slate-400">Dibayar langsung tunai / transfer</span>
                                </div>
                            </label>

                            <label @click="form.is_tempo = true"
                                   class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3 transition"
                                   :class="form.is_tempo ? 'bg-amber-50/70 border-amber-300 text-amber-900 shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                <input type="radio" name="payment_term" :checked="form.is_tempo" @change="form.is_tempo = true" class="text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="block text-xs font-extrabold">Tempo (Utang Dagang)</span>
                                    <span class="block text-[11px] text-slate-400">Bayar bertahap atau cicilan nanti</span>
                                </div>
                            </label>
                        </div>

                        <!-- Jika Lunas: Pilih Cara Bayar -->
                        <div x-show="!form.is_tempo" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Metode Bayar</label>
                                <select x-model="form.payment_method" class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                                    <option value="Tunai">Tunai / Kas Toko</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Debit">Kartu Debit</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Tanggal Pembayaran</label>
                                <input type="date" x-model="form.purchase_date" class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                            </div>
                        </div>

                        <!-- Jika Tempo: Input Jatuh Tempo & DP -->
                        <div x-show="form.is_tempo" class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/60 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 mb-1.5">Tanggal Jatuh Tempo <span class="text-rose-500">*</span></label>
                                    <input type="date"
                                           x-model="form.due_date"
                                           :required="form.is_tempo"
                                           class="w-full text-xs font-medium border-amber-200 rounded-xl focus:border-amber-500 focus:ring-amber-500 py-2.5 px-3 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 mb-1.5">Uang Muka / DP (Rp)</label>
                                    <input type="number"
                                           min="0"
                                           :max="totalAmount"
                                           x-model.number="form.initial_deposit"
                                           placeholder="0"
                                           class="w-full text-xs font-medium border-amber-200 rounded-xl focus:border-amber-500 focus:ring-amber-500 py-2.5 px-3 bg-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-amber-900 mb-1.5">Metode Bayar DP</label>
                                <select x-model="form.payment_method" class="w-full text-xs font-medium border-amber-200 rounded-xl focus:border-amber-500 focus:ring-amber-500 py-2 px-3 bg-white">
                                    <option value="Tunai">Tunai / Kas Toko</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                </select>
                            </div>
                        </div>

                        <!-- Catatan Faktur -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan Tambahan</label>
                            <textarea x-model="form.notes"
                                      rows="2"
                                      placeholder="Keterangan kurir, kondisi barang, dsb (opsional)"
                                      class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 p-3"></textarea>
                        </div>
                    </div>

                    <!-- Kanan: Ringkasan Total Tagihan (Modern Executive Billing Card) -->
                    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); border: 1px solid rgba(148, 163, 184, 0.2); color: #ffffff;"
                         class="rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5 relative overflow-hidden flex flex-col justify-between">

                        <!-- Header Card -->
                        <div class="flex items-center justify-between z-10 relative">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest block" style="color: #34d399;">Kalkulasi Otomatis</span>
                                <h4 class="text-base font-black text-white mt-0.5">Ringkasan Tagihan Masuk</h4>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                  :style="form.is_tempo 
                                      ? 'background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.4);' 
                                      : 'background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4);'"
                                  x-text="form.is_tempo ? 'TEMPO / UTANG' : 'LUNAS PENUH'"></span>
                        </div>

                        <!-- Financial Breakdown Box -->
                        <div class="z-10 relative space-y-3 p-4 rounded-2xl" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div class="flex justify-between items-center text-xs">
                                <span style="color: #94a3b8;">Total Item Produk</span>
                                <span class="font-bold text-white" x-text="form.items.length + ' Baris Produk'"></span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span style="color: #94a3b8;">Subtotal Nilai Barang</span>
                                <span class="font-extrabold text-white text-sm" x-text="formatRupiah(totalAmount)"></span>
                            </div>
                            
                            <!-- Tempo / Utang Detail -->
                            <template x-if="form.is_tempo">
                                <div class="space-y-2.5 pt-3 border-t" style="border-color: rgba(255, 255, 255, 0.1);">
                                    <div class="flex justify-between items-center text-xs">
                                        <span style="color: #38bdf8;">Uang Muka (DP Dibayar)</span>
                                        <span class="font-bold" style="color: #38bdf8;" x-text="formatRupiah(form.initial_deposit || 0)"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span style="color: #fb7185; font-weight: 700;">Sisa Utang Usaha</span>
                                        <span class="font-black text-sm" style="color: #fb7185;" x-text="formatRupiah(Math.max(0, totalAmount - (form.initial_deposit || 0)))"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Grand Total Highlight Section -->
                        <div class="z-10 relative p-4 rounded-2xl flex items-center justify-between" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3);">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: #a7f3d0;">Grand Total Tagihan</span>
                                <div class="text-2xl sm:text-3xl font-black tracking-tight mt-0.5" style="color: #34d399;" x-text="formatRupiah(totalAmount)"></div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold block" style="color: #94a3b8;">Metode Bayar</span>
                                <span class="text-xs font-black text-white" x-text="form.payment_method"></span>
                            </div>
                        </div>

                        <!-- Submit Action Button -->
                        <div class="z-10 relative pt-1">
                            <button type="submit"
                                    :disabled="isSubmitting || totalAmount <= 0"
                                    style="background: linear-gradient(135deg, #059669 0%, #0d9488 100%);"
                                    class="w-full py-3.5 hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black text-sm rounded-2xl shadow-xl shadow-emerald-950/40 transition duration-150 active:scale-[0.98] flex items-center justify-center gap-2">
                                <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <svg x-show="isSubmitting" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" style="display: none;">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Memproses Data Stok...' : 'Simpan & Tambahkan ke Stok'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ======================================================= -->
        <!-- TAB 2: RIWAYAT PEMBELIAN (DAFTAR FAKTUR MASUK) -->
        <!-- ======================================================= -->
        <div x-show="activeTab === 'history'" class="space-y-4">
            
            <!-- Filter Bar -->
            <div class="bg-white rounded-3xl border border-slate-100 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.purchases.index') }}" class="flex flex-wrap items-center gap-2 text-xs w-full lg:w-auto">
                    <!-- Maintain History Tab across filtering -->
                    <input type="hidden" name="tab" value="history">

                    <!-- Tanggal Mulai -->
                    <input type="date"
                           name="start_date"
                           value="{{ request('start_date') }}"
                           class="text-xs border-slate-200 rounded-xl focus:border-emerald-500 py-2 px-3">
                    <span class="text-slate-400 font-bold">s/d</span>
                    <!-- Tanggal Selesai -->
                    <input type="date"
                           name="end_date"
                           value="{{ request('end_date') }}"
                           class="text-xs border-slate-200 rounded-xl focus:border-emerald-500 py-2 px-3">

                    <!-- Status Bayar -->
                    <select name="status" class="text-xs border-slate-200 rounded-xl focus:border-emerald-500 py-2 px-3">
                        <option value="">Semua Status</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas</option>
                        <option value="debt" {{ request('status') === 'debt' ? 'selected' : '' }}>Tempo / Utang</option>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition shadow-sm">
                        Filter
                    </button>
                    @if(request()->hasAny(['start_date', 'end_date', 'status']))
                        <a href="{{ route('admin.purchases.index', ['tab' => 'history']) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition">
                            Reset
                        </a>
                    @endif
                </form>

                <span class="text-xs font-semibold text-slate-400">
                    Menampilkan {{ $purchases->count() }} dari {{ $purchases->total() }} faktur
                </span>
            </div>

            <!-- Table Riwayat Pembelian -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal &amp; Kode</th>
                                <th class="px-5 py-4">Pemasok (Supplier)</th>
                                <th class="px-5 py-4">Lokasi Masuk</th>
                                <th class="px-5 py-4">Barang Masuk</th>
                                <th class="px-5 py-4 text-right">Total Tagihan</th>
                                <th class="px-5 py-4 text-center">Status Bayar</th>
                                <th class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse ($purchases as $p)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <!-- Tanggal & Kode -->
                                    <td class="px-5 py-4">
                                        <div class="text-xs font-mono font-bold text-emerald-800">{{ $p->purchase_code }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">{{ $p->purchase_date->translatedFormat('d M Y') }}</div>
                                        @if($p->invoice_number)
                                            <div class="text-[10px] text-slate-500 mt-0.5">Faktur: <span class="font-bold">{{ $p->invoice_number }}</span></div>
                                        @endif
                                    </td>

                                    <!-- Supplier -->
                                    <td class="px-5 py-4 text-xs">
                                        <div class="font-bold text-slate-800">{{ $p->supplier_name }}</div>
                                        @if($p->supplier_phone)
                                            <div class="text-[11px] text-slate-400">{{ $p->supplier_phone }}</div>
                                        @endif
                                    </td>

                                    <!-- Lokasi Masuk -->
                                    <td class="px-5 py-4 text-xs">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $p->location->name ?? 'Gudang' }}
                                        </span>
                                    </td>

                                    <!-- Ringkasan Barang -->
                                    <td class="px-5 py-4 text-xs">
                                        <div class="flex flex-col gap-1 max-w-[280px]">
                                            @foreach($p->items->take(2) as $it)
                                                <div class="flex items-center justify-between gap-2 px-2 py-1 rounded-lg bg-slate-50 border border-slate-100 text-[11px]">
                                                    <span class="font-bold text-slate-700 truncate" title="{{ $it->product_name }}">{{ $it->product_name }}</span>
                                                    <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded font-black shrink-0">
                                                        {{ round($it->quantity, 2) == round($it->quantity) ? (int)$it->quantity : number_format($it->quantity, 2, ',', '.') }} {{ $it->unit }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($p->items->count() > 2)
                                                <span class="text-[10px] text-slate-400 italic">+ {{ $p->items->count() - 2 }} barang lainnya</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Total Tagihan -->
                                    <td class="px-5 py-4 text-right">
                                        <div class="font-black text-emerald-700 text-sm">
                                            Rp {{ number_format($p->total_amount, 0, ',', '.') }}
                                        </div>
                                        @if(!$p->is_paid)
                                            <div class="text-[10px] font-bold text-rose-500 mt-0.5">
                                                Sisa: Rp {{ number_format($p->remaining_debt, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Status Bayar -->
                                    <td class="px-5 py-4 text-center">
                                        @if($p->is_paid)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Lunas
                                            </span>
                                        @else
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $p->is_overdue ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                                    {{ $p->is_overdue ? 'Jatuh Tempo' : 'Tempo' }}
                                                </span>
                                                @if($p->due_date)
                                                    <span class="text-[9px] text-slate-400">{{ $p->due_date->format('d/m/Y') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button"
                                                    @click="openDetailModal({{ $p->id }})"
                                                    class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl font-bold text-xs border border-slate-200 transition shadow-sm"
                                                    title="Rincian Faktur">
                                                Detail
                                            </button>
                                            @if(!$p->is_paid)
                                                <button type="button"
                                                        @click="openPayModal({{ json_encode($p) }})"
                                                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition shadow-sm"
                                                        title="Bayar Cicilan / Pelunasan">
                                                    Bayar Utang
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan faktur pembelian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($purchases->hasPages())
                    <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4">
                        {{ $purchases->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- TAB 3: BUKU UTANG SUPPLIER (HUTANG USAHA) -->
        <!-- ======================================================= -->
        <div x-show="activeTab === 'payables'" class="space-y-4">
            
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-base">Buku Utang Supplier Aktif</h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Daftar seluruh faktur pembelian tempo yang belum lunas dibayarkan ke supplier.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 font-semibold block">Total Kewajiban</span>
                    <span class="text-xl font-black text-rose-600">Rp {{ number_format($totalOutstandingDebt, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- List of Payables Cards / Table -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($payables as $pay)
                    @php 
                        $pctPaid = $pay->total_amount > 0 ? min(100, round(($pay->paid_amount / $pay->total_amount) * 100)) : 0; 
                    @endphp
                    <div class="bg-white rounded-3xl border {{ $pay->is_overdue ? 'border-rose-200 ring-2 ring-rose-100/70 shadow-rose-100/50' : 'border-slate-100 shadow-slate-100/80' }} shadow-lg hover:shadow-xl transition-all duration-200 p-5 flex flex-col justify-between space-y-4 relative overflow-hidden group">
                        
                        <!-- Top Accent Bar for Overdue -->
                        @if($pay->is_overdue)
                            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-amber-500"></div>
                        @endif

                        <!-- Header Card: Avatar, Name & Overdue Badge -->
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br {{ $pay->is_overdue ? 'from-rose-600 to-rose-800' : 'from-emerald-700 to-teal-800' }} text-white font-black flex items-center justify-center text-sm shadow-md shrink-0">
                                        {{ strtoupper(substr($pay->supplier_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-black text-slate-800 text-sm truncate" title="{{ $pay->supplier_name }}">{{ $pay->supplier_name }}</h4>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-mono mt-0.5">
                                            <span class="font-bold text-emerald-700">{{ $pay->purchase_code }}</span>
                                            @if($pay->invoice_number)
                                                <span class="truncate">| {{ $pay->invoice_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0 {{ $pay->is_overdue ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                    {{ $pay->is_overdue ? 'Lewat Tempo' : 'Tempo Aktif' }}
                                </span>
                            </div>

                            <!-- Due Date Pill & Counter -->
                            <div class="mt-3.5 p-2.5 rounded-2xl {{ $pay->is_overdue ? 'bg-rose-50/70 border border-rose-100' : 'bg-slate-50 border border-slate-100' }} flex items-center justify-between text-xs">
                                <div class="flex items-center gap-1.5 text-slate-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-medium text-[11px]">Jatuh Tempo:</span>
                                    <span class="font-black text-[11px] {{ $pay->is_overdue ? 'text-rose-700' : 'text-slate-800' }}">
                                        {{ $pay->due_date ? $pay->due_date->translatedFormat('d M Y') : '-' }}
                                    </span>
                                </div>
                                <span class="font-black text-[10px] px-2 py-0.5 rounded-lg {{ $pay->is_overdue ? 'bg-rose-200 text-rose-900' : 'bg-slate-200 text-slate-700' }}">
                                    @if($pay->is_overdue)
                                        +{{ abs($pay->days_until_due) }} hari
                                    @elseif($pay->days_until_due === 0)
                                        Hari ini!
                                    @else
                                        {{ $pay->days_until_due }} hari lagi
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar & Dual Financial Metrics -->
                        <div class="space-y-3 pt-1">
                            <!-- Progress Bar -->
                            <div>
                                <div class="flex justify-between items-center text-[11px] font-bold mb-1.5">
                                    <span class="text-slate-500">Progres Pelunasan</span>
                                    <span class="text-emerald-700">{{ $pctPaid }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/50">
                                    <div class="bg-gradient-to-r from-emerald-600 to-teal-500 h-2 rounded-full transition-all duration-500" style="width: {{ $pctPaid }}%"></div>
                                </div>
                            </div>

                            <!-- Financial Values Grid -->
                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                                <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Faktur</span>
                                    <span class="text-xs font-black text-slate-700 block mt-0.5">Rp {{ number_format($pay->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="p-2.5 rounded-2xl bg-emerald-50/60 border border-emerald-100">
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Terbayar</span>
                                    <span class="text-xs font-black text-emerald-700 block mt-0.5">Rp {{ number_format($pay->paid_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Remaining Debt Highlight -->
                            <div class="p-3 rounded-2xl bg-gradient-to-r from-rose-50 to-orange-50 border border-rose-100 flex items-center justify-between">
                                <span class="text-xs font-bold text-rose-700">Sisa Utang</span>
                                <span class="text-base font-black text-rose-600">Rp {{ number_format($pay->remaining_debt, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Actions: WA & Pay -->
                        <div class="pt-2 flex items-center gap-2">
                            @if($pay->supplier_phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $pay->supplier_phone) }}"
                                   target="_blank"
                                   class="p-2.5 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-600 rounded-2xl transition border border-slate-200 hover:border-emerald-200 shrink-0 flex items-center justify-center"
                                   title="Hubungi Supplier via WhatsApp">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.54 1.78.829 2.796.83h.005c3.18 0 5.767-2.586 5.768-5.766 0-3.18-2.587-5.767-5.773-5.773zm0 10.379h-.004c-.886 0-1.636-.25-2.316-.677l-.166-.1-.976.256.26-.951-.109-.174c-.45-.722-.689-1.543-.688-2.387.001-2.535 2.064-4.598 4.601-4.598 1.229 0 2.384.479 3.253 1.348s1.347 2.024 1.347 3.253c0 2.536-2.063 4.599-4.598 4.599z"/>
                                    </svg>
                                </a>
                            @endif
                            <button type="button"
                                    @click="openPayModal({{ json_encode($pay) }})"
                                    class="flex-1 py-2.5 px-3 bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-800 hover:to-teal-800 text-white rounded-2xl font-black text-xs shadow-md shadow-emerald-900/20 transition-all active:scale-[0.98] flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Bayar Cicilan / Lunas</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-100 shadow-sm">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="font-extrabold text-slate-800 text-sm">Tidak Ada Utang Supplier Aktif</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Semua faktur pembelian tempo telah lunas atau belum ada pembelian tempo yang dicatat.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- TAB 4: KELOLA PIUTANG PELANGGAN (NOTA PARTAI & BON) -->
        <!-- ======================================================= -->
        <div x-show="activeTab === 'receivables'" class="space-y-4">
            <style>
                .receivables-flex-container {
                    display: flex;
                    flex-direction: column;
                    gap: 24px;
                    width: 100%;
                }
                .receivables-left-panel {
                    width: 100%;
                    transition: all 0.3s ease;
                }
                .receivables-right-panel {
                    width: 100%;
                    transition: all 0.3s ease;
                }
                @media (min-width: 1024px) {
                    .receivables-flex-container {
                        flex-direction: row;
                        align-items: start;
                    }
                    .receivables-left-panel.split-view {
                        width: 40% !important;
                    }
                    .receivables-left-panel.full-view {
                        width: 100% !important;
                    }
                    .receivables-right-panel {
                        width: 60% !important;
                    }
                }
            </style>

            <!-- Sub-tab Navigation for Kelola Piutang -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                <div class="inline-flex p-1.5 bg-slate-100 rounded-2xl border border-slate-200/80 text-xs font-bold gap-1">
                    <button type="button" 
                            @click="receivablesSubTab = 'active'"
                            :class="receivablesSubTab === 'active' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            class="px-4 py-2 rounded-xl transition flex items-center gap-2">
                        <span>Piutang Aktif</span>
                        <template x-if="receivables.length > 0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-700 font-extrabold" x-text="receivables.length"></span>
                        </template>
                    </button>
                    <button type="button" 
                            @click="receivablesSubTab = 'history'; fetchReceivablesHistory()"
                            :class="receivablesSubTab === 'history' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            class="px-4 py-2 rounded-xl transition flex items-center gap-2">
                        <span>Riwayat Piutang Lunas</span>
                        <template x-if="receivablesHistory.length > 0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-extrabold" x-text="receivablesHistory.length"></span>
                        </template>
                    </button>
                    <button type="button" 
                            @click="receivablesSubTab = 'logs'; fetchReceivablesHistory()"
                            :class="receivablesSubTab === 'logs' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            class="px-4 py-2 rounded-xl transition flex items-center gap-2">
                        <span>Log Pembayaran Masuk</span>
                    </button>
                </div>

                <!-- Search bar for History / Logs -->
                <div x-show="receivablesSubTab !== 'active'" class="w-full sm:w-72">
                    <input type="text"
                           x-model="receivablesHistorySearch"
                           placeholder="Cari pelanggan / invoice..."
                           class="w-full border-slate-200 rounded-xl text-xs font-semibold focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3 shadow-inner bg-slate-50">
                </div>
            </div>

            <!-- SUB-TAB 1: PIUTANG AKTIF -->
            <div x-show="receivablesSubTab === 'active'" class="receivables-flex-container">
                
                <!-- LEFT PANEL: Customer List -->
                <div :class="selectedCustomer ? 'receivables-left-panel split-view' : 'receivables-left-panel full-view'" class="flex flex-col gap-6 shrink-0">
                    <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Daftar Rekap Piutang Pelanggan</span>
                            <button type="button" 
                                    @click="fetchReceivables()" 
                                    class="px-3 py-1.5 text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl border border-slate-200 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Segarkan</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto w-full">
                            <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                                <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                                    <tr>
                                        <th class="px-4 py-3">Pelanggan</th>
                                        <th class="px-4 py-3 text-right">Total Piutang</th>
                                        <th class="px-4 py-3 text-right">Sudah Dibayar</th>
                                        <th class="px-4 py-3 text-right text-rose-600">Sisa Tagihan</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                                    <template x-for="c in receivables" :key="c.customer_name">
                                        <tr :class="selectedCustomer && selectedCustomer.customer_name === c.customer_name ? 'bg-emerald-50/50' : ''" class="hover:bg-slate-50/50 transition duration-150">
                                            <td class="px-4 py-3.5 whitespace-nowrap">
                                                <div class="font-extrabold text-slate-800" x-text="c.customer_name"></div>
                                                <div class="text-[10px] text-slate-400" x-text="c.customer_phone || '-'"></div>
                                            </td>
                                            <td class="px-4 py-3.5 text-right whitespace-nowrap" x-text="formatRupiah(c.total_original)"></td>
                                            <td class="px-4 py-3.5 text-right text-emerald-600 whitespace-nowrap" x-text="formatRupiah(c.total_paid)"></td>
                                            <td class="px-4 py-3.5 text-right text-rose-600 font-extrabold whitespace-nowrap" x-text="formatRupiah(c.total_outstanding)"></td>
                                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                                <button type="button" 
                                                        @click="selectedCustomer = c"
                                                        class="px-3.5 py-1.5 text-xs font-bold bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-800 rounded-xl transition duration-150 border border-emerald-200">
                                                    Lihat Detail
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="receivables.length === 0">
                                        <tr>
                                            <td colspan="5" class="px-4 py-12 text-center text-slate-400 font-semibold">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <span class="font-bold text-slate-700 text-sm">Tidak ada piutang aktif saat ini. Semua tagihan telah lunas.</span>
                                                    <button type="button" 
                                                            @click="receivablesSubTab = 'history'; fetchReceivablesHistory()"
                                                            class="mt-2 px-3.5 py-1.5 text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl border border-emerald-200 transition">
                                                        Lihat Riwayat Piutang Lunas &rarr;
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANEL: Customer Debt Invoices & Installment Logs -->
                <div x-show="selectedCustomer" class="receivables-right-panel flex flex-col gap-6 shrink-0" style="display: none;">
                    <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                            <div>
                                <span class="text-xs font-black text-slate-400 uppercase tracking-wider block">Pelanggan Terpilih</span>
                                <h3 class="text-base font-extrabold text-slate-800" x-text="selectedCustomer ? selectedCustomer.customer_name : ''"></h3>
                            </div>
                            <button type="button" 
                                    @click="selectedCustomer = null"
                                    class="px-3 py-1.5 text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-xl border border-slate-200 transition duration-150">
                                Tutup Panel
                            </button>
                        </div>

                        <!-- Customer Summary Stats -->
                        <div class="flex flex-row gap-3 bg-slate-50/70 p-4 rounded-2xl border border-slate-100 justify-between">
                            <div style="flex: 1;">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wide">Total Pembelian</span>
                                <span class="text-sm font-extrabold text-slate-800" x-text="selectedCustomer ? formatRupiah(selectedCustomer.total_original) : ''"></span>
                            </div>
                            <div style="flex: 1; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wide">Sudah Dicicil</span>
                                <span class="text-sm font-extrabold text-emerald-600" x-text="selectedCustomer ? formatRupiah(selectedCustomer.total_paid) : ''"></span>
                            </div>
                            <div style="flex: 1; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wide text-rose-600">Sisa Piutang</span>
                                <span class="text-sm font-black text-rose-600" x-text="selectedCustomer ? formatRupiah(selectedCustomer.total_outstanding) : ''"></span>
                            </div>
                        </div>

                        <!-- Outstanding Invoices List -->
                        <div class="flex flex-col gap-4 mt-2">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-wider block">Daftar Invoice Belum Lunas</span>
                            
                            <template x-for="t in (selectedCustomer ? selectedCustomer.transactions : [])" :key="t.id">
                                <div class="bg-slate-50/40 border border-slate-100 hover:border-slate-200 p-5 rounded-2xl flex flex-col gap-4 transition">
                                    <!-- Invoice Main Info -->
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-xs font-black text-emerald-800 font-mono" x-text="t.transaction_code"></span>
                                            <span class="text-[10px] text-slate-400 block" x-text="t.date"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs text-slate-400 block">Sisa Tagihan</span>
                                            <span class="text-sm font-extrabold text-rose-600" x-text="formatRupiah(t.outstanding_amount)"></span>
                                        </div>
                                    </div>

                                    <!-- Price summary -->
                                    <div class="flex justify-between text-xs text-slate-600 border-t border-slate-100 pt-3">
                                        <span>Total Belanja: <strong class="text-slate-800" x-text="formatRupiah(t.total_price)"></strong></span>
                                        <span>Telah Dibayar: <strong class="text-emerald-700" x-text="formatRupiah(t.paid_amount)"></strong></span>
                                    </div>

                                    <!-- Installment Logs for this Invoice -->
                                    <div class="bg-white p-4 rounded-xl border border-slate-100 flex flex-col gap-2">
                                        <span class="text-[10px] font-bold text-slate-400 tracking-wide block">Log Pembayaran Cicilan Masuk</span>
                                        
                                        <div class="flex flex-col gap-1.5 max-h-36 overflow-y-auto pr-1">
                                            <template x-for="inst in t.installments" :key="inst.id">
                                                <div class="flex justify-between items-center text-xs border-b border-slate-50 pb-1.5 last:border-0 last:pb-0">
                                                    <div>
                                                        <span class="font-extrabold text-slate-800" x-text="formatRupiah(inst.amount)"></span>
                                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-bold" x-text="inst.payment_method"></span>
                                                        <span class="text-[10px] text-slate-400 block" x-text="inst.note || '-'"></span>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 font-bold" x-text="inst.date"></span>
                                                </div>
                                            </template>
                                            <template x-if="t.installments.length === 0">
                                                <span class="text-xs text-slate-400 italic">Belum ada cicilan yang masuk.</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                                        <!-- Open Installment Payment Modal -->
                                        <button type="button"
                                                @click="openInstallmentModal(t)"
                                                class="px-4 py-2 text-xs font-extrabold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl transition shadow-md shadow-emerald-700/10 flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>Bayar Cicilan</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            <!-- SUB-TAB 2: RIWAYAT PIUTANG LUNAS -->
            <div x-show="receivablesSubTab === 'history'" class="flex flex-col gap-6" style="display: none;">
                <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Riwayat Nota Piutang Yang Telah Lunas</span>
                            <p class="text-[11px] text-slate-400 font-medium">Daftar transaksi nota tempo yang telah selesai dibayar dan dilunasi</p>
                        </div>
                        <button type="button" 
                                @click="fetchReceivablesHistory()" 
                                class="self-start sm:self-auto px-3 py-1.5 text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl border border-slate-200 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Segarkan</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto w-full">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                                <tr>
                                    <th class="px-4 py-3">Tanggal Nota</th>
                                    <th class="px-4 py-3">Kode Nota</th>
                                    <th class="px-4 py-3">Pelanggan</th>
                                    <th class="px-4 py-3">Ringkasan Produk</th>
                                    <th class="px-4 py-3 text-right">Total Belanja</th>
                                    <th class="px-4 py-3 text-right text-emerald-600">Total Terbayar</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                                <template x-for="t in filteredReceivablesHistory" :key="t.id">
                                    <tr class="hover:bg-slate-50/50 transition duration-150">
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-500">
                                            <div class="font-bold text-slate-700" x-text="t.date"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'Lunas: ' + (t.last_payment_date || '-')"></div>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs font-mono font-bold text-emerald-800" x-text="t.transaction_code"></td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-extrabold text-slate-800" x-text="t.customer_name"></div>
                                            <div class="text-[10px] text-slate-400" x-text="t.customer_phone ? 'WA: ' + t.customer_phone : '-'"></div>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-slate-600 max-w-[260px]">
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="(item, idx) in (t.parsed_items || [])" :key="idx">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-50 text-slate-700 border border-slate-200/80">
                                                        <span class="font-extrabold text-slate-800" x-text="item.name"></span>
                                                        <span class="text-emerald-700 font-extrabold ml-1" x-text="'(' + item.display_qty + ')'"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!t.parsed_items || t.parsed_items.length === 0">
                                                    <span class="truncate block text-slate-600" :title="t.items_summary" x-text="t.items_summary || '-'"></span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-bold text-slate-700" x-text="formatRupiah(t.total_price)"></td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-extrabold text-emerald-600" x-text="formatRupiah(t.total_paid)"></td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">Lunas</span>
                                            <div class="text-[9px] text-slate-400 mt-0.5" x-text="t.installments_count ? t.installments_count + 'x Cicilan' : 'Langsung'"></div>
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button" 
                                                        @click="selectedHistoryTrx = t"
                                                        class="px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition duration-150 border border-slate-200"
                                                        title="Detail Cicilan & Produk">
                                                    Detail
                                                </button>
                                                <a :href="'/admin/wholesale-transactions/' + t.id + '/print'" 
                                                   target="_blank"
                                                   class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition text-xs inline-flex items-center justify-center"
                                                   title="Cetak Nota">
                                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                </a>
                                                <a :href="'/admin/wholesale-transactions/' + t.id + '/print?format=pdf'" 
                                                   target="_blank"
                                                   class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition text-xs inline-flex items-center justify-center"
                                                   title="Unduh PDF">
                                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredReceivablesHistory.length === 0">
                                    <tr>
                                        <td colspan="8" class="px-4 py-12 text-center text-slate-400 font-semibold">
                                            Belum ada riwayat piutang yang selesai dilunasi.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SUB-TAB 3: LOG PEMBAYARAN CICILAN MASUK -->
            <div x-show="receivablesSubTab === 'logs'" class="flex flex-col gap-6" style="display: none;">
                <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Log Riwayat Pembayaran Cicilan Masuk</span>
                            <p class="text-[11px] text-slate-400 font-medium">Semua transaksi pembayaran cicilan piutang yang telah diterima dan tercatat di sistem</p>
                        </div>
                        <button type="button" 
                                @click="fetchReceivablesHistory()" 
                                class="self-start sm:self-auto px-3 py-1.5 text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl border border-slate-200 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Segarkan</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto w-full">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                                <tr>
                                    <th class="px-4 py-3">Waktu Bayar</th>
                                    <th class="px-4 py-3">Invoice Nota</th>
                                    <th class="px-4 py-3">Pelanggan</th>
                                    <th class="px-4 py-3 text-right">Nominal Masuk</th>
                                    <th class="px-4 py-3 text-center">Metode</th>
                                    <th class="px-4 py-3">Keterangan / Catatan</th>
                                    <th class="px-4 py-3 text-center">Status Nota</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                                <template x-for="l in filteredReceivablesLogs" :key="l.id">
                                    <tr class="hover:bg-slate-50/50 transition duration-150">
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-500 font-semibold" x-text="l.date"></td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs font-mono font-bold text-emerald-800" x-text="l.transaction_code"></td>
                                        <td class="px-4 py-3.5 whitespace-nowrap font-extrabold text-slate-800 text-xs" x-text="l.customer_name"></td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-extrabold text-emerald-600 text-xs" x-text="formatRupiah(l.amount)"></td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold" 
                                                  :class="l.payment_method === 'Tunai' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                                  x-text="l.payment_method">
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-slate-600" x-text="l.note || '-'"></td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <template x-if="l.is_settled">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">Lunas</span>
                                            </template>
                                            <template x-if="!l.is_settled">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200">Tempo</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredReceivablesLogs.length === 0">
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center text-slate-400 font-semibold">
                                            Belum ada log pembayaran cicilan tercatat.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 1: RINCIAN DETAIL PEMBELIAN -->
        <!-- ======================================================= -->
        <div x-show="showDetailModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDetailModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden" @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Rincian Faktur Pembelian</h3>
                            <p class="text-xs font-mono font-bold text-emerald-700" x-text="selectedPurchase?.purchase_code"></p>
                        </div>
                        <button type="button" @click="closeDetailModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                        <!-- Meta Info Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Pemasok</span>
                                <span class="font-extrabold text-slate-800" x-text="selectedPurchase?.supplier_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Tanggal Faktur</span>
                                <span class="font-bold text-slate-700" x-text="selectedPurchase?.purchase_date"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">No. Faktur Fisik</span>
                                <span class="font-bold text-slate-700" x-text="selectedPurchase?.invoice_number || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Lokasi Masuk</span>
                                <span class="font-bold text-slate-700" x-text="selectedPurchase?.location?.name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Status Bayar</span>
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                      :class="selectedPurchase?.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="selectedPurchase?.payment_status === 'paid' ? 'LUNAS' : 'TEMPO / UTANG'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Jatuh Tempo</span>
                                <span class="font-bold text-slate-700" x-text="selectedPurchase?.due_date || '-'"></span>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Item Barang Diterima</h4>
                            <div class="border border-slate-100 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 font-bold text-slate-600 border-b border-slate-100">
                                        <tr>
                                            <th class="px-4 py-2.5">Produk</th>
                                            <th class="px-3 py-2.5 text-center">Jumlah</th>
                                            <th class="px-4 py-2.5 text-right">Modal Satuan</th>
                                            <th class="px-4 py-2.5 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="item in (selectedPurchase?.items || [])" :key="item.id">
                                            <tr>
                                                <td class="px-4 py-3 font-bold text-slate-800" x-text="item.product_name"></td>
                                                <td class="px-3 py-3 text-center">
                                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-extrabold text-[11px]" x-text="item.quantity + ' ' + item.unit"></span>
                                                </td>
                                                <td class="px-4 py-3 text-right text-slate-500" x-text="formatRupiah(item.unit_cost)"></td>
                                                <td class="px-4 py-3 text-right font-extrabold text-slate-800" x-text="formatRupiah(item.subtotal)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Riwayat Pembayaran & Cicilan -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Riwayat Pembayaran & Cicilan</h4>
                            <div class="space-y-2">
                                <template x-for="pay in (selectedPurchase?.payments || [])" :key="pay.id">
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-extrabold text-emerald-700 block" x-text="formatRupiah(pay.amount)"></span>
                                            <span class="text-[11px] text-slate-400" x-text="pay.notes || 'Pembayaran Utang'"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700" x-text="pay.payment_method"></span>
                                            <span class="block text-[10px] text-slate-400 mt-0.5" x-text="pay.payment_date"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedPurchase?.payments || selectedPurchase?.payments.length === 0">
                                    <p class="text-xs text-slate-400 italic">Belum ada riwayat pembayaran yang tercatat.</p>
                                </template>
                            </div>
                        </div>

                        <!-- Total Summary -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Total Tagihan:</span>
                                <span class="font-bold" x-text="formatRupiah(selectedPurchase?.total_amount)"></span>
                            </div>
                            <div class="flex justify-between text-emerald-700">
                                <span>Sudah Dibayar:</span>
                                <span class="font-bold" x-text="formatRupiah(selectedPurchase?.paid_amount)"></span>
                            </div>
                            <div class="flex justify-between text-rose-600 pt-2 border-t border-slate-200 text-sm font-black">
                                <span>Sisa Utang:</span>
                                <span x-text="formatRupiah(Math.max(0, (selectedPurchase?.total_amount || 0) - (selectedPurchase?.paid_amount || 0)))"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                        <button type="button" @click="closeDetailModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 2: CATAT PEMBAYARAN UTANG SUPPLIER -->
        <!-- ======================================================= -->
        <div x-show="showPayModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closePayModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden" @click.stop>
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Catat Pembayaran Utang</h3>
                            <p class="text-xs text-slate-400 font-medium" x-text="'Supplier: ' + (activePayTrx?.supplier_name || '')"></p>
                        </div>
                        <button type="button" @click="closePayModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitDebtPayment()" class="p-6 space-y-4">
                        <!-- Sisa Utang Display -->
                        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-xs flex justify-between items-center">
                            <span class="text-amber-900 font-bold">Sisa Utang Saat Ini:</span>
                            <span class="text-base font-black text-rose-600" x-text="formatRupiah(activePayTrx ? (activePayTrx.total_amount - activePayTrx.paid_amount) : 0)"></span>
                        </div>

                        <!-- Nominal Pembayaran -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Nominal yang Dibayar (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number"
                                   min="1"
                                   :max="activePayTrx ? (activePayTrx.total_amount - activePayTrx.paid_amount) : 0"
                                   x-model.number="payForm.amount"
                                   required
                                   placeholder="Masukkan jumlah uang yang dibayarkan"
                                   class="w-full text-sm font-extrabold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                            <button type="button"
                                    @click="payForm.amount = activePayTrx ? (activePayTrx.total_amount - activePayTrx.paid_amount) : 0"
                                    class="text-[11px] font-bold text-emerald-700 hover:underline mt-1">
                                Bayar Lunas Penuh
                            </button>
                        </div>

                        <!-- Tanggal Pembayaran -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Tanggal Bayar <span class="text-rose-500">*</span></label>
                            <input type="date"
                                   x-model="payForm.payment_date"
                                   required
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                        </div>

                        <!-- Metode Bayar -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Metode Bayar <span class="text-rose-500">*</span></label>
                            <select x-model="payForm.payment_method" required class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="Tunai">Tunai / Kas Toko</option>
                            </select>
                        </div>

                        <!-- No. Referensi / Bukti Transfer -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">No. Referensi / Bukti Transfer</label>
                            <input type="text"
                                   x-model="payForm.reference_number"
                                   placeholder="Contoh: REF-BCA-89212 (opsional)"
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan</label>
                            <input type="text"
                                   x-model="payForm.notes"
                                   placeholder="Contoh: Cicilan tahap 1 (opsional)"
                                   class="w-full text-xs font-medium border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="closePayModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="isSubmittingPay || payForm.amount <= 0"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-200 transition active:scale-95 flex items-center gap-1.5">
                                <span x-text="isSubmittingPay ? 'Memproses...' : 'Simpan Pembayaran'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 3: TAMBAH SKU / PRODUK BARU CEPAT -->
        <!-- ======================================================= -->
        <div x-show="showQuickProductModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeQuickProductModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden" @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Tambah SKU / Produk Baru
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Daftarkan produk baru langsung ke katalog dan gunakan di faktur ini.</p>
                        </div>
                        <button type="button" @click="closeQuickProductModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form Body -->
                    <form @submit.prevent="submitQuickProduct()" class="p-6 space-y-4">
                        <!-- Nama Produk -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Produk <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   x-model="newProductForm.name"
                                   required
                                   placeholder="Contoh: Kurma Ajwa Al-Madinah 500g"
                                   class="w-full text-xs font-semibold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                        </div>

                        <!-- Grid 1: SKU & Kategori -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- SKU / Barcode -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Kode SKU / Barcode
                                    <span class="text-[10px] text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           x-model="newProductForm.sku"
                                           placeholder="Scan barcode / generate..."
                                           class="w-full text-xs font-mono font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 pl-3 pr-8">
                                    <button type="button" 
                                            @click="newProductForm.sku = 'PK-' + Math.random().toString(36).substring(2, 8).toUpperCase()"
                                            title="Generate SKU Acak"
                                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-emerald-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Kategori -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Produk <span class="text-rose-500">*</span></label>
                                <select x-model="newProductForm.category"
                                        required
                                        class="w-full text-xs font-semibold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 bg-white">
                                    <option value="">-- Pilih Kategori --</option>
                                    <template x-for="cat in categoriesList" :key="cat">
                                        <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                                    </template>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" class="hidden">{{ ucfirst($cat) }}</option>
                                    @endforeach
                                    <option value="__NEW__">+ Ketik Kategori Baru...</option>
                                </select>
                                
                                <!-- Input jika memilih kategori baru -->
                                <div x-show="newProductForm.category === '__NEW__'" class="mt-2" style="display: none;">
                                    <input type="text"
                                           x-model="newProductForm.custom_category"
                                           placeholder="Ketik nama kategori baru di sini..."
                                           class="w-full text-xs font-semibold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3">
                                </div>
                            </div>
                        </div>

                        <!-- Grid 2: Satuan, Harga Beli (HPP), & Harga Jual -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Satuan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Satuan Stok <span class="text-rose-500">*</span></label>
                                <select x-model="newProductForm.price_unit"
                                        required
                                        class="w-full text-xs font-semibold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 bg-white">
                                    <option value="pcs">pcs</option>
                                    <option value="pack">pack</option>
                                    <option value="dus">dus</option>
                                    <option value="kg">kg</option>
                                    <option value="gram">gram</option>
                                </select>
                            </div>

                            <!-- Harga Beli / HPP -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga Beli / HPP (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number"
                                       min="0"
                                       x-model.number="newProductForm.cost_price"
                                       required
                                       placeholder="0"
                                       class="w-full text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                            </div>

                            <!-- Harga Jual -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga Jual Kasir (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number"
                                       min="0"
                                       x-model.number="newProductForm.selling_price"
                                       required
                                       placeholder="0"
                                       class="w-full text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3">
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100 flex items-start gap-2.5 text-xs text-emerald-900">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-[11px] leading-relaxed">
                                Stok awal diset <strong>0</strong>. Kuantitas stok otomatis bertambah ketika Anda menyimpan form penerimaan barang pengadaan ini.
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="closeQuickProductModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="isSubmittingNewProduct"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-200 transition active:scale-95 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="isSubmittingNewProduct ? 'Mendaftarkan...' : 'Simpan & Gunakan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 4: DETAIL RIWAYAT PIUTANG & CIRI PRODUK -->
        <!-- ======================================================= -->
        <div x-show="selectedHistoryTrx" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
             style="display: none;">
            <div class="modal-card bg-white p-6 rounded-3xl border border-slate-100 shadow-2xl w-full max-w-lg max-h-screen overflow-y-auto"
                 @click.away="selectedHistoryTrx = null">
                
                <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <span class="text-xs font-black text-slate-400 uppercase tracking-wider block">Rincian Pelunasan Piutang</span>
                        <h3 class="text-base font-extrabold text-slate-800" x-text="selectedHistoryTrx ? selectedHistoryTrx.transaction_code : ''"></h3>
                    </div>
                    <button type="button" @click="selectedHistoryTrx = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="selectedHistoryTrx">
                    <div class="flex flex-col gap-4">
                        <!-- Info Pelanggan & Nilai -->
                        <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100 flex flex-col gap-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Pelanggan:</span>
                                <strong class="text-slate-800" x-text="selectedHistoryTrx.customer_name"></strong>
                            </div>
                            <div class="flex justify-between" x-show="selectedHistoryTrx.customer_phone">
                                <span class="text-slate-500">No. WhatsApp:</span>
                                <span class="text-slate-700 font-semibold" x-text="selectedHistoryTrx.customer_phone"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tanggal Transaksi:</span>
                                <span class="text-slate-700 font-semibold" x-text="selectedHistoryTrx.date"></span>
                            </div>
                            <div class="flex justify-between border-t border-slate-100/80 pt-2 font-bold">
                                <span class="text-slate-500">Total Nilai Tagihan:</span>
                                <span class="text-emerald-700 font-extrabold" x-text="formatRupiah(selectedHistoryTrx.total_price)"></span>
                            </div>
                            <div class="flex justify-between font-bold">
                                <span class="text-slate-500">Status Pembayaran:</span>
                                <span class="text-emerald-600 font-extrabold">LUNAS (100%)</span>
                            </div>
                        </div>

                        <!-- Ringkasan Produk Terstruktur -->
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-400 tracking-wide block uppercase">Barang / Produk Dibeli</span>
                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100" 
                                      x-text="(selectedHistoryTrx.parsed_items ? selectedHistoryTrx.parsed_items.length : 0) + ' Macam Produk'"></span>
                            </div>
                            
                            <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-white shadow-sm">
                                <table class="min-w-full text-xs divide-y divide-slate-100">
                                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="px-3.5 py-2.5 text-left">Nama Produk</th>
                                            <th class="px-3 py-2.5 text-center">Jumlah</th>
                                            <th class="px-3 py-2.5 text-right">Harga Satuan</th>
                                            <th class="px-3.5 py-2.5 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                        <template x-for="(item, idx) in (selectedHistoryTrx.parsed_items || [])" :key="idx">
                                            <tr class="hover:bg-slate-50/70 transition">
                                                <td class="px-3.5 py-2.5">
                                                    <span class="font-extrabold text-slate-800 block text-xs" x-text="item.name"></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-bold text-[11px] border border-emerald-100/70" x-text="item.display_qty"></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right whitespace-nowrap text-slate-500 font-semibold text-xs" x-text="item.unit_price > 0 ? formatRupiah(item.unit_price) : '-'"></td>
                                                <td class="px-3.5 py-2.5 text-right whitespace-nowrap font-extrabold text-emerald-700 text-xs" x-text="item.total_price > 0 ? formatRupiah(item.total_price) : '-'"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!selectedHistoryTrx.parsed_items || selectedHistoryTrx.parsed_items.length === 0">
                                            <tr>
                                                <td colspan="4" class="px-3.5 py-3 text-slate-500 italic" x-text="selectedHistoryTrx.items_summary || '-'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Log Pembayaran Cicilan -->
                        <div class="flex flex-col gap-2">
                            <span class="text-[10px] font-bold text-slate-400 tracking-wide block uppercase">Riwayat Pembayaran & Cicilan:</span>
                            <div class="flex flex-col gap-2 max-h-48 overflow-y-auto pr-1">
                                <template x-for="(inst, idx) in selectedHistoryTrx.installments" :key="inst.id">
                                    <div class="p-3 rounded-xl border border-slate-100 bg-emerald-50/20 flex justify-between items-center text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-slate-800" x-text="formatRupiah(inst.amount)"></span>
                                                <span class="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold" x-text="inst.payment_method"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 block mt-0.5" x-text="inst.note || 'Pembayaran cicilan'"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-bold whitespace-nowrap" x-text="inst.date"></span>
                                    </div>
                                </template>
                                <template x-if="!selectedHistoryTrx.installments || selectedHistoryTrx.installments.length === 0">
                                    <span class="text-xs text-slate-400 italic">Pelunasan langsung saat pembuatan nota.</span>
                                </template>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                            <button type="button" 
                                    @click="selectedHistoryTrx = null"
                                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-xs transition">
                                Tutup
                            </button>
                            <a :href="'/admin/wholesale-transactions/' + selectedHistoryTrx.id + '/print'" 
                               target="_blank"
                               class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-extrabold text-xs transition flex items-center gap-1.5 shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Cetak Nota</span>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 5: CATAT CICILAN PIUTANG PELANGGAN -->
        <!-- ======================================================= -->
        <div x-show="showInstallmentModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
             style="display: none;">
            <div class="modal-card bg-white p-6 rounded-3xl border border-slate-100 shadow-2xl w-full max-w-md max-h-screen overflow-y-auto"
                 @click.away="showInstallmentModal = false">
                
                <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-base font-extrabold text-slate-800">Catat Cicilan Pembayaran Piutang</h3>
                    <button type="button" @click="showInstallmentModal = false" class="text-slate-400 hover:text-slate-600 font-bold p-1 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex flex-col gap-4">
                    <!-- Invoice Details -->
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex flex-col gap-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Invoice:</span>
                            <strong class="text-emerald-800 font-mono" x-text="installmentForm.transaction_code"></strong>
                        </div>
                        <div class="flex justify-between border-t border-slate-100/80 pt-1.5 mt-1.5 font-bold">
                            <span class="text-slate-500">Maksimal Sisa Tagihan:</span>
                            <span class="text-rose-600 font-extrabold" x-text="formatRupiah(installmentForm.max_amount)"></span>
                        </div>
                    </div>

                    <!-- Amount Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nominal Pembayaran (Rp)</label>
                        <input type="number" 
                               x-model="installmentForm.amount" 
                               placeholder="Masukkan nominal bayar..." 
                               class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4 text-right">
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Metode Pembayaran</label>
                        <select x-model="installmentForm.payment_method" class="w-full border-slate-200 rounded-xl text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 shadow-inner bg-slate-50 text-slate-700">
                            <option value="Tunai">Uang Tunai (Cash)</option>
                            <option value="Transfer Bank">Transfer Rekening Bank</option>
                        </select>
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Catatan / Keterangan</label>
                        <input type="text" 
                               x-model="installmentForm.note" 
                               placeholder="Contoh: Pembayaran cicilan ke-2" 
                               class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                    </div>

                    <!-- Action buttons -->
                    <div class="flex gap-3 justify-end pt-3 border-t border-slate-100">
                        <button type="button" 
                                @click="showInstallmentModal = false"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold text-xs tracking-wide uppercase transition duration-150">
                            Batal
                        </button>
                        <button type="button" 
                                :disabled="isSubmittingInstallment"
                                @click="submitInstallment()"
                                class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 shadow-md shadow-emerald-700/10 flex items-center gap-1.5">
                            <span x-text="isSubmittingInstallment ? 'Menyimpan...' : 'Simpan Pembayaran'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ======================================================= -->
    <!-- ALPINE.JS COMPONENT SCRIPT -->
    <!-- ======================================================= -->
    <script>
        function purchaseManagementState(products, locations, suppliers, categories) {
            return {
                activeTab: '{{ request('tab', request()->hasAny(['start_date', 'end_date', 'status', 'supplier_id', 'location_id', 'page']) ? 'history' : 'input') }}',
                productsList: products || [],
                locationsList: locations || [],
                suppliersList: suppliers || [],
                categoriesList: categories || [],

                init() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const tabParam = urlParams.get('tab');
                    if (tabParam && ['input', 'history', 'payables', 'receivables'].includes(tabParam)) {
                        this.activeTab = tabParam;
                    } else if (window.location.hash) {
                        const hash = window.location.hash.replace('#', '');
                        if (['input', 'history', 'payables', 'receivables'].includes(hash)) {
                            this.activeTab = hash;
                        }
                    }

                    if (this.activeTab === 'receivables') {
                        this.fetchReceivables();
                        this.fetchReceivablesHistory();
                    }
                },

                setTab(tab) {
                    this.activeTab = tab;
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    window.history.replaceState({}, '', url.toString());

                    if (tab === 'receivables' && this.receivables.length === 0) {
                        this.fetchReceivables();
                        this.fetchReceivablesHistory();
                    }
                },

                // Receivables / Piutang State
                receivables: [],
                receivablesSubTab: 'active', // 'active' | 'history' | 'logs'
                receivablesHistory: [],
                receivablesLogs: [],
                receivablesHistorySearch: '',
                selectedCustomer: null,
                selectedHistoryTrx: null,
                showInstallmentModal: false,
                installmentForm: {
                    transaction_id: null,
                    transaction_code: '',
                    amount: '',
                    payment_method: 'Tunai',
                    note: '',
                    max_amount: 0
                },
                isSubmittingInstallment: false,

                isSubmitting: false,
                isSubmittingPay: false,

                showDetailModal: false,
                selectedPurchase: null,

                showPayModal: false,
                activePayTrx: null,

                // Quick Product Modal State
                showQuickProductModal: false,
                activeRowIndexForNewProduct: null,
                newProductForm: {
                    name: '',
                    sku: '',
                    category: '',
                    price_unit: 'pcs',
                    cost_price: 0,
                    selling_price: 0
                },
                isSubmittingNewProduct: false,

                // Form Input State
                form: {
                    supplier_name: '',
                    supplier_phone: '',
                    invoice_number: '',
                    location_id: (locations && locations.length > 0) ? locations[0].id : '',
                    purchase_date: new Date().toISOString().slice(0, 10),
                    payment_method: 'Tunai',
                    is_tempo: false,
                    due_date: '',
                    initial_deposit: 0,
                    notes: '',
                    items: [
                        {
                            product_id: '',
                            search_query: '',
                            is_open: false,
                            quantity: 1,
                            unit: 'pcs',
                            unit_cost: 0,
                            update_product_cost: true
                        }
                    ]
                },

                payForm: {
                    amount: 0,
                    payment_date: new Date().toISOString().slice(0, 10),
                    payment_method: 'Transfer Bank',
                    reference_number: '',
                    notes: ''
                },

                get totalAmount() {
                    return this.form.items.reduce((sum, it) => {
                        const qty = Number(it.quantity) || 0;
                        const cost = Number(it.unit_cost) || 0;
                        return sum + (qty * cost);
                    }, 0);
                },

                addItemRow() {
                    this.form.items.push({
                        product_id: '',
                        search_query: '',
                        is_open: false,
                        quantity: 1,
                        unit: 'pcs',
                        unit_cost: 0,
                        update_product_cost: true
                    });
                },

                removeItemRow(index) {
                    if (this.form.items.length > 1) {
                        this.form.items.splice(index, 1);
                        this.calculateTotals();
                    }
                },

                openSearch(index) {
                    this.form.items.forEach((it, i) => {
                        it.is_open = (i === index);
                    });
                },

                toggleSearch(index) {
                    const current = this.form.items[index].is_open;
                    this.form.items.forEach((it, i) => {
                        it.is_open = (i === index) ? !current : false;
                    });
                },

                getSelectedProduct(productId) {
                    if (!productId) return null;
                    return this.productsList.find(p => p.id == productId);
                },

                getFilteredProducts(query) {
                    if (!query || !query.trim()) {
                        return this.productsList.slice(0, 15);
                    }
                    const terms = query.toLowerCase().trim().split(/\s+/).filter(Boolean);
                    return this.productsList.filter(p => {
                        const name = (p.name || '').toLowerCase();
                        const sku = (p.sku || '').toLowerCase();
                        const cat = (p.category || '').toLowerCase();
                        const combined = `${name} ${sku} ${cat}`;
                        return terms.every(term => combined.includes(term));
                    }).slice(0, 25);
                },

                onSearchInput(index) {
                    this.form.items[index].is_open = true;
                    const row = this.form.items[index];
                    if (row.product_id) {
                        const sel = this.getSelectedProduct(row.product_id);
                        if (sel && sel.name.toLowerCase() !== row.search_query.trim().toLowerCase()) {
                            row.product_id = '';
                        }
                    }
                },

                selectProduct(index, p) {
                    const row = this.form.items[index];
                    row.product_id = p.id;
                    row.search_query = p.name;
                    row.unit = p.price_unit || 'pcs';
                    row.unit_cost = p.cost_price || 0;
                    row.is_open = false;
                    this.calculateTotals();
                },

                clearProduct(index) {
                    const row = this.form.items[index];
                    row.product_id = '';
                    row.search_query = '';
                    row.unit_cost = 0;
                    row.is_open = false;
                    this.calculateTotals();
                },

                openQuickProductModal(rowIndex = null, prefilledName = '') {
                    this.activeRowIndexForNewProduct = rowIndex;
                    this.newProductForm = {
                        name: prefilledName ? prefilledName.trim() : '',
                        sku: 'PK-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
                        category: '',
                        custom_category: '',
                        price_unit: 'pcs',
                        cost_price: (rowIndex !== null && this.form.items[rowIndex]) ? (this.form.items[rowIndex].unit_cost || 0) : 0,
                        selling_price: 0
                    };
                    this.showQuickProductModal = true;
                    if (rowIndex !== null && this.form.items[rowIndex]) {
                        this.form.items[rowIndex].is_open = false;
                    }
                },

                closeQuickProductModal() {
                    this.showQuickProductModal = false;
                    this.activeRowIndexForNewProduct = null;
                },

                async submitQuickProduct() {
                    const finalCategory = (this.newProductForm.category === '__NEW__')
                        ? this.newProductForm.custom_category.trim()
                        : this.newProductForm.category;

                    if (!this.newProductForm.name || !finalCategory || !this.newProductForm.price_unit) {
                        alert('Mohon lengkapi Nama Produk, Kategori, dan Satuan.');
                        return;
                    }

                    this.isSubmittingNewProduct = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const payload = {
                        ...this.newProductForm,
                        category: finalCategory
                    };

                    try {
                        const res = await fetch("{{ route('admin.purchases.quick-create-product') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (data.success && data.product) {
                            // 1. Add to productsList (at beginning)
                            this.productsList.unshift(data.product);

                            // Add category to categoriesList if not exists
                            if (data.product.category && !this.categoriesList.includes(data.product.category)) {
                                this.categoriesList.push(data.product.category);
                            }

                            // 2. Auto-select for the active row, or create row
                            if (this.activeRowIndexForNewProduct !== null && this.form.items[this.activeRowIndexForNewProduct]) {
                                this.selectProduct(this.activeRowIndexForNewProduct, data.product);
                            } else {
                                const emptyRowIdx = this.form.items.findIndex(it => !it.product_id);
                                if (emptyRowIdx !== -1) {
                                    this.selectProduct(emptyRowIdx, data.product);
                                } else {
                                    this.form.items.push({
                                        product_id: data.product.id,
                                        search_query: data.product.name,
                                        is_open: false,
                                        quantity: 1,
                                        unit: data.product.price_unit || 'pcs',
                                        unit_cost: data.product.cost_price || 0,
                                        update_product_cost: true
                                    });
                                    this.calculateTotals();
                                }
                            }

                            if (window.showToast) {
                                window.showToast(data.message, 'success');
                            } else {
                                alert(data.message);
                            }

                            this.closeQuickProductModal();
                        } else {
                            alert(data.message || 'Gagal mendaftarkan SKU baru.');
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan: ' + e);
                    } finally {
                        this.isSubmittingNewProduct = false;
                    }
                },

                onSupplierSelected() {
                    const match = this.suppliersList.find(s => s.name.toLowerCase() === this.form.supplier_name.trim().toLowerCase());
                    if (match && match.phone) {
                        this.form.supplier_phone = match.phone;
                    }
                },

                calculateTotals() {
                    // Reactive triggers via getters
                },

                formatRupiah(num) {
                    if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
                    return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                },

                async submitPurchase() {
                    // Validate that all rows have selected product_id
                    const invalidItem = this.form.items.find(it => !it.product_id);
                    if (invalidItem) {
                        alert('Mohon pilih produk yang valid untuk setiap baris barang masuk.');
                        return;
                    }

                    if (this.form.items.length === 0 || this.totalAmount <= 0) {
                        alert('Mohon masukkan minimal 1 barang dengan kuantitas dan harga modal valid.');
                        return;
                    }

                    if (this.form.is_tempo && !this.form.due_date) {
                        alert('Mohon tentukan tanggal jatuh tempo untuk pembayaran tempo.');
                        return;
                    }

                    this.isSubmitting = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const payload = {
                        ...this.form,
                        is_tempo: Boolean(this.form.is_tempo),
                        initial_deposit: Number(this.form.initial_deposit) || 0,
                        items: this.form.items.map(it => ({
                            product_id: it.product_id,
                            quantity: Number(it.quantity) || 0,
                            unit: it.unit,
                            unit_cost: Number(it.unit_cost) || 0,
                            update_product_cost: Boolean(it.update_product_cost)
                        }))
                    };

                    try {
                        const res = await fetch("{{ route('admin.purchases.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (data.success) {
                            if (window.showToast) {
                                window.showToast(data.message, 'success');
                            } else {
                                alert(data.message);
                            }
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            alert(data.message || 'Gagal menyimpan faktur pembelian.');
                            this.isSubmitting = false;
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan: ' + e);
                        this.isSubmitting = false;
                    }
                },

                openDetailModal(purchaseId) {
                    fetch('/admin/purchases/' + purchaseId)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                this.selectedPurchase = data.purchase;
                                this.showDetailModal = true;
                            }
                        })
                        .catch(err => alert('Gagal memuat rincian: ' + err));
                },

                closeDetailModal() {
                    this.showDetailModal = false;
                    this.selectedPurchase = null;
                },

                openPayModal(purchase) {
                    this.activePayTrx = purchase;
                    this.payForm.amount = Math.max(0, purchase.total_amount - purchase.paid_amount);
                    this.payForm.payment_date = new Date().toISOString().slice(0, 10);
                    this.payForm.reference_number = '';
                    this.payForm.notes = '';
                    this.showPayModal = true;
                },

                closePayModal() {
                    this.showPayModal = false;
                    this.activePayTrx = null;
                },

                async submitDebtPayment() {
                    if (!this.activePayTrx) return;
                    this.isSubmittingPay = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    try {
                        const res = await fetch('/admin/purchases/' + this.activePayTrx.id + '/pay-debt', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.payForm)
                        });

                        const data = await res.json();
                        if (data.success) {
                            if (window.showToast) {
                                window.showToast(data.message, 'success');
                            } else {
                                alert(data.message);
                            }
                            this.closePayModal();
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            alert(data.message || 'Gagal mencatat pembayaran.');
                            this.isSubmittingPay = false;
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan: ' + e);
                        this.isSubmittingPay = false;
                    }
                },

                showToast(msg, type = 'info') {
                    if (window.showToast) {
                        window.showToast(msg, type);
                    } else {
                        alert(msg);
                    }
                },

                fetchReceivables() {
                    fetch('/admin/receivables')
                        .then(res => res.json())
                        .then(data => {
                            this.receivables = Array.isArray(data) ? data : [];
                            if (this.selectedCustomer) {
                                const refreshed = this.receivables.find(c => c.customer_name.toLowerCase() === this.selectedCustomer.customer_name.toLowerCase());
                                this.selectedCustomer = refreshed || null;
                            }
                        })
                        .catch(err => {
                            this.showToast('Gagal memuat data piutang!', 'error');
                        });
                },

                fetchReceivablesHistory() {
                    fetch('/admin/receivables/history')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.receivablesHistory = data.history || [];
                                this.receivablesLogs = data.logs || [];
                            }
                        })
                        .catch(err => {
                            console.error('Gagal memuat riwayat piutang:', err);
                        });
                },

                get filteredReceivablesHistory() {
                    if (!this.receivablesHistorySearch) {
                        return this.receivablesHistory;
                    }
                    const q = this.receivablesHistorySearch.toLowerCase().trim();
                    return this.receivablesHistory.filter(t => 
                        (t.customer_name && t.customer_name.toLowerCase().includes(q)) ||
                        (t.transaction_code && t.transaction_code.toLowerCase().includes(q)) ||
                        (t.customer_phone && t.customer_phone.includes(q))
                    );
                },

                get filteredReceivablesLogs() {
                    if (!this.receivablesHistorySearch) {
                        return this.receivablesLogs;
                    }
                    const q = this.receivablesHistorySearch.toLowerCase().trim();
                    return this.receivablesLogs.filter(l => 
                        (l.customer_name && l.customer_name.toLowerCase().includes(q)) ||
                        (l.transaction_code && l.transaction_code.toLowerCase().includes(q)) ||
                        (l.payment_method && l.payment_method.toLowerCase().includes(q)) ||
                        (l.note && l.note.toLowerCase().includes(q))
                    );
                },

                openInstallmentModal(trx) {
                    this.installmentForm = {
                        transaction_id: trx.id,
                        transaction_code: trx.transaction_code,
                        amount: '',
                        payment_method: 'Tunai',
                        note: '',
                        max_amount: trx.outstanding_amount
                    };
                    this.showInstallmentModal = true;
                },

                async submitInstallment() {
                    const amount = parseInt(this.installmentForm.amount);
                    if (!amount || amount <= 0) {
                        this.showToast('Nominal pembayaran cicilan harus valid!', 'warning');
                        return;
                    }
                    if (amount > this.installmentForm.max_amount) {
                        this.showToast('Jumlah pembayaran melebihi sisa tagihan!', 'warning');
                        return;
                    }

                    this.isSubmittingInstallment = true;
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    try {
                        const res = await fetch(`/admin/transactions/${this.installmentForm.transaction_id}/installments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                amount: amount,
                                payment_method: this.installmentForm.payment_method,
                                note: this.installmentForm.note
                            })
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message, 'success');
                            this.showInstallmentModal = false;
                            this.fetchReceivables();
                            this.fetchReceivablesHistory();
                        } else {
                            this.showToast(data.message || 'Gagal menyimpan cicilan!', 'error');
                        }
                    } catch (e) {
                        this.showToast('Terjadi kesalahan jaringan: ' + e, 'error');
                    } finally {
                        this.isSubmittingInstallment = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>

