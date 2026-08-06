<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3" x-data="{ 
            open: false, 
            activeTabLabel: 'Stok Inventori',
            setTab(tab, label) {
                this.activeTabLabel = label;
                this.open = false;
                window.location.hash = tab;
                window.dispatchEvent(new CustomEvent('change-tab', { detail: tab }));
            }
        }" @change-tab.window="
            if ($event.detail === 'inventory') activeTabLabel = 'Stok Inventori';
            if ($event.detail === 'cashiers') activeTabLabel = 'Kelola Kasir';
            if ($event.detail === 'categories') activeTabLabel = 'Kelola Kategori';
            if ($event.detail === 'wholesale') activeTabLabel = 'Nota Partai';
            if ($event.detail === 'receivables') activeTabLabel = 'Kelola Piutang';
            if ($event.detail === 'settings') activeTabLabel = 'Pengaturan Shop';
        ">
            <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight hidden sm:block">
                {{ __('Manajemen:') }}
            </h2>
            
            <div class="relative">
                <button @click="open = !open" 
                        type="button"
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-dp-800 text-emerald-800 dark:text-purple-100 rounded-xl text-sm font-extrabold border border-emerald-100 dark:border-dp-700 shadow-sm transition duration-150 hover:bg-emerald-100/50 dark:hover:bg-dp-700">
                    <span x-text="activeTabLabel">Stok Inventori</span>
                    <svg class="h-4 w-4 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 mt-2 w-48 bg-white dark:bg-dp-800 border border-slate-200 dark:border-dp-700 rounded-xl shadow-lg z-50 p-1 flex flex-col gap-0.5"
                     style="display: none;">
                    
                    <button type="button" @click="setTab('inventory', 'Stok Inventori')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>📦</span> <span>Stok Inventori</span>
                    </button>
                    <button type="button" @click="setTab('cashiers', 'Kelola Kasir')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>👥</span> <span>Kelola Kasir</span>
                    </button>
                    <button type="button" @click="setTab('categories', 'Kelola Kategori')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>🏷️</span> <span>Kelola Kategori</span>
                    </button>
                    <button type="button" @click="setTab('wholesale', 'Nota Partai')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>📝</span> <span>Nota Partai</span>
                    </button>
                    <button type="button" @click="setTab('receivables', 'Kelola Piutang')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>💳</span> <span>Kelola Piutang</span>
                    </button>
                    <button type="button" @click="setTab('settings', 'Pengaturan Shop')" 
                            class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-slate-50 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 flex items-center gap-2">
                        <span>⚙️</span> <span>Pengaturan Shop</span>
                    </button>
                    <hr class="border-slate-100 dark:border-dp-700 my-0.5">
                    <a href="{{ route('admin.orders.index') }}"
                       class="w-full text-left px-3 py-2.5 text-xs font-bold rounded-lg transition duration-150 hover:bg-emerald-50 dark:hover:bg-dp-700 text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <span>🛍️</span> <span>Pesanan Online</span>
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- ============================================================
         ADMIN DASHBOARD - DARK MODE STYLE OVERRIDES
         Higher specificity using html.dark to override Tailwind
         ============================================================ --}}
    <style>
        /* ── CARDS & PANELS ── */
        html.dark .admin-card,
        html.dark .bg-white {
            background-color: #1a1240 !important;
        }

        /* ── TABLE CONTAINER ── */
        html.dark .admin-table-wrap {
            background-color: #1a1240;
            border-color: rgba(42,29,99,0.5);
        }

        /* ── TABLE HEADER ROW ── */
        html.dark thead tr,
        html.dark .admin-thead {
            background-color: #140e34 !important;
        }
        html.dark thead th {
            color: #c4b5fd !important;
            border-color: rgba(42,29,99,0.4) !important;
        }

        /* ── TABLE BODY ── */
        html.dark tbody tr {
            background-color: #1a1240 !important;
            border-color: rgba(42,29,99,0.3) !important;
            color: #ddd6fe !important;
        }
        html.dark tbody tr:hover {
            background-color: rgba(42,29,99,0.35) !important;
        }
        html.dark tbody td {
            color: #ddd6fe !important;
            border-color: rgba(42,29,99,0.25) !important;
        }

        /* ── ALL TEXT COLORS ── */
        html.dark .text-slate-800 { color: #ede9fe !important; }
        html.dark .text-slate-700 { color: #ddd6fe !important; }
        html.dark .text-slate-600 { color: #c4b5fd !important; }
        html.dark .text-slate-500 { color: #a78bfa !important; }
        html.dark .text-slate-400 { color: #8b5cf6 !important; }

        /* ── SLATE BACKGROUNDS ── */
        html.dark .bg-slate-50 { background-color: rgba(42,29,99,0.3) !important; }
        html.dark .bg-slate-100 { background-color: rgba(42,29,99,0.35) !important; }
        html.dark .bg-slate-200 { background-color: rgba(42,29,99,0.5) !important; }

        /* ── BORDERS ── */
        html.dark .border-slate-100 { border-color: rgba(42,29,99,0.4) !important; }
        html.dark .border-slate-200 { border-color: rgba(42,29,99,0.5) !important; }
        html.dark .divide-slate-100 > * + * { border-color: rgba(42,29,99,0.3) !important; }

        /* ── FORM INPUTS ── */
        html.dark input[type="text"],
        html.dark input[type="number"],
        html.dark input[type="email"],
        html.dark input[type="password"],
        html.dark input[type="file"],
        html.dark select,
        html.dark textarea {
            background-color: #140e34 !important;
            border-color: rgba(109,40,217,0.4) !important;
            color: #ede9fe !important;
        }
        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #7c3aed !important;
            opacity: 0.7;
        }
        html.dark input:focus,
        html.dark select:focus,
        html.dark textarea:focus {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124,58,237,0.25) !important;
            outline: none !important;
        }

        /* ── CANCEL / SECONDARY BUTTONS ── */
        html.dark .btn-cancel,
        html.dark button.bg-slate-100,
        html.dark button.bg-slate-200 {
            background-color: rgba(42,29,99,0.4) !important;
            color: #c4b5fd !important;
        }
        html.dark button.bg-slate-100:hover {
            background-color: rgba(67,46,145,0.55) !important;
        }

        /* ── CATEGORY FILTER CHIP BUTTONS ── */
        html.dark .cat-btn-inactive {
            background-color: rgba(42,29,99,0.3) !important;
            color: #c4b5fd !important;
            border-color: rgba(42,29,99,0.4) !important;
        }
        html.dark .cat-btn-inactive:hover {
            background-color: rgba(67,46,145,0.5) !important;
            color: #ede9fe !important;
        }

        /* ── MODALS ── */
        html.dark .modal-card {
            background-color: #1e1545 !important;
            border-color: rgba(42,29,99,0.6) !important;
            box-shadow: 0 25px 60px rgba(0,0,0,0.7), 0 0 0 1px rgba(109,40,217,0.2) !important;
        }

        /* ── EMERALD TEXT (keep readable in dark) ── */
        html.dark .text-emerald-700 { color: #34d399 !important; }
        html.dark .text-emerald-800 { color: #6ee7b7 !important; }
        html.dark .text-emerald-600 { color: #34d399 !important; }
        html.dark .bg-emerald-50 { background-color: rgba(6,46,30,0.45) !important; }

        /* ── ROSE/DANGER ── */
        html.dark .bg-rose-50 { background-color: rgba(76,5,25,0.5) !important; }

        /* ── WHOLESALE ITEMS ROW ── */
        html.dark .wholesale-row {
            background-color: rgba(20,14,52,0.7) !important;
            border-color: rgba(42,29,99,0.35) !important;
        }
        html.dark .wholesale-row:hover {
            background-color: rgba(42,29,99,0.4) !important;
        }

        /* ── SKU MONO TEXT ── */
        html.dark .font-mono { color: #a78bfa !important; }

        /* ── PAGINATION ROW ── */
        html.dark .pagination-wrap {
            background-color: #1a1240 !important;
            border-color: rgba(42,29,99,0.4) !important;
        }
        html.dark .page-btn {
            background-color: rgba(42,29,99,0.35) !important;
            color: #c4b5fd !important;
        }
        html.dark .page-btn:hover {
            background-color: rgba(67,46,145,0.5) !important;
            color: #ede9fe !important;
        }

        /* ── HOVER SUGGESTIONS ── */
        html.dark .suggestion-wrap {
            background-color: #1e1545 !important;
            border-color: rgba(42,29,99,0.7) !important;
        }
        html.dark .suggestion-item:hover {
            background-color: rgba(42,29,99,0.5) !important;
            color: #a7f3d0 !important;
        }

        /* ── PRICE TIER SECTION ── */
        html.dark .price-tier-row {
            background-color: #140e34 !important;
            border-color: rgba(42,29,99,0.4) !important;
        }
        html.dark .price-tier-header {
            background-color: rgba(20,14,52,0.7) !important;
            border-color: rgba(42,29,99,0.4) !important;
        }
    </style>

    {{-- JSON Data Islands --}}
    <script type="application/json" id="products-data">@json($products)</script>
    <script type="application/json" id="cashiers-data">@json($cashiers)</script>
    <script type="application/json" id="categories-data">@json($categories)</script>


    <!-- Alpine.js Admin State -->
    <script>
        function adminDashboardState() {
            return {
        activeTab: 'inventory',
        showProductModal: false,
        showCashierModal: false,
        isEditingCashier: false,
        editingCashierId: null,
        showCategoryModal: false,
        isEditing: false,
        isEditingCategory: false,
        search: '',
        activeCategory: 'Semua',
        currentPage: 1,
        itemsPerPage: 10,
        
        // Products list (fed dynamically from JSON Island)
        products: JSON.parse(document.getElementById('products-data').textContent),

        // Cashiers list (fed dynamically from JSON Island)
        cashiers: JSON.parse(document.getElementById('cashiers-data').textContent),

        // Categories list (fed dynamically from JSON Island)
        categories: JSON.parse(document.getElementById('categories-data').textContent),

        // Product Form State
        statusFilter: 'all', // 'all' | 'active' | 'inactive'
        newProduct: { id: null, sku: '', name: '', category: 'Premium', cost_price: '', selling_price: '', price_unit: 'pcs', stock: '', weight_grams: 500, price_tiers: [], is_bundle: false, bundle_items: [], is_active: true },
        hasPriceTiers: false,
        costPriceMode: 'pct',   // 'pct' = persentase dari harga jual, 'manual' = isi sendiri
        costPricePct: 50,       // default 50% dari harga jual
        
        // Wholesale Form State
        wholesaleForm: {
            customer_name: '',
            customer_phone: '',
            payment_method: 'Tunai',
            discount: '',
            shipping_cost: '',
            paymentReceived: '',
            items: [
                { name: '', qty: 1, price_unit: 'pcs', selling_price: '', cost_price: '', showSuggestions: false, activeSuggestionIndex: -1 }
            ]
        },

        // Cashier Form State
        newCashier: { name: '', email: '', password: '', branch: 'Pusat Cianjur' },

        // Category Form State
        newCategory: { id: null, name: '' },

        // Receivables / Piutang State
        receivables: [],
        selectedCustomer: null,
        showInstallmentModal: false,
        installmentForm: { transaction_id: null, transaction_code: '', amount: '', payment_method: 'Tunai', note: '', max_amount: 0 },

        // Camera integration state
        showCamera: false,
        cameraStream: null,
        capturedPhotoFile: null,
        capturedPhotoPreview: '',

        init() {
            // Restore active tab from URL hash on page load
            const validTabs = ['inventory', 'cashiers', 'categories', 'wholesale', 'receivables', 'settings'];
            const hash = window.location.hash.replace('#', '');
            if (validTabs.includes(hash)) {
                this.activeTab = hash;
                // Also update the header label
                const labelMap = { 
                    inventory: 'Stok Inventori', 
                    cashiers: 'Kelola Kasir', 
                    categories: 'Kelola Kategori', 
                    wholesale: 'Nota Partai', 
                    receivables: 'Kelola Piutang',
                    settings: 'Pengaturan Shop' 
                };
                window.dispatchEvent(new CustomEvent('change-tab', { detail: hash }));
            }

            if (this.categories.length > 0) {
                this.newProduct.category = this.categories[0].name;
            }
            this.$watch('search', value => {
                this.currentPage = 1;
            });
            this.$watch('activeCategory', value => {
                this.currentPage = 1;
            });
            this.$watch('activeTab', value => {
                if (value === 'receivables') {
                    this.fetchReceivables();
                }
            });
            if (this.activeTab === 'receivables') {
                this.fetchReceivables();
            }
            // Keep hash in sync when tab changes via event
            window.addEventListener('change-tab', (e) => {
                this.activeTab = e.detail;
                window.location.hash = e.detail;
            });
        },

        fetchReceivables() {
            fetch('/admin/receivables')
                .then(res => res.json())
                .then(data => {
                    this.receivables = data;
                    // If we have a selected customer, refresh their data too
                    if (this.selectedCustomer) {
                        const refreshed = data.find(c => c.customer_name.toLowerCase() === this.selectedCustomer.customer_name.toLowerCase());
                        this.selectedCustomer = refreshed || null;
                    }
                })
                .catch(err => {
                    this.showToast('Gagal memuat data piutang!', 'error');
                });
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

        submitInstallment() {
            const amount = parseInt(this.installmentForm.amount);
            if (!amount || amount <= 0) {
                this.showToast('Nominal pembayaran cicilan harus valid!', 'warning');
                return;
            }
            if (amount > this.installmentForm.max_amount) {
                this.showToast('Jumlah pembayaran melebihi sisa tagihan!', 'warning');
                return;
            }

            const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

            fetch(`/admin/transactions/${this.installmentForm.transaction_id}/installments`, {
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
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw err; });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.showInstallmentModal = false;
                    this.fetchReceivables();
                }
            })
            .catch(err => {
                this.showToast(err.message || 'Gagal menyimpan cicilan!', 'error');
            });
        },

        formatRupiah(num) {
            return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        },

        formatStock(stock, unit) {
            if (unit === 'gram' && stock >= 1000) {
                return (stock / 1000).toFixed(2).replace(/\.?0+$/, '') + ' kg';
            }
            return stock + ' ' + unit;
        },

        levenshteinDistance(s1, s2) {
            const len1 = s1.length;
            const len2 = s2.length;
            const matrix = [];
            
            for (let i = 0; i <= len1; i++) {
                matrix[i] = [i];
            }
            for (let j = 0; j <= len2; j++) {
                matrix[0][j] = j;
            }
            
            for (let i = 1; i <= len1; i++) {
                for (let j = 1; j <= len2; j++) {
                    const cost = s1[i - 1] === s2[j - 1] ? 0 : 1;
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j] + 1,      // Deletion
                        matrix[i][j - 1] + 1,      // Insertion
                        matrix[i - 1][j - 1] + cost // Substitution
                    );
                }
            }
            
            return matrix[len1][len2];
        },

        fuzzyMatch(text, query) {
            if (!query) return true;
            if (!text) return false;
            text = text.toLowerCase().trim();
            query = query.toLowerCase().trim();
            
            if (text.includes(query)) return true;
            
            const queryWords = query.split(/\s+/);
            const textWords = text.split(/\s+/);
            
            return queryWords.every(qWord => {
                if (!qWord) return true;
                if (text.includes(qWord)) return true;
                
                return textWords.some(tWord => {
                    const distance = this.levenshteinDistance(qWord, tWord);
                    let threshold = 1;
                    if (qWord.length > 4) {
                        threshold = 2;
                    }
                    return distance <= threshold;
                });
            });
        },

        get filteredProducts() {
            return this.products.filter(p => {
                const matchesCategory = this.activeCategory === 'Semua' || p.category === this.activeCategory;
                if (!matchesCategory) return false;
                
                const isAct = p.is_active !== undefined ? Boolean(p.is_active) : true;
                if (this.statusFilter === 'active' && !isAct) return false;
                if (this.statusFilter === 'inactive' && isAct) return false;

                const matchesName = p.name ? this.fuzzyMatch(p.name, this.search) : false;
                const matchesSku = p.sku ? this.fuzzyMatch(p.sku, this.search) : false;
                return matchesName || matchesSku;
            });
        },

        get totalPages() {
            return Math.ceil(this.filteredProducts.length / this.itemsPerPage);
        },

        get paginatedProducts() {
            const total = this.totalPages;
            if (this.currentPage > total && total > 0) {
                this.currentPage = total;
            }
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredProducts.slice(start, end);
        },

        async toggleProductActive(product) {
            try {
                const res = await fetch(`/admin/products/${product.id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    product.is_active = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Gagal mengubah status produk', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Terjadi kesalahan jaringan', 'error');
            }
        },

        // Toast Helper
        showToast(message, type = 'success') {
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        },

        // Confirmation Modal State
        confirmModal: {
            show: false,
            title: '',
            message: '',
            onConfirm: null,
            confirmText: 'Ya, Lanjutkan',
            type: 'warning' // 'danger' for delete, 'warning' for save
        },

        showConfirm(title, message, callback, type = 'warning', confirmText = 'Ya, Lanjutkan') {
            this.confirmModal = {
                show: true,
                title: title,
                message: message,
                onConfirm: callback,
                confirmText: confirmText,
                type: type
            };
        },

        executeConfirm() {
            if (this.confirmModal.onConfirm) {
                this.confirmModal.onConfirm();
            }
            this.confirmModal.show = false;
        },

        editProduct(product) {
            this.isEditing = true;
            this.newProduct = {
                id: product.id,
                sku: product.sku,
                name: product.name,
                category: product.category,
                cost_price: product.cost_price,
                selling_price: product.selling_price,
                price_unit: product.price_unit,
                stock: product.stock,
                weight_grams: product.weight_grams || 500,
                price_tiers: product.price_tiers ? JSON.parse(JSON.stringify(product.price_tiers)) : [],
                is_bundle: product.is_bundle || false,
                is_active: product.is_active !== undefined ? Boolean(product.is_active) : true,
                bundle_items: product.bundle_items ? JSON.parse(JSON.stringify(product.bundle_items)).map(item => {
                    const matchedProd = this.products.find(p => p.id === item.product_id);
                    return {
                        product_id: item.product_id,
                        name: matchedProd ? matchedProd.name : '',
                        quantity: item.quantity,
                        showSuggestions: false
                    };
                }) : []
            };
            this.hasPriceTiers = this.newProduct.price_tiers.length > 0;
            // When editing, pre-detect mode: check if cost_price is a round % of selling_price
            if (product.selling_price > 0) {
                const pct = Math.round((product.cost_price / product.selling_price) * 100);
                if (pct > 0 && pct <= 99) {
                    this.costPriceMode = 'pct';
                    this.costPricePct  = pct;
                } else {
                    this.costPriceMode = 'manual';
                }
            } else {
                this.costPriceMode = 'manual';
            }
            this.showProductModal = true;
        },

        dataURItoBlob(dataURI) {
            try {
                const byteString = atob(dataURI.split(',')[1]);
                const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
                const ab = new ArrayBuffer(byteString.length);
                const ia = new Uint8Array(ab);
                for (let i = 0; i < byteString.length; i++) {
                    ia[i] = byteString.charCodeAt(i);
                }
                return new Blob([ab], {type: mimeString});
            } catch (e) {
                console.error('dataURItoBlob failed:', e);
                return null;
            }
        },

        startCamera() {
            this.capturedPhotoFile = null;
            this.capturedPhotoPreview = '';
            
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                } 
            })
            .then(stream => {
                this.cameraStream = stream;
                this.showCamera = true;
                this.$nextTick(() => {
                    const video = document.getElementById('camera_feed');
                    if (video) {
                        video.srcObject = stream;
                    }
                });
            })
            .catch(err => {
                console.error('Error camera:', err);
                this.showToast('Gagal mengakses kamera! Pastikan izin kamera sudah diberikan.', 'error');
            });
        },

        stopCamera() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
                this.cameraStream = null;
            }
            this.showCamera = false;
            const video = document.getElementById('camera_feed');
            if (video) {
                video.srcObject = null;
            }
        },

        capturePhoto() {
            const video = document.getElementById('camera_feed');
            if (!video) return;

            const canvas = document.createElement('canvas');
            const videoWidth = video.videoWidth || 640;
            const videoHeight = video.videoHeight || 480;
            
            const maxDimension = 800;
            let targetWidth = videoWidth;
            let targetHeight = videoHeight;
            if (videoWidth > maxDimension || videoHeight > maxDimension) {
                if (videoWidth > videoHeight) {
                    targetWidth = maxDimension;
                    targetHeight = Math.round((videoHeight / videoWidth) * maxDimension);
                } else {
                    targetHeight = maxDimension;
                    targetWidth = Math.round((videoWidth / videoHeight) * maxDimension);
                }
            }

            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext('2d');
            
            ctx.drawImage(video, 0, 0, targetWidth, targetHeight);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            this.capturedPhotoPreview = dataUrl;
            
            const blob = this.dataURItoBlob(dataUrl);
            if (blob) {
                this.capturedPhotoFile = blob;
                console.log('Photo converted synchronously to Blob, size:', blob.size, 'bytes');
            } else {
                console.warn('Synchronous blob decoding failed, falling back to toBlob');
                canvas.toBlob(b => {
                    if (b) {
                        this.capturedPhotoFile = b;
                    }
                }, 'image/jpeg', 0.85);
            }

            this.stopCamera();
            this.showToast('Foto berhasil diambil!', 'success');
        },

        resetProductForm(keepOpen = false) {
            this.stopCamera();
            this.capturedPhotoFile = null;
            this.capturedPhotoPreview = '';
            
            const defaultCategory = this.categories.length > 0 ? this.categories[0].name : '';
            this.newProduct = { id: null, sku: '', name: '', category: defaultCategory, cost_price: '', selling_price: '', price_unit: 'pcs', stock: '', weight_grams: 500, price_tiers: [], is_bundle: false, bundle_items: [], is_active: true };
            this.hasPriceTiers = false;
            this.costPriceMode = 'pct';
            this.costPricePct  = 50;
            this.isEditing = false;
            if (!keepOpen) {
                this.showProductModal = false;
            }
            const fileInput = document.getElementById('product_image');
            if (fileInput) fileInput.value = '';
        },

        addPriceTier() {
            this.newProduct.price_tiers.push({ min_qty: '', max_qty: '', price: '' });
        },

        removePriceTier(idx) {
            this.newProduct.price_tiers.splice(idx, 1);
        },

        addBundleItem() {
            if (!this.newProduct.bundle_items) {
                this.newProduct.bundle_items = [];
            }
            this.newProduct.bundle_items.push({ product_id: '', name: '', quantity: 1, showSuggestions: false });
        },

        removeBundleItem(idx) {
            this.newProduct.bundle_items.splice(idx, 1);
        },

        getBundleSuggestions(query) {
            const currentProductId = this.newProduct.id;
            let list = this.products.filter(p => !p.is_bundle && p.id !== currentProductId);
            
            if (!query || query.trim().length === 0) {
                return list.slice(0, 5);
            }
            
            const q = query.toLowerCase().trim();
            return list.filter(p => 
                (p.name && p.name.toLowerCase().includes(q)) || 
                (p.sku && p.sku.toLowerCase().includes(q))
            ).slice(0, 5);
        },

        selectBundleProduct(idx, product) {
            const item = this.newProduct.bundle_items[idx];
            if (item) {
                item.product_id = product.id;
                item.name = product.name;
                item.showSuggestions = false;
            }
        },

        calculateBundleCostPrice() {
            if (!this.newProduct.bundle_items) return 0;
            return this.newProduct.bundle_items.reduce((sum, item) => {
                const prod = this.products.find(p => p.id == item.product_id);
                const cost = prod ? (parseFloat(prod.cost_price) || 0) : 0;
                const qty = parseFloat(item.quantity) || 0;
                return sum + (cost * qty);
            }, 0);
        },

        // Computed effective cost_price (used before submit)
        get effectiveCostPrice() {
            if (this.costPriceMode === 'pct') {
                const sell = parseFloat(this.newProduct.selling_price) || 0;
                return Math.round(sell * (parseFloat(this.costPricePct) || 0) / 100);
            }
            return parseFloat(this.newProduct.cost_price) || 0;
        },

        compressImageJS(file, maxWidth = 800, quality = 0.75) {
            return new Promise((resolve) => {
                if (!file.type.startsWith('image/')) {
                    resolve(file);
                    return;
                }
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth || height > maxWidth) {
                            if (width > height) {
                                height = Math.round((height / width) * maxWidth);
                                width = maxWidth;
                            } else {
                                width = Math.round((width / height) * maxWidth);
                                height = maxWidth;
                            }
                        }
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            if (blob) {
                                const originalName = file.name || 'image.jpg';
                                const dotIndex = originalName.lastIndexOf('.');
                                const baseName = dotIndex !== -1 ? originalName.slice(0, dotIndex) : originalName;
                                const newFile = new File([blob], baseName + '.jpg', { type: 'image/jpeg' });
                                resolve(newFile);
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => resolve(file);
                };
                reader.onerror = () => resolve(file);
            });
        },

        async saveProduct() {
            // Sync cost_price from mode before validation
            if (this.costPriceMode === 'pct') {
                this.newProduct.cost_price = this.effectiveCostPrice;
            }
            if (!this.newProduct.name || this.newProduct.cost_price === '' || this.newProduct.selling_price === '' || (this.newProduct.stock === '' && !this.newProduct.is_bundle)) {
                this.showToast('Silakan lengkapi semua kolom wajib!', 'warning');
                return;
            }
            if (this.newProduct.is_bundle && (!this.newProduct.bundle_items || this.newProduct.bundle_items.length === 0)) {
                this.showToast('Silakan tambahkan minimal satu produk komponen untuk bundling!', 'warning');
                return;
            }

            this.showConfirm(
                this.isEditing ? 'Simpan Perubahan Produk?' : 'Tambah Produk Baru?',
                this.isEditing ? 'Apakah Anda yakin ingin menyimpan perubahan pada produk ini?' : 'Apakah Anda yakin ingin menambahkan produk baru ini?',
                async () => {
                    this.showToast('Mengompresi & mengunggah gambar...', 'info');
                    const formData = new FormData();
                    formData.append('sku', this.newProduct.sku || '');
                    formData.append('name', this.newProduct.name);
                    formData.append('category', this.newProduct.category);
                    formData.append('cost_price', this.newProduct.cost_price);
                    formData.append('selling_price', this.newProduct.selling_price);
                    formData.append('price_unit', this.newProduct.price_unit);
                    formData.append('stock', this.newProduct.is_bundle ? '0' : this.newProduct.stock);
                    const activeTiers = this.hasPriceTiers ? this.newProduct.price_tiers : [];
                    formData.append('price_tiers', JSON.stringify(activeTiers));
                    formData.append('weight_grams', this.newProduct.weight_grams || 500);
                    formData.append('is_bundle', this.newProduct.is_bundle ? '1' : '0');
                    formData.append('is_active', this.newProduct.is_active ? '1' : '0');
                    formData.append('bundle_items', JSON.stringify(this.newProduct.is_bundle ? this.newProduct.bundle_items : []));

                    let rawFile = null;
                    if (this.capturedPhotoFile) {
                        rawFile = this.capturedPhotoFile;
                    } else {
                        const fileInput = document.getElementById('product_image');
                        if (fileInput && fileInput.files[0]) {
                            rawFile = fileInput.files[0];
                        }
                    }

                    if (rawFile) {
                        try {
                            const compressed = await this.compressImageJS(rawFile);
                            formData.append('image', compressed, compressed.name || 'photo.jpg');
                        } catch (e) {
                            console.error('JS Compression failed:', e);
                            formData.append('image', rawFile);
                        }
                    }

                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    const url = this.isEditing ? `/admin/products/${this.newProduct.id}` : '/admin/products';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (this.isEditing) {
                                const idx = this.products.findIndex(p => p.id === this.newProduct.id);
                                if (idx !== -1) {
                                    this.products[idx] = data.product;
                                }
                                this.resetProductForm(false);
                            } else {
                                this.products.push(data.product);
                                this.resetProductForm(true); // Keep modal open for faster entry of next products
                            }
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menyimpan produk. Pastikan SKU unik, harga/stok numerik, dan format gambar valid.', 'error');
                    });
                },
                'warning',
                'Ya, Simpan'
            );
        },

        deleteProduct(id) {
            this.showConfirm(
                'Hapus Produk?',
                'Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch(`/admin/products/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.products = this.products.filter(p => p.id !== id);
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menghapus produk.', 'error');
                    });
                },
                'danger',
                'Ya, Hapus'
            );
        },

        addCashier() {
            if (!this.newCashier.name || !this.newCashier.email || !this.newCashier.password || !this.newCashier.branch) {
                this.showToast('Silakan lengkapi semua kolom!', 'warning');
                return;
            }

            this.showConfirm(
                'Daftarkan Kasir Baru?',
                'Apakah Anda yakin ingin mendaftarkan akun kasir baru ini?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch('/admin/cashiers', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.newCashier)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.cashiers.push(data.cashier);
                            this.newCashier = { name: '', email: '', password: '', branch: 'Pusat Cianjur' };
                            this.showCashierModal = false;
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal mendaftarkan kasir. Pastikan email belum terdaftar.', 'error');
                    });
                },
                'warning',
                'Ya, Daftarkan'
            );
        },

        editCashier(c) {
            this.isEditingCashier = true;
            this.editingCashierId = c.id;
            this.newCashier = { name: c.name, email: c.email, password: '', branch: c.branch };
            this.showCashierModal = true;
        },

        updateCashier() {
            if (!this.newCashier.name || !this.newCashier.email || !this.newCashier.branch) {
                this.showToast('Nama, email, dan cabang wajib diisi!', 'warning');
                return;
            }

            this.showConfirm(
                'Simpan Perubahan Kasir?',
                'Apakah Anda yakin ingin memperbarui data akun kasir ini?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch(`/admin/cashiers/${this.editingCashierId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.newCashier)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const index = this.cashiers.findIndex(c => c.id === this.editingCashierId);
                            if (index !== -1) {
                                const lastActive = this.cashiers[index].lastActive;
                                this.cashiers[index] = data.cashier;
                                this.cashiers[index].lastActive = lastActive;
                            }
                            this.newCashier = { name: '', email: '', password: '', branch: 'Pusat Cianjur' };
                            this.showCashierModal = false;
                            this.isEditingCashier = false;
                            this.editingCashierId = null;
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal memperbarui kasir.', 'error');
                    });
                },
                'warning',
                'Ya, Simpan'
            );
        },

        deleteCashier(id) {
            this.showConfirm(
                'Hapus Akun Kasir?',
                'Apakah Anda yakin ingin menghapus akun kasir ini? Kasir tidak akan dapat masuk ke sistem lagi.',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch(`/admin/cashiers/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.cashiers = this.cashiers.filter(c => c.id !== id);
                            this.showToast(data.message, 'success');
                        } else {
                            this.showToast(data.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast('Gagal menghapus akun kasir.', 'error');
                    });
                },
                'danger',
                'Ya, Hapus'
            );
        },

        impersonateUser(id, name) {
            this.showConfirm(
                'Masuk Sebagai ' + name + '?',
                'Apakah Anda yakin ingin masuk ke akun kasir ' + name + '? Anda akan diarahkan ke dashboard kasir.',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/impersonate/${id}`;
                    
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = csrfToken;
                    
                    form.appendChild(tokenInput);
                    document.body.appendChild(form);
                    form.submit();
                },
                'warning',
                'Ya, Masuk'
            );
        },

        editCategory(category) {
            this.isEditingCategory = true;
            this.newCategory = {
                id: category.id,
                name: category.name
            };
            this.showCategoryModal = true;
        },

        resetCategoryForm() {
            this.newCategory = { id: null, name: '' };
            this.isEditingCategory = false;
            this.showCategoryModal = false;
        },

        saveCategory() {
            if (!this.newCategory.name.trim()) {
                this.showToast('Silakan masukkan nama kategori!', 'warning');
                return;
            }

            this.showConfirm(
                this.isEditingCategory ? 'Simpan Perubahan Kategori?' : 'Tambah Kategori Baru?',
                this.isEditingCategory ? 'Apakah Anda yakin ingin menyimpan perubahan pada kategori ini?' : 'Apakah Anda yakin ingin menambahkan kategori baru ini?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    const url = this.isEditingCategory ? `/admin/categories/${this.newCategory.id}` : '/admin/categories';
                    const method = this.isEditingCategory ? 'PUT' : 'POST';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: this.newCategory.name })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (this.isEditingCategory) {
                                const idx = this.categories.findIndex(c => c.id === this.newCategory.id);
                                if (idx !== -1) {
                                    // Update products list locally to match renamed category
                                    const oldName = this.categories[idx].name;
                                    this.products.forEach(p => {
                                        if (p.category === oldName) p.category = data.category.name;
                                    });

                                    this.categories[idx] = data.category;
                                }
                            } else {
                                this.categories.push(data.category);
                            }
                            this.resetCategoryForm();
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menyimpan kategori. Pastikan nama kategori unik.', 'error');
                    });
                },
                'warning',
                'Ya, Simpan'
            );
        },

        deleteCategory(id) {
            const category = this.categories.find(c => c.id === id);
            if (!category) return;

            this.showConfirm(
                'Hapus Kategori?',
                'Apakah Anda yakin ingin menghapus kategori \'' + category.name + '\'? Tindakan ini tidak dapat dibatalkan.',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch(`/admin/categories/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.categories = this.categories.filter(c => c.id !== id);
                            this.showToast(data.message, 'success');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menghapus kategori.', 'error');
                    });
                },
                'danger',
                'Ya, Hapus'
            );
        },

        shareWholesaleToWa() {
            if (!this.wholesaleForm.customer_name.trim()) {
                this.showToast('Nama pelanggan wajib diisi untuk share WA!', 'warning');
                return;
            }
            
            const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            let msg = `*NOTA PENJUALAN PARTAI - PUSAT KURMA*\n`;
            msg += `--------------------------------------\n`;
            msg += `*Pelanggan:* ${this.wholesaleForm.customer_name}\n`;
            msg += `*Tanggal:* ${today}\n`;
            msg += `*Status Bayar:* ${this.wholesaleForm.payment_method.toUpperCase()}\n`;
            msg += `--------------------------------------\n\n`;
            
            msg += `*RINCIAN BARANG:*\n`;
            let subtotal = 0;
            this.wholesaleForm.items.forEach((item, idx) => {
                if (!item.name) return;
                const itemSub = Math.round((parseFloat(item.selling_price) || 0) * (parseFloat(item.qty) || 0));
                subtotal += itemSub;
                msg += `${idx + 1}. *${item.name}* : ${item.qty} ${item.price_unit} x ${this.formatRupiah(item.selling_price)} = ${this.formatRupiah(itemSub)}\n`;
            });
            msg += `--------------------------------------\n`;
            msg += `*Subtotal:* ${this.formatRupiah(subtotal)}\n`;
            
            const disc = parseFloat(this.wholesaleForm.discount) || 0;
            if (disc > 0) {
                msg += `*Diskon:* -${this.formatRupiah(disc)}\n`;
            }
            
            const ship = parseFloat(this.wholesaleForm.shipping_cost) || 0;
            if (ship > 0) {
                msg += `*Ongkir:* ${this.formatRupiah(ship)}\n`;
            }
            
            const grand = Math.max(0, subtotal - disc + ship);
            msg += `*TOTAL TAGIHAN:* *${this.formatRupiah(grand)}*\n`;
            
            const pay = parseFloat(this.wholesaleForm.paymentReceived) || 0;
            if (pay > 0) {
                msg += `*DP / Uang Diterima:* ${this.formatRupiah(pay)}\n`;
                const rem = Math.max(0, grand - pay);
                if (rem > 0) {
                    msg += `*Sisa Tagihan:* *${this.formatRupiah(rem)}*\n`;
                }
            }
            
            msg += `\n*Pembayaran dapat ditransfer ke rekening resmi kami:*\n`;
            msg += `- Bank Mandiri: 182-000-888-9990 a/n Pusat Kurma Indonesia\n`;
            msg += `- Bank BCA: 379-000-777-1110 a/n Rizky Wijaya\n\n`;
            msg += `_Terima kasih atas orderan partai Anda!_`;
            
            let phone = this.wholesaleForm.customer_phone ? this.wholesaleForm.customer_phone.replace(/[^0-9]/g, '') : '';
            let url = '';
            if (phone) {
                if (phone.startsWith('0')) {
                    phone = '62' + phone.slice(1);
                }
                url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(msg)}`;
            } else {
                url = `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
            }
            window.open(url, '_blank');
        },

        addWholesaleItem() {
            this.wholesaleForm.items.push({
                name: '',
                qty: 1,
                price_unit: 'pcs',
                selling_price: '',
                cost_price: '',
                showSuggestions: false,
                activeSuggestionIndex: -1
            });
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('.product-search-input');
                if (inputs.length > 0) {
                    inputs[inputs.length - 1].focus();
                }
            });
        },

        removeWholesaleItem(idx) {
            this.wholesaleForm.items.splice(idx, 1);
            if (this.wholesaleForm.items.length === 0) {
                this.addWholesaleItem();
            }
        },

        getWholesaleSuggestions(query) {
            if (!query || query.trim().length < 2) return [];
            const q = query.toLowerCase().trim();
            return this.products.filter(p => 
                (p.name && p.name.toLowerCase().includes(q)) || 
                (p.sku && p.sku.toLowerCase().includes(q))
            ).slice(0, 5);
        },

        selectWholesaleProduct(idx, product) {
            const item = this.wholesaleForm.items[idx];
            if (item) {
                item.name = product.name;
                item.selling_price = product.selling_price;
                item.cost_price = product.cost_price;
                item.price_unit = product.price_unit || 'pcs';
                item.showSuggestions = false;
                item.activeSuggestionIndex = -1;
            }
        },

        navigateSuggestions(idx, dir) {
            const item = this.wholesaleForm.items[idx];
            const suggestions = this.getWholesaleSuggestions(item.name);
            if (suggestions.length === 0) return;
            
            if (item.activeSuggestionIndex === undefined || item.activeSuggestionIndex === null) {
                item.activeSuggestionIndex = -1;
            }
            
            let newIdx = item.activeSuggestionIndex + dir;
            if (newIdx < 0) newIdx = suggestions.length - 1;
            if (newIdx >= suggestions.length) newIdx = 0;
            
            item.activeSuggestionIndex = newIdx;
        },

        selectActiveSuggestion(idx) {
            const item = this.wholesaleForm.items[idx];
            const suggestions = this.getWholesaleSuggestions(item.name);
            
            if (item.activeSuggestionIndex !== undefined && item.activeSuggestionIndex >= 0 && item.activeSuggestionIndex < suggestions.length) {
                this.selectWholesaleProduct(idx, suggestions[item.activeSuggestionIndex]);
            } else if (suggestions.length > 0) {
                this.selectWholesaleProduct(idx, suggestions[0]);
            }
            item.activeSuggestionIndex = -1;
        },

        calculateWholesaleSubtotal() {
            return this.wholesaleForm.items.reduce((sum, item) => {
                const qty = parseFloat(item.qty) || 0;
                const price = parseFloat(item.selling_price) || 0;
                return sum + (qty * price);
            }, 0);
        },

        calculateWholesaleGrandTotal() {
            const subtotal = this.calculateWholesaleSubtotal();
            const discount = parseFloat(this.wholesaleForm.discount) || 0;
            const shipping = parseFloat(this.wholesaleForm.shipping_cost) || 0;
            return Math.max(0, subtotal - discount + shipping);
        },

        calculateWholesaleRemaining() {
            const grandTotal = this.calculateWholesaleGrandTotal();
            const received = parseFloat(this.wholesaleForm.paymentReceived) || 0;
            return grandTotal - received;
        },

        saveWholesale() {
            if (!this.wholesaleForm.customer_name.trim()) {
                this.showToast('Nama pelanggan wajib diisi!', 'warning');
                return;
            }
            if (this.wholesaleForm.items.length === 0 || !this.wholesaleForm.items[0].name.trim()) {
                this.showToast('Minimal harus ada 1 barang dengan nama yang terisi!', 'warning');
                return;
            }

            this.showConfirm(
                'Simpan & Cetak Nota?',
                'Apakah Anda yakin ingin menyimpan transaksi partai ini dan mencetak nota?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    
                    const itemsData = this.wholesaleForm.items
                        .filter(item => item.name.trim() !== '')
                        .map(item => ({
                            name: item.name,
                            qty: parseFloat(item.qty) || 1,
                            price_unit: item.price_unit,
                            selling_price: parseInt(item.selling_price) || 0,
                            cost_price: item.cost_price !== '' ? parseInt(item.cost_price) : 0
                        }));

                    const payload = {
                        customer_name: this.wholesaleForm.customer_name,
                        customer_phone: this.wholesaleForm.customer_phone,
                        payment_method: this.wholesaleForm.payment_method,
                        discount: parseInt(this.wholesaleForm.discount) || 0,
                        shipping_cost: parseInt(this.wholesaleForm.shipping_cost) || 0,
                        payment_received: parseInt(this.wholesaleForm.paymentReceived) || 0,
                        items: itemsData
                    };

                    fetch('/admin/wholesale-transactions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.showToast(data.message, 'success');
                            
                            // Reset form
                            this.wholesaleForm = {
                                customer_name: '',
                                customer_phone: '',
                                payment_method: 'Tunai',
                                discount: '',
                                shipping_cost: '',
                                paymentReceived: '',
                                items: [
                                    { name: '', qty: 1, price_unit: 'pcs', selling_price: '', cost_price: '', showSuggestions: false, activeSuggestionIndex: -1 }
                                ]
                            };

                            const printUrl = `/admin/wholesale-transactions/${data.transaction.id}/print`;
                            window.open(printUrl, '_blank');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menyimpan transaksi partai.', 'error');
                    });
                },
                'warning',
                'Ya, Simpan'
            );
        },

        markWholesalePaid(trxId) {
            this.showConfirm(
                'Tandai Lunas?',
                'Apakah Anda yakin ingin menandai transaksi partai ini sebagai LUNAS?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    fetch(`/admin/transactions/${trxId}/mark-as-paid`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Gagal memperbarui status pembayaran.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal memperbarui status pembayaran.', 'error');
                    });
                },
                'warning',
                'Ya, Lunas'
            );
        },

        deleteWholesaleTransaction(trxId) {
            this.showConfirm(
                'Hapus Transaksi?',
                'Apakah Anda yakin ingin menghapus transaksi partai ini secara permanen?',
                () => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    fetch(`/admin/transactions/${trxId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Gagal menghapus transaksi.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast(err.message || 'Gagal menghapus transaksi.', 'error');
                    });
                },
                'danger',
                'Ya, Hapus'
            );
        }
            };
        }
    </script>
    <div x-data="adminDashboardState()" class="max-w-full overflow-hidden">

        <div class="w-full min-w-0">
            <!-- 1. INVENTORY TAB CONTENT -->
            <div x-show="activeTab === 'inventory'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <!-- <div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-lg leading-tight">Daftar Stok Produk Kurma</h3>
                    <p class="text-sm text-slate-400 dark:text-purple-400 font-medium mt-1">Kelola stok, harga, dan SKU semua produk kurma</p>
                </div> -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('admin.products.fast-upload') }}"
                       class="px-5 py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-violet-600/20 whitespace-nowrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                        Upload Foto AI
                    </a>
                    <button type="button"
                            @click="resetProductForm(); showProductModal = true"
                            class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-emerald-700/10 whitespace-nowrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Produk
                    </button>
                </div>
            </div>

            <!-- Category and Search Panel -->
            <div class="admin-card bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
                <!-- Category Selectors -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 w-full md:w-auto">
                    <template x-for="cat in ['Semua', ...categories.map(c => c.name)]">
                        <button type="button" 
                                @click="activeCategory = cat"
                                :class="activeCategory === cat ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 whitespace-nowrap"
                                x-text="cat">
                        </button>
                    </template>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Status Filter Pills -->
                    <div class="flex items-center bg-slate-100/80 p-1 rounded-xl gap-1 text-xs font-bold w-full sm:w-auto justify-center">
                        <button type="button" @click="statusFilter = 'all'" :class="statusFilter === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-lg transition duration-150">Semua</button>
                        <button type="button" @click="statusFilter = 'active'" :class="statusFilter === 'active' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-lg transition duration-150">Aktif</button>
                        <button type="button" @click="statusFilter = 'inactive'" :class="statusFilter === 'inactive' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-lg transition duration-150">Nonaktif</button>
                    </div>

                    <!-- Search box -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" 
                            x-model="search"
                            placeholder="Cari kurma atau SKU..." 
                            class="w-full pl-9 pr-4 py-2 text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                    </div>
                </div>
            </div>

            <!-- Responsive Table (Scrolls horizontally or turns into beautiful cards on Mobile) -->
            <div class="admin-card admin-table-wrap bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4">Foto</th>
                                <th class="px-6 py-4">SKU</th>
                                <th class="px-6 py-4">Nama Produk</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4 text-right">Harga Modal</th>
                                <th class="px-6 py-4 text-right">Harga Jual</th>
                                <th class="px-6 py-4 text-right">Stok</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            <template x-for="p in paginatedProducts" :key="p.id">
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <template x-if="p.image_path">
                                            <img :src="'/storage/' + p.image_path" class="w-10 h-10 object-cover rounded-xl border border-slate-100 shadow-sm" alt="Foto">
                                        </template>
                                        <template x-if="!p.image_path">
                                            <div class="w-10 h-10 bg-emerald-800 text-white font-bold flex items-center justify-center rounded-xl text-xs uppercase" x-text="(p.name || '').split(' ').slice(0, 2).map(w => w[0]).join('')"></div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 text-xs font-mono" x-text="p.sku"></td>
                                     <td class="px-6 py-4 text-slate-800">
                                         <div class="flex items-center gap-2">
                                             <span x-text="p.name"></span>
                                             <template x-if="p.is_bundle">
                                                 <span class="text-[9px] bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 font-extrabold uppercase px-1.5 py-0.5 rounded border border-purple-200 dark:border-purple-800">📦 Paket</span>
                                             </template>
                                         </div>
                                     </td>
                                    <td class="px-6 py-4">
                                        <span :class="{
                                            'bg-purple-50 text-purple-700 border-purple-100': p.category === 'Premium',
                                            'bg-blue-50 text-blue-700 border-blue-100': p.category === 'Basah',
                                            'bg-amber-50 text-amber-700 border-amber-100': p.category === 'Kering',
                                            'bg-emerald-50 text-emerald-700 border-emerald-100': p.category !== 'Premium' && p.category !== 'Basah' && p.category !== 'Kering'
                                        }" class="text-[10px] font-bold tracking-wide uppercase px-2 py-0.5 rounded border" x-text="p.category"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-slate-500" x-text="formatRupiah(p.cost_price)"></td>
                                    <td class="px-6 py-4 text-right text-emerald-700" x-text="formatRupiah(p.selling_price) + ' / ' + p.price_unit"></td>
                                    <td class="px-6 py-4 text-right" x-text="formatStock(p.stock, p.price_unit)"></td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col gap-1.5 items-center justify-center">
                                            <!-- Main Product Active Toggle Button -->
                                            <button type="button" 
                                                    @click="toggleProductActive(p)"
                                                    :class="(p.is_active !== undefined ? p.is_active : true) ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-extrabold rounded-full border transition duration-150 shadow-sm cursor-pointer"
                                                    :title="(p.is_active !== undefined ? p.is_active : true) ? 'Klik untuk menonaktifkan produk' : 'Klik untuk mengaktifkan produk'">
                                                <span class="w-2 h-2 rounded-full" :class="(p.is_active !== undefined ? p.is_active : true) ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                                <span x-text="(p.is_active !== undefined ? p.is_active : true) ? 'Aktif' : 'Nonaktif'"></span>
                                            </button>

                                            <div class="flex items-center gap-1">
                                                <template x-if="p.stock <= 10">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-rose-50 text-rose-700 border border-rose-100">
                                                        Stok Menipis
                                                    </span>
                                                </template>

                                                <!-- Shop Active Status Badge -->
                                                <template x-if="p.is_active_in_shop">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-bold rounded bg-sky-50 text-sky-700 border border-sky-100">
                                                        🌐 Shop Aktif
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center flex items-center justify-center gap-1">
                                        <button type="button" 
                                                @click="editProduct(p)"
                                                class="p-2 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-xl transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button type="button" 
                                                @click="deleteProduct(p.id)"
                                                class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <div class="pagination-wrap bg-white px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs sm:text-sm font-semibold text-slate-500">
                        Menampilkan <span class="text-slate-800 font-bold" x-text="filteredProducts.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0"></span> 
                        sampai <span class="text-slate-800 font-bold" x-text="Math.min(currentPage * itemsPerPage, filteredProducts.length)"></span> 
                        dari <span class="text-slate-800 font-bold" x-text="filteredProducts.length"></span> produk
                    </div>
                    
                    <div class="flex items-center gap-1.5" x-show="totalPages > 1">
                        <!-- Prev Button -->
                        <button type="button" 
                                @click="if (currentPage > 1) currentPage--" 
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:text-emerald-700'"
                                class="page-btn p-2 text-slate-500 bg-slate-50 rounded-xl transition duration-150 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Page Numbers -->
                        <template x-for="page in totalPages" :key="page">
                            <button type="button" 
                                    @click="currentPage = page"
                                    :class="currentPage === page ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10 font-bold' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-emerald-700'"
                                    class="w-9 h-9 text-xs sm:text-sm rounded-xl transition duration-150 flex items-center justify-center"
                                    x-text="page">
                            </button>
                        </template>

                        <!-- Next Button -->
                        <button type="button" 
                                @click="if (currentPage < totalPages) currentPage++" 
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:text-emerald-700'"
                                class="p-2 text-slate-500 bg-slate-50 rounded-xl transition duration-150 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CASHIERS TAB CONTENT -->
        <div x-show="activeTab === 'cashiers'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <!-- <div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-lg leading-tight">Daftar Akun Kasir Toko</h3>
                    <p class="text-sm text-slate-400 dark:text-purple-400 font-medium mt-1">Kelola kredensial akun kasir yang bertugas melayani transaksi</p>
                </div> -->
                <button type="button" 
                        @click="isEditingCashier = false; newCashier = { name: '', email: '', password: '', branch: 'Pusat Cianjur' }; showCashierModal = true"
                        class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-emerald-700/10 whitespace-nowrap">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                    Daftarkan Kasir
                </button>
            </div>

            <!-- Responsive Table (Cashiers) -->
            <div class="admin-card admin-table-wrap bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4">Nama Lengkap</th>
                                <th class="px-6 py-4">Alamat Email</th>
                                <th class="px-6 py-4">Cabang</th>
                                <th class="px-6 py-4 text-center">Status Aktivitas</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            <template x-for="c in cashiers" :key="c.id">
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4 text-slate-800" x-text="c.name"></td>
                                    <td class="px-6 py-4 text-slate-500 text-xs" x-text="c.email"></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200" x-text="c.branch"></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="c.lastActive === 'Aktif Sekarang' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-100'"
                                              class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase rounded border"
                                              x-text="c.lastActive">
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" 
                                                    @click="impersonateUser(c.id, c.name)"
                                                    class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 rounded-xl text-xs font-bold transition duration-150 flex items-center gap-1.5 active:scale-95 shadow-sm"
                                                    title="Masuk sebagai kasir ini">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                                </svg>
                                                <span>Masuk</span>
                                            </button>
                                            <button type="button" 
                                                    @click="editCashier(c)"
                                                    class="p-2 text-slate-500 hover:text-emerald-700 hover:bg-slate-50 rounded-xl transition duration-150"
                                                    title="Edit Kasir">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 21.75a.75.75 0 0 1-.364.212l-3.5 1a.75.75 0 0 1-.914-.915l1-3.5a.75.75 0 0 1 .212-.364L16.863 4.487Zm0 0L19.5 7.125" />
                                                </svg>
                                            </button>
                                            <button type="button" 
                                                    @click="deleteCashier(c.id)"
                                                    class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition duration-150"
                                                    title="Hapus Kasir">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
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

        <!-- 3. CATEGORIES TAB CONTENT -->
        <div x-show="activeTab === 'categories'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <!-- <div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-lg leading-tight">Daftar Kategori Produk</h3>
                    <p class="text-sm text-slate-400 dark:text-purple-400 font-medium mt-1">Kelola kategori produk kurma dan lihat jumlah produk terkait</p>
                </div> -->
                <button type="button" 
                        @click="resetCategoryForm(); showCategoryModal = true"
                        class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-emerald-700/10 whitespace-nowrap">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Kategori
                </button>
            </div>

            <!-- Responsive Table (Categories) -->
            <div class="admin-card admin-table-wrap bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4">Nama Kategori</th>
                                <th class="px-6 py-4 text-center">Jumlah Produk Terkait</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            <template x-for="cat in categories" :key="cat.id">
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4 text-slate-800" x-text="cat.name"></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase border bg-slate-50 text-slate-600 border-slate-200" x-text="cat.products_count + ' produk'"></span>
                                    </td>
                                    <td class="px-6 py-4 text-center flex items-center justify-center gap-1">
                                        <button type="button" 
                                                @click="editCategory(cat)"
                                                class="p-2 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-xl transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button type="button" 
                                                @click="deleteCategory(cat.id)"
                                                class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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

        <!-- 4. WHOLESALE ORDER TAB CONTENT -->
        <div x-show="activeTab === 'wholesale'" class="flex flex-col gap-6" style="display: none;">
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <!-- <div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-lg leading-tight">Pembuatan Nota Penjualan Partai (Grosir)</h3>
                    <p class="text-sm text-slate-400 dark:text-purple-400 font-medium mt-1">Buat nota dengan penentuan produk, kuantitas, harga jual, dan modal secara manual/kustom</p>
                </div> -->
            </div>

            <!-- Wholesale Form Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT PANEL: Customer Info & Items List (8 cols) -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    
                    <!-- Customer Information Card -->
                    <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                        <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Informasi Penerima / Pelanggan</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Pelanggan (Wajib)</label>
                                <input type="text" 
                                       x-model="wholesaleForm.customer_name" 
                                       placeholder="Contoh: Ibu Fatimah / Toko Kurma Berkah" 
                                       class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nomor WhatsApp Pelanggan (Opsional)</label>
                                <input type="text" 
                                       x-model="wholesaleForm.customer_phone" 
                                       placeholder="Contoh: 081234567890" 
                                       class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Card -->
                    <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Rincian Barang Belanjaan</span>
                            <button type="button" 
                                    @click="addWholesaleItem()" 
                                    class="px-3.5 py-2 text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl transition duration-150 flex items-center gap-1.5 shadow-sm shadow-emerald-700/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Baris Baru
                            </button>
                        </div>

                        <!-- Desktop/Mobile Layout Styles Override -->
                        <style>
                            .wholesale-desktop-header {
                                display: none;
                            }
                            .wholesale-mobile-label {
                                display: block;
                            }
                            @media (min-width: 768px) {
                                .wholesale-desktop-header {
                                    display: grid !important;
                                    grid-template-columns: 35px minmax(120px, 1fr) 70px 115px 115px 45px !important;
                                    gap: 12px !important;
                                    padding: 8px 16px !important;
                                    border-bottom: 1px solid #e2e8f0 !important;
                                    font-size: 10px !important;
                                    font-weight: 800 !important;
                                    color: #94a3b8 !important;
                                    text-transform: uppercase !important;
                                    letter-spacing: 0.05em !important;
                                    margin-bottom: 8px !important;
                                }
                                .wholesale-mobile-label {
                                    display: none !important;
                                }
                                .wholesale-item-row {
                                    display: grid !important;
                                    grid-template-columns: 35px minmax(120px, 1fr) 70px 115px 115px 45px !important;
                                    align-items: center !important;
                                    gap: 12px !important;
                                    padding-top: 8px !important;
                                    padding-bottom: 8px !important;
                                    padding-left: 16px !important;
                                    padding-right: 16px !important;
                                }
                                .wholesale-col-no { grid-column: auto !important; order: 1 !important; text-align: center; padding-bottom: 0 !important; }
                                .wholesale-col-name { grid-column: auto !important; order: 2 !important; min-width: 0 !important; width: 100% !important; }
                                .wholesale-col-qty { grid-column: auto !important; order: 3 !important; min-width: 0 !important; width: 100% !important; }
                                .wholesale-col-sell { grid-column: auto !important; order: 4 !important; min-width: 0 !important; width: 100% !important; }
                                .wholesale-col-cost { grid-column: auto !important; order: 5 !important; min-width: 0 !important; width: 100% !important; }
                                .wholesale-col-del { grid-column: auto !important; order: 6 !important; text-align: center; padding-bottom: 0 !important; }
                            }
                        </style>

                        <!-- Desktop Header (visible only on md and above) -->
                        <div class="wholesale-desktop-header">
                            <div class="wholesale-col-no">No</div>
                            <div class="wholesale-col-name">Nama Produk / Custom Nama</div>
                            <div class="wholesale-col-qty" style="text-align: center;">Qty</div>
                            <div class="wholesale-col-sell" style="text-align: right;">Harga Jual (Rp)</div>
                            <div class="wholesale-col-cost" style="text-align: right;">Harga Modal (Rp)</div>
                            <div class="wholesale-col-del">Aksi</div>
                        </div>

                        <!-- Dynamic Items List -->
                        <div class="flex flex-col gap-3">
                            <template x-for="(item, idx) in wholesaleForm.items" :key="idx">
                                <div class="wholesale-item-row grid grid-cols-12 gap-3 items-end p-4 rounded-2xl bg-slate-50/50 border border-slate-100/50 hover:bg-slate-50 hover:border-slate-200 transition duration-150">
                                    
                                    <!-- No -->
                                    <div class="wholesale-col-no col-span-1 order-1 text-center font-bold text-slate-400 text-xs pb-3.5" x-text="idx + 1"></div>
                                    
                                    <!-- Nama Produk (Autocomplete & Custom input) -->
                                    <div class="wholesale-col-name col-span-9 order-2 relative flex flex-col gap-1">
                                        <label class="wholesale-mobile-label text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Produk / Custom Nama</label>
                                        <input type="text" 
                                               x-model="item.name" 
                                               @input="item.showSuggestions = true; item.activeSuggestionIndex = -1"
                                               @focus="item.showSuggestions = true"
                                               @keydown.arrow-down.prevent="navigateSuggestions(idx, 1)"
                                               @keydown.arrow-up.prevent="navigateSuggestions(idx, -1)"
                                               @keydown.enter.prevent="selectActiveSuggestion(idx)"
                                               placeholder="Ketik nama produk..." 
                                               class="product-search-input w-full border-slate-200 rounded-lg text-xs font-bold py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500">
                                        
                                        <!-- Suggestions Dropdown -->
                                        <div x-show="item.showSuggestions && getWholesaleSuggestions(item.name).length > 0" 
                                             @click.away="item.showSuggestions = false"
                                             class="absolute z-20 top-full left-0 right-0 bg-white border border-slate-200 shadow-xl rounded-xl mt-1 overflow-hidden max-h-48 overflow-y-auto">
                                            <template x-for="p in getWholesaleSuggestions(item.name)" :key="p.id">
                                                <button type="button" 
                                                        @click="selectWholesaleProduct(idx, p)"
                                                        :class="item.activeSuggestionIndex === getWholesaleSuggestions(item.name).indexOf(p) ? 'bg-emerald-100 text-emerald-800' : 'hover:bg-emerald-50 text-slate-700'"
                                                        class="w-full text-left px-4 py-2.5 text-xs hover:text-emerald-800 border-b border-slate-100 font-bold transition flex justify-between items-center">
                                                    <span x-text="p.name"></span>
                                                    <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-black uppercase" x-text="'Stok: ' + formatStock(p.stock, p.price_unit)"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Action Hapus (Delete Button) -->
                                    <div class="wholesale-col-del col-span-2 order-3 flex justify-center pb-3">
                                        <button type="button" 
                                                @click="removeWholesaleItem(idx)" 
                                                class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Qty -->
                                    <div class="wholesale-col-qty col-span-4 order-4 flex flex-col gap-1">
                                        <label class="wholesale-mobile-label text-[10px] font-bold text-slate-400 uppercase tracking-wider">Qty</label>
                                        <input type="number" 
                                               step="any"
                                               x-model="item.qty" 
                                               placeholder="1" 
                                               class="w-full border-slate-200 rounded-lg text-xs font-bold py-2 px-3 text-center focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>

                                    <!-- Harga Jual Satuan -->
                                    <div class="wholesale-col-sell col-span-4 order-5 flex flex-col gap-1">
                                        <label class="wholesale-mobile-label text-[10px] font-bold text-slate-400 uppercase tracking-wider">Harga Jual (Rp)</label>
                                        <input type="number" 
                                               x-model="item.selling_price" 
                                               placeholder="Harga jual..." 
                                               class="w-full border-slate-200 rounded-lg text-xs font-bold py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 text-right">
                                    </div>

                                    <!-- Harga Modal Satuan (Untuk Profit) -->
                                    <div class="wholesale-col-cost col-span-4 order-6 flex flex-col gap-1">
                                        <label class="wholesale-mobile-label text-[10px] font-bold text-slate-400 uppercase tracking-wider">Harga Modal (Rp)</label>
                                        <input type="number" 
                                               x-model="item.cost_price" 
                                               @keydown.tab="if (idx === wholesaleForm.items.length - 1) { $event.preventDefault(); addWholesaleItem(); }"
                                               placeholder="Modal..." 
                                               class="w-full border-slate-200 rounded-lg text-xs font-bold py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 text-right">
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANEL: Calculations & Payment Summary (4 cols) -->
                <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-lg flex flex-col gap-5 sticky top-6">
                    <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Ringkasan Pembayaran & Aksi</span>
                    
                    <div class="flex flex-col gap-3.5 text-sm font-semibold text-slate-600">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center">
                            <span>Subtotal Barang</span>
                            <span class="text-slate-800 font-extrabold" x-text="formatRupiah(calculateWholesaleSubtotal())"></span>
                        </div>

                        <!-- Diskon manual -->
                        <div class="flex flex-col gap-1 bg-rose-50/40 border border-rose-100/50 p-3 rounded-xl mt-1">
                            <span class="text-xs font-bold text-rose-700 uppercase">Potongan Diskon (Rp)</span>
                            <input type="number" 
                                   x-model="wholesaleForm.discount" 
                                   placeholder="Nominal diskon..." 
                                   class="w-full text-right py-1.5 px-3 border-slate-200 rounded-lg text-xs font-extrabold text-slate-800 focus:border-rose-500 focus:ring-rose-500 shadow-inner">
                        </div>

                        <!-- Ongkir manual -->
                        <div class="flex flex-col gap-1 bg-slate-50 border border-slate-200/60 p-3 rounded-xl">
                            <span class="text-xs font-bold text-slate-600 uppercase">Biaya Kirim / Ongkir (Rp)</span>
                            <input type="number" 
                                   x-model="wholesaleForm.shipping_cost" 
                                   placeholder="Biaya kirim..." 
                                   class="w-full text-right py-1.5 px-3 border-slate-200 rounded-lg text-xs font-extrabold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                        </div>

                        <!-- Metode Bayar -->
                        <div class="flex flex-col gap-1 mt-1">
                            <span class="text-xs font-bold text-slate-500 uppercase">Metode Pembayaran</span>
                            <select x-model="wholesaleForm.payment_method" class="w-full border-slate-200 rounded-xl text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 shadow-inner bg-slate-50 text-slate-700">
                                <option value="Tunai">💵 Uang Tunai (Cash)</option>
                                <option value="Transfer Bank">🏢 Transfer Rekening Bank</option>
                                <option value="Piutang / Tempo">⏱️ Piutang / Tempo</option>
                            </select>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-extrabold items-center">
                            <span class="text-slate-800">Total Akhir Nota</span>
                            <span class="text-emerald-700 text-lg" x-text="formatRupiah(calculateWholesaleGrandTotal())"></span>
                        </div>

                        <!-- Uang Diterima / DP -->
                        <div class="flex flex-col gap-1 bg-teal-50/40 border border-teal-100/50 p-3 rounded-xl mt-1">
                            <span class="text-xs font-bold text-teal-700 uppercase">Uang Diterima / DP (Rp)</span>
                            <input type="number" 
                                   x-model="wholesaleForm.paymentReceived" 
                                   placeholder="Nominal uang diterima..." 
                                   class="w-full text-right py-1.5 px-3 border-slate-200 rounded-lg text-xs font-extrabold text-slate-800 focus:border-teal-500 focus:ring-teal-500 shadow-inner">
                        </div>

                        <!-- Sisa Tagihan / Kembalian -->
                        <div class="flex justify-between items-center border-t border-dashed border-slate-100 pt-3">
                            <span x-text="calculateWholesaleRemaining() >= 0 ? 'Sisa Tagihan' : 'Kembalian'"></span>
                            <span :class="calculateWholesaleRemaining() >= 0 ? 'text-rose-600 font-extrabold' : 'text-emerald-700 font-extrabold'" 
                                  x-text="formatRupiah(Math.abs(calculateWholesaleRemaining()))"></span>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-2.5 pt-2 border-t border-slate-100">
                        <!-- Share WA -->
                        <button type="button" 
                                @click="shareWholesaleToWa()"
                                class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-bold text-xs tracking-wide uppercase transition duration-150 flex items-center justify-center gap-1.5 shadow-md shadow-teal-600/10">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.449 5.412 1.451 5.928 0 10.751-4.82 10.754-10.748.002-2.873-1.116-5.573-3.149-7.608C17.628 4.214 14.93 3.093 12.005 3.093c-5.93 0-10.756 4.821-10.76 10.75-.001 1.993.52 3.94 1.508 5.662l-.99 3.61 3.733-.979zm11.238-7.73c-.302-.151-1.787-.881-2.056-.979-.269-.099-.465-.148-.659.15-.195.299-.754.979-.924 1.178-.17.199-.341.224-.643.075-.3-.15-1.268-.467-2.417-1.492-.893-.797-1.496-1.783-1.672-2.083-.176-.3-.019-.461.13-.61.135-.133.302-.35.453-.524.151-.174.2-.299.3-.498.101-.2.05-.375-.025-.524-.075-.15-.659-1.587-.902-2.172-.237-.57-.497-.493-.659-.501-.17-.008-.365-.01-.56-.01-.196 0-.517.073-.787.37-.27.299-1.031 1.008-1.031 2.459 0 1.452 1.054 2.853 1.202 3.053.148.2 2.074 3.167 5.024 4.444.702.304 1.25.485 1.678.621.705.224 1.347.193 1.854.117.565-.084 1.787-.73 2.039-1.436.252-.706.252-1.312.176-1.436-.076-.124-.271-.199-.573-.35z"/>
                            </svg>
                            Bagikan Tagihan WA
                        </button>

                        <!-- Simpan & Cetak -->
                        <button type="button" 
                                @click="saveWholesale()"
                                class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 flex items-center justify-center gap-1.5 shadow-md shadow-emerald-700/10 active:scale-98">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15M9 12l3 3m0 0l3-3m-3 3V2.25" />
                            </svg>
                            Simpan & Cetak Nota
                        </button>
                    </div>

                </div>

            </div>

            <!-- 2. HISTORY PANEL: Recent Wholesale Transactions -->
            <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4 mt-6">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Riwayat Pembuatan Nota Partai Terakhir</span>
                </div>

                <div class="overflow-x-auto w-full max-w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-5 py-4">Tanggal & Waktu</th>
                                <th class="px-5 py-4">Kode Nota</th>
                                <th class="px-5 py-4">Pelanggan</th>
                                <th class="px-5 py-4">Ringkasan Item</th>
                                <th class="px-5 py-4 text-right">Total</th>
                                <th class="px-5 py-4 text-center">Status</th>
                                <th class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                            @forelse($wholesaleTransactions ?? [] as $wTrx)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                        {{ $wTrx->created_at->translatedFormat('d M Y - H:i') }}
                                    </td>
                                    <td class="px-5 py-4 text-emerald-700 font-mono text-xs">
                                        {{ $wTrx->transaction_code }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <div class="text-slate-800 font-bold">{{ $wTrx->customer_name }}</div>
                                        @if($wTrx->customer_phone)
                                            <div class="text-slate-400 text-[10px]">WA: {{ $wTrx->customer_phone }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 text-xs">
                                        <div class="flex flex-wrap gap-1 max-w-[280px]">
                                            @foreach(explode(', ', $wTrx->items_summary) as $itemStr)
                                                @if(trim($itemStr))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50/60 text-emerald-800 border border-emerald-100/50">
                                                        {{ trim($itemStr) }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right text-emerald-700 font-extrabold whitespace-nowrap">
                                        Rp {{ number_format($wTrx->total_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        @if(($wTrx->payment_status ?? 'paid') === 'paid')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">Lunas</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200">Tempo</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Print Screen -->
                                            <a href="{{ route('admin.wholesale-transactions.print', $wTrx) }}" 
                                               target="_blank"
                                               class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition duration-150 text-xs text-center"
                                               title="Cetak Nota (A4)" style="text-decoration: none;">
                                                🖨️
                                            </a>
                                            <!-- Download PDF -->
                                            <a href="{{ route('admin.wholesale-transactions.print', [$wTrx, 'format' => 'pdf']) }}" 
                                               target="_blank"
                                               class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition duration-150 text-xs text-center"
                                               title="Unduh PDF" style="text-decoration: none;">
                                                📥
                                            </a>
                                            <!-- Delete Transaction -->
                                            <button type="button"
                                                    @click="deleteWholesaleTransaction({{ $wTrx->id }})"
                                                    class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 transition duration-150 text-xs text-center"
                                                    title="Hapus Transaksi">
                                                🗑️
                                            </button>
                                            <!-- Quick Mark As Paid -->
                                            @if(($wTrx->payment_status ?? 'paid') !== 'paid')
                                                <button type="button"
                                                        @click="markWholesalePaid({{ $wTrx->id }})"
                                                        class="px-2.5 py-1.5 text-[10px] font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg transition duration-150 shadow-sm"
                                                        title="Tandai Lunas">
                                                    Tandai Lunas
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada riwayat pembuatan nota partai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- 4.5 RECEIVABLES (KELOLA PIUTANG) TAB CONTENT -->
        <div x-show="activeTab === 'receivables'" class="flex flex-col gap-6" style="display: none;">
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

            <div class="receivables-flex-container">
                
                <!-- LEFT PANEL: Customer List -->
                <div :class="selectedCustomer ? 'receivables-left-panel split-view' : 'receivables-left-panel full-view'" class="flex flex-col gap-6 shrink-0">
                    <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Daftar Rekap Piutang Pelanggan</span>
                        </div>
                        
                        <div class="overflow-x-auto w-full">
                            <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                                <thead class="bg-slate-50 font-bold text-slate-500 uppercase tracking-wider text-xs">
                                    <tr>
                                        <th class="px-4 py-3">Pelanggan</th>
                                        <th class="px-4 py-3 text-right">Total Piutang</th>
                                        <th class="px-4 py-3 text-right">Sudah Dibayar</th>
                                        <th class="px-4 py-3 text-right text-rose-600">Sisa Utang</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                                    <template x-for="c in receivables" :key="c.customer_name">
                                        <tr :class="selectedCustomer && selectedCustomer.customer_name === c.customer_name ? 'bg-slate-50' : ''" class="hover:bg-slate-50/50 transition duration-150">
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
                                                        class="px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition duration-150 border border-slate-200">
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="receivables.length === 0">
                                        <tr>
                                            <td colspan="5" class="px-4 py-12 text-center text-slate-400 font-semibold">
                                                Tidak ada piutang aktif saat ini. Semua lunas! 🎉
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
                                ✕ Tutup Detail
                            </button>
                        </div>

                        <!-- Customer Summary Stats -->
                        <div class="flex flex-row gap-3 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 justify-between">
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
                                <div class="bg-slate-50/30 border border-slate-100 hover:border-slate-200 p-5 rounded-2xl flex flex-col gap-4 transition">
                                    <!-- Invoice Main Info -->
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-xs font-black text-emerald-800" x-text="t.transaction_code"></span>
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
                                    <div class="bg-white p-4 rounded-xl border border-slate-100/80 flex flex-col gap-2">
                                        <span class="text-[10px] font-bold text-slate-400 tracking-wide block">Log Pembayaran Cicilan</span>
                                        
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
                                                class="px-4 py-2 text-xs font-extrabold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl transition shadow-md shadow-emerald-700/10">
                                            💵 Bayar Cicilan
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- INSTALLMENT MODAL DIALOG -->
        <div x-show="showInstallmentModal" 
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
             style="display: none;">
            <div class="modal-card bg-white p-6 rounded-3xl border border-slate-100 shadow-2xl w-full max-w-md max-h-screen overflow-y-auto"
                 @click.away="showInstallmentModal = false">
                
                <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-base font-extrabold text-slate-800">Catat Cicilan Pembayaran</h3>
                    <button type="button" @click="showInstallmentModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <div class="flex flex-col gap-4">
                    <!-- Invoice Details -->
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex flex-col gap-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Invoice:</span>
                            <strong class="text-emerald-800" x-text="installmentForm.transaction_code"></strong>
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
                            <option value="Tunai">💵 Uang Tunai (Cash)</option>
                            <option value="Transfer Bank">🏢 Transfer Rekening Bank</option>
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
                                @click="submitInstallment()"
                                class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 shadow-md shadow-emerald-700/10">
                            Simpan Pembayaran
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- 5. SHOP SETTINGS TAB CONTENT -->
        <div x-show="activeTab === 'settings'" class="flex flex-col gap-6" style="display: none;">
            
            <!-- Sync Banner dari API Profile Web -->
            <div class="bg-gradient-to-r from-emerald-800 to-teal-900 p-6 rounded-3xl text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl">
                        🔄
                    </div>
                    <div>
                        <h4 class="font-extrabold text-base tracking-tight text-white">Sinkronkan Profil Toko dari Web API</h4>
                        <p class="text-xs text-emerald-100/90 mt-0.5">Ambil & perbarui otomatis Nama Toko, Logo, Alamat, WA, Jam Operasional, & Medsos dari <span class="font-mono text-amber-200">pusatkurmacianjur.my.id/api/profile</span></p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.sync_profile') }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin memperbarui data profil toko dari API web profil?')" class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-emerald-950 font-black rounded-xl text-xs uppercase tracking-wider transition-all duration-200 shadow-md flex items-center gap-2 whitespace-nowrap">
                        <span>⚡</span> Sync Data Profil
                    </button>
                </form>
            </div>
            <form action="{{ route('admin.settings.update') }}" method="POST" class="w-full">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT COLUMN: Configurations Form (8 cols) -->
                    <div class="lg:col-span-8 flex flex-col gap-6">
                        
                        <!-- Header Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Pengaturan Header / Navigasi</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Toko (Header)</label>
                                    <input type="text" 
                                           name="settings[shop_name]" 
                                           value="{{ $settings['shop_name'] ?? '' }}" 
                                           placeholder="Contoh: Pusat Kurma" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tagline Toko</label>
                                    <input type="text" 
                                           name="settings[shop_tagline]" 
                                           value="{{ $settings['shop_tagline'] ?? '' }}" 
                                           placeholder="Contoh: Cianjur ✦ Premium Dates" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nomor WhatsApp Link (Format: 628xxx)</label>
                                    <input type="text" 
                                           name="settings[shop_whatsapp]" 
                                           value="{{ $settings['shop_whatsapp'] ?? '' }}" 
                                           placeholder="Contoh: 6281234567890 (Tanpa tanda + atau spasi)" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                    <span class="text-xs text-slate-400 font-medium mt-1 block">Digunakan untuk link tombol chat WhatsApp di header dan footer.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Pengaturan Hero Section (Halaman Depan)</span>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Badge Hero (Teks Kecil di Atas)</label>
                                    <input type="text" 
                                           name="settings[shop_hero_badge]" 
                                           value="{{ $settings['shop_hero_badge'] ?? '' }}" 
                                           placeholder="Contoh: Kurma Premium Berkualitas Tinggi" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Judul Hero (Mendukung tag HTML seperti &lt;span class="highlight"&gt;)</label>
                                    <input type="text" 
                                           name="settings[shop_hero_title]" 
                                           value="{{ $settings['shop_hero_title'] ?? '' }}" 
                                           placeholder="Contoh: Temukan <span class='highlight'>Kurma Terbaik</span>" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Deskripsi Hero</label>
                                    <textarea name="settings[shop_hero_desc]" 
                                              rows="3" 
                                              placeholder="Masukkan teks paragraf deskripsi di bawah judul..." 
                                              class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">{{ $settings['shop_hero_desc'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Produk Unggulan Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4"
                             x-data="{ searchQuery: '' }">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Produk Unggulan (Featured Products)</span>
                            <div class="flex flex-col gap-3">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pilih produk yang ingin ditampilkan di bagian produk unggulan:</label>
                                
                                <!-- Search Input -->
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input type="text" 
                                           x-model="searchQuery" 
                                           placeholder="Cari nama produk atau SKU..." 
                                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 text-xs font-semibold shadow-inner">
                                </div>

                                @php
                                    $featuredIds = json_decode($settings['featured_product_ids'] ?? '[]', true) ?: [];
                                @endphp
                                <input type="hidden" name="settings[featured_product_ids]" value="[]">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50">
                                    @foreach($products as $prod)
                                        <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer transition"
                                               x-show="searchQuery === '' || 
                                                       '{{ strtolower(addslashes($prod->name)) }}'.includes(searchQuery.toLowerCase()) || 
                                                       '{{ strtolower(addslashes($prod->sku ?? '')) }}'.includes(searchQuery.toLowerCase())"
                                               style="display: flex;">
                                            <input type="checkbox" 
                                                   name="settings[featured_product_ids][]" 
                                                   value="{{ $prod->id }}"
                                                   {{ in_array($prod->id, $featuredIds) ? 'checked' : '' }}
                                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-slate-700 leading-tight">{{ $prod->name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $prod->sku }} — Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <span class="text-xs text-slate-400 font-medium block">Produk yang dicentang akan muncul di bagian khusus produk unggulan pada halaman utama shop.</span>
                            </div>
                        </div>

                        <!-- Produk Gratis Ongkir Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4"
                             x-data="{ searchFreeShippingQuery: '' }">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">🚚 Produk Gratis Ongkir (Free Shipping Products)</span>
                            <div class="flex flex-col gap-3">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pilih produk yang mendapatkan fasilitas bebas ongkos kirim:</label>
                                
                                <!-- Search Input -->
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input type="text" 
                                           x-model="searchFreeShippingQuery" 
                                           placeholder="Cari nama produk atau SKU..." 
                                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 text-xs font-semibold shadow-inner">
                                </div>

                                @php
                                    $freeShippingIds = json_decode($settings['free_shipping_product_ids'] ?? '[]', true) ?: [];
                                @endphp
                                <input type="hidden" name="settings[free_shipping_product_ids]" value="[]">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50">
                                    @foreach($products as $prod)
                                        <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer transition"
                                               x-show="searchFreeShippingQuery === '' || 
                                                       '{{ strtolower(addslashes($prod->name)) }}'.includes(searchFreeShippingQuery.toLowerCase()) || 
                                                       '{{ strtolower(addslashes($prod->sku ?? '')) }}'.includes(searchFreeShippingQuery.toLowerCase())"
                                               style="display: flex;">
                                            <input type="checkbox" 
                                                   name="settings[free_shipping_product_ids][]" 
                                                   value="{{ $prod->id }}"
                                                   {{ in_array($prod->id, $freeShippingIds) ? 'checked' : '' }}
                                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-slate-700 leading-tight">{{ $prod->name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $prod->sku }} — Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <span class="text-xs text-slate-400 font-medium block">Produk yang dicentang akan ditandai dengan badge "Gratis Ongkir" dan dibebaskan dari ongkos kirim saat checkout.</span>
                            </div>
                        </div>

                        <!-- Visibilitas Produk Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4"
                             x-data="{ searchVisibilityQuery: '' }">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Visibilitas Produk di Online Shop</span>
                            <div class="flex flex-col gap-3">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pilih produk yang ingin ditampilkan dan dapat dipesan di online shop:</label>
                                
                                <!-- Search Input -->
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input type="text" 
                                           x-model="searchVisibilityQuery" 
                                           placeholder="Cari nama produk atau SKU..." 
                                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 text-xs font-semibold shadow-inner">
                                </div>

                                <input type="hidden" name="settings[active_product_ids_submitted]" value="1">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50">
                                    @foreach($products as $prod)
                                        <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer transition"
                                               x-show="searchVisibilityQuery === '' || 
                                                       '{{ strtolower(addslashes($prod->name)) }}'.includes(searchVisibilityQuery.toLowerCase()) || 
                                                       '{{ strtolower(addslashes($prod->sku ?? '')) }}'.includes(searchVisibilityQuery.toLowerCase())"
                                               style="display: flex;">
                                            <input type="checkbox" 
                                                   name="settings[active_product_ids][]" 
                                                   value="{{ $prod->id }}"
                                                   {{ $prod->is_active_in_shop ? 'checked' : '' }}
                                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-slate-700 leading-tight">{{ $prod->name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $prod->sku }} — Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <span class="text-xs text-slate-400 font-medium block">Produk yang dicentang akan muncul dan dapat dipesan di toko online. Produk yang tidak dicentang akan disembunyikan.</span>
                            </div>
                        </div>

                        <!-- Footer Config Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Pengaturan Footer & Kontak</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Deskripsi Toko (Footer)</label>
                                    <textarea name="settings[shop_description]" 
                                              rows="3" 
                                              placeholder="Masukkan deskripsi singkat tentang toko..." 
                                              class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">{{ $settings['shop_description'] ?? '' }}</textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Alamat Lengkap Toko</label>
                                    <textarea name="settings[shop_address]" 
                                              rows="2" 
                                              placeholder="Masukkan alamat toko..." 
                                              class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">{{ $settings['shop_address'] ?? '' }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Telepon Tampilan (Teks)</label>
                                    <input type="text" 
                                           name="settings[shop_phone]" 
                                           value="{{ $settings['shop_phone'] ?? '' }}" 
                                           placeholder="Contoh: +62 812-3456-7890" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Jam Operasional</label>
                                    <input type="text" 
                                           name="settings[shop_operational_hours]" 
                                           value="{{ $settings['shop_operational_hours'] ?? '' }}" 
                                           placeholder="Contoh: Senin–Sabtu: 08.00–17.00" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Teks Hak Cipta (Copyright)</label>
                                    <input type="text" 
                                           name="settings[shop_copyright]" 
                                           value="{{ $settings['shop_copyright'] ?? '' }}" 
                                           placeholder="Contoh: Pusat Kurma Cianjur. Semua hak dilindungi." 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Social Media & Save Button (4 cols) -->
                    <div class="lg:col-span-4 flex flex-col gap-6">
                        
                        <!-- Social Media Links Card -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Media Sosial (Tautan)</span>
                            <div class="flex flex-col gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tautan Instagram</label>
                                    <input type="text" 
                                           name="settings[shop_social_instagram]" 
                                           value="{{ $settings['shop_social_instagram'] ?? '' }}" 
                                           placeholder="Contoh: https://instagram.com/username" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tautan Facebook</label>
                                    <input type="text" 
                                           name="settings[shop_social_facebook]" 
                                           value="{{ $settings['shop_social_facebook'] ?? '' }}" 
                                           placeholder="Contoh: https://facebook.com/username" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tautan TikTok</label>
                                    <input type="text" 
                                           name="settings[shop_social_tiktok]" 
                                           value="{{ $settings['shop_social_tiktok'] ?? '' }}" 
                                           placeholder="Contoh: https://tiktok.com/@username" 
                                           class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 font-semibold shadow-inner text-sm py-2.5 px-4">
                                </div>
                            </div>
                        </div>

                        <!-- Action Panel -->
                        <div class="admin-card bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider block border-b border-slate-100 pb-2">Aksi Pengaturan</span>
                            <p class="text-xs text-slate-400 font-medium">Pastikan semua data yang Anda masukkan sudah benar sebelum disimpan. Perubahan akan langsung berdampak pada halaman depan toko (shop).</p>
                            <button type="submit" 
                                    class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 flex items-center justify-center gap-1.5 shadow-md shadow-emerald-700/10 active:scale-98">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>

                    </div>

                </div>
            </form>
        </div>
    </div>

        <!-- CATEGORY MODAL FORM -->
        <div x-show="showCategoryModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;">
            
            <div class="modal-card bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col gap-4" @click.away="resetCategoryForm()">
                <h3 class="font-extrabold text-slate-800 text-lg" x-text="isEditingCategory ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
                <div class="flex flex-col gap-3 text-sm font-semibold text-slate-700">
                    <div>
                        <label class="block mb-1">Nama Kategori</label>
                        <input type="text" x-model="newCategory.name" placeholder="Contoh: Madu & Herbal" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <button type="button" @click="resetCategoryForm()" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="button" @click="saveCategory()" class="py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold">Simpan</button>
                </div>
            </div>
        </div>

        <!-- PRODUCT MODAL FORM -->
        <div x-show="showProductModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;">
            
            <div class="modal-card bg-white rounded-3xl p-6 w-full max-w-md border border-slate-100 shadow-2xl flex flex-col gap-4 max-h-[90vh] overflow-y-auto" @click.away="if (!confirmModal.show) resetProductForm()">
                <h3 class="font-extrabold text-slate-800 text-lg" x-text="isEditing ? 'Edit Produk' : 'Tambah Produk Baru'"></h3>
                <div class="flex flex-col gap-3 text-sm font-semibold text-slate-700">
                    <div>
                        <label class="block mb-1">SKU Produk <span class="text-xs text-slate-400 font-normal">(Opsional - Otomatis dibuat jika kosong)</span></label>
                        <input type="text" x-model="newProduct.sku" placeholder="Contoh: PK-AJW-005 (Kosongkan untuk otomatis)" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1">Nama Produk</label>
                        <input type="text" x-model="newProduct.name" placeholder="Contoh: Kurma Ajwa Madinah" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1">Kategori</label>
                            <select x-model="newProduct.category" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.name" x-text="cat.name" :selected="newProduct.category === cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1">Satuan Harga</label>
                            <select x-model="newProduct.price_unit" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="gram">Per Gram</option>
                                <option value="pcs">Per Pcs</option>
                                <option value="kg">Per Kg</option>
                                <option value="pack">Per Pack</option>
                                <option value="dus">Per Dus</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1">Berat Produk (gram) <span class="text-xs text-slate-400 font-normal">— untuk kalkulasi ongkir</span></label>
                            <input type="number" x-model="newProduct.weight_grams" min="1" placeholder="Contoh: 500"
                                   class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block mb-1">Harga Jual (Rp)</label>
                            <input type="number" x-model="newProduct.selling_price" placeholder="Contoh: 150000"
                                   class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500"
                                   @input="if(costPriceMode==='pct') { /* reactive via effectiveCostPrice */ }">
                        </div>
                    </div>
                    {{-- Harga Modal: mode toggle --}}
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="font-semibold text-slate-700">Harga Modal (Rp)</label>
                            {{-- Toggle mode --}}
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                                <button type="button"
                                        @click="costPriceMode = 'pct'"
                                        :class="costPriceMode === 'pct' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition-all duration-150">
                                    % Persen
                                </button>
                                <button type="button"
                                        @click="costPriceMode = 'manual'"
                                        :class="costPriceMode === 'manual' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition-all duration-150">
                                    Manual
                                </button>
                            </div>
                        </div>

                        {{-- Mode: Persentase --}}
                        <div x-show="costPriceMode === 'pct'" class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <input type="range" min="1" max="99" step="1"
                                       x-model="costPricePct"
                                       class="flex-1 h-2 accent-emerald-500 cursor-pointer">
                                <div class="flex items-center gap-1">
                                    <input type="number" min="1" max="99" step="1"
                                           x-model="costPricePct"
                                           class="w-16 text-center text-sm font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2">
                                    <span class="text-sm font-bold text-slate-500">%</span>
                                </div>
                            </div>
                            <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 flex items-center justify-between">
                                <span class="text-xs font-semibold text-emerald-700">
                                    Harga modal =
                                    <span x-text="costPricePct"></span>% dari harga jual
                                </span>
                                <span class="text-sm font-extrabold text-emerald-700"
                                      x-text="'Rp ' + effectiveCostPrice.toLocaleString('id-ID')">
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 font-semibold -mt-1">Harga jual belum diisi? Isi harga jual dulu agar kalkulasi akurat.</p>
                        </div>

                        {{-- Mode: Manual --}}
                        <div x-show="costPriceMode === 'manual'" class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <input type="number" x-model="newProduct.cost_price"
                                       placeholder="Masukkan harga modal..."
                                       class="flex-1 border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                                <template x-if="newProduct.is_bundle">
                                    <button type="button" 
                                            @click="newProduct.cost_price = calculateBundleCostPrice()"
                                            class="px-3 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold rounded-xl border border-purple-200 transition duration-150 shrink-0">
                                        Gunakan Estimasi
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Hint Bundling --}}
                        <div x-show="newProduct.is_bundle" class="bg-purple-50 border border-purple-100 rounded-xl px-4 py-2.5 flex flex-col gap-1 text-xs text-purple-700 mt-1">
                            <div class="flex justify-between font-bold">
                                <span>Estimasi Modal Komponen:</span>
                                <span class="font-extrabold" x-text="formatRupiah(calculateBundleCostPrice())"></span>
                            </div>
                            <span class="text-[10px] text-purple-500 font-medium">Isi "0" atau kosongkan Harga Modal di atas agar sistem otomatis menggunakan estimasi modal komponen secara dinamis (real-time). Isi nominal tertentu hanya jika ada biaya tambahan (misal: kotak kemasan).</span>
                        </div>
                    </div>

                    {{-- Opsi Harga Grosir / Bertingkat --}}
                    <div class="flex items-center gap-2 mt-1 bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 select-none">
                        <input type="checkbox" id="has_price_tiers" x-model="hasPriceTiers" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500 h-4 w-4 cursor-pointer">
                        <label for="has_price_tiers" class="text-xs font-bold text-slate-700 cursor-pointer">Aktifkan Harga Grosir / Bertingkat</label>
                    </div>

                    {{-- Form Skema Harga Grosir --}}
                    <div x-show="hasPriceTiers" class="flex flex-col gap-3 p-4 bg-emerald-50/30 rounded-2xl border border-emerald-100/50 mt-1" style="display: none;">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">Skema Harga Grosir</span>
                            <button type="button" @click="addPriceTier()" class="px-3 py-1.5 text-[11px] font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl transition duration-150 flex items-center gap-1 shadow-sm shadow-emerald-700/10">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Rentang
                            </button>
                        </div>

                        <!-- Empty State -->
                        <template x-if="newProduct.price_tiers.length === 0">
                            <p class="text-[11px] text-slate-400 font-semibold italic text-center py-2">Belum ada skema harga. Silakan klik "Tambah Rentang".</p>
                        </template>

                        <!-- Tiers List -->
                        <div class="flex flex-col gap-2">
                            <template x-for="(tier, index) in newProduct.price_tiers" :key="index">
                                <div class="grid grid-cols-12 gap-2 items-center bg-white p-3 rounded-2xl border border-slate-100 shadow-sm">
                                    <!-- Min Qty -->
                                    <div class="col-span-4 flex flex-col gap-0.5">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase">Min Qty</span>
                                        <input type="number" step="any" x-model="tier.min_qty" placeholder="0" class="w-full text-xs font-bold border-slate-200 rounded-lg p-1.5 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <!-- Max Qty -->
                                    <div class="col-span-4 flex flex-col gap-0.5">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase">Max Qty (∞ jika kosong)</span>
                                        <input type="number" step="any" x-model="tier.max_qty" placeholder="Kosongkan" class="w-full text-xs font-bold border-slate-200 rounded-lg p-1.5 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <!-- Price -->
                                    <div class="col-span-3 flex flex-col gap-0.5">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase">Harga Satuan (Rp)</span>
                                        <input type="number" x-model="tier.price" placeholder="Harga" class="w-full text-xs font-bold border-slate-200 rounded-lg p-1.5 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <!-- Action -->
                                    <div class="col-span-1 flex items-center justify-center pt-3.5">
                                        <button type="button" @click="removePriceTier(index)" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- Switch Status Produk (Aktif / Nonaktif) --}}
                    <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 select-none">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Status Produk</span>
                            <p class="text-[11px] text-slate-500">Produk aktif akan tampil di Kasir dan Toko Online</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="is_active_toggle" x-model="newProduct.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-2.5 text-xs font-black" :class="newProduct.is_active ? 'text-emerald-700' : 'text-rose-600'" x-text="newProduct.is_active ? 'Aktif' : 'Nonaktif'"></span>
                        </label>
                    </div>

                    {{-- Checkbox Bundling --}}
                    <div class="flex items-center gap-2 mt-1 bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 select-none">
                        <input type="checkbox" id="is_bundle" x-model="newProduct.is_bundle" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500 h-4 w-4 cursor-pointer">
                        <label for="is_bundle" class="text-xs font-bold text-slate-700 cursor-pointer">Produk Bundling / Paket</label>
                    </div>



                    {{-- Repeater Komponen Bundling --}}
                    <div x-show="newProduct.is_bundle" class="flex flex-col gap-3 p-4 bg-purple-50/30 rounded-2xl border border-purple-100/50 mt-1" style="display: none;">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-black text-purple-800 uppercase tracking-wider">Komponen Bundling</span>
                            <button type="button" @click="addBundleItem()" class="px-3 py-1.5 text-[11px] font-bold bg-purple-700 hover:bg-purple-800 text-white rounded-xl transition duration-150 flex items-center gap-1 shadow-sm shadow-purple-700/10">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Komponen
                            </button>
                        </div>

                        <!-- Empty State -->
                        <template x-if="!newProduct.bundle_items || newProduct.bundle_items.length === 0">
                            <p class="text-[11px] text-slate-400 font-semibold italic text-center py-2">Belum ada komponen. Klik "Tambah Komponen".</p>
                        </template>

                        <!-- Components List -->
                        <div class="flex flex-col gap-2">
                            <template x-for="(item, index) in newProduct.bundle_items" :key="index">
                                <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-slate-100 shadow-sm relative w-full">
                                    <!-- Product selection (with autocomplete) -->
                                    <div class="flex-1 min-w-0 flex flex-col gap-0.5 relative">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Produk Komponen</span>
                                        <input type="text" 
                                               x-model="item.name" 
                                               @input="item.showSuggestions = true"
                                               @focus="item.showSuggestions = true"
                                               placeholder="Ketik/Cari produk..."
                                               class="w-full text-xs font-bold border-slate-200 rounded-lg p-1.5 focus:border-purple-500 focus:ring-purple-500">
                                        
                                        <!-- Suggestions Dropdown -->
                                        <div x-show="item.showSuggestions" 
                                             @click.away="item.showSuggestions = false"
                                             class="absolute z-30 top-full left-0 right-0 bg-white border border-slate-200 shadow-xl rounded-xl mt-1 overflow-hidden max-h-48 overflow-y-auto">
                                            <template x-for="p in getBundleSuggestions(item.name)" :key="p.id">
                                                <button type="button" 
                                                        @click="selectBundleProduct(index, p)"
                                                        class="w-full text-left px-4 py-2 text-xs hover:bg-purple-50 text-slate-700 hover:text-purple-800 border-b border-slate-100 font-bold transition flex justify-between items-center">
                                                    <span x-text="p.name"></span>
                                                    <span class="text-[9px] bg-slate-100 text-slate-600 px-1 py-0.5 rounded font-black" x-text="formatStock(p.stock, p.price_unit)"></span>
                                                </button>
                                            </template>
                                            <template x-if="getBundleSuggestions(item.name).length === 0">
                                                <div class="px-4 py-2 text-xs text-slate-400 italic">Produk tidak ditemukan</div>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Quantity -->
                                    <div class="w-24 flex flex-col gap-0.5">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Qty</span>
                                        <input type="number" step="any" x-model="item.quantity" min="0.01" class="w-full text-xs font-bold border-slate-200 rounded-lg p-1.5 focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    <!-- Delete button -->
                                    <div class="flex-shrink-0 pt-3.5">
                                        <button type="button" @click="removeBundleItem(index)" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition duration-150">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Stock Input --}}
                    <div x-show="!newProduct.is_bundle">
                        <label class="block mb-1" x-text="'Stok Saat Ini (' + newProduct.price_unit + ')'"></label>
                        <input type="number" step="any" x-model="newProduct.stock" placeholder="Stok..." class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div x-show="newProduct.is_bundle" class="bg-purple-50 border border-purple-100 rounded-xl px-4 py-2.5 flex items-center justify-between text-xs font-semibold text-purple-700">
                        <span>Stok dihitung otomatis berdasarkan stok terkecil komponen.</span>
                    </div>
                    <div>
                        <label class="block mb-1">Foto Produk</label>
                        <input type="file" id="product_image" accept="image/*" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1.5 focus:border-emerald-500 focus:ring-emerald-500">
                        <p class="text-[10px] text-slate-400 font-semibold mt-1">Bebas ukuran file. Foto akan otomatis dikompres oleh sistem agar ringan.</p>
                    </div>

                    <!-- Kamera Langsung -->
                    <div class="mt-2 bg-slate-50 border border-slate-200/60 rounded-2xl p-4 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600">Ambil Foto Langsung</span>
                            <button type="button" 
                                    x-on:click="if(showCamera) { stopCamera(); } else { startCamera(); }"
                                    :class="showCamera ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                    class="px-3 py-1.5 text-xs font-bold rounded-xl border transition flex items-center gap-1.5 shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                <span x-text="showCamera ? 'Matikan Kamera' : 'Buka Kamera'"></span>
                            </button>
                        </div>

                        <!-- Live Camera Viewport -->
                        <div x-show="showCamera" class="relative rounded-xl overflow-hidden bg-slate-900 border border-slate-800 shadow-inner flex flex-col items-center justify-center aspect-video">
                            <video id="camera_feed" autoplay playsinline class="w-full h-full object-cover"></video>
                            
                            <!-- Shutter Overlay -->
                            <button type="button" 
                                    x-on:click="capturePhoto()" 
                                    class="absolute bottom-4 left-1/2 -translate-x-1/2 h-14 w-14 rounded-full bg-white border-4 border-slate-300 hover:border-emerald-500 shadow-lg active:scale-95 transition flex items-center justify-center group">
                                <span class="h-8 w-8 rounded-full bg-rose-600 group-hover:bg-emerald-600 transition duration-150"></span>
                            </button>
                        </div>

                        <!-- Captured Preview Section -->
                        <div x-show="capturedPhotoPreview" class="relative rounded-xl overflow-hidden border border-slate-200/80 shadow-sm bg-white p-2">
                            <img :src="capturedPhotoPreview" class="w-full rounded-lg max-h-48 object-cover" alt="Preview Foto Kamera">
                            
                            <!-- Delete Preview Button -->
                            <button type="button" 
                                    x-on:click="capturedPhotoFile = null; capturedPhotoPreview = '';" 
                                    class="absolute top-4 right-4 bg-rose-600 hover:bg-rose-700 text-white rounded-full p-2.5 shadow-md active:scale-90 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <button type="button" @click="resetProductForm()" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="button" @click="saveProduct()" class="py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold">Simpan</button>
                </div>
            </div>
        </div>

        <!-- CASHIER MODAL FORM -->
        <div x-show="showCashierModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;">
            
            <div class="modal-card bg-white rounded-3xl p-6 w-full max-w-md border border-slate-100 shadow-2xl flex flex-col gap-4" @click.away="showCashierModal = false">
                <h3 class="font-extrabold text-slate-800 text-lg" x-text="isEditingCashier ? 'Edit Akun Kasir' : 'Daftarkan Akun Kasir Baru'"></h3>
                <div class="flex flex-col gap-3 text-sm font-semibold text-slate-700">
                    <div>
                        <label class="block mb-1">Nama Lengkap</label>
                        <input type="text" x-model="newCashier.name" placeholder="Nama lengkap kasir..." class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1">Alamat Email</label>
                        <input type="email" x-model="newCashier.email" placeholder="Email untuk masuk sistem..." class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1">Kata Sandi (Password)</label>
                        <input type="password" x-model="newCashier.password" :placeholder="isEditingCashier ? 'Kosongkan jika tidak ingin mengubah password...' : 'Kata sandi minimal 8 karakter...'" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1">Penugasan Cabang</label>
                        <select x-model="newCashier.branch" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach(\App\Models\User::getBranchEnumValues() as $enumValue)
                                <option value="{{ $enumValue }}">{{ $enumValue }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <button type="button" @click="showCashierModal = false" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="button" @click="isEditingCashier ? updateCashier() : addCashier()" class="py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold">Simpan</button>
                </div>
            </div>
        </div>

        <!-- CUSTOM CONFIRMATION MODAL -->
        <div x-show="confirmModal.show" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="confirmModal.show = false">
            
            <div class="modal-card bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col items-center gap-5 text-center" @click.stop @click.away="confirmModal.show = false">
                <!-- Icon depending on type -->
                <template x-if="confirmModal.type === 'danger'">
                    <div class="w-14 h-14 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </template>
                <template x-if="confirmModal.type === 'warning'">
                    <div class="w-14 h-14 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                </template>
                <template x-if="confirmModal.type === 'success'">
                    <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                        <svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>

                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg" x-text="confirmModal.title"></h3>
                    <p class="text-sm text-slate-400 font-medium mt-1" x-text="confirmModal.message"></p>
                </div>

                <div class="grid grid-cols-2 gap-3 w-full">
                    <button type="button" @click="confirmModal.show = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                    <button type="button" @click="executeConfirm()" 
                            :class="{
                                'bg-rose-600 hover:bg-rose-700 shadow-rose-600/10 text-white': confirmModal.type === 'danger',
                                'bg-amber-500 hover:bg-amber-600 shadow-amber-500/10 text-white': confirmModal.type === 'warning',
                                'bg-emerald-700 hover:bg-emerald-800 shadow-emerald-700/10 text-white': confirmModal.type === 'success'
                            }"
                            class="py-3 rounded-2xl font-bold shadow-md transition duration-150" x-text="confirmModal.confirmText"></button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
