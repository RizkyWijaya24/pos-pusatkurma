<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Manajemen Inventori & Kasir') }}
        </h2>
    </x-slot>

    <!-- JSON Data Islands for Security and HTML parsing safety -->
    <script type="application/json" id="products-data">@json($products)</script>
    <script type="application/json" id="cashiers-data">@json($cashiers)</script>
    <script type="application/json" id="categories-data">@json($categories)</script>

    <!-- Alpine.js Admin State -->
    <div x-data="{
        activeTab: 'inventory',
        showProductModal: false,
        showCashierModal: false,
        showCategoryModal: false,
        isEditing: false,
        isEditingCategory: false,
        search: '',
        activeCategory: 'Semua',
        
        // Products list (fed dynamically from JSON Island)
        products: JSON.parse(document.getElementById('products-data').textContent),

        // Cashiers list (fed dynamically from JSON Island)
        cashiers: JSON.parse(document.getElementById('cashiers-data').textContent),

        // Categories list (fed dynamically from JSON Island)
        categories: JSON.parse(document.getElementById('categories-data').textContent),

        // Product Form State
        newProduct: { id: null, sku: '', name: '', category: 'Premium', cost_price: '', selling_price: '', price_unit: 'pcs', stock: '' },
        costPriceMode: 'pct',   // 'pct' = persentase dari harga jual, 'manual' = isi sendiri
        costPricePct: 50,       // default 50% dari harga jual
        
        // Cashier Form State
        newCashier: { name: '', email: '', password: '', branch: 'Pusat Cianjur' },

        // Category Form State
        newCategory: { id: null, name: '' },

        init() {
            if (this.categories.length > 0) {
                this.newProduct.category = this.categories[0].name;
            }
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
                const matchesSearch = this.fuzzyMatch(p.name, this.search) || this.fuzzyMatch(p.sku, this.search);
                const matchesCategory = this.activeCategory === 'Semua' || p.category === this.activeCategory;
                return matchesSearch && matchesCategory;
            });
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

        // Actions
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
                stock: product.stock
            };
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

        resetProductForm(keepOpen = false) {
            const defaultCategory = this.categories.length > 0 ? this.categories[0].name : '';
            this.newProduct = { id: null, sku: '', name: '', category: defaultCategory, cost_price: '', selling_price: '', price_unit: 'pcs', stock: '' };
            this.costPriceMode = 'pct';
            this.costPricePct  = 50;
            this.isEditing = false;
            if (!keepOpen) {
                this.showProductModal = false;
            }
            const fileInput = document.getElementById('product_image');
            if (fileInput) fileInput.value = '';
        },

        // Computed effective cost_price (used before submit)
        get effectiveCostPrice() {
            if (this.costPriceMode === 'pct') {
                const sell = parseFloat(this.newProduct.selling_price) || 0;
                return Math.round(sell * (parseFloat(this.costPricePct) || 0) / 100);
            }
            return parseFloat(this.newProduct.cost_price) || 0;
        },

        saveProduct() {
            // Sync cost_price from mode before validation
            if (this.costPriceMode === 'pct') {
                this.newProduct.cost_price = this.effectiveCostPrice;
            }
            if (!this.newProduct.name || this.newProduct.cost_price === '' || this.newProduct.selling_price === '' || this.newProduct.stock === '') {
                this.showToast('Silakan lengkapi semua kolom wajib!', 'warning');
                return;
            }

            this.showConfirm(
                this.isEditing ? 'Simpan Perubahan Produk?' : 'Tambah Produk Baru?',
                this.isEditing ? 'Apakah Anda yakin ingin menyimpan perubahan pada produk ini?' : 'Apakah Anda yakin ingin menambahkan produk baru ini?',
                () => {
                    const formData = new FormData();
                    formData.append('sku', this.newProduct.sku || '');
                    formData.append('name', this.newProduct.name);
                    formData.append('category', this.newProduct.category);
                    formData.append('cost_price', this.newProduct.cost_price);
                    formData.append('selling_price', this.newProduct.selling_price);
                    formData.append('price_unit', this.newProduct.price_unit);
                    formData.append('stock', this.newProduct.stock);

                    const fileInput = document.getElementById('product_image');
                    if (fileInput && fileInput.files[0]) {
                        formData.append('image', fileInput.files[0]);
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

        formatRupiah(num) {
            if (num === null || num === undefined) return 'Rp 0';
            return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        }
    }" class="flex flex-col gap-8 max-w-full overflow-hidden">

        <!-- Tabs Navigation -->
        <div class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex gap-2 self-start">
            <button type="button" 
                    @click="activeTab = 'inventory'"
                    :class="activeTab === 'inventory' ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10' : 'bg-transparent text-slate-600 hover:bg-slate-50'"
                    class="px-5 py-3 text-sm font-bold rounded-xl transition duration-150 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                Stok Inventori
            </button>
            <button type="button" 
                    @click="activeTab = 'cashiers'"
                    :class="activeTab === 'cashiers' ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10' : 'bg-transparent text-slate-600 hover:bg-slate-50'"
                    class="px-5 py-3 text-sm font-bold rounded-xl transition duration-150 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0110.089 18M14.214 16.058A9.396 9.396 0 0013.3 14.12M14.214 16.058A9.38 9.38 0 0110.089 18M9.75 18.917A11.542 11.542 0 016 18M9.75 18.917V19.128c0 .248-.02.492-.059.729l-.12.718a2.25 2.25 0 01-2.236 1.903H5.068a2.25 2.25 0 01-2.236-1.903l-.12-.718A2.25 2.25 0 012.653 19.1M9.75 18.917c0-1.113-.285-2.16-.786-3.07M2.653 19.1c0-1.113.285-2.16.786-3.07M2.653 19.1v.109c0 .777.12 1.525.343 2.227M6 18c-2.435 0-4.646.993-6.223 2.6M6 18V18.128c0 .248-.02.492-.059.729l-.12.718a2.25 2.25 0 01-2.236 1.903H5.068a2.25 2.25 0 01-2.236-1.903l-.12-.718A2.25 2.25 0 012.653 19.1M6 18a11.386 11.386 0 00-3.004 2.822" />
                </svg>
                Kelola Kasir
            </button>
            <button type="button" 
                    @click="activeTab = 'categories'"
                    :class="activeTab === 'categories' ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10' : 'bg-transparent text-slate-600 hover:bg-slate-50'"
                    class="px-5 py-3 text-sm font-bold rounded-xl transition duration-150 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.125 1.125 0 001.591 0l7.1-7.1a1.125 1.125 0 000-1.591l-9.581-9.581A2.25 2.25 0 0010.74 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                </svg>
                Kelola Kategori
            </button>
        </div>

        <!-- 1. INVENTORY TAB CONTENT -->
        <div x-show="activeTab === 'inventory'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Daftar Stok Produk Kurma</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Kelola stok, harga, dan SKU semua produk kurma</p>
                </div>
                <button type="button" 
                        @click="resetProductForm(); showProductModal = true"
                        class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-emerald-700/10 whitespace-nowrap">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Produk
                </button>
            </div>

            <!-- Category and Search Panel -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col sm:flex-row gap-4 justify-between items-center">
                <!-- Category Selectors -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 w-full sm:w-auto">
                    <template x-for="cat in ['Semua', ...categories.map(c => c.name)]">
                        <button type="button" 
                                @click="activeCategory = cat"
                                :class="activeCategory === cat ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/10' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 whitespace-nowrap"
                                x-text="cat">
                        </button>
                    </template>
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

            <!-- Responsive Table (Scrolls horizontally or turns into beautiful cards on Mobile) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
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
                            <template x-for="p in filteredProducts" :key="p.id">
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <template x-if="p.image_path">
                                            <img :src="'/storage/' + p.image_path" class="w-10 h-10 object-cover rounded-xl border border-slate-100 shadow-sm" alt="Foto">
                                        </template>
                                        <template x-if="!p.image_path">
                                            <div class="w-10 h-10 bg-emerald-800 text-white font-bold flex items-center justify-center rounded-xl text-xs uppercase" x-text="p.name.split(' ').slice(0, 2).map(w => w[0]).join('')"></div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 text-xs font-mono" x-text="p.sku"></td>
                                    <td class="px-6 py-4 text-slate-800" x-text="p.name"></td>
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
                                    <td class="px-6 py-4 text-right" x-text="p.stock + ' ' + p.price_unit"></td>
                                    <td class="px-6 py-4 text-center">
                                        <template x-if="p.stock <= 10">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase rounded bg-rose-50 text-rose-700 border border-rose-100">
                                                Stok Menipis
                                            </span>
                                        </template>
                                        <template x-if="p.stock > 10">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold uppercase rounded bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                Aman
                                            </span>
                                        </template>
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
            </div>
        </div>

        <!-- 2. CASHIERS TAB CONTENT -->
        <div x-show="activeTab === 'cashiers'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Daftar Akun Kasir Toko</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Kelola kredensial akun kasir yang bertugas melayani transaksi</p>
                </div>
                <button type="button" 
                        @click="showCashierModal = true"
                        class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-sm transition duration-150 flex items-center gap-2 shadow-md shadow-emerald-700/10 whitespace-nowrap">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                    Daftarkan Kasir
                </button>
            </div>

            <!-- Responsive Table (Cashiers) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
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
                                        <button type="button" 
                                                @click="deleteCashier(c.id)"
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

        <!-- 3. CATEGORIES TAB CONTENT -->
        <div x-show="activeTab === 'categories'" class="flex flex-col gap-6">
            
            <!-- Tab Controls -->
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">Daftar Kategori Produk</h3>
                    <p class="text-sm text-slate-400 font-medium mt-1">Kelola kategori produk kurma dan lihat jumlah produk terkait</p>
                </div>
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
            <div class="bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
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

        <!-- CATEGORY MODAL FORM -->
        <div x-show="showCategoryModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;">
            
            <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col gap-4" @click.away="resetCategoryForm()">
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
            
            <div class="bg-white rounded-3xl p-6 w-full max-w-md border border-slate-100 shadow-2xl flex flex-col gap-4 max-h-[90vh] overflow-y-auto" @click.away="if (!confirmModal.show) resetProductForm()">
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
                        <div x-show="costPriceMode === 'manual'">
                            <input type="number" x-model="newProduct.cost_price"
                                   placeholder="Masukkan harga modal..."
                                   class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1" x-text="'Stok Saat Ini (' + newProduct.price_unit + ')'"></label>
                        <input type="number" step="any" x-model="newProduct.stock" placeholder="Stok..." class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1">Foto Produk</label>
                        <input type="file" id="product_image" accept="image/*" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1.5 focus:border-emerald-500 focus:ring-emerald-500">
                        <p class="text-[10px] text-slate-400 font-semibold mt-1">Bebas ukuran file. Foto akan otomatis dikompres oleh sistem agar ringan.</p>
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
            
            <div class="bg-white rounded-3xl p-6 w-full max-w-md border border-slate-100 shadow-2xl flex flex-col gap-4" @click.away="showCashierModal = false">
                <h3 class="font-extrabold text-slate-800 text-lg">Daftarkan Akun Kasir Baru</h3>
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
                        <input type="password" x-model="newCashier.password" placeholder="Kata sandi minimal 8 karakter..." class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500">
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
                    <button type="button" @click="addCashier()" class="py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold">Simpan</button>
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
            
            <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col items-center gap-5 text-center" @click.stop @click.away="confirmModal.show = false">
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
