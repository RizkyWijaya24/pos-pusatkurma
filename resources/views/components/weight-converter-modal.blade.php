<!-- GLOBAL WEIGHT CONVERTER MODAL (GRAM ⇄ KG & KALKULATOR TIMBANGAN) -->
<div x-data="weightConverterModalState()"
     x-cloak
     @open-weight-converter.window="openModal($event.detail)"
     @keydown.f2.window.prevent="toggleModal()"
     @keydown.escape.window="if (isOpen) closeModal()">

    <!-- MODAL BACKDROP & DIALOG -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[999999] flex items-center justify-center p-4 bg-slate-900/75 backdrop-blur-sm"
         style="display: none;">

        <div @click.away="closeModal()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="bg-white dark:bg-dp-900 rounded-3xl p-6 w-full max-w-lg border border-slate-100 dark:border-dp-700 shadow-2xl flex flex-col gap-5 text-slate-800 dark:text-purple-100">

            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-dp-750 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-center shadow-inner">
                        <svg class="w-5 h-5 text-emerald-700 dark:text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m-7-5l3.5-7 3.5 7H5zm10 0l3.5-7 3.5 7h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-800 dark:text-white leading-tight">Konverter Gram ⇄ Kilogram</h3>
                        <p class="text-xs text-slate-400 dark:text-purple-400 font-semibold">Kalkulator berat & harga kurma / timbangan <span class="hidden sm:inline">(Tekan F2)</span></p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-dp-800 hover:bg-slate-200 dark:hover:bg-dp-700 text-slate-500 dark:text-purple-300 flex items-center justify-center font-bold text-lg transition">
                    ×
                </button>
            </div>

            <!-- TABS -->
            <div class="flex gap-2 p-1 bg-slate-100 dark:bg-dp-800 rounded-2xl text-xs font-bold">
                <button type="button"
                        @click="activeTab = 'weight'"
                        :class="activeTab === 'weight' ? 'bg-white dark:bg-dp-900 text-emerald-800 dark:text-emerald-300 shadow-sm' : 'text-slate-500 dark:text-purple-300 hover:text-slate-800'"
                        class="flex-1 py-2 px-3 rounded-xl transition text-center">
                    Konversi Berat
                </button>
                <button type="button"
                        @click="activeTab = 'price'"
                        :class="activeTab === 'price' ? 'bg-white dark:bg-dp-900 text-emerald-800 dark:text-emerald-300 shadow-sm' : 'text-slate-500 dark:text-purple-300 hover:text-slate-800'"
                        class="flex-1 py-2 px-3 rounded-xl transition text-center">
                    Hitung Harga
                </button>
                <button type="button"
                        @click="activeTab = 'table'"
                        :class="activeTab === 'table' ? 'bg-white dark:bg-dp-900 text-emerald-800 dark:text-emerald-300 shadow-sm' : 'text-slate-500 dark:text-purple-300 hover:text-slate-800'"
                        class="flex-1 py-2 px-3 rounded-xl transition text-center">
                    Tabel Acuan
                </button>
            </div>

            <!-- TAB 1: KONVERSI BERAT (GRAM ⇄ KG) -->
            <div x-show="activeTab === 'weight'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <!-- Input Gram -->
                    <div class="bg-slate-50 dark:bg-dp-850 border border-slate-200 dark:border-dp-700 rounded-2xl p-3.5 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 transition">
                        <label class="block text-[11px] font-extrabold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1">
                            Gram (g)
                        </label>
                        <div class="relative flex items-center">
                            <input type="number"
                                   step="any"
                                   min="0"
                                   x-model="gramValue"
                                   @input="onGramInput()"
                                   placeholder="Contoh: 250"
                                   class="w-full bg-transparent font-extrabold text-xl sm:text-2xl text-slate-800 dark:text-white focus:outline-none pr-8">
                            <span class="absolute right-0 text-xs font-bold text-slate-400">g</span>
                        </div>
                    </div>

                    <!-- Input Kg -->
                    <div class="bg-slate-50 dark:bg-dp-850 border border-slate-200 dark:border-dp-700 rounded-2xl p-3.5 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 transition">
                        <label class="block text-[11px] font-extrabold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-1">
                            Kilogram (kg)
                        </label>
                        <div class="relative flex items-center">
                            <input type="number"
                                   step="any"
                                   min="0"
                                   x-model="kgValue"
                                   @input="onKgInput()"
                                   placeholder="Contoh: 0.25"
                                   class="w-full bg-transparent font-extrabold text-xl sm:text-2xl text-emerald-800 dark:text-emerald-300 focus:outline-none pr-8">
                            <span class="absolute right-0 text-xs font-bold text-slate-400">kg</span>
                        </div>
                    </div>
                </div>

                <!-- PRESETS TOMBOL CEPAT -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 dark:text-purple-400 mb-2">Pilihan Cepat (Standar Timbangan Kurma):</label>
                    <div class="grid grid-cols-4 sm:grid-cols-5 gap-1.5 text-xs font-bold">
                        <button type="button" @click="setGrams(100)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">100g</button>
                        <button type="button" @click="setGrams(250)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">250g <span class="text-[9px] text-slate-400 block font-normal">(¼ kg)</span></button>
                        <button type="button" @click="setGrams(500)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">500g <span class="text-[9px] text-slate-400 block font-normal">(½ kg)</span></button>
                        <button type="button" @click="setGrams(750)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">750g <span class="text-[9px] text-slate-400 block font-normal">(¾ kg)</span></button>
                        <button type="button" @click="setKg(1)" class="py-2 px-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-100 transition text-center border border-emerald-200 dark:border-emerald-800 font-extrabold">1 kg</button>
                        <button type="button" @click="setKg(2)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">2 kg</button>
                        <button type="button" @click="setKg(3)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">3 kg</button>
                        <button type="button" @click="setKg(5)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">5 kg <span class="text-[9px] text-slate-400 block font-normal">(1 Dus)</span></button>
                        <button type="button" @click="setKg(10)" class="py-2 px-1 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-800 dark:hover:text-emerald-300 transition text-center border border-slate-200/60 dark:border-dp-700">10 kg <span class="text-[9px] text-slate-400 block font-normal">(Dus Besar)</span></button>
                        <button type="button" @click="resetWeight()" class="py-2 px-1 rounded-xl bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 hover:bg-rose-100 transition text-center border border-rose-200/60 font-bold">Reset</button>
                    </div>
                </div>

                <!-- HASIL & COPY -->
                <div x-show="gramValue > 0 || kgValue > 0" class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase">Ekivalen Berat</div>
                        <div class="font-black text-lg text-emerald-900 dark:text-white mt-0.5">
                            <span x-text="formatNumber(gramValue) + ' gram'"></span>
                            <span class="text-emerald-500 font-bold mx-1.5">⇄</span>
                            <span x-text="formatNumber(kgValue) + ' kg'"></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="copyText(kgValue)" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-white dark:bg-dp-850 hover:bg-emerald-100 dark:hover:bg-dp-750 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 transition shadow-xs">
                            Salin (kg)
                        </button>
                        <button type="button" @click="copyText(gramValue)" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-white dark:bg-dp-850 hover:bg-emerald-100 dark:hover:bg-dp-750 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 transition shadow-xs">
                            Salin (g)
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: HITUNG HARGA (TIMBANGAN CURAH) -->
            <div x-show="activeTab === 'price'" class="space-y-4" style="display: none;">
                <div class="space-y-3">
                    <!-- Harga Satuan -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-[11px] font-extrabold text-slate-500 dark:text-purple-300 uppercase tracking-wider">
                                Harga Produk
                            </label>
                            <div class="flex items-center gap-1 text-[11px] font-bold">
                                <button type="button" @click="priceUnitMode = 'kg'" :class="priceUnitMode === 'kg' ? 'bg-emerald-700 text-white' : 'bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300'" class="px-2 py-0.5 rounded-md transition">per Kg</button>
                                <button type="button" @click="priceUnitMode = 'gram'" :class="priceUnitMode === 'gram' ? 'bg-emerald-700 text-white' : 'bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300'" class="px-2 py-0.5 rounded-md transition">per Gram</button>
                            </div>
                        </div>
                        <div class="relative flex items-center bg-slate-50 dark:bg-dp-850 border border-slate-200 dark:border-dp-700 rounded-2xl px-3.5 py-2.5 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20">
                            <span class="text-xs font-bold text-slate-400 mr-2">Rp</span>
                            <input type="number"
                                   min="0"
                                   x-model="unitPrice"
                                   placeholder="Contoh: 120000"
                                   class="w-full bg-transparent font-extrabold text-lg text-slate-800 dark:text-white focus:outline-none">
                            <span class="text-xs font-bold text-slate-400" x-text="'/ ' + priceUnitMode"></span>
                        </div>
                    </div>

                    <!-- Berat Timbangan -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-[11px] font-extrabold text-slate-500 dark:text-purple-300 uppercase tracking-wider">
                                Berat Ditimbang
                            </label>
                            <div class="flex items-center gap-1 text-[11px] font-bold">
                                <button type="button" @click="calcWeightMode = 'gram'" :class="calcWeightMode === 'gram' ? 'bg-emerald-700 text-white' : 'bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300'" class="px-2 py-0.5 rounded-md transition">Gram</button>
                                <button type="button" @click="calcWeightMode = 'kg'" :class="calcWeightMode === 'kg' ? 'bg-emerald-700 text-white' : 'bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300'" class="px-2 py-0.5 rounded-md transition">Kg</button>
                            </div>
                        </div>
                        <div class="relative flex items-center bg-slate-50 dark:bg-dp-850 border border-slate-200 dark:border-dp-700 rounded-2xl px-3.5 py-2.5 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20">
                            <input type="number"
                                   step="any"
                                   min="0"
                                   x-model="calcWeight"
                                   :placeholder="calcWeightMode === 'gram' ? 'Contoh: 350' : 'Contoh: 0.35'"
                                   class="w-full bg-transparent font-extrabold text-lg text-slate-800 dark:text-white focus:outline-none">
                            <span class="text-xs font-bold text-slate-400" x-text="calcWeightMode"></span>
                        </div>
                    </div>
                </div>

                <!-- HASIL PERHITUNGAN TOTAL -->
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-inner">
                    <div>
                        <div class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Total Harga Timbangan</div>
                        <div class="font-black text-2xl text-emerald-900 dark:text-white mt-0.5" x-text="formatRupiah(calculateTotalPrice())"></div>
                        <div class="text-[11px] text-slate-500 dark:text-purple-300 mt-0.5" x-text="getPriceCalculationFormula()"></div>
                    </div>
                    <button type="button"
                            @click="copyText(calculateTotalPrice())"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs transition shadow-sm active:scale-95 flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                        </svg>
                        <span>Salin Nominal</span>
                    </button>
                </div>
            </div>

            <!-- TAB 3: TABEL ACUAN CEPAT (CHEAT SHEET) -->
            <div x-show="activeTab === 'table'" class="space-y-3" style="display: none;">
                <p class="text-xs text-slate-500 dark:text-purple-400">Tabel konversi standar yang sering digunakan di toko kurma & oleh-oleh haji:</p>
                <div class="border border-slate-200 dark:border-dp-700 rounded-2xl overflow-hidden text-xs">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="p-2.5">Satuan Gram</th>
                                <th class="p-2.5">Satuan Kilogram</th>
                                <th class="p-2.5">Keterangan Umum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-dp-750 font-medium">
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">100 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">0,10 kg</td><td class="p-2.5 text-slate-400 text-[11px]">1 Ons</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">250 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">0,25 kg</td><td class="p-2.5 text-slate-400 text-[11px]">¼ Kilogram / Kotak kecil</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">500 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">0,50 kg</td><td class="p-2.5 text-slate-400 text-[11px]">½ Kilogram / Kotak sedang</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">750 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">0,75 kg</td><td class="p-2.5 text-slate-400 text-[11px]">¾ Kilogram</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">1.000 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">1,00 kg</td><td class="p-2.5 text-slate-400 text-[11px]">1 Kilogram pas</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">2.500 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">2,50 kg</td><td class="p-2.5 text-slate-400 text-[11px]">2,5 Kilogram</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">5.000 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">5,00 kg</td><td class="p-2.5 text-slate-400 text-[11px]">1 Dus Standar (Kurma Sukari/Ajwa)</td></tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-dp-800/40"><td class="p-2.5 font-bold">10.000 gram</td><td class="p-2.5 text-emerald-700 dark:text-emerald-400 font-mono font-bold">10,00 kg</td><td class="p-2.5 text-slate-400 text-[11px]">1 Dus Besar / Karton Grosir</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-dp-750 text-xs">
                <span class="text-slate-400 dark:text-purple-400">Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-dp-800 font-mono text-[10px] font-bold border border-slate-300">Esc</kbd> untuk menutup</span>
                <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-dp-800 hover:bg-slate-200 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 font-bold transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function weightConverterModalState() {
        return {
            isOpen: false,
            activeTab: 'weight',
            gramValue: '',
            kgValue: '',
            
            // Calculator tab state
            unitPrice: '',
            priceUnitMode: 'kg', // 'kg' or 'gram'
            calcWeight: '',
            calcWeightMode: 'gram', // 'gram' or 'kg'

            openModal(opts = {}) {
                this.isOpen = true;
                if (opts.tab) this.activeTab = opts.tab;
                if (opts.gram !== undefined) {
                    this.setGrams(opts.gram);
                } else if (opts.kg !== undefined) {
                    this.setKg(opts.kg);
                }
                if (opts.price !== undefined) {
                    this.unitPrice = opts.price;
                    this.activeTab = 'price';
                }
                if (opts.priceUnit) {
                    this.priceUnitMode = opts.priceUnit;
                }
            },

            closeModal() {
                this.isOpen = false;
            },

            toggleModal() {
                this.isOpen = !this.isOpen;
            },

            onGramInput() {
                const g = parseFloat(this.gramValue);
                if (isNaN(g) || g < 0) {
                    this.kgValue = '';
                    return;
                }
                this.kgValue = parseFloat((g / 1000).toFixed(4));
            },

            onKgInput() {
                const k = parseFloat(this.kgValue);
                if (isNaN(k) || k < 0) {
                    this.gramValue = '';
                    return;
                }
                this.gramValue = Math.round(k * 1000);
            },

            setGrams(g) {
                this.gramValue = g;
                this.onGramInput();
            },

            setKg(k) {
                this.kgValue = k;
                this.onKgInput();
            },

            resetWeight() {
                this.gramValue = '';
                this.kgValue = '';
            },

            calculateTotalPrice() {
                const price = parseFloat(this.unitPrice) || 0;
                const weight = parseFloat(this.calcWeight) || 0;
                if (price <= 0 || weight <= 0) return 0;

                // Hitung total berdasarkan kombinasi unit harga dan unit timbangan
                let total = 0;
                if (this.priceUnitMode === 'kg') {
                    // Harga per kg
                    const weightInKg = this.calcWeightMode === 'kg' ? weight : (weight / 1000);
                    total = price * weightInKg;
                } else {
                    // Harga per gram
                    const weightInGram = this.calcWeightMode === 'gram' ? weight : (weight * 1000);
                    total = price * weightInGram;
                }
                return Math.round(total);
            },

            getPriceCalculationFormula() {
                const price = parseFloat(this.unitPrice) || 0;
                const weight = parseFloat(this.calcWeight) || 0;
                if (price <= 0 || weight <= 0) return 'Masukkan harga dan berat untuk melihat rincian.';

                if (this.priceUnitMode === 'kg') {
                    const wKg = this.calcWeightMode === 'kg' ? weight : (weight / 1000);
                    const wG = this.calcWeightMode === 'gram' ? weight : (weight * 1000);
                    return `${wG.toLocaleString('id-ID')} gram (${wKg} kg) × Rp ${price.toLocaleString('id-ID')}/kg`;
                } else {
                    const wG = this.calcWeightMode === 'gram' ? weight : (weight * 1000);
                    return `${wG.toLocaleString('id-ID')} gram × Rp ${price.toLocaleString('id-ID')}/g`;
                }
            },

            formatNumber(num) {
                const n = parseFloat(num);
                if (isNaN(n)) return '0';
                return n.toLocaleString('id-ID');
            },

            formatRupiah(num) {
                const n = parseInt(num) || 0;
                return 'Rp ' + n.toLocaleString('id-ID');
            },

            copyText(val) {
                if (!val && val !== 0) return;
                const text = String(val);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (window.showToast) window.showToast('Tersalin ke clipboard: ' + text, 'success');
                    });
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    if (window.showToast) window.showToast('Tersalin: ' + text, 'success');
                }
            }
        };
    }

    // Helper global untuk dipanggil dari mana saja
    window.openWeightConverter = function(opts) {
        window.dispatchEvent(new CustomEvent('open-weight-converter', { detail: opts || {} }));
    };
</script>

