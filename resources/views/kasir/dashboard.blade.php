    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Mesin Kasir / POS') }}
            </h2>
        </x-slot>

        <!-- JSON Data Islands for Security and HTML parsing safety -->
        <script type="application/json" id="products-data">@json($products)</script>
        <script type="application/json" id="transactions-data">@json($todayTransactionsMapped)</script>
        <script type="application/json" id="expenses-data">@json($todayExpensesMapped)</script>
        <script type="application/json" id="categories-data">@json($categories)</script>

        <!-- Alpine.js POS State -->
        <div x-data="{
            search: '',
            activeCategory: 'Semua',
            
            products: JSON.parse(document.getElementById('products-data').textContent),
            todayTransactions: JSON.parse(document.getElementById('transactions-data').textContent),
            expenses: JSON.parse(document.getElementById('expenses-data').textContent),
            categories: JSON.parse(document.getElementById('categories-data').textContent),
            
            showExpenseModal: false,
            newExpense: { amount: '', category: 'Operasional Toko', description: '' },
            pendingDeleteExpenseId: null,
            
            cart: [],
            discount: '',
            showReceipt: false,
            showQtyModal: false,
            qtyProduct: null,
            qtyValue: '',
            paymentMethod: '',
            cashReceived: '',

            checkoutStage: 0, // 0 = closed, 1 = details entry, 2 = loading/processing, 3 = success/receipt
            statusMessage: '',
            cardDigits: '',
            latestTransaction: null,

            showToast(message, type = 'success') {
                if (window.showToast) {
                    window.showToast(message, type);
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: message, type: type } }));
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
                
                // If exact substring, match immediately (high performance)
                if (text.includes(query)) return true;
                
                const queryWords = query.split(/\s+/);
                const textWords = text.split(/\s+/);
                
                // Ensure all query words match at least one word in name/sku with typo tolerance
                return queryWords.every(qWord => {
                    if (!qWord) return true;
                    if (text.includes(qWord)) return true;
                    
                    return textWords.some(tWord => {
                        const distance = this.levenshteinDistance(qWord, tWord);
                        
                        // Adaptive threshold: 1 typo for short queries, 2 for longer queries
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

            addToCart(product) {
                if (product.price_unit === 'gram' || product.price_unit === 'kg') {
                    this.qtyProduct = product;
                    this.qtyValue = '';
                    this.showQtyModal = true;
                } else {
                    const item = this.cart.find(c => c.id === product.id);
                    if (item) {
                        if (item.qty < product.stock) {
                            item.qty++;
                        } else {
                            this.showToast('Stok tidak mencukupi!', 'warning');
                        }
                    } else {
                        this.cart.push({ ...product, qty: 1 });
                    }
                }
            },

            confirmQty() {
                const qty = parseFloat(this.qtyValue);
                if (isNaN(qty) || qty <= 0) {
                    this.showToast('Kuantitas tidak valid!', 'warning');
                    return;
                }
                if (qty > this.qtyProduct.stock) {
                    this.showToast('Stok tidak mencukupi! Tersedia: ' + this.qtyProduct.stock + ' ' + this.qtyProduct.price_unit, 'warning');
                    return;
                }

                const item = this.cart.find(c => c.id === this.qtyProduct.id);
                if (item) {
                    item.qty = qty;
                } else {
                    this.cart.push({ ...this.qtyProduct, qty: qty });
                }

                this.showQtyModal = false;
                this.qtyProduct = null;
                this.qtyValue = '';
            },

            updateQty(id, qty) {
                const item = this.cart.find(c => c.id === id);
                if (item) {
                    const product = this.products.find(p => p.id === id);
                    if (qty <= 0) {
                        this.cart = this.cart.filter(c => c.id !== id);
                        this.showToast(product.name + ' dihapus dari keranjang.', 'info');
                    } else if (qty <= product.stock) {
                        item.qty = parseFloat(qty);
                    } else {
                        this.showToast('Stok tidak mencukupi!', 'warning');
                    }
                }
            },

            get subtotal() {
                return this.cart.reduce((sum, item) => sum + Math.round(item.price * item.qty), 0);
            },

            get discountAmount() {
                const val = parseFloat(this.discount);
                return isNaN(val) ? 0 : Math.max(0, Math.min(val, this.subtotal));
            },

            get discountedSubtotal() {
                return Math.max(0, this.subtotal - this.discountAmount);
            },

            get tax() {
                return 0; // PPN dihapus
            },

            get total() {
                return this.discountedSubtotal;
            },

            get changeAmount() {
                if (!this.cashReceived) return 0;
                const change = parseFloat(this.cashReceived) - this.total;
                return change > 0 ? change : 0;
            },

            get todaySalesTotal() {
                return this.todayTransactions.reduce((sum, t) => sum + parseFloat(t.total_price), 0);
            },

            get todayExpensesTotal() {
                return this.expenses.reduce((sum, e) => sum + parseFloat(e.amount), 0);
            },

            get todayNetCash() {
                return this.todaySalesTotal - this.todayExpensesTotal;
            },

            processCheckout(method) {
                if (this.cart.length === 0) {
                    this.showToast('Keranjang masih kosong!', 'warning');
                    return;
                }
                this.paymentMethod = method;
                this.cashReceived = '';
                this.cardDigits = '';
                this.latestTransaction = null;
                if (method === 'QRIS' || method === 'Debit') {
                    this.cashReceived = this.total;
                }
                this.checkoutStage = 1; // Transition to Stage 1: Input details
                this.showReceipt = true;
            },

            finishTransaction() {
                if (this.cart.length === 0) return;

                this.checkoutStage = 2; // Transition to Stage 2: Processing
                this.statusMessage = 'Menghubungkan ke gateway pembayaran...';

                setTimeout(() => {
                    this.statusMessage = 'Memverifikasi transaksi & mengurangi stok...';
                }, 700);

                setTimeout(() => {
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch('/kasir/transactions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            total_price: this.total,
                            discount: this.discountAmount,
                            payment_method: this.paymentMethod,
                            items: this.cart.map(item => ({
                                id: item.id,
                                name: item.name,
                                qty: item.qty,
                                price_unit: item.price_unit
                            }))
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Prepend new transaction to Alpine list
                            this.todayTransactions.unshift({
                                id: data.transaction.id,
                                time: data.transaction.time,
                                transaction_code: data.transaction.transaction_code,
                                items_summary: data.transaction.items_summary,
                                payment_method: data.transaction.payment_method,
                                total_price: data.transaction.total_price,
                                discount: data.transaction.discount
                            });
                            
                            // Save latest transaction details for receipt screen
                            this.latestTransaction = data.transaction;
                            
                            // Reset cart, keep staging info
                            this.cart = [];
                            this.checkoutStage = 3; // Transition to Stage 3: Success view
                            this.showToast('Transaksi ' + data.transaction.transaction_code + ' berhasil!', 'success');
                        } else {
                            this.showToast('Gagal mencatat transaksi!', 'error');
                            this.checkoutStage = 1;
                        }
                    })
                    .catch(err => {
                        console.error('Gagal mencatat transaksi:', err);
                        this.showToast('Terjadi kesalahan jaringan!', 'error');
                        this.checkoutStage = 1;
                    });
                }, 1400);
            },

            selectDenomination(amount) {
                this.cashReceived = amount;
            },

            resetPOS() {
                this.cart = [];
                this.discount = '';
                this.showReceipt = false;
                this.checkoutStage = 0;
                this.cashReceived = '';
                this.paymentMethod = '';
                this.cardDigits = '';
                this.latestTransaction = null;
            },

            cancelCheckout() {
                this.showReceipt = false;
                this.checkoutStage = 0;
                this.cashReceived = '';
                this.paymentMethod = '';
                this.cardDigits = '';
                this.latestTransaction = null;
            },

            addExpense() {
                const amount = parseFloat(this.newExpense.amount);
                if (isNaN(amount) || amount <= 0) {
                    this.showToast('Jumlah pengeluaran tidak valid!', 'warning');
                    return;
                }
                if (!this.newExpense.description.trim()) {
                    this.showToast('Deskripsi pengeluaran tidak boleh kosong!', 'warning');
                    return;
                }

                const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                fetch('/kasir/expenses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: amount,
                        category: this.newExpense.category,
                        description: this.newExpense.description
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.expenses.unshift(data.expense);
                        this.newExpense = { amount: '', category: 'Operasional Toko', description: '' };
                        this.showExpenseModal = false;
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || 'Gagal menyimpan pengeluaran!', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    this.showToast('Terjadi kesalahan jaringan!', 'error');
                });
            },

            deleteExpense(id) {
                if (this.pendingDeleteExpenseId !== null) return; // already waiting
                this.pendingDeleteExpenseId = id;
            },

            confirmDeleteExpense() {
                const id = this.pendingDeleteExpenseId;
                this.pendingDeleteExpenseId = null;
                if (!id) return;

                const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                fetch('/kasir/expenses/' + id, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.expenses = this.expenses.filter(e => e.id !== id);
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || 'Gagal menghapus pengeluaran!', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    this.showToast('Terjadi kesalahan jaringan!', 'error');
                });
            },

            formatRupiah(num) {
                if (num === null || num === undefined) return 'Rp 0';
                return 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            }
        }" class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full max-w-full overflow-hidden">

            <!-- LEFT SIDE: Product Catalogue (8 cols on desktop, full on mobile) -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- Real-time Shift Summary / Ringkasan Kas Toko Hari Ini -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- Total Penjualan -->
                    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;" class="p-4 rounded-2xl flex flex-col gap-1 shadow-sm relative overflow-hidden select-none">
                        <span class="text-[9px] font-black text-emerald-700 uppercase tracking-wider">Omset Hari Ini</span>
                        <h4 class="text-base sm:text-lg font-black text-slate-800 leading-tight whitespace-nowrap" x-text="formatRupiah(todaySalesTotal)">Rp 0</h4>
                    </div>
                    <!-- Total Pengeluaran -->
                    <div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1px solid #fecdd3;" class="p-4 rounded-2xl flex flex-col gap-1 shadow-sm relative overflow-hidden select-none">
                        <span class="text-[9px] font-black text-rose-700 uppercase tracking-wider">Total Pengeluaran</span>
                        <h4 class="text-base sm:text-lg font-black text-slate-800 leading-tight whitespace-nowrap" x-text="formatRupiah(todayExpensesTotal)">Rp 0</h4>
                    </div>
                    <!-- Uang di Laci / Kas Bersih -->
                    <div :style="todayNetCash >= 0 ? 'background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border: 1px solid #a5f3fc;' : 'background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;'"
                         class="p-4 rounded-2xl flex flex-col gap-1 shadow-sm relative overflow-hidden select-none transition-all duration-300">
                        <span class="text-[9px] font-black uppercase tracking-wider" :class="todayNetCash >= 0 ? 'text-cyan-700' : 'text-amber-700'">Uang di Laci / Kas Bersih</span>
                        <h4 class="text-base sm:text-lg font-black text-slate-800 leading-tight whitespace-nowrap" x-text="formatRupiah(todayNetCash)">Rp 0</h4>
                    </div>
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

                    <!-- Button Catat Pengeluaran -->
                    <button type="button" 
                            @click="showExpenseModal = true"
                            class="shrink-0 px-4 py-2 text-xs font-bold rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition duration-150 flex items-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Catat Pengeluaran
                    </button>

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

                <!-- Product Grid -->
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 overflow-y-auto max-h-[calc(100vh-210px)] pb-4 pr-1">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" 
                            class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-emerald-200 active:scale-[0.98] transition duration-150 overflow-hidden flex flex-col justify-between group cursor-pointer select-none">
                            
                            <!-- Product Photo / Forest-green Placeholder -->
                            <div class="w-full relative overflow-hidden border-b border-slate-100/50" style="height: 128px; background-color: #f8fafc;">
                                <template x-if="product.image_path">
                                    <img :src="'/storage/' + product.image_path" class="transition duration-300 group-hover:scale-105" style="width: 100%; height: 100%; object-fit: cover;" alt="Foto produk">
                                </template>
                                <template x-if="!product.image_path">
                                    <div class="text-emerald-100 font-bold flex flex-col items-center justify-center gap-1 select-none" style="width: 100%; height: 100%; background-color: #1b4332;">
                                        <span class="text-3xl tracking-widest uppercase" x-text="product.name.split(' ').filter(w => w.trim() !== '').slice(0, 2).map(w => w[0]).join('').toUpperCase()"></span>
                                        <span class="text-[9px] tracking-widest text-emerald-400/80 font-bold uppercase">Pusat Kurma</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Product Info -->
                            <div class="p-3 sm:p-4 flex flex-col gap-1.5 relative">
                                <!-- Category Badge -->
                                <span :class="{
                                    'bg-purple-50 text-purple-700 border-purple-100': product.category === 'Premium',
                                    'bg-blue-50 text-blue-700 border-blue-100': product.category === 'Basah',
                                    'bg-amber-50 text-amber-700 border-amber-100': product.category === 'Kering',
                                    'bg-emerald-50 text-emerald-700 border-emerald-100': product.category !== 'Premium' && product.category !== 'Basah' && product.category !== 'Kering'
                                }" class="self-start text-[10px] font-bold tracking-wide uppercase px-2 py-0.5 rounded border" x-text="product.category"></span>

                                <!-- Title -->
                                <h3 class="font-bold text-slate-800 text-base leading-snug group-hover:text-emerald-700 transition duration-150" x-text="product.name"></h3>
                                <p class="text-xs text-slate-400 font-medium" x-text="'SKU: ' + product.sku"></p>

                                <!-- Price & Stock (Stacked cleanly to prevent overlapping) -->
                                <div class="mt-2 flex flex-col gap-1">
                                    <span class="text-emerald-700 font-black text-base sm:text-lg leading-none" x-text="formatRupiah(product.price) + ' / ' + product.price_unit"></span>
                                    <span class="text-[11px] text-slate-400 font-bold" x-text="'Stok Tersedia: ' + product.stock + ' ' + product.price_unit"></span>
                                </div>
                            </div>

                            <!-- Add Button (Stylized Indicator) -->
                            <div class="w-full py-3 bg-slate-50 hover:bg-emerald-50 border-t border-slate-100 group-hover:border-emerald-100 font-bold text-xs tracking-wider uppercase text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition duration-200 flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah ke Keranjang
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- RIGHT SIDE: Cart System (4 cols on desktop, stacks below on mobile) -->
            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-lg flex flex-col justify-between h-[calc(100vh-160px)] lg:h-[calc(100vh-140px)] min-h-[500px] sticky top-6">
                
                <!-- Cart Header -->
                <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                        <h3 class="font-extrabold text-slate-800 text-lg">Keranjang Belanja</h3>
                    </div>
                    <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1 rounded-full" x-text="cart.reduce((sum, item) => sum + item.qty, 0) + ' item'"></span>
                </div>

                <!-- Cart Items list -->
                <div class="flex-grow overflow-y-auto my-4 pr-1 flex flex-col gap-4">
                    <!-- Empty Cart State -->
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-slate-400 gap-2 py-8">
                            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5h6.75M8.625 12.75h6.75" />
                            </svg>
                            <p class="text-sm font-semibold text-slate-400">Keranjang Kosong</p>
                            <p class="text-xs text-slate-400 text-center">Silakan klik produk di sebelah kiri untuk berbelanja.</p>
                        </div>
                    </template>

                    <!-- List of Items -->
                    <template x-for="item in cart" :key="item.id">
                        <div class="flex gap-3 justify-between items-center p-3 rounded-2xl bg-slate-50 border border-slate-100/50 hover:bg-slate-100/30 transition duration-150">
                            <div class="overflow-hidden flex-grow cursor-pointer" @click="qtyProduct = products.find(p => p.id === item.id); qtyValue = item.qty.toString(); showQtyModal = true;">
                                <h4 class="font-bold text-sm text-slate-800 truncate" x-text="item.name"></h4>
                                <span class="text-xs font-semibold text-emerald-700" x-text="formatRupiah(item.price) + ' / ' + item.price_unit"></span>
                            </div>

                            <!-- Quantity control -->
                            <div class="flex items-center gap-2">
                                <button type="button" @click="updateQty(item.id, item.qty - (item.price_unit === 'gram' ? 100 : 1))" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold flex items-center justify-center">-</button>
                                <span class="font-bold text-xs text-slate-800 w-16 text-center whitespace-nowrap cursor-pointer hover:underline" @click="qtyProduct = products.find(p => p.id === item.id); qtyValue = item.qty.toString(); showQtyModal = true;" x-text="item.qty + ' ' + item.price_unit"></span>
                                <button type="button" @click="updateQty(item.id, item.qty + (item.price_unit === 'gram' ? 100 : 1))" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold flex items-center justify-center">+</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Summary & Payment -->
                <div class="border-t border-slate-100 pt-4 flex flex-col gap-4">
                    
                    <!-- Financial details -->
                    <div class="flex flex-col gap-2 text-sm font-semibold text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="text-slate-800" x-text="formatRupiah(subtotal)"></span>
                        </div>

                        
                        <!-- Input Diskon Langsung -->
                        <div class="flex justify-between items-center bg-rose-50/40 border border-rose-100 p-2.5 rounded-xl gap-2 mt-1">
                            <span class="text-xs font-bold text-rose-700 uppercase shrink-0">Diskon (Rp)</span>
                            <input type="number" 
                                   x-model="discount" 
                                   placeholder="Masukkan nominal..." 
                                   min="0"
                                   :max="subtotal"
                                   class="w-full text-right py-1 px-2 border-slate-200 rounded-lg text-xs font-extrabold text-slate-800 focus:border-rose-500 focus:ring-rose-500 shadow-inner">
                        </div>
                        
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-extrabold">
                            <span class="text-slate-800">Total Harga</span>
                            <span class="text-emerald-700 text-lg" x-text="formatRupiah(total)"></span>
                        </div>
                    </div>

                    <!-- Big Touch-Friendly Checkout Buttons -->
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" 
                                @click="processCheckout('Cash')"
                                class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-emerald-700/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                            </svg>
                            TUNAI
                        </button>
                        <button type="button" 
                                @click="processCheckout('QRIS')"
                                class="py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-teal-600/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 15h.008v.008H15V15zm0 2.25h.008v.008H15v-.008zm-2.25-2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H15v-.008zm-2.25 0h.008v.008h-.008v-.008zm4.5 4.5h.008v.008H17.25V15zm0 2.25h.008v.008H17.25v-.008z" />
                            </svg>
                            QRIS
                        </button>
                        <button type="button" 
                                @click="processCheckout('Debit')"
                                class="py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-2xl font-extrabold text-xs tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-sky-600/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0016.5 13.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.75A1.125 1.125 0 002.625 11.625v1.5a1.125 1.125 0 001.125 1.125z" />
                            </svg>
                            DEBIT
                        </button>
                    </div>
                </div>
            </div>

            <!-- CHECKOUT RECEIPT MODAL (Multi-Stage POS Workflow) -->
            <div x-show="showReceipt" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
                style="display: none;"
                @keydown.escape.window="if (checkoutStage === 1) cancelCheckout()">
                
                <div class="bg-white rounded-3xl p-6 w-full max-w-md border border-slate-100 shadow-2xl flex flex-col gap-5">
                    
                    <!-- ================= STAGE 1: payment details entry ================= -->
                    <template x-if="checkoutStage === 1">
                        <div class="flex flex-col gap-4">
                            <!-- Header -->
                            <div class="text-center pb-3 border-b border-slate-100">
                                <span class="bg-emerald-50 text-emerald-800 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase border border-emerald-100 tracking-wider">
                                    Tahap 1: Verifikasi Pembayaran
                                </span>
                                <h3 class="font-extrabold text-slate-800 text-lg mt-2" x-text="'Metode: ' + paymentMethod"></h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">Lengkapi details pembayaran sebelum memproses transaksi</p>
                            </div>

                            <!-- Total Billing display -->
                            <div class="bg-emerald-50/50 border border-emerald-100/50 rounded-2xl p-4 flex flex-col items-center justify-center text-center">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tagihan</span>
                                <span class="text-2xl font-black text-emerald-800 mt-1" x-text="formatRupiah(total)"></span>
                            </div>

                            <!-- CASH payment logic -->
                            <template x-if="paymentMethod === 'Cash'">
                                <div class="flex flex-col gap-3">
                                    <!-- Input for Cash Received -->
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah Uang Tunai Diterima (Rp)</label>
                                        <input type="number" 
                                            x-model="cashReceived" 
                                            placeholder="Masukkan jumlah tunai..."
                                            class="w-full py-2.5 px-4 border-slate-200 rounded-xl text-center font-extrabold text-lg text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                                    </div>

                                    <!-- Quick Cash Denominations -->
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Pilihan Cepat Uang Pas / Pecahan</span>
                                        <div class="grid grid-cols-3 gap-2">
                                            <button type="button" @click="selectDenomination(total)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">Uang Pas</button>
                                            <button type="button" @click="selectDenomination(10000)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">10.000</button>
                                            <button type="button" @click="selectDenomination(20000)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">20.000</button>
                                            <button type="button" @click="selectDenomination(50000)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">50.000</button>
                                            <button type="button" @click="selectDenomination(100000)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">100.000</button>
                                            <button type="button" @click="selectDenomination(200000)" class="py-2 px-1 text-xs font-extrabold bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg border border-slate-100 text-slate-600 transition duration-150">200.000</button>
                                        </div>
                                    </div>

                                    <!-- Change calculation & Warning -->
                                    <div class="mt-2 pt-2 border-t border-slate-100">
                                        <template x-if="cashReceived && parseFloat(cashReceived) >= total">
                                            <div class="flex justify-between items-center text-sm font-bold text-slate-800">
                                                <span>Uang Kembali:</span>
                                                <span class="text-emerald-600 text-base font-extrabold" x-text="formatRupiah(changeAmount)"></span>
                                            </div>
                                        </template>
                                        <template x-if="cashReceived && parseFloat(cashReceived) < total">
                                            <div class="bg-amber-50 text-amber-800 border border-amber-100 text-[11px] font-bold p-2.5 rounded-xl flex items-center gap-1.5">
                                                <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.303V18a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-5.553m18 0V4.5A2.25 2.25 0 0 0 18.75 2.25H5.25A2.25 2.25 0 0 0 3 4.5v8.7m18 0h-3.375a1.125 1.125 0 0 1-1.125-1.125V9.75M3 13.2h3.375c.621 0 1.125-.504 1.125-1.125V9.75" />
                                                </svg>
                                                Uang diterima kurang dari total tagihan!
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- QRIS payment logic -->
                            <template x-if="paymentMethod === 'QRIS'">
                                <div class="bg-slate-50 p-4 rounded-2xl flex flex-col items-center gap-3 border border-slate-100 text-center">
                                    <span class="text-[10px] font-bold text-emerald-800 uppercase bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 animate-pulse">Menunggu Scan Pelanggan</span>
                                    <!-- Simulated QR Code -->
                                    <div class="w-32 h-32 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-2.5 shadow-md relative group">
                                        <div class="w-full h-full bg-slate-900 flex flex-col gap-1 p-2 justify-center items-center rounded-lg relative overflow-hidden select-none">
                                            <div class="w-full h-1 bg-white absolute top-0 left-0 animate-ping"></div>
                                            <div class="w-6 h-6 bg-white rounded flex items-center justify-center font-bold text-[8px] text-slate-800 select-none">QR</div>
                                            <span class="text-[8px] text-white font-extrabold uppercase mt-1">Pusat Kurma</span>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">Tunjukkan QRIS pada layar ini kepada pelanggan untuk dipindai dengan E-Wallet/Mobile Banking</p>
                                </div>
                            </template>

                            <!-- DEBIT payment logic -->
                            <template x-if="paymentMethod === 'Debit'">
                                <div class="flex flex-col gap-3">
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-col gap-2 items-center text-center">
                                        <svg class="h-8 w-8 text-slate-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0 0 16.5 13.125v-1.5a1.125 1.125 0 0 0-1.125-1.125H3.75A1.125 1.125 0 0 0 2.625 11.625v1.5a1.125 1.125 0 0 0 1.125 1.125z" />
                                        </svg>
                                        <h4 class="font-bold text-xs text-slate-700">Petunjuk Pembayaran EDC Debit</h4>
                                        <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">Silakan gesek/masukkan kartu Debit pelanggan ke mesin EDC kasir PK, kemudian input 4 digit terakhir nomor kartu di bawah ini.</p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">4 Digit Terakhir Kartu</label>
                                        <input type="text" 
                                            x-model="cardDigits" 
                                            maxlength="4"
                                            placeholder="Contoh: 4321" 
                                            class="w-full py-2 px-4 border-slate-200 rounded-xl text-center font-extrabold text-base focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                                    </div>
                                </div>
                            </template>

                            <!-- Confirm & Cancel Actions -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button type="button" @click="cancelCheckout()" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                                <button type="button" 
                                    @click="finishTransaction()" 
                                    :disabled="(paymentMethod === 'Cash' && (!cashReceived || parseFloat(cashReceived) < total)) || (paymentMethod === 'Debit' && cardDigits.length !== 4)"
                                    :class="(paymentMethod === 'Cash' && (!cashReceived || parseFloat(cashReceived) < total)) || (paymentMethod === 'Debit' && cardDigits.length !== 4) ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' : 'bg-emerald-700 hover:bg-emerald-800 text-white shadow-md shadow-emerald-700/10'"
                                    class="py-3 rounded-2xl font-bold transition duration-150">
                                    Proses Transaksi
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- ================= STAGE 2: processing transaction loader ================= -->
                    <template x-if="checkoutStage === 2">
                        <div class="py-8 flex flex-col items-center gap-5 text-center">
                            <!-- Circular Spinner -->
                            <div class="relative w-16 h-16">
                                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-emerald-700 border-t-transparent animate-spin"></div>
                            </div>
                            
                            <div>
                                <h3 class="font-extrabold text-slate-800 text-base uppercase tracking-wider">Memproses Transaksi</h3>
                                <p class="text-xs text-emerald-700 font-bold mt-2" x-text="statusMessage"></p>
                            </div>
                        </div>
                    </template>

                    <!-- ================= STAGE 3: success receipt final view ================= -->
                    <template x-if="checkoutStage === 3 && latestTransaction">
                        <div class="flex flex-col gap-4">
                            <!-- Success Checkmark -->
                            <div class="text-center pb-3 border-b border-dashed border-slate-200">
                                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2 border border-emerald-100">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <h3 class="font-black text-slate-800 text-lg">Pembayaran Sukses!</h3>
                                <span class="text-xs font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100" x-text="latestTransaction.transaction_code"></span>
                            </div>

                            <!-- Receipt Slip Summary -->
                            <div class="flex flex-col gap-2.5 text-xs font-semibold text-slate-600">
                                <div class="flex justify-between border-b border-slate-50 pb-1.5">
                                    <span>Tanggal & Jam:</span>
                                    <span class="text-slate-800" x-text="latestTransaction.time"></span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-1.5">
                                    <span>Metode Pembayaran:</span>
                                    <span class="text-slate-800 uppercase" x-text="latestTransaction.payment_method + (latestTransaction.payment_method === 'Debit' ? ' (Debit EDC)' : '')"></span>
                                </div>
                                <div class="flex flex-col gap-1 py-1 border-b border-slate-50">
                                    <span class="text-slate-400 font-bold uppercase tracking-wider text-[9px]">Ringkasan Belanja</span>
                                    <span class="text-slate-800 font-semibold" x-text="latestTransaction.items_summary"></span>
                                </div>
                                <template x-if="latestTransaction && latestTransaction.discount > 0">
                                    <div class="flex justify-between border-b border-slate-50 pb-1.5 text-rose-600 font-bold">
                                        <span>Potongan Diskon:</span>
                                        <span x-text="'-' + formatRupiah(latestTransaction.discount)"></span>
                                    </div>
                                </template>
                                <div class="flex justify-between font-extrabold text-sm text-slate-800 pt-1 border-b border-slate-50 pb-1.5">
                                    <span>Total Tagihan:</span>
                                    <span class="text-emerald-800" x-text="formatRupiah(latestTransaction.total_price)"></span>
                                </div>
                                
                                <!-- Paid Details for cash only -->
                                <template x-if="latestTransaction.payment_method === 'Cash'">
                                    <div class="flex flex-col gap-1.5 pt-1 text-slate-700">
                                        <div class="flex justify-between">
                                            <span>Uang Diterima:</span>
                                            <span x-text="formatRupiah(cashReceived)"></span>
                                        </div>
                                        <div class="flex justify-between font-extrabold text-emerald-600 text-sm">
                                            <span>Uang Kembalian:</span>
                                            <span x-text="formatRupiah(changeAmount)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Printing & New Checkout Actions -->
                            <div class="flex flex-col gap-2 pt-2 border-t border-slate-100">
                                <button type="button" 
                                    @click="printReceipt(latestTransaction.id)" 
                                    class="w-full py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-2xl font-bold text-xs tracking-wide uppercase transition duration-150 flex items-center justify-center gap-1.5 shadow-sm">
                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.821V7.5a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75v6.321m-10.5 0h10.5m-10.5 0-1.755-.351A1.25 1.25 0 0 1 3 12.25v-2.5a1.25 1.25 0 0 1 1.25-1.25h15.5c.69 0 1.25.56 1.25 1.25v2.5a1.25 1.25 0 0 1-1.025 1.22l-1.725.345m-12 0a1.25 1.25 0 1 0 2.5 0m9.5 0a1.25 1.25 0 1 0 2.5 0" />
                                    </svg>
                                    Cetak Struk Belanja
                                </button>
                                <button type="button" 
                                    @click="resetPOS()" 
                                    class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-2xl font-black text-xs tracking-wide uppercase transition duration-150 shadow-md">
                                    Transaksi Baru
                                </button>
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <!-- TOUCH QTY KEYPAD MODAL -->
            <div x-show="showQtyModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm shadow-2xl"
                style="display: none;"
                @keydown.escape.window="showQtyModal = false">
                
                <div class="bg-white rounded-3xl p-6 w-full max-w-sm border border-slate-100 shadow-2xl flex flex-col gap-4" @click.away="showQtyModal = false">
                    <div class="text-center">
                        <h3 class="font-extrabold text-slate-800 text-lg" x-text="qtyProduct ? qtyProduct.name : ''"></h3>
                        <p class="text-xs text-slate-400 font-semibold mt-1" x-text="'Masukkan berat/jumlah (' + (qtyProduct ? qtyProduct.price_unit : '') + ')'"></p>
                    </div>

                    <!-- Quantity input box -->
                    <div class="relative">
                        <input type="text" 
                            readonly
                            x-model="qtyValue"
                            placeholder="0"
                            class="w-full py-4 px-4 text-center font-extrabold text-2xl border-slate-200 rounded-2xl bg-slate-50 text-emerald-800 focus:outline-none">
                    </div>

                    <!-- Numeric On-Screen Keypad -->
                    <div class="grid grid-cols-3 gap-2 text-slate-700 font-extrabold text-lg">
                        <template x-for="num in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '.', 'C']">
                            <button type="button" 
                                    @click="if (num === 'C') { qtyValue = ''; } else if (num === '.') { if (!qtyValue.includes('.')) qtyValue += '.'; } else { qtyValue += num; }"
                                    class="py-3 bg-slate-50 active:bg-slate-100 border border-slate-100 hover:border-slate-200 rounded-xl transition duration-100 select-none"
                                    x-text="num">
                            </button>
                        </template>
                    </div>

                    <!-- Quick selectors for common weights (e.g. 100g, 250g, 500g, 1000g) -->
                    <template x-if="qtyProduct && qtyProduct.price_unit === 'gram'">
                        <div class="grid grid-cols-4 gap-1.5 text-xs font-bold text-emerald-800 mt-1">
                            <button type="button" @click="qtyValue = '100'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">100g</button>
                            <button type="button" @click="qtyValue = '250'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">250g</button>
                            <button type="button" @click="qtyValue = '500'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">500g</button>
                            <button type="button" @click="qtyValue = '1000'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">1000g</button>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="button" @click="showQtyModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                        <button type="button" @click="confirmQty()" class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold shadow-md shadow-emerald-700/10 transition duration-150">Konfirmasi</button>
                    </div>
                </div>
            </div>

            <!-- TOUCH EXPENSE MODAL (Pencatatan Pengeluaran Kasir) -->
            <div x-show="showExpenseModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm shadow-2xl"
                style="display: none;"
                @keydown.escape.window="showExpenseModal = false">
                
                <div x-data="{ activeTab: 'form' }" 
                     class="bg-white rounded-3xl p-6 w-full max-w-lg border border-slate-100 shadow-2xl flex flex-col gap-4" 
                     @click.away="showExpenseModal = false">
                    
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-lg">Pengeluaran Operasional Toko</h3>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">Catat pengeluaran harian cabang kasir Anda</p>
                        </div>
                        <button type="button" @click="showExpenseModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Tabs -->
                    <div class="flex gap-2 p-1 bg-slate-50 border border-slate-100 rounded-xl">
                        <button type="button" 
                                @click="activeTab = 'form'" 
                                :class="activeTab === 'form' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition duration-150">
                            Catat Baru
                        </button>
                        <button type="button" 
                                @click="activeTab = 'list'" 
                                :class="activeTab === 'list' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition duration-150 flex justify-center items-center gap-1.5">
                            Riwayat Hari Ini
                            <span class="bg-slate-100 text-slate-700 text-[10px] px-2.5 py-0.5 rounded-full font-extrabold" x-text="expenses.length"></span>
                        </button>
                    </div>

                    <!-- TAB 1: FORM INPUT -->
                    <div x-show="activeTab === 'form'" class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah Uang (Rp)</label>
                            <input type="number" 
                                   x-model="newExpense.amount" 
                                   placeholder="Masukkan jumlah dalam Rupiah..." 
                                   class="w-full py-2.5 px-4 border-slate-200 rounded-xl font-bold focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Pengeluaran</label>
                            <select x-model="newExpense.category" class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5">
                                <option value="Operasional Toko">⚙️ Operasional Toko</option>
                                <option value="Transportasi">🚚 Transportasi</option>
                                <option value="Alat Tulis & Kebersihan">🧼 Alat Tulis & Kebersihan</option>
                                <option value="Konsumsi">🍜 Konsumsi / Makan</option>
                                <option value="Lain-lain">📦 Lain-lain</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan / Keperluan</label>
                            <textarea x-model="newExpense.description" 
                                      rows="3" 
                                      placeholder="Tulis alasan/deskripsi pengeluaran..." 
                                      class="w-full border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 text-sm font-semibold"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-2">
                            <button type="button" @click="showExpenseModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                            <button type="button" @click="addExpense()" class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold shadow-md shadow-emerald-700/10 transition duration-150">Simpan Pengeluaran</button>
                        </div>
                    </div>

                    <!-- TAB 2: LIST VIEW -->
                    <div x-show="activeTab === 'list'" class="flex flex-col gap-3">
                        <div class="overflow-y-auto max-h-64 flex flex-col gap-2.5 pr-1">
                            <!-- Empty State -->
                            <template x-if="expenses.length === 0">
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400 gap-1.5">
                                    <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs font-bold">Belum Ada Pengeluaran Hari Ini</p>
                                </div>
                            </template>

                            <!-- List Loop -->
                            <template x-for="item in expenses" :key="item.id">
                                <div class="flex justify-between items-center p-3 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-slate-100/50 transition">
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="bg-rose-100 text-rose-800 text-[9px] font-extrabold px-1.5 py-0.5 rounded tracking-wide uppercase" x-text="item.category"></span>
                                            <span class="text-[9px] text-slate-400 font-bold" x-text="item.time"></span>
                                        </div>
                                        <p class="text-xs text-slate-700 font-bold truncate mt-1" x-text="item.description"></p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 ml-3">
                                        <span class="text-rose-600 font-black text-sm" x-text="'-' + formatRupiah(item.amount)"></span>

                                        <!-- Step 1: Tap trash icon to request delete -->
                                        <button type="button"
                                                x-show="pendingDeleteExpenseId !== item.id"
                                                @click="deleteExpense(item.id)"
                                                class="text-slate-400 hover:text-rose-600 p-1.5 rounded-lg hover:bg-rose-50 transition"
                                                title="Hapus pengeluaran">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>

                                        <!-- Step 2: Inline confirm (replaces button, no page freeze) -->
                                        <div x-show="pendingDeleteExpenseId === item.id" class="flex items-center gap-1">
                                            <button type="button" @click="pendingDeleteExpenseId = null"
                                                    class="text-[10px] font-bold px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition">
                                                Batal
                                            </button>
                                            <button type="button" @click="confirmDeleteExpense()"
                                                    class="text-[10px] font-bold px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition">
                                                Yakin?
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Summary footer -->
                        <div class="border-t border-slate-100 pt-3 flex justify-between items-center font-extrabold text-sm text-slate-700">
                            <span>Total Pengeluaran Hari Ini</span>
                            <span class="text-rose-600 text-base" x-text="formatRupiah(expenses.reduce((sum, e) => sum + e.amount, 0))"></span>
                        </div>
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
                        try {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        } catch (e) {
                            console.error('Print failed:', e);
                        }
                    }, 300);
                };
                
                iframe.src = '/kasir/transactions/' + transactionId + '/print';
            }
        </script>
    </x-app-layout>
