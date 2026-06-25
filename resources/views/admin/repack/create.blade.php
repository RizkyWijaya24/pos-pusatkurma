@extends('layouts.app')

@section('title', 'Proses Repack & Pecah Stok Baru')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden" 
     x-data="repackFormState()">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.repack.index') }}"
           class="text-slate-500 dark:text-purple-400 hover:text-slate-700 dark:hover:text-white transition-colors text-sm font-bold">
            ← Kembali
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-2">
                🔄 Proses Repack & Pecah Stok
            </h1>
            <p class="text-slate-400 dark:text-purple-400 text-sm font-medium">Buka kemasan dus besar dan kemas ulang ke unit retail/eceran</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Form Panel (kiri) --}}
        <div class="lg:col-span-8 xl:col-span-9 space-y-5">

            {{-- Lokasi & Bahan Asal --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4 flex items-center gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400">📥</span> 1. Bahan Baku (Produk Asal)
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Pilihan Lokasi --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📍 Lokasi Proses <span class="text-rose-500">*</span></label>
                        <select x-model="locationId" @change="onLocationChange()"
                                class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-700 dark:text-purple-200 rounded-xl px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner text-sm">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">
                                {{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}
                                {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pilihan Produk Asal --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📦 Produk Asal (Dus / Bulk) <span class="text-rose-500">*</span></label>
                        <div class="relative" @click.outside="showSourceDropdown = false">
                            <input type="text" 
                                   x-model="sourceProductSearch" 
                                   @focus="showSourceDropdown = true"
                                   @input="filterSourceProducts()"
                                   placeholder="🔍 Ketik nama atau SKU produk asal..."
                                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner"
                                   autocomplete="off">
                            
                            {{-- Autocomplete Dropdown --}}
                            <div x-show="showSourceDropdown && filteredSourceProducts.length > 0"
                                 class="absolute z-50 left-0 right-0 bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700 shadow-xl rounded-xl mt-1 max-h-60 overflow-y-auto">
                                <template x-for="p in filteredSourceProducts" :key="p.id">
                                    <button type="button" 
                                            @click="selectSourceProduct(p)"
                                            class="w-full text-left px-4 py-2.5 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-slate-700 dark:text-purple-200 hover:text-emerald-800 dark:hover:text-emerald-300 border-b border-slate-100 dark:border-dp-700/50 font-bold transition flex justify-between items-center">
                                        <span>
                                            <span x-text="p.name"></span>
                                            <span class="text-slate-400 font-normal" x-text="' (' + p.sku + ')'"></span>
                                        </span>
                                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold" x-text="p.price_unit"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detail Stok & Jumlah Repack --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-dp-700" x-show="sourceProduct">
                    {{-- Stok Tersedia --}}
                    <div>
                        <span class="block text-xs font-bold text-slate-400 dark:text-purple-400 uppercase tracking-wider">Stok Tersedia di Lokasi</span>
                        <div class="text-lg font-black mt-1" :class="sourceStock > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600'">
                            <span x-text="sourceStock"></span> <span class="text-sm font-semibold" x-text="sourceProduct?.price_unit"></span>
                        </div>
                        <span class="text-[10px] text-slate-400" x-text="'Modal: Rp ' + formatRupiah(sourceProduct?.cost_price) + '/' + sourceProduct?.price_unit"></span>
                    </div>

                    {{-- Jumlah Di-repack --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">🔢 Jumlah Produk Asal yang Dibuka/Di-repack <span class="text-rose-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0.01" step="0.01" x-model="sourceQty" @input="recalculateYieldEstimations()"
                                   placeholder="Misal: 1 atau 2.5"
                                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner">
                            <span class="text-slate-500 font-bold uppercase" x-text="sourceProduct?.price_unit"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Produk Hasil Repack (Yield) --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md" x-show="sourceProduct && sourceQty > 0">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-slate-800 dark:text-purple-100 font-extrabold flex items-center gap-2">
                        <span class="text-emerald-600 dark:text-emerald-400">📤</span> 2. Hasil Repack / Pecah Stok
                    </h2>
                </div>

                {{-- Indikator Pemuatan Konversi --}}
                <div x-show="loadingConversions" class="text-center py-6 text-slate-400 font-semibold text-xs">
                    ⏳ Memuat daftar unit konversi produk...
                </div>

                {{-- Daftar target repack --}}
                <div x-show="!loadingConversions && targets.length === 0" class="text-center py-8 text-rose-500 text-sm font-bold bg-rose-50/50 rounded-2xl border border-rose-100 p-4">
                    ⚠️ Produk asal ini belum memiliki pemetaan konversi stok terdaftar! <br>
                    <span class="text-xs text-slate-400 font-normal">Daftarkan relasi konversi produk bulk ke eceran terlebih dahulu atau hubungi Admin.</span>
                </div>

                <div x-show="!loadingConversions && targets.length > 0" class="space-y-4">
                    <template x-for="(t, index) in targets" :key="t.target_product_id">
                        <div class="p-4 bg-slate-50 dark:bg-dp-900 border border-slate-200/50 dark:border-dp-700/50 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            
                            {{-- Info Produk Hasil --}}
                            <div class="flex-1">
                                <h4 class="font-extrabold text-slate-800 dark:text-purple-100 text-sm" x-text="t.target_product_name"></h4>
                                <div class="flex items-center gap-3 text-[10px] text-slate-400 mt-1 font-bold">
                                    <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded" x-text="'Satuan: ' + t.target_unit"></span>
                                    <span>|</span>
                                    <span class="text-emerald-600" x-text="'Rasio: 1 ' + sourceProduct?.price_unit + ' = ' + t.conversion_rate + ' ' + t.target_unit"></span>
                                    <span>|</span>
                                    <span x-text="'Estimasi Hasil: ' + (sourceQty * t.conversion_rate) + ' ' + t.target_unit"></span>
                                </div>
                            </div>

                            {{-- Form Kuantitas Hasil Riil & Biaya Kemasan --}}
                            <div class="flex flex-wrap items-center gap-4 shrink-0 sm:w-auto w-full">
                                {{-- Hasil Riil --}}
                                <div class="w-36">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Hasil Riil (Fisik) <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-1.5 bg-white dark:bg-dp-800 border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
                                        <input type="number" min="0.01" step="0.01" x-model="t.target_quantity"
                                               class="w-full bg-transparent border-none p-0 text-xs font-black text-slate-800 dark:text-purple-100 focus:outline-none focus:ring-0">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase" x-text="t.target_unit"></span>
                                    </div>
                                </div>

                                {{-- Biaya Kemasan Tambahan --}}
                                <div class="w-36">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">B. Kemasan / Unit (Rp)</label>
                                    <div class="flex items-center gap-1 bg-white dark:bg-dp-800 border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
                                        <span class="text-[10px] text-slate-400 font-bold">Rp</span>
                                        <input type="number" min="0" x-model="t.additional_packaging_cost"
                                               class="w-full bg-transparent border-none p-0 text-xs font-black text-slate-800 dark:text-purple-100 focus:outline-none focus:ring-0 text-right">
                                    </div>
                                </div>

                                {{-- Kalkulasi HPP Baru --}}
                                <div class="w-36 text-center sm:text-right bg-emerald-50/50 border border-emerald-100 rounded-lg px-3 py-2">
                                    <span class="block text-[8px] font-black text-emerald-800 uppercase tracking-wider">HPP / Modal Baru</span>
                                    <span class="text-xs font-black text-emerald-950 block mt-0.5" x-text="'Rp ' + formatRupiah(calculateUnitHpp(t))"></span>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </div>

            {{-- Catatan --}}
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md" x-show="sourceProduct">
                <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📝 Catatan / Alasan Pecah Stok</label>
                <textarea x-model="notes" rows="3" placeholder="Opsional: keterangan alasan repack, catatan penyusutan berat, dll."
                    class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none resize-none shadow-inner"></textarea>
            </div>
        </div>

        {{-- Summary Panel (kanan) --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-5">
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md sticky top-6">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4">📋 Ringkasan Repacking</h2>

                <div class="space-y-4 mb-5 border-b border-slate-100 dark:border-dp-700 pb-4 text-xs font-semibold">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Lokasi</span>
                        <span class="text-slate-800 dark:text-purple-100 font-bold" x-text="locationName || '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Bahan Baku</span>
                        <span class="text-slate-800 dark:text-purple-100 font-bold text-right" x-text="sourceProduct ? sourceProduct.name : '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Jumlah Dipotong</span>
                        <span class="text-slate-800 dark:text-purple-100 font-bold text-right" x-text="sourceQty ? (sourceQty + ' ' + sourceProduct.price_unit) : '-'"></span>
                    </div>

                    {{-- Informasi Penyusutan/Waste --}}
                    <template x-if="sourceProduct && sourceQty > 0 && targets.length > 0">
                        <div class="pt-2 border-t border-slate-100 dark:border-dp-700 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Kuantitas Hasil</span>
                                <span class="text-slate-800 dark:text-purple-100 font-bold" x-text="calculateTotalYield() + ' ' + targets[0].target_unit"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Selisih Penyusutan (Waste)</span>
                                <span class="font-black" :class="calculateWaste() >= 0 ? 'text-rose-600' : 'text-emerald-600'" 
                                      x-text="calculateWaste() + ' ' + targets[0].target_unit"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <button id="submitBtn" @click="submitRepack()" :disabled="!isFormValid()"
                        class="w-full bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3.5 rounded-xl font-bold uppercase tracking-wider text-xs transition duration-150 shadow-sm">
                    🚀 Proses Repack Sekarang
                </button>
                <p class="text-slate-400 dark:text-purple-500 text-xs text-center mt-2.5 font-medium leading-relaxed">
                    Stok bahan baku akan langsung berkurang dan stok baru hasil repack beserta HPP barunya akan langsung terupdate.
                </p>
            </div>
        </div>

    </div>
</div>

<script>
function repackFormState() {
    return {
        locationId: '',
        locationName: '',
        sourceProductSearch: '',
        showSourceDropdown: false,
        sourceProduct: null,
        sourceQty: '',
        sourceStock: 0.0,
        notes: '',
        loadingConversions: false,
        
        allProducts: {!! json_encode($products->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price_unit' => $p->price_unit,
                'cost_price' => $p->cost_price,
            ];
        })) !!},
        
        filteredSourceProducts: [],
        targets: [],
        
        init() {
            this.filteredSourceProducts = [...this.allProducts];
        },
        
        filterSourceProducts() {
            const searchVal = this.sourceProductSearch.toLowerCase().trim();
            if (!searchVal) {
                this.filteredSourceProducts = [...this.allProducts];
            } else {
                this.filteredSourceProducts = this.allProducts.filter(p => {
                    return p.name.toLowerCase().includes(searchVal) || p.sku.toLowerCase().includes(searchVal);
                });
            }
        },
        
        onLocationChange() {
            // Update location name for summary
            const selectEl = document.querySelector('select');
            this.locationName = selectEl.options[selectEl.selectedIndex]?.text || '';
            
            this.fetchSourceStock();
        },
        
        selectSourceProduct(product) {
            this.sourceProduct = product;
            this.sourceProductSearch = `${product.name} (${product.sku})`;
            this.showSourceDropdown = false;
            this.sourceQty = '';
            this.targets = [];
            
            this.fetchSourceStock();
            this.fetchProductConversions();
        },
        
        fetchSourceStock() {
            if (!this.locationId || !this.sourceProduct) {
                this.sourceStock = 0.0;
                return;
            }
            
            fetch(`/admin/stock-by-location?location_id=${this.locationId}`)
                .then(r => r.json())
                .then(data => {
                    const found = data.find(s => s.product_id === this.sourceProduct.id);
                    this.sourceStock = found ? parseFloat(found.stock) : 0.0;
                });
        },
        
        fetchProductConversions() {
            if (!this.sourceProduct) return;
            
            this.loadingConversions = true;
            fetch(`/admin/products/${this.sourceProduct.id}/conversions`)
                .then(r => r.json())
                .then(data => {
                    this.targets = data.map(item => ({
                        target_product_id: item.target_product_id,
                        target_product_name: item.target_product_name,
                        target_unit: item.target_unit,
                        conversion_rate: parseFloat(item.conversion_rate),
                        target_quantity: '',
                        additional_packaging_cost: 0,
                    }));
                    this.loadingConversions = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loadingConversions = false;
                });
        },
        
        recalculateYieldEstimations() {
            const qty = parseFloat(this.sourceQty) || 0.0;
            this.targets.forEach(t => {
                // Auto-fill target quantity based on rate
                t.target_quantity = qty > 0 ? (qty * t.conversion_rate).toFixed(2).replace(/\.?0+$/, '') : '';
            });
        },
        
        calculateTotalYield() {
            return this.targets.reduce((sum, t) => sum + (parseFloat(t.target_quantity) || 0), 0).toFixed(2).replace(/\.?0+$/, '');
        },
        
        calculateWaste() {
            if (!this.sourceProduct || !this.sourceQty || this.targets.length === 0) return 0;
            
            const firstTarget = this.targets[0];
            const expectedYield = parseFloat(this.sourceQty) * firstTarget.conversion_rate;
            const actualYield = parseFloat(this.calculateTotalYield()) || 0;
            
            return (expectedYield - actualYield).toFixed(2).replace(/\.?0+$/, '');
        },
        
        calculateUnitHpp(target) {
            const srcQty = parseFloat(this.sourceQty) || 0.0;
            if (srcQty <= 0) return 0;
            
            // Total cost bahan baku terpakai
            const totalSourceCost = (this.sourceProduct?.cost_price || 0) * srcQty;
            
            // Hitung total ekivalen yield dalam unit source
            let totalSourceEquivalents = 0.0;
            this.targets.forEach(t => {
                const tQty = parseFloat(t.target_quantity) || 0.0;
                const tRate = parseFloat(t.conversion_rate) || 1.0;
                totalSourceEquivalents += tQty / tRate;
            });
            
            if (totalSourceEquivalents <= 0) return 0;
            
            // Raw cost allocation per unit
            const rate = parseFloat(target.conversion_rate) || 1.0;
            const allocatedSourceCostUnit = (totalSourceCost / totalSourceEquivalents) / rate;
            
            // Tambahkan biaya kemasan
            const packagingCost = parseInt(target.additional_packaging_cost) || 0;
            
            return Math.round(allocatedSourceCostUnit + packagingCost);
        },
        
        isFormValid() {
            if (!this.locationId || !this.sourceProduct || !this.sourceQty) return false;
            
            const srcQtyVal = parseFloat(this.sourceQty);
            if (isNaN(srcQtyVal) || srcQtyVal <= 0 || srcQtyVal > this.sourceStock) return false;
            
            if (this.targets.length === 0) return false;
            
            // Minimal satu target terisi kuantitas > 0
            const hasValidTarget = this.targets.some(t => {
                const qtyVal = parseFloat(t.target_quantity);
                return !isNaN(qtyVal) && qtyVal > 0;
            });
            
            return hasValidTarget;
        },
        
        formatRupiah(num) {
            if (num === null || num === undefined) return '0';
            return Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        },
        
        submitRepack() {
            if (!this.isFormValid()) return;
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Memproses...';
            
            const payload = {
                location_id: this.locationId,
                source_product_id: this.sourceProduct.id,
                source_quantity: this.sourceQty,
                notes: this.notes,
                items: this.targets
                    .filter(t => parseFloat(t.target_quantity) > 0)
                    .map(t => ({
                        target_product_id: t.target_product_id,
                        target_quantity: t.target_quantity,
                        additional_packaging_cost: t.additional_packaging_cost || 0
                    }))
            };
            
            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            
            fetch('/admin/repack', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.showToast) {
                        window.showToast(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'Gagal menyimpan repack!', 'error');
                    } else {
                        alert(data.message);
                    }
                    btn.disabled = false;
                    btn.textContent = '🚀 Proses Repack Sekarang';
                }
            })
            .catch(err => {
                console.error(err);
                if (window.showToast) {
                    window.showToast('Terjadi kesalahan jaringan!', 'error');
                } else {
                    alert('Terjadi kesalahan jaringan!');
                }
                btn.disabled = false;
                btn.textContent = '🚀 Proses Repack Sekarang';
            });
        }
    };
}
</script>
@endsection
