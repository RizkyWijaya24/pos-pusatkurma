    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Mesin Kasir / POS') }}
            </h2>
        </x-slot>

        {{-- ============================================================
             KASIR DASHBOARD - DARK MODE STYLE OVERRIDES
             Higher specificity using html.dark to override Tailwind
             ============================================================ --}}
        <style>
            /* ── KASIR DASHBOARD METRIC CARDS DARK MODE (SOLID COLORS) ── */
            html.dark #pos-card-omset {
                background: #0d2b1a !important; /* Solid dark green */
                border-color: rgba(52, 211, 153, 0.25) !important;
            }
            html.dark #pos-card-omset .text-emerald-700 {
                color: #a7f3d0 !important;
            }

            html.dark #pos-card-expenses {
                background: #350914 !important; /* Solid dark rose */
                border-color: rgba(251, 113, 133, 0.25) !important;
            }
            html.dark #pos-card-expenses .text-rose-700 {
                color: #fecdd3 !important;
            }

            /* Dynamic state backgrounds for net cash card */
            html.dark #pos-card-net[style*="#ecfeff"] {
                background: #09252c !important; /* Solid dark cyan */
                border-color: rgba(6, 182, 212, 0.25) !important;
            }
            html.dark #pos-card-net[style*="#fffbeb"] {
                background: #301705 !important; /* Solid dark amber */
                border-color: rgba(251, 191, 36, 0.25) !important;
            }
        </style>

        <!-- Global JS Error Catcher to diagnose hosting issues -->
        <script>
            window.onerror = function(message, source, lineno, colno, error) {
                alert("POS JS Error: " + message + "\nSource: " + source + "\nLine: " + lineno + "\nCol: " + colno);
                return false;
            };
        </script>

        <!-- JSON Data Islands for Security and HTML parsing safety -->
        <script type="application/json" id="products-data">@json($products)</script>
        <script type="application/json" id="transactions-data">@json($todayTransactionsMapped)</script>
        <script type="application/json" id="expenses-data">@json($todayExpensesMapped)</script>
        <script type="application/json" id="categories-data">@json($categories)</script>
        <script type="application/json" id="affiliates-data">@json($affiliates)</script>
        <script type="application/json" id="unpaid-dp-data">@json($unpaidDpMapped ?? [])</script>

        <!-- Alpine.js POS State Definition -->
        <script>
        function kasirPosState() {
            return {
                search: '',
                activeCategory: 'Semua',
                
                products: JSON.parse(document.getElementById('products-data').textContent),
            productsVersion: @json($productsVersion ?? '1'),
            isSyncingProducts: false,
            todayTransactions: JSON.parse(document.getElementById('transactions-data').textContent),
            unpaidDpTransactions: JSON.parse(document.getElementById('unpaid-dp-data')?.textContent || '[]'),
            extraDpCashToday: {{ (float) ($extraDpCashToday ?? 0) }},
            expenses: JSON.parse(document.getElementById('expenses-data').textContent),
            categories: JSON.parse(document.getElementById('categories-data').textContent),
            affiliates: JSON.parse(document.getElementById('affiliates-data')?.textContent || '[]'),
            selectedAffiliateId: '',
            paxCount: '',
            getSelectedAffiliateRate() {
                const aff = this.affiliates.find(a => a.id == this.selectedAffiliateId);
                return aff ? aff.commission_rate_percentage : 0;
            },
            
            showExpenseModal: false,
            newExpense: { amount: '', category: 'Operasional Toko', description: '' },
            pendingDeleteExpenseId: null,

            showDpModal: false,
            dpSearch: '',
            selectedDpTrx: null,
            dpSettleAmount: '',
            dpSettleMethod: 'Cash',
            dpSettleNote: '',
            isSettlingDp: false,
            settledDpResult: null,
            
            showWaModal: false,
            waNumber: '{{ config('app.whatsapp_report_number', '') }}',
            waMessage: '',
            
            cart: (function() {
                try {
                    const saved = localStorage.getItem('pos_kasir_cart');
                    return saved ? JSON.parse(saved) : [];
                } catch(e) {
                    return [];
                }
            })(),
            discount: (function() {
                try {
                    return localStorage.getItem('pos_kasir_discount') || '';
                } catch(e) {
                    return '';
                }
            })(),
            showReceipt: false,
            showQtyModal: false,
            qtyProduct: null,
            qtyValue: '',
            qtyInputMode: 'kg', // 'kg' or 'gram'
            qtyMode: 'add',
            qtyCartId: null,
            paymentMethod: '',
            cashReceived: '',
            splitCashAmount: '',
            splitNonCashMethod: 'QRIS', // 'QRIS' or 'Debit'
            splitNonCashAmount: 0,
            customerName: '',
            customerPhone: '',
            dpAmount: '',
            dpPaymentMethod: 'Cash',
            dpNote: '',
            showCustomerFields: false,
            waCustomerPhone: '',
            isSharingPdf: false,
            printMethod: 'iframe',

            checkoutStage: 0, // 0 = closed, 1 = details entry, 2 = loading/processing, 3 = success/receipt
            isSubmitting: false,
            statusMessage: '',
            cardDigits: '',
            latestTransaction: null,

            init() {
                // Auto-save cart & discount to localStorage whenever modified
                this.$watch('cart', val => {
                    try {
                        localStorage.setItem('pos_kasir_cart', JSON.stringify(val || []));
                    } catch(e) {}
                });
                this.$watch('discount', val => {
                    try {
                        localStorage.setItem('pos_kasir_discount', val || '');
                    } catch(e) {}
                });

                // Sinkronkan produk & stok secara otomatis saat tab aktif atau setiap 15 detik
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.syncProductsCatalog(false);
                    }
                });
                window.addEventListener('focus', () => {
                    this.syncProductsCatalog(false);
                });
                setInterval(() => {
                    if (!document.hidden) {
                        this.syncProductsCatalog(false);
                    }
                }, 15000);
            },

            async syncProductsCatalog(manual = false) {
                if (this.isSyncingProducts) return;
                this.isSyncingProducts = true;
                try {
                    const params = new URLSearchParams({
                        version: this.productsVersion || '',
                    });
                    if (manual) {
                        params.set('force', '1');
                    }

                    const res = await fetch('{{ route("kasir.products.sync") }}?' + params.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    if (!data.success) return;

                    if (data.changed) {
                        const prevIds = new Set((this.products || []).map(p => p.id));
                        const nextProducts = Array.isArray(data.products) ? data.products : [];
                        const addedCount = nextProducts.filter(p => !prevIds.has(p.id)).length;

                        this.products = nextProducts;
                        if (Array.isArray(data.categories)) {
                            this.categories = data.categories;
                        }
                        this.productsVersion = data.version || this.productsVersion;

                        if (addedCount > 0) {
                            this.showToast(addedCount + ' produk baru masuk ke kasir!', 'success');
                        } else if (manual) {
                            this.showToast('Katalog produk & stok berhasil diperbarui.', 'success');
                        }
                    } else if (manual) {
                        this.showToast('Katalog produk sudah terbaru.', 'info');
                    }
                } catch (e) {
                    if (manual) {
                        this.showToast('Gagal menyinkronkan katalog produk.', 'warning');
                    }
                } finally {
                    this.isSyncingProducts = false;
                }
            },

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
                if (!text) return false;
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



            calculateSearchScore(product, rawQuery) {
                if (!rawQuery) return 1;
                const q = rawQuery.toLowerCase().trim();
                if (!q) return 1;

                const name = (product.name || '').toLowerCase().trim();
                const sku = (product.sku || '').toLowerCase().trim();

                // 1. Exact match (Prioritas Tertinggi #1)
                if (name === q) return 100000;
                if (sku === q) return 95000;

                // 2. Awalan nama persis sama (Starts with prefix)
                if (name.startsWith(q)) {
                    // Semakin mendekati panjang query, semakin tinggi skor
                    return 80000 - (name.length - q.length) * 10;
                }
                if (sku.startsWith(q)) {
                    return 75000 - (sku.length - q.length) * 10;
                }

                // 3. Substring utuh di nama (Contains query)
                const nameSubIndex = name.indexOf(q);
                if (nameSubIndex !== -1) {
                    // Cek apakah kecocokan ada di batas awal kata (word boundary)
                    const isWordBoundary = nameSubIndex === 0 || name[nameSubIndex - 1] === ' ';
                    const boundaryBonus = isWordBoundary ? 20000 : 0;
                    return 50000 + boundaryBonus - (nameSubIndex * 150) - (name.length - q.length) * 5;
                }

                if (sku.indexOf(q) !== -1) {
                    return 40000 - (sku.indexOf(q) * 50);
                }

                // FAST-PATH: Jika query adalah angka murni (barcode scanner atau kode SKU),
                // jangan buang CPU di loop Levenshtein distance
                if (/^\d+$/.test(q)) {
                    return 0; // Karena sudah dicek exact/prefix/substring SKU di atas dan tidak cocok
                }

                // 4. Multi-kata & Pencarian Fuzzy per kata dengan toleransi typo
                const qWords = q.split(/\s+/).filter(Boolean);
                if (qWords.length === 0) return 0;

                const nameWords = name.split(/\s+/).filter(Boolean);
                const skuWords = sku.split(/\s+/).filter(Boolean);

                let totalWordScore = 0;
                let matchedWordsCount = 0;
                const usedNameWordIndices = new Set();
                const usedSkuWordIndices = new Set();

                for (let qi = 0; qi < qWords.length; qi++) {
                    const qWord = qWords[qi];
                    let bestScore = 0;
                    let bestType = null;
                    let bestIdx = -1;

                    // Cocokkan terhadap setiap kata di nama produk yang BELUM dipasangkan
                    for (let i = 0; i < nameWords.length; i++) {
                        if (usedNameWordIndices.has(i)) continue;
                        const tWord = nameWords[i];

                        // Kata persis sama
                        if (tWord === qWord) {
                            const score = 10000 - (i * 100);
                            if (score > bestScore) {
                                bestScore = score;
                                bestType = 'name';
                                bestIdx = i;
                            }
                            continue;
                        }

                        // Kata diawali query
                        if (tWord.startsWith(qWord)) {
                            const score = 7000 - (tWord.length - qWord.length) * 30 - (i * 100);
                            if (score > bestScore) {
                                bestScore = score;
                                bestType = 'name';
                                bestIdx = i;
                            }
                            continue;
                        }

                        // Query lebih panjang dan memuat kata target
                        if (qWord.startsWith(tWord) && tWord.length >= 3) {
                            const score = 5000 - (qWord.length - tWord.length) * 40 - (i * 100);
                            if (score > bestScore) {
                                bestScore = score;
                                bestType = 'name';
                                bestIdx = i;
                            }
                            continue;
                        }

                        // Substring di dalam kata
                        if (tWord.includes(qWord)) {
                            const score = 4000 - (tWord.length - qWord.length) * 20 - (i * 100);
                            if (score > bestScore) {
                                bestScore = score;
                                bestType = 'name';
                                bestIdx = i;
                            }
                            continue;
                        }

                        // Toleransi typo / salah ketik via Levenshtein distance (hanya untuk kata >= 3 karakter)
                        if (qWord.length >= 3) {
                            const maxAllowedTypo = qWord.length <= 4 ? 1 : 2;
                            if (Math.abs(tWord.length - qWord.length) <= maxAllowedTypo) {
                                const dist = this.levenshteinDistance(qWord, tWord);
                                if (dist <= maxAllowedTypo) {
                                    const similarity = 1 - (dist / Math.max(qWord.length, tWord.length));
                                    const score = Math.round(3000 * similarity) - (dist * 600) - (i * 100);
                                    if (score > bestScore) {
                                        bestScore = score;
                                        bestType = 'name';
                                        bestIdx = i;
                                    }
                                }
                            }
                        }
                    }

                    // Cocokkan juga terhadap SKU bila belum ada skor kata
                    if (bestScore === 0) {
                        for (let j = 0; j < skuWords.length; j++) {
                            if (usedSkuWordIndices.has(j)) continue;
                            const sWord = skuWords[j];
                            if (sWord === qWord || sWord.startsWith(qWord) || sWord.includes(qWord)) {
                                const score = 2500;
                                if (score > bestScore) {
                                    bestScore = score;
                                    bestType = 'sku';
                                    bestIdx = j;
                                }
                            }
                        }
                    }

                    if (bestScore > 0) {
                        matchedWordsCount++;
                        totalWordScore += bestScore;
                        if (bestType === 'name') usedNameWordIndices.add(bestIdx);
                        if (bestType === 'sku') usedSkuWordIndices.add(bestIdx);
                    }
                }

                // Seluruh kata kunci yang diketik wajib cocok (dengan toleransi typo)
                if (matchedWordsCount === qWords.length) {
                    // Bonus jika urutan kata di nama produk sama dengan yang diketik
                    let orderBonus = 0;
                    let lastIdx = -1;
                    let inOrder = true;
                    for (const qWord of qWords) {
                        const idx = name.indexOf(qWord);
                        if (idx !== -1) {
                            if (idx < lastIdx) {
                                inOrder = false;
                                break;
                            }
                            lastIdx = idx;
                        }
                    }
                    if (inOrder && lastIdx !== -1) {
                        orderBonus = 4000;
                    }

                    const lengthPenalty = Math.min(1000, name.length * 5);
                    return Math.max(1, 10000 + totalWordScore + orderBonus - lengthPenalty);
                }

                return 0; // Tidak cocok
            },

            get filteredProducts() {
                const currentActive = String(this.activeCategory || 'semua').trim().toLowerCase();
                const trimmedSearch = (this.search || '').trim();

                // 1. Filter kategori terlebih dahulu
                const categoryFiltered = this.products.filter(p => {
                    const productCat = p.category ? String(p.category).trim().toLowerCase() : '';
                    return currentActive === 'semua' || productCat === currentActive;
                });

                // 2. Jika tidak ada pencarian, tampilkan urutan standar katalog
                if (!trimmedSearch) {
                    return categoryFiltered;
                }

                // 3. Hitung skor relevansi & kemiripan pencarian
                const scoredList = [];
                for (let i = 0; i < categoryFiltered.length; i++) {
                    const p = categoryFiltered[i];
                    const score = this.calculateSearchScore(p, trimmedSearch);
                    if (score > 0) {
                        scoredList.push({ product: p, score: score, origIndex: i });
                    }
                }

                // 4. Urutkan berdasarkan skor relevansi tertinggi (paling mirip di urutan teratas)
                scoredList.sort((a, b) => {
                    if (b.score !== a.score) {
                        return b.score - a.score;
                    }
                    return a.origIndex - b.origIndex;
                });

                return scoredList.map(item => item.product);
            },

            addToCart(product) {
                if (product.price_unit === 'gram' || product.price_unit === 'kg') {
                    this.qtyProduct = product;
                    this.qtyValue = '';
                    this.qtyInputMode = (product.price_unit || 'kg').toLowerCase();
                    this.qtyMode = 'add';
                    this.qtyCartId = null;
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
                        this.cart.push({
                            ...product,
                            cart_id: 'cart_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                            qty: 1
                        });
                    }
                }
            },

            switchQtyInputMode(newMode) {
                if (this.qtyInputMode === newMode) return;
                const val = parseFloat(this.qtyValue);
                if (!isNaN(val) && val > 0) {
                    if (newMode === 'kg') {
                        // Dari gram ke kg
                        this.qtyValue = parseFloat((val / 1000).toFixed(4)).toString();
                    } else {
                        // Dari kg ke gram
                        this.qtyValue = Math.round(val * 1000).toString();
                    }
                }
                this.qtyInputMode = newMode;
            },

            getEffectiveQty() {
                const rawVal = parseFloat(this.qtyValue);
                if (isNaN(rawVal) || rawVal <= 0) return 0;

                const baseUnit = (this.qtyProduct?.price_unit || '').toLowerCase();
                if (baseUnit === 'kg' && this.qtyInputMode === 'gram') {
                    return rawVal / 1000;
                } else if (baseUnit === 'gram' && this.qtyInputMode === 'kg') {
                    return rawVal * 1000;
                }
                return rawVal;
            },

            getQtyEquivalentText() {
                const rawVal = parseFloat(this.qtyValue);
                if (isNaN(rawVal) || rawVal <= 0) return '';

                const baseUnit = (this.qtyProduct?.price_unit || '').toLowerCase();
                if (this.qtyInputMode === 'gram') {
                    return `= ${(rawVal / 1000).toFixed(3).replace(/\.?0+$/, '')} kg`;
                } else {
                    return `= ${(rawVal * 1000).toLocaleString('id-ID')} gram`;
                }
            },

            getQtyEstimatedPriceText() {
                const effQty = this.getEffectiveQty();
                if (effQty <= 0 || !this.qtyProduct) return '';
                const price = (this.qtyProduct.selling_price || this.qtyProduct.price || 0) * effQty;
                return 'Rp ' + Math.round(price).toLocaleString('id-ID');
            },

            confirmQty() {
                const qty = this.getEffectiveQty();
                if (qty <= 0) {
                    this.showToast('Kuantitas tidak valid!', 'warning');
                    return;
                }

                if (this.qtyMode === 'edit') {
                    const item = this.cart.find(c => c.cart_id === this.qtyCartId);
                    if (item) {
                        if (qty > this.qtyProduct.stock) {
                            this.showToast('Stok tidak mencukupi! Tersedia: ' + this.formatStock(this.qtyProduct.stock, this.qtyProduct.price_unit), 'warning');
                            return;
                        }
                        item.qty = qty;
                    }
                } else {
                    // Always add as a new separate item row for weight products
                    if (qty > this.qtyProduct.stock) {
                        this.showToast('Stok tidak mencukupi! Tersedia: ' + this.formatStock(this.qtyProduct.stock, this.qtyProduct.price_unit), 'warning');
                        return;
                    }
                    this.cart.push({
                        ...this.qtyProduct,
                        cart_id: 'cart_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                        qty: qty
                    });
                }

                this.showQtyModal = false;
                this.qtyProduct = null;
                this.qtyValue = '';
                this.qtyCartId = null;
            },

            updateQty(cart_id, qty) {
                const item = this.cart.find(c => c.cart_id === cart_id);
                if (item) {
                    const product = this.products.find(p => p.id === item.id);
                    if (qty <= 0) {
                        this.cart = this.cart.filter(c => c.cart_id !== cart_id);
                        this.showToast(item.name + ' dihapus dari keranjang.', 'info');
                    } else if (!product || qty <= product.stock) {
                        item.qty = parseFloat(qty);
                    } else {
                        this.showToast('Stok tidak mencukupi!', 'warning');
                    }
                }
            },

            getItemPrice(item) {
                if (item.price_tiers && item.price_tiers.length > 0) {
                    const qty = parseFloat(item.qty) || 0;
                    const matchingTier = item.price_tiers.find(tier => {
                        const min = parseFloat(tier.min_qty) || 0;
                        const max = tier.max_qty !== null && tier.max_qty !== '' ? parseFloat(tier.max_qty) : Infinity;
                        return qty >= min && qty <= max;
                    });
                    if (matchingTier) {
                        return parseFloat(matchingTier.price);
                    }
                }
                return parseFloat(item.price);
            },

            get subtotal() {
                return this.cart.reduce((sum, item) => sum + Math.round((this.getItemPrice(item) * item.qty) / 500) * 500, 0);
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
                return Math.round(this.discountedSubtotal / 500) * 500;
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
                // Hitung total tunai fisik masuk (termasuk porsi Cash dari transaksi SPLIT, DP hari ini, dan pelunasan DP Cash untuk order hari sebelumnya)
                const cashSales = this.todayTransactions.reduce((sum, t) => {
                    if (t.cash_in_amount !== undefined && t.cash_in_amount !== null) {
                        return sum + parseFloat(t.cash_in_amount);
                    }
                    const method = t.payment_method || 'Cash';
                    if (method === 'Cash') {
                        return sum + parseFloat(t.total_price);
                    } else if (method.startsWith('SPLIT:')) {
                        const match = method.match(/Cash\s*Rp\s*([\d\.]+)/i);
                        if (match) {
                            const val = parseFloat(match[1].replace(/\./g, '')) || 0;
                            return sum + val;
                        }
                    }
                    return sum;
                }, 0) + (parseFloat(this.extraDpCashToday) || 0);
                return cashSales - this.todayExpensesTotal;
            },

            processCheckout(method) {
                if (this.cart.length === 0) {
                    this.showToast('Keranjang masih kosong!', 'warning');
                    return;
                }
                this.paymentMethod = method;
                this.cashReceived = '';
                this.splitCashAmount = '';
                this.splitNonCashMethod = 'QRIS';
                this.customerName = '';
                this.customerPhone = '';
                this.dpAmount = '';
                this.dpPaymentMethod = 'Cash';
                this.dpNote = '';
                this.waCustomerPhone = '';
                this.showCustomerFields = (method === 'DP');
                this.cardDigits = '';
                this.latestTransaction = null;
                if (method === 'QRIS' || method === 'Debit') {
                    this.cashReceived = this.total;
                }
                this.checkoutStage = 1; // Transition to Stage 1: Input details
                this.showReceipt = true;
            },

            finishTransaction() {
                if (this.cart.length === 0 || this.isSubmitting) return;

                if (this.paymentMethod === 'DP') {
                    if (!this.customerName.trim()) {
                        this.showToast('Nama pemesan wajib diisi untuk transaksi DP!', 'warning');
                        return;
                    }
                    const dpVal = parseInt(this.dpAmount || 0, 10);
                    if (!dpVal || dpVal <= 0 || dpVal >= this.total) {
                        this.showToast('Nominal DP harus lebih dari Rp 0 dan kurang dari total tagihan!', 'warning');
                        return;
                    }
                }

                let finalPaymentMethod = this.paymentMethod;
                if (this.paymentMethod === 'SPLIT') {
                    const cashPart = parseFloat(this.splitCashAmount) || 0;
                    const nonCashPart = Math.max(0, this.total - cashPart);
                    finalPaymentMethod = `SPLIT: Cash Rp ${this.formatNumberOnly(cashPart)} + ${this.splitNonCashMethod} Rp ${this.formatNumberOnly(nonCashPart)}`;
                }

                this.isSubmitting = true;
                this.checkoutStage = 2; // Transition to Stage 2: Processing
                this.statusMessage = 'Memverifikasi transaksi & mengurangi stok...';

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
                        payment_method: finalPaymentMethod,
                        customer_name: this.customerName.trim() || null,
                        customer_phone: this.customerPhone.trim() || null,
                        dp_amount: this.paymentMethod === 'DP' ? parseInt(this.dpAmount || 0, 10) : null,
                        dp_payment_method: this.paymentMethod === 'DP' ? this.dpPaymentMethod : null,
                        dp_note: this.paymentMethod === 'DP' ? (this.dpNote.trim() || null) : null,
                        affiliate_id: this.selectedAffiliateId || null,
                        pax_count: this.paxCount ? parseInt(this.paxCount) : 0,
                        items: this.cart.map(item => ({
                            id: item.id,
                            name: item.name,
                            qty: item.qty,
                            price_unit: item.price_unit,
                            price: this.getItemPrice(item)
                        }))
                    })
                })
                .then(async res => {
                    let data = {};
                    try {
                        data = await res.json();
                    } catch(e) {
                        data = { success: false, message: 'Respon server tidak valid (' + res.status + ')' };
                    }
                    return { ok: res.ok, status: res.status, data };
                })
                .then(({ ok, status, data }) => {
                    this.isSubmitting = false;
                    if (ok && data.success) {
                        // Prepend new transaction to Alpine list
                        this.todayTransactions.unshift({
                            id: data.transaction.id,
                            time: data.transaction.time,
                            transaction_code: data.transaction.transaction_code,
                            items_summary: data.transaction.items_summary,
                            payment_method: data.transaction.payment_method,
                            payment_status: data.transaction.payment_status,
                            customer_name: data.transaction.customer_name,
                            customer_phone: data.transaction.customer_phone,
                            total_price: data.transaction.total_price,
                            discount: data.transaction.discount,
                            paid_amount: data.transaction.paid_amount,
                            remaining_amount: data.transaction.remaining_amount,
                            cash_in_amount: data.transaction.cash_in_amount,
                            digital_receipt_url: data.transaction.digital_receipt_url,
                            digital_receipt_download_url: data.transaction.digital_receipt_download_url
                        });

                        if (data.transaction.payment_method === 'DP' && data.transaction.payment_status === 'unpaid') {
                            this.unpaidDpTransactions.unshift({
                                id: data.transaction.id,
                                created_at_formatted: data.transaction.time || 'Baru saja',
                                transaction_code: data.transaction.transaction_code,
                                items_summary: data.transaction.items_summary,
                                customer_name: data.transaction.customer_name || '-',
                                customer_phone: data.transaction.customer_phone || '-',
                                total_price: parseFloat(data.transaction.total_price || 0),
                                paid_amount: Math.round(data.transaction.paid_amount || 0),
                                remaining_amount: Math.round(data.transaction.remaining_amount || 0),
                                payment_status: data.transaction.payment_status,
                                digital_receipt_url: data.transaction.digital_receipt_url,
                                digital_receipt_download_url: data.transaction.digital_receipt_download_url,
                                installments: [{
                                    id: Date.now(),
                                    amount: Math.round(data.transaction.paid_amount || 0),
                                    payment_method: this.dpPaymentMethod || 'Cash',
                                    note: this.dpNote ? this.dpNote.trim() : 'DP Awal (Kasir)',
                                    date: data.transaction.time || 'Hari ini'
                                }]
                            });
                        }

                        // Sync local product stock immediately
                        if (Array.isArray(data.transaction.items_sold)) {
                            data.transaction.items_sold.forEach(sold => {
                                const prod = this.products.find(p => p.id === sold.id);
                                if (prod) {
                                    prod.stock = Math.max(0, parseFloat(prod.stock || 0) - parseFloat(sold.qty || 0));
                                }
                            });
                        }
                        
                        // Save latest transaction details for receipt screen
                        this.latestTransaction = data.transaction;
                        this.waCustomerPhone = data.transaction.customer_phone || this.customerPhone || '';
                        
                        // Reset cart, keep staging info
                        this.cart = [];
                        this.checkoutStage = 3; // Transition to Stage 3: Success view
                        this.showToast('Transaksi ' + data.transaction.transaction_code + ' berhasil!', 'success');
                    } else {
                        // Ambil pesan error spesifik jika stok tidak cukup atau validasi gagal
                        let errorMsg = data.message || 'Gagal mencatat transaksi!';
                        if (data.errors && typeof data.errors === 'object') {
                            const errValues = Object.values(data.errors).flat();
                            if (errValues.length > 0) {
                                errorMsg = errValues[0];
                            }
                        }
                        this.showToast(errorMsg, 'error');
                        this.checkoutStage = 1;
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    console.error('Gagal mencatat transaksi:', err);
                    this.showToast('Terjadi kesalahan jaringan atau koneksi!', 'error');
                    this.checkoutStage = 1;
                });
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
                this.splitCashAmount = '';
                this.splitNonCashMethod = 'QRIS';
                this.paymentMethod = '';
                this.customerName = '';
                this.customerPhone = '';
                this.dpAmount = '';
                this.dpPaymentMethod = 'Cash';
                this.dpNote = '';
                this.showCustomerFields = false;
                this.waCustomerPhone = '';
                this.cardDigits = '';
                this.selectedAffiliateId = '';
                this.paxCount = '';
                this.latestTransaction = null;
            },

            cancelCheckout() {
                this.showReceipt = false;
                this.checkoutStage = 0;
                this.cashReceived = '';
                this.splitCashAmount = '';
                this.splitNonCashMethod = 'QRIS';
                this.paymentMethod = '';
                this.customerName = '';
                this.customerPhone = '';
                this.dpAmount = '';
                this.dpPaymentMethod = 'Cash';
                this.dpNote = '';
                this.showCustomerFields = false;
                this.waCustomerPhone = '';
                this.cardDigits = '';
                this.latestTransaction = null;
            },

            normalizeWaPhone(raw) {
                if (!raw) return '';
                let digits = String(raw).replace(/[^0-9]/g, '');
                if (digits.startsWith('0')) {
                    digits = '62' + digits.substring(1);
                } else if (digits.startsWith('8')) {
                    digits = '62' + digits;
                }
                return digits;
            },

            async shareDigitalReceiptPdf(trx) {
                if (!trx || !trx.digital_receipt_url || this.isSharingPdf) return;
                this.isSharingPdf = true;
                const fileName = 'Struk-' + trx.transaction_code + '.pdf';

                try {
                    const res = await fetch(trx.digital_receipt_url, { headers: { 'Accept': 'application/pdf' } });
                    if (!res.ok) throw new Error('Gagal mengunduh file PDF.');
                    const blob = await res.blob();
                    const pdfFile = new File([blob], fileName, { type: 'application/pdf' });

                    if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                        await navigator.share({
                            title: 'Struk Digital ' + trx.transaction_code,
                            files: [pdfFile],
                        });
                        this.showToast('File Struk PDF siap dikirim!', 'success');
                        return;
                    }

                    // Fallback desktop: download PDF langsung agar siap dilampirkan ke WA / email
                    const blobUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(() => URL.revokeObjectURL(blobUrl), 2000);
                    this.showToast('PDF diunduh (' + fileName + '). Silakan lampirkan file ke chat customer.', 'success');
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                    window.open(trx.digital_receipt_download_url || trx.digital_receipt_url, '_blank');
                } finally {
                    this.isSharingPdf = false;
                }
            },

            openCustomerWaChat(trx, phoneOverride = null) {
                if (!trx) return;
                const cleanPhone = this.normalizeWaPhone(phoneOverride !== null ? phoneOverride : (this.waCustomerPhone || trx.customer_phone));
                const greeting = trx.customer_name
                    ? `Halo Kak ${trx.customer_name}, berikut kami kirimkan struk PDF resmi untuk transaksi #${trx.transaction_code}.`
                    : `Halo Kak, berikut kami kirimkan struk PDF resmi untuk transaksi #${trx.transaction_code}.`;
                const url = cleanPhone
                    ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(greeting)}`
                    : `https://wa.me/?text=${encodeURIComponent(greeting)}`;
                window.open(url, '_blank');
            },

            copyDigitalReceiptLink(trx) {
                if (!trx || !trx.digital_receipt_url) {
                    this.showToast('Link struk digital belum tersedia.', 'warning');
                    return;
                }
                const text = trx.digital_receipt_url;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text)
                        .then(() => this.showToast('Link struk digital berhasil disalin!', 'success'))
                        .catch(() => this.fallbackCopyText(text));
                } else {
                    this.fallbackCopyText(text);
                }
            },

            fallbackCopyText(text) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    this.showToast('Link struk digital berhasil disalin!', 'success');
                } catch (e) {
                    this.showToast('Gagal menyalin link struk.', 'error');
                }
                document.body.removeChild(ta);
            },

            printReceiptAction(transaction) {
                if (!transaction) return;
                if (this.printMethod === 'bluetooth') {
                    if (typeof window.printDirectBluetooth === 'function') {
                        window.printDirectBluetooth(transaction);
                    } else {
                        this.showToast('Driver Bluetooth belum siap!', 'error');
                    }
                } else {
                    if (typeof window.printReceipt === 'function') {
                        window.printReceipt(transaction.id);
                    } else {
                        this.showToast('Driver Cetak Iframe belum siap!', 'error');
                    }
                }
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
            },

            formatNumberOnly(num) {
                if (num === null || num === undefined) return '0';
                return Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            },

            formatStock(stock, unit) {
                if (unit === 'gram' && stock >= 1000) {
                    return (stock / 1000).toFixed(2).replace(/\.?0+$/, '') + ' kg';
                }
                return stock + ' ' + unit;
            },

            formatQty(qty, unit) {
                if (unit === 'gram' && qty >= 1000) {
                    return (qty / 1000).toFixed(2).replace(/\.?0+$/, '') + ' kg';
                }
                return qty + ' ' + unit;
            },

            generateWaMessage() {
                const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                const cashierName = '{{ addslashes(auth()->user()->name) }}';
                const branch = '{{ addslashes(auth()->user()->branch ?? "Pusat Cianjur") }}';
                
                let msg = `*LAPORAN HARIAN KASIR - PUSAT KURMA*\n`;
                msg += `--------------------------------------\n`;
                msg += `*Tanggal:* ${today}\n`;
                msg += `*Kasir:* ${cashierName}\n`;
                msg += `*Cabang:* ${branch}\n`;
                msg += `--------------------------------------\n\n`;
                
                msg += `*RINGKASAN KAS BERSIH*\n`;
                msg += `*Omset Hari Ini:* ${this.formatRupiah(this.todaySalesTotal)}\n`;
                msg += `*Total Pengeluaran:* ${this.formatRupiah(this.todayExpensesTotal)}\n`;
                msg += `*Uang di Laci (Kas Bersih):* ${this.formatRupiah(this.todayNetCash)}\n`;
                msg += `--------------------------------------\n\n`;
                
                msg += `*RINCIAN METODE PEMBAYARAN*\n`;
                const methods = { 'Cash': 0, 'QRIS': 0, 'Debit': 0, 'Split': 0, 'DP': 0 };
                const methodCounts = { 'Cash': 0, 'QRIS': 0, 'Debit': 0, 'Split': 0, 'DP': 0 };
                
                this.todayTransactions.forEach(t => {
                    const m = t.payment_method || 'Cash';
                    if (m === 'Cash' || m === 'QRIS' || m === 'Debit') {
                        methods[m] += parseFloat(t.total_price);
                        methodCounts[m]++;
                    } else if (m === 'DP') {
                        methods['DP'] += parseFloat(t.paid_amount ?? t.total_price ?? 0);
                        methodCounts['DP']++;
                    } else if (m.startsWith('SPLIT:')) {
                        methods['Split'] += parseFloat(t.total_price);
                        methodCounts['Split']++;
                        
                        // Parse split amounts into Cash and non-cash totals
                        const cashMatch = m.match(/Cash\s*Rp\s*([\d\.]+)/i);
                        if (cashMatch) {
                            const cVal = parseFloat(cashMatch[1].replace(/\./g, '')) || 0;
                            methods['Cash'] += cVal;
                        }
                        const qrisMatch = m.match(/QRIS\s*Rp\s*([\d\.]+)/i);
                        if (qrisMatch) {
                            const qVal = parseFloat(qrisMatch[1].replace(/\./g, '')) || 0;
                            methods['QRIS'] += qVal;
                        }
                        const debitMatch = m.match(/Debit\s*Rp\s*([\d\.]+)/i);
                        if (debitMatch) {
                            const dVal = parseFloat(debitMatch[1].replace(/\./g, '')) || 0;
                            methods['Debit'] += dVal;
                        }
                    } else {
                        // Fallback default
                        methods['Cash'] += parseFloat(t.total_price);
                        methodCounts['Cash']++;
                    }
                });
                
                msg += `• *Tunai (Cash):* ${this.formatRupiah(methods['Cash'])}\n`;
                msg += `• *QRIS:* ${this.formatRupiah(methods['QRIS'])}\n`;
                msg += `• *Debit:* ${this.formatRupiah(methods['Debit'])}\n`;
                if (methodCounts['DP'] > 0) {
                    msg += `• *DP (Uang Muka):* ${methodCounts['DP']} trx (Masuk ${this.formatRupiah(methods['DP'])})\n`;
                }
                if (methodCounts['Split'] > 0) {
                    msg += `• *Transaksi Split (Kombinasi):* ${methodCounts['Split']} trx (${this.formatRupiah(methods['Split'])})\n`;
                }
                msg += `--------------------------------------\n\n`;
                
                msg += `*RINCIAN PENGELUARAN*\n`;
                if (this.expenses.length === 0) {
                    msg += `Tidak ada pengeluaran hari ini.\n`;
                } else {
                    this.expenses.forEach((e, index) => {
                        msg += `${index + 1}. [${e.time}] *${e.category}* - ${this.formatRupiah(e.amount)} (${e.description})\n`;
                    });
                }
                msg += `--------------------------------------\n\n`;
                
                // Calculate best sellers from todayTransactions
                const bestSellers = {};
                this.todayTransactions.forEach(t => {
                    if (!t.items_summary) return;
                    const parts = t.items_summary.split(', ');
                    parts.forEach(part => {
                        const trimmedPart = part.trim();
                        if (!trimmedPart) return;
                        
                        let name = trimmedPart;
                        let qty = 1.0;
                        let unit = 'pcs';
                        
                        const match = trimmedPart.match(/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)$/);
                        if (match) {
                            name = match[1].trim();
                            qty = parseFloat(match[2]);
                            unit = match[3].trim().toLowerCase();
                        }
                        
                        if (unit === 'gram' || unit === 'g') {
                            qty = qty / 1000;
                            unit = 'kg';
                        }
                        
                        if (!bestSellers[name]) {
                            bestSellers[name] = {
                                name: name,
                                qty: 0.0,
                                unit: unit,
                                count: 0
                            };
                        }
                        
                        bestSellers[name].qty += qty;
                        bestSellers[name].count += 1;
                    });
                });
                
                const sortedBestSellers = Object.values(bestSellers).sort((a, b) => {
                    if (b.count === a.count) {
                        return b.qty - a.qty;
                    }
                    return b.count - a.count;
                }).slice(0, 5);
                
                msg += `*TOP 5 PRODUK TERLARIS HARI INI*\n`;
                if (sortedBestSellers.length === 0) {
                    msg += `Belum ada penjualan produk hari ini.\n`;
                } else {
                    sortedBestSellers.forEach((item, index) => {
                        const qtyStr = Number(item.qty.toFixed(2)).toString();
                        msg += `${index + 1}. *${item.name}*: ${qtyStr} ${item.unit} (${item.count}x terjual)\n`;
                    });
                }
                msg += `--------------------------------------\n\n`;
                msg += `_Laporan ini dibuat otomatis melalui sistem POS Pusat Kurma._`;
                
                this.waMessage = msg;
            },

            sendWaReport(toGroup = false) {
                let url = '';
                if (toGroup) {
                    url = `https://api.whatsapp.com/send?text=${encodeURIComponent(this.waMessage)}`;
                } else {
                    let number = this.waNumber.replace(/[^0-9]/g, '');
                    if (!number) {
                        this.showToast('Nomor WhatsApp tujuan harus diisi!', 'warning');
                        return;
                    }
                    if (number.startsWith('0')) {
                        number = '62' + number.slice(1);
                    }
                    url = `https://api.whatsapp.com/send?phone=${number}&text=${encodeURIComponent(this.waMessage)}`;
                }
                window.open(url, '_blank');
                this.showWaModal = false;
            },

            get filteredUnpaidDp() {
                const q = (this.dpSearch || '').toLowerCase().trim();
                if (!q) return this.unpaidDpTransactions;
                return this.unpaidDpTransactions.filter(trx => {
                    return (trx.transaction_code || '').toLowerCase().includes(q)
                        || (trx.customer_name || '').toLowerCase().includes(q)
                        || (trx.customer_phone || '').toLowerCase().includes(q)
                        || (trx.items_summary || '').toLowerCase().includes(q);
                });
            },

            openDpModal() {
                this.dpSearch = '';
                this.selectedDpTrx = null;
                this.settledDpResult = null;
                this.showDpModal = true;
            },

            selectDpTransaction(trx) {
                this.settledDpResult = null;
                this.selectedDpTrx = trx;
                this.dpSettleAmount = Math.max(1, Math.round(trx.remaining_amount || 0));
                this.dpSettleMethod = 'Cash';
                this.dpSettleNote = '';
            },

            async submitDpSettlement() {
                if (!this.selectedDpTrx || this.isSettlingDp) return;
                const amount = parseInt(this.dpSettleAmount || 0, 10);
                const remaining = Math.round(this.selectedDpTrx.remaining_amount || 0);
                if (!amount || amount <= 0) {
                    this.showToast('Nominal pelunasan harus lebih dari 0.', 'warning');
                    return;
                }
                if (amount > remaining) {
                    this.showToast('Nominal melebihi sisa tagihan (' + this.formatRupiah(remaining) + ').', 'warning');
                    return;
                }

                this.isSettlingDp = true;
                const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                const trxSnapshot = this.selectedDpTrx;
                const method = this.dpSettleMethod || 'Cash';

                try {
                    const res = await fetch(`/kasir/transactions/${trxSnapshot.id}/installments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            amount: amount,
                            payment_method: method,
                            note: this.dpSettleNote ? this.dpSettleNote.trim() : null
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.showToast(data.message || 'Gagal mencatat pembayaran sisa DP.', 'error');
                        return;
                    }

                    // Update todayTransactions if this DP order belongs to today's list; else add Cash portion to extraDpCashToday
                    const todayMatch = this.todayTransactions.find(t => t.id === trxSnapshot.id);
                    if (todayMatch) {
                        todayMatch.paid_amount = data.paid_amount;
                        todayMatch.remaining_amount = data.remaining_amount;
                        todayMatch.payment_status = data.payment_status;
                        todayMatch.digital_receipt_url = data.digital_receipt_url || todayMatch.digital_receipt_url;
                        todayMatch.digital_receipt_download_url = data.digital_receipt_download_url || todayMatch.digital_receipt_download_url;
                        if (method === 'Cash') {
                            todayMatch.cash_in_amount = (parseFloat(todayMatch.cash_in_amount || 0) + amount);
                        }
                    } else if (method === 'Cash') {
                        this.extraDpCashToday = (parseFloat(this.extraDpCashToday) || 0) + amount;
                    }

                    const updatedInstallments = [...(trxSnapshot.installments || [])];
                    if (data.installment) {
                        updatedInstallments.push(data.installment);
                    }

                    if (data.is_paid) {
                        this.unpaidDpTransactions = this.unpaidDpTransactions.filter(t => t.id !== trxSnapshot.id);
                    } else {
                        const idx = this.unpaidDpTransactions.findIndex(t => t.id === trxSnapshot.id);
                        if (idx !== -1) {
                            this.unpaidDpTransactions[idx] = {
                                ...this.unpaidDpTransactions[idx],
                                paid_amount: data.paid_amount,
                                remaining_amount: data.remaining_amount,
                                payment_status: data.payment_status,
                                installments: updatedInstallments,
                                digital_receipt_url: data.digital_receipt_url || this.unpaidDpTransactions[idx].digital_receipt_url,
                                digital_receipt_download_url: data.digital_receipt_download_url || this.unpaidDpTransactions[idx].digital_receipt_download_url
                            };
                        }
                    }

                    this.settledDpResult = {
                        ...trxSnapshot,
                        paid_amount: data.paid_amount,
                        remaining_amount: data.remaining_amount,
                        payment_status: data.payment_status,
                        is_paid: data.is_paid,
                        last_payment_amount: amount,
                        last_payment_method: method,
                        installments: updatedInstallments,
                        digital_receipt_url: data.digital_receipt_url || trxSnapshot.digital_receipt_url,
                        digital_receipt_download_url: data.digital_receipt_download_url || trxSnapshot.digital_receipt_download_url
                    };
                    this.selectedDpTrx = null;
                    this.showToast(data.message || 'Pembayaran sisa tagihan DP berhasil dicatat!', 'success');
                } catch (e) {
                    console.error(e);
                    this.showToast('Terjadi kesalahan jaringan saat memproses pelunasan.', 'error');
                } finally {
                    this.isSettlingDp = false;
                }
            }
        };
    }
    </script>

    <!-- Kasir POS UI Grid -->
    <div x-data="kasirPosState()" class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full max-w-full overflow-hidden">

            <!-- LEFT SIDE: Product Catalogue (8 cols on desktop, full on mobile) -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- Real-time Shift Summary / Ringkasan Kas Toko Hari Ini -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- Total Penjualan -->
                    <div id="pos-card-omset" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;" class="p-4 rounded-2xl flex flex-col gap-1 shadow-sm relative overflow-hidden select-none">
                        <span class="text-[9px] font-black text-emerald-700 uppercase tracking-wider">Omset Hari Ini</span>
                        <h4 class="text-base sm:text-lg font-black text-slate-800 leading-tight whitespace-nowrap" x-text="formatRupiah(todaySalesTotal)">Rp 0</h4>
                    </div>
                    <!-- Total Pengeluaran -->
                    <div id="pos-card-expenses" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1px solid #fecdd3;" class="p-4 rounded-2xl flex flex-col gap-1 shadow-sm relative overflow-hidden select-none">
                        <span class="text-[9px] font-black text-rose-700 uppercase tracking-wider">Total Pengeluaran</span>
                        <h4 class="text-base sm:text-lg font-black text-slate-800 leading-tight whitespace-nowrap" x-text="formatRupiah(todayExpensesTotal)">Rp 0</h4>
                    </div>
                    <!-- Uang di Laci / Kas Bersih -->
                    <div id="pos-card-net" :style="todayNetCash >= 0 ? 'background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border: 1px solid #a5f3fc;' : 'background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;'"
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

                    <!-- Button Pelunasan DP -->
                    <button type="button"
                            @click="openDpModal()"
                            class="shrink-0 px-4 py-2 text-xs font-bold rounded-xl bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200 transition duration-150 flex items-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                        </svg>
                        <span>Pelunasan DP</span>
                        <span x-show="unpaidDpTransactions.length > 0"
                              x-text="unpaidDpTransactions.length"
                              class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-600 text-white"></span>
                    </button>

                    <!-- Button Laporan WA -->
                    <button type="button" 
                            @click="generateWaMessage(); showWaModal = true;"
                            class="shrink-0 px-4 py-2 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition duration-150 flex items-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.488 1.449 5.412 1.451 5.928 0 10.751-4.82 10.754-10.748.002-2.873-1.116-5.573-3.149-7.608C17.628 4.214 14.93 3.093 12.005 3.093c-5.93 0-10.756 4.821-10.76 10.75-.001 1.993.52 3.94 1.508 5.662l-.99 3.61 3.733-.979zm11.238-7.73c-.302-.151-1.787-.881-2.056-.979-.269-.099-.465-.148-.659.15-.195.299-.754.979-.924 1.178-.17.199-.341.224-.643.075-.3-.15-1.268-.467-2.417-1.492-.893-.797-1.496-1.783-1.672-2.083-.176-.3-.019-.461.13-.61.135-.133.302-.35.453-.524.151-.174.2-.299.3-.498.101-.2.05-.375-.025-.524-.075-.15-.659-1.587-.902-2.172-.237-.57-.497-.493-.659-.501-.17-.008-.365-.01-.56-.01-.196 0-.517.073-.787.37-.27.299-1.031 1.008-1.031 2.459 0 1.452 1.054 2.853 1.202 3.053.148.2 2.074 3.167 5.024 4.444.702.304 1.25.485 1.678.621.705.224 1.347.193 1.854.117.565-.084 1.787-.73 2.039-1.436.252-.706.252-1.312.176-1.436-.076-.124-.271-.199-.573-.35z"/>
                        </svg>
                        Laporan WA
                    </button>

                    <!-- Search box & Sync button -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" 
                                x-model.debounce.150ms="search"
                                placeholder="Cari kurma atau SKU..." 
                                class="w-full pl-9 pr-8 py-2 text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                            <button type="button" 
                                x-show="search" 
                                @click="search = ''" 
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-600 transition" 
                                title="Hapus pencarian">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <button type="button"
                            @click="syncProductsCatalog(true)"
                            :disabled="isSyncingProducts"
                            title="Sinkronkan produk & stok terbaru"
                            class="shrink-0 p-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 text-slate-600 hover:text-emerald-700 transition duration-150 disabled:opacity-50">
                            <svg class="h-4 w-4" :class="isSyncingProducts ? 'animate-spin text-emerald-600' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 overflow-y-auto max-h-[calc(100vh-210px)] pb-32 pr-1" style="overflow-y: auto; max-height: calc(100vh - 210px); padding-bottom: 120px; -webkit-overflow-scrolling: touch;" translate="no">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" 
                            style="min-height: 340px; display: flex; flex-direction: column; justify-content: space-between;"
                            class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-emerald-200 active:scale-[0.98] transition duration-150 overflow-hidden group cursor-pointer select-none">
                            
                            <!-- Foto Produk: placeholder hijau selalu tampil, gambar overlay di atas -->
                            <div style="height: 128px; background-color: #1b4332; position: relative; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 4px; overflow: hidden; border-bottom: 1px solid rgba(241,245,249,0.5); flex-shrink: 0;" class="shrink-0">
                                <!-- Inisial: selalu ada sebagai layer bawah -->
                                <span style="font-size: 1.875rem; font-weight: 700; color: #d1fae5; letter-spacing: 0.1em; text-transform: uppercase; z-index: 1; line-height: 1;"
                                      x-text="product.name ? product.name.split(' ').filter(w => w.trim() !== '').slice(0, 2).map(w => w ? w[0] : '').join('').toUpperCase() : 'PK'"></span>
                                <span style="font-size: 9px; letter-spacing: 0.1em; color: rgba(52,211,153,0.8); font-weight: 700; text-transform: uppercase; z-index: 1;">Pusat Kurma</span>
                                <!-- Gambar: overlay di atas placeholder, sembunyi jika error -->
                                <img x-show="product.image_path"
                                     :src="product.image_path ? ('/storage/' + product.image_path) : ''"
                                     loading="lazy"
                                     decoding="async"
                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 2;"
                                     class="transition duration-300 group-hover:scale-105"
                                     onerror="this.style.display='none'"
                                     alt="Foto produk">
                            </div>

                            <!-- Info Produk -->
                            <div class="p-3 sm:p-4 flex flex-col gap-1.5 relative flex-grow shrink-0" style="display: flex; flex-direction: column; gap: 6px; padding: 12px; flex-grow: 1; flex-shrink: 0;">
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
                                    <template x-if="product.price_tiers && product.price_tiers.length > 0">
                                        <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded self-start mt-0.5 select-none">Harga Grosir Aktif</span>
                                    </template>
                                     <span class="text-[11px] text-slate-400 font-bold" x-text="'Stok Tersedia: ' + formatStock(product.stock, product.price_unit)"></span>
                                </div>
                            </div>

                            <!-- Add Button (Stylized Indicator) -->
                            <div class="w-full py-3 bg-slate-50 hover:bg-emerald-50 border-t border-slate-100 group-hover:border-emerald-100 font-bold text-xs tracking-wider uppercase text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition duration-200 flex items-center justify-center gap-2 shrink-0" style="flex-shrink: 0;">
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
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                x-show="cart.length > 0" 
                                @click="if(confirm('Kosongkan semua barang di keranjang?')) { cart = []; discount = ''; showToast('Keranjang telah dikosongkan.', 'info'); }" 
                                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2 py-1 rounded-lg transition" 
                                title="Kosongkan Keranjang">
                            Bersihkan
                        </button>
                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1 rounded-full" x-text="cart.reduce((sum, item) => sum + item.qty, 0) + ' item'"></span>
                    </div>
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
                    <template x-for="item in cart" :key="item.cart_id">
                        <div class="flex gap-3 justify-between items-center p-3 rounded-2xl bg-slate-50 border border-slate-100/50 hover:bg-slate-100/30 transition duration-150">
                            <div class="overflow-hidden flex-grow cursor-pointer" @click="qtyProduct = products.find(p => p.id === item.id); qtyValue = item.qty.toString(); qtyCartId = item.cart_id; qtyMode = 'edit'; showQtyModal = true;">
                                <h4 class="font-bold text-sm text-slate-800 truncate" x-text="item.name"></h4>
                                <div class="text-xs text-slate-600 font-semibold mt-1">
                                    <span x-text="formatQty(item.qty, item.price_unit) + ' = ' + formatRupiah(Math.round((getItemPrice(item) * item.qty) / 500) * 500)"></span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium mt-0.5" x-text="'(Harga: ' + formatRupiah(getItemPrice(item)) + '/' + item.price_unit + ')'"></div>
                            </div>

                            <!-- Quantity control -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="updateQty(item.cart_id, item.qty - (item.price_unit === 'gram' ? 100 : 1))" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold flex items-center justify-center">-</button>
                                <span class="font-bold text-xs text-slate-800 w-16 text-center whitespace-nowrap cursor-pointer hover:underline" @click="qtyProduct = products.find(p => p.id === item.id); qtyValue = item.qty.toString(); qtyCartId = item.cart_id; qtyMode = 'edit'; showQtyModal = true;" x-text="formatQty(item.qty, item.price_unit)"></span>
                                <button type="button" @click="updateQty(item.cart_id, item.qty + (item.price_unit === 'gram' ? 100 : 1))" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold flex items-center justify-center">+</button>
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
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-1.5">
                        <button type="button" 
                                @click="processCheckout('Cash')"
                                class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-extrabold text-[10px] tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-emerald-700/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                            </svg>
                            TUNAI
                        </button>
                        <button type="button" 
                                @click="processCheckout('QRIS')"
                                class="py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-extrabold text-[10px] tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-teal-600/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 15h.008v.008H15V15zm0 2.25h.008v.008H15v-.008zm-2.25-2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H15v-.008zm-2.25 0h.008v.008h-.008v-.008zm4.5 4.5h.008v.008H17.25V15zm0 2.25h.008v.008H17.25v-.008z" />
                            </svg>
                            QRIS
                        </button>
                        <button type="button" 
                                @click="processCheckout('Debit')"
                                class="py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-2xl font-extrabold text-[10px] tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-sky-600/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h12.75A1.125 1.125 0 0016.5 13.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.75A1.125 1.125 0 002.625 11.625v1.5a1.125 1.125 0 001.125 1.125z" />
                            </svg>
                            DEBIT
                        </button>
                        <button type="button" 
                                @click="processCheckout('SPLIT')"
                                class="py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-2xl font-extrabold text-[10px] tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-violet-600/10"
                                title="Bayar sebagian tunai dan sebagian QRIS/Debit">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                            SPLIT
                        </button>
                        <button type="button" 
                                @click="processCheckout('DP')"
                                class="py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-2xl font-extrabold text-[10px] tracking-wide uppercase transition duration-150 flex flex-col items-center gap-1 shadow-md shadow-amber-600/10"
                                title="Pembayaran Uang Muka (DP) beserta identitas pemesan">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            DP
                        </button>
                    </div>
                </div>
            </div>

            <!-- CHECKOUT RECEIPT MODAL (Multi-Stage POS Workflow) -->
            <div x-show="showReceipt" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
                style="display: none;"
                @keydown.escape.window="if (checkoutStage === 1) cancelCheckout()">
                
                <div class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto border border-slate-100 shadow-2xl flex flex-col gap-5">
                    
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

                            <!-- B2B Tour Leader / Afiliasi Selector (Opsional) -->
                            <div class="bg-slate-50 dark:bg-dp-800 border border-slate-200 dark:border-dp-700 rounded-2xl p-3 flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-extrabold text-slate-700 dark:text-purple-200 flex items-center gap-1.5">
                                        <span>Mitra Tour Leader (B2B)</span>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 rounded">Opsional</span>
                                    </span>
                                    <template x-if="selectedAffiliateId">
                                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-dp-900 px-2 py-0.5 rounded-md border border-emerald-200">
                                            Komisi: <strong x-text="getSelectedAffiliateRate() + '%'"></strong>
                                            (~ <span x-text="formatRupiah(Math.round(total * (getSelectedAffiliateRate() / 100)))"></span>)
                                        </span>
                                    </template>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <select x-model="selectedAffiliateId" 
                                                class="w-full text-xs font-semibold py-2 px-2.5 border border-slate-200 dark:border-dp-600 rounded-xl bg-white dark:bg-dp-900 text-slate-800 dark:text-purple-100 focus:border-emerald-500 focus:ring-emerald-500">
                                            <option value="">-- Tanpa Afiliasi / Reguler --</option>
                                            <template x-for="aff in affiliates" :key="aff.id">
                                                <option :value="aff.id" x-text="aff.code + ' - ' + aff.name + ' (' + aff.commission_rate_percentage + '%)'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="number" 
                                               min="0"
                                               x-model="paxCount" 
                                               placeholder="Pax" 
                                               title="Jumlah Jamaah / Pax dalam Rombongan"
                                               class="w-full text-xs font-bold py-2 px-2 border border-slate-200 dark:border-dp-600 rounded-xl bg-white dark:bg-dp-900 text-slate-800 dark:text-purple-100 text-center focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Identitas Pemesan / Customer (Wajib untuk DP, Opsional untuk metode lain agar bisa kirim Struk Digital) -->
                            <div :class="paymentMethod === 'DP' ? 'bg-amber-50/70 border-amber-200' : 'bg-slate-50 border-slate-200'"
                                 class="border rounded-2xl p-3 flex flex-col gap-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-extrabold text-slate-700 flex items-center gap-1.5">
                                        <span>Identitas Pemesan</span>
                                        <span x-show="paymentMethod === 'DP'" class="text-[9px] font-black px-1.5 py-0.5 bg-amber-200/80 text-amber-900 rounded">Wajib DP</span>
                                        <span x-show="paymentMethod !== 'DP'" class="text-[9px] font-bold px-1.5 py-0.5 bg-slate-200 text-slate-600 rounded">Opsional</span>
                                    </span>
                                    <button type="button"
                                            x-show="paymentMethod !== 'DP'"
                                            @click="showCustomerFields = !showCustomerFields"
                                            class="text-[10px] font-extrabold text-emerald-700 hover:underline cursor-pointer"
                                            x-text="showCustomerFields ? 'Sembunyikan' : '+ Isi Identitas / WA'">
                                    </button>
                                </div>

                                <div x-show="paymentMethod === 'DP' || showCustomerFields" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">
                                            Nama Pemesan <span x-show="paymentMethod === 'DP'" class="text-rose-600">*</span>
                                        </label>
                                        <input type="text"
                                               x-model="customerName"
                                               placeholder="Contoh: Ibu Siti / Pak Budi"
                                               class="w-full text-xs font-semibold py-2 px-3 border border-slate-200 rounded-xl bg-white text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">No. WhatsApp / HP</label>
                                        <input type="text"
                                               x-model="customerPhone"
                                               placeholder="08xxxxxxxxxx"
                                               class="w-full text-xs font-semibold py-2 px-3 border border-slate-200 rounded-xl bg-white text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                </div>
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

                            <!-- SPLIT payment logic (Cash + QRIS/Debit) -->
                            <template x-if="paymentMethod === 'SPLIT'">
                                <div class="flex flex-col gap-3">
                                    <div class="bg-violet-50/70 border border-violet-100 p-3 rounded-2xl flex flex-col gap-1 text-center">
                                        <span class="text-[10px] font-bold text-violet-700 uppercase tracking-wider">Pembayaran Campuran (Split Payment)</span>
                                        <p class="text-xs text-slate-600 font-medium">Bagi tagihan menjadi pembayaran Tunai & Non-Tunai.</p>
                                    </div>

                                    <!-- Bagian 1: Nominal Tunai -->
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex justify-between items-center">
                                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">1. Bayar Tunai (Cash)</label>
                                            <span class="text-[10px] text-slate-400 font-semibold">Nominal tunai yang dibayar</span>
                                        </div>
                                        <input type="number" 
                                            x-model="splitCashAmount" 
                                            min="0"
                                            :max="total"
                                            placeholder="Contoh: 50000"
                                            class="w-full py-2.5 px-4 border-slate-200 rounded-xl text-center font-extrabold text-lg text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-inner">
                                    </div>

                                    <!-- Bagian 2: Sisa Non-Tunai -->
                                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col gap-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">2. Sisa Tagihan (Non-Tunai)</span>
                                            <div class="flex items-center gap-1 bg-white p-0.5 rounded-lg border border-slate-200">
                                                <button type="button" 
                                                    @click="splitNonCashMethod = 'QRIS'" 
                                                    :class="splitNonCashMethod === 'QRIS' ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-extrabold transition">
                                                    QRIS
                                                </button>
                                                <button type="button" 
                                                    @click="splitNonCashMethod = 'Debit'" 
                                                    :class="splitNonCashMethod === 'Debit' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-extrabold transition">
                                                    DEBIT
                                                </button>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
                                            <span class="text-xs text-slate-500 font-semibold" x-text="'Wajib bayar via ' + splitNonCashMethod + ':'"></span>
                                            <span class="text-base font-extrabold text-violet-700" x-text="formatRupiah(Math.max(0, total - (parseFloat(splitCashAmount) || 0)))"></span>
                                        </div>
                                    </div>

                                    <template x-if="splitNonCashMethod === 'Debit' && (parseFloat(splitCashAmount) || 0) < total">
                                        <div class="flex flex-col gap-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">4 Digit Kartu Debit</label>
                                            <input type="text" 
                                                x-model="cardDigits" 
                                                maxlength="4"
                                                placeholder="Contoh: 1234" 
                                                class="w-full py-1.5 px-3 border-slate-200 rounded-lg text-center font-bold text-xs focus:border-violet-500 focus:ring-violet-500 shadow-inner">
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- DP (Down Payment) payment logic -->
                            <template x-if="paymentMethod === 'DP'">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex justify-between items-center">
                                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nominal DP Diterima (Rp) <span class="text-rose-600">*</span></label>
                                            <span class="text-[10px] text-amber-700 font-bold">Harus &lt; Total Tagihan</span>
                                        </div>
                                        <input type="number"
                                            x-model="dpAmount"
                                            min="1"
                                            :max="Math.max(1, total - 1)"
                                            placeholder="Masukkan nominal DP..."
                                            class="w-full py-2.5 px-4 border-amber-200 rounded-xl text-center font-extrabold text-lg text-slate-800 focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                                        <div class="grid grid-cols-3 gap-1.5">
                                            <button type="button" @click="dpAmount = Math.round((total * 0.3) / 500) * 500 || 1" class="py-1.5 text-[11px] font-extrabold bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg border border-amber-200 transition">DP 30%</button>
                                            <button type="button" @click="dpAmount = Math.round((total * 0.5) / 500) * 500 || 1" class="py-1.5 text-[11px] font-extrabold bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg border border-amber-200 transition">DP 50%</button>
                                            <button type="button" @click="dpAmount = Math.round((total * 0.7) / 500) * 500 || 1" class="py-1.5 text-[11px] font-extrabold bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg border border-amber-200 transition">DP 70%</button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">DP Dibayar Melalui</label>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <template x-for="m in ['Cash', 'QRIS', 'Debit', 'Transfer']" :key="m">
                                                <button type="button"
                                                    @click="dpPaymentMethod = m"
                                                    :class="dpPaymentMethod === m ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                                    class="py-1.5 rounded-xl border text-[11px] font-extrabold transition"
                                                    x-text="m === 'Cash' ? 'Tunai' : m">
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Catatan / Jadwal Pelunasan (Opsional)</label>
                                        <input type="text"
                                            x-model="dpNote"
                                            placeholder="Contoh: Diambil besok sore / titip kemasan hampers"
                                            class="w-full py-2 px-3 border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                                    </div>

                                    <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 flex justify-between items-center text-xs">
                                        <span class="font-bold text-amber-900">Sisa Pelunasan Nanti:</span>
                                        <span class="text-base font-black text-amber-800" x-text="formatRupiah(Math.max(0, total - (parseInt(dpAmount || 0, 10) || 0)))"></span>
                                    </div>

                                    <template x-if="dpAmount && parseInt(dpAmount, 10) >= total">
                                        <div class="bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold p-2.5 rounded-xl">
                                            Nominal DP harus lebih kecil dari Total Tagihan. Jika sudah lunas penuh, pilih metode Tunai / QRIS / Debit.
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Confirm & Cancel Actions -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button type="button" @click="cancelCheckout()" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition duration-150">Batal</button>
                                <button type="button" 
                                    @click="finishTransaction()" 
                                    :disabled="isSubmitting || (paymentMethod === 'Cash' && (!cashReceived || parseFloat(cashReceived) < total)) || 
                                               (paymentMethod === 'Debit' && cardDigits.length !== 4) ||
                                               (paymentMethod === 'SPLIT' && ((parseFloat(splitCashAmount) || 0) <= 0 || (parseFloat(splitCashAmount) || 0) >= total || (splitNonCashMethod === 'Debit' && cardDigits.length !== 4))) ||
                                               (paymentMethod === 'DP' && (!customerName.trim() || !(parseInt(dpAmount || 0, 10) > 0) || parseInt(dpAmount || 0, 10) >= total))"
                                    :class="(isSubmitting || (paymentMethod === 'Cash' && (!cashReceived || parseFloat(cashReceived) < total)) || 
                                            (paymentMethod === 'Debit' && cardDigits.length !== 4) ||
                                            (paymentMethod === 'SPLIT' && ((parseFloat(splitCashAmount) || 0) <= 0 || (parseFloat(splitCashAmount) || 0) >= total || (splitNonCashMethod === 'Debit' && cardDigits.length !== 4))) ||
                                            (paymentMethod === 'DP' && (!customerName.trim() || !(parseInt(dpAmount || 0, 10) > 0) || parseInt(dpAmount || 0, 10) >= total))) 
                                            ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' : 'bg-emerald-700 hover:bg-emerald-800 text-white shadow-md shadow-emerald-700/10'"
                                    class="py-3 rounded-2xl font-bold transition duration-150 flex items-center justify-center gap-2">
                                    <span x-show="!isSubmitting">Proses Transaksi</span>
                                    <span x-show="isSubmitting">Memproses...</span>
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

                                <template x-if="latestTransaction.customer_name || latestTransaction.customer_phone">
                                    <div class="flex justify-between border-b border-slate-50 pb-1.5 text-slate-700">
                                        <span>Pemesan:</span>
                                        <span class="font-bold text-slate-900" x-text="(latestTransaction.customer_name || '-') + (latestTransaction.customer_phone ? ' (' + latestTransaction.customer_phone + ')' : '')"></span>
                                    </div>
                                </template>

                                <!-- DP details when paymentMethod === 'DP' -->
                                <template x-if="latestTransaction.payment_method === 'DP'">
                                    <div class="flex flex-col gap-1.5 pt-1 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900">
                                        <div class="flex justify-between">
                                            <span>DP Dibayar (<span x-text="latestTransaction.dp_payment_method || 'Cash'"></span>):</span>
                                            <span class="font-extrabold text-emerald-700" x-text="formatRupiah(latestTransaction.paid_amount)"></span>
                                        </div>
                                        <div class="flex justify-between font-black text-sm text-amber-800">
                                            <span>Sisa Pelunasan:</span>
                                            <span x-text="formatRupiah(latestTransaction.remaining_amount)"></span>
                                        </div>
                                    </div>
                                </template>
                                
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

                            <!-- Struk Digital Resmi (PDF) untuk Customer -->
                            <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-200 flex flex-col gap-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-black text-emerald-800 uppercase tracking-wider">Struk Digital Resmi (PDF)</span>
                                    <div class="flex items-center gap-2.5">
                                        <a :href="latestTransaction.digital_receipt_url" target="_blank" class="text-[11px] font-extrabold text-emerald-700 hover:underline">
                                            Pratinjau PDF
                                        </a>
                                        <button type="button"
                                                @click="copyDigitalReceiptLink(latestTransaction)"
                                                class="text-[11px] font-extrabold text-slate-600 hover:text-emerald-700 hover:underline">
                                            Salin Link
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <a :href="latestTransaction.digital_receipt_download_url || latestTransaction.digital_receipt_url"
                                       class="py-2.5 px-3 bg-white hover:bg-slate-50 border border-emerald-300 text-emerald-800 rounded-xl font-extrabold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                                        Unduh Struk PDF
                                    </a>
                                    <button type="button"
                                            @click="shareDigitalReceiptPdf(latestTransaction)"
                                            :disabled="isSharingPdf"
                                            class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-xl font-extrabold text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                        <span x-show="!isSharingPdf">Bagikan File PDF</span>
                                        <span x-show="isSharingPdf">Menyiapkan PDF...</span>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 pt-0.5">
                                    <input type="text"
                                           x-model="waCustomerPhone"
                                           placeholder="No. WhatsApp Customer (opsional: 08...)"
                                           class="w-full py-1.5 px-3 text-xs font-semibold border-slate-200 rounded-xl bg-white text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                                    <button type="button"
                                            @click="openCustomerWaChat(latestTransaction, waCustomerPhone)"
                                            class="shrink-0 px-3 py-1.5 bg-white hover:bg-emerald-50 border border-slate-200 text-emerald-700 rounded-xl text-xs font-bold transition whitespace-nowrap">
                                        Chat WA
                                    </button>
                                </div>
                            </div>

                            <!-- Pilihan Metode Cetak -->
                            <div class="flex flex-col gap-1.5 pb-2 border-t border-slate-100 pt-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Metode Cetak Struk</label>
                                <select x-model="printMethod" class="w-full border-slate-200 rounded-xl text-xs font-bold focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 shadow-inner bg-slate-50 text-slate-700">
                                    <option value="iframe">Sistem Browser (Iframe/Default)</option>
                                    <option value="bluetooth">Direct Bluetooth API (WebBLE)</option>
                                </select>
                            </div>

                            <!-- Printing & New Checkout Actions -->
                            <div class="flex flex-col gap-2 pt-2">
                                <button type="button" 
                                    @click="printReceiptAction(latestTransaction)" 
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
                        <p class="text-xs text-slate-400 font-semibold mt-0.5" x-text="'Satuan Produk: ' + (qtyProduct ? qtyProduct.price_unit : '') + ' • Harga: Rp ' + (qtyProduct ? (qtyProduct.selling_price || qtyProduct.price || 0).toLocaleString('id-ID') : 0) + '/' + (qtyProduct ? qtyProduct.price_unit : '')"></p>
                    </div>

                    <!-- WEIGHT UNIT CONVERTER SWITCHER (Gram ⇄ Kg) -->
                    <template x-if="qtyProduct && (qtyProduct.price_unit === 'kg' || qtyProduct.price_unit === 'gram')">
                        <div class="flex items-center justify-center p-1 bg-slate-100 rounded-2xl text-xs font-bold gap-1 border border-slate-200/50">
                            <button type="button" 
                                    @click="switchQtyInputMode('gram')"
                                    :class="qtyInputMode === 'gram' ? 'bg-white text-emerald-800 shadow-xs border border-emerald-200/60 font-black' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                                    class="flex-1 py-1.5 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                <span>Input dlm Gram (g)</span>
                            </button>
                            <button type="button" 
                                    @click="switchQtyInputMode('kg')"
                                    :class="qtyInputMode === 'kg' ? 'bg-white text-emerald-800 shadow-xs border border-emerald-200/60 font-black' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                                    class="flex-1 py-1.5 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                <span>Input dlm Kg</span>
                            </button>
                        </div>
                    </template>

                    <!-- Quantity input box -->
                    <div class="relative">
                        <input type="text" 
                            readonly
                            x-model="qtyValue"
                            placeholder="0"
                            class="w-full py-3 px-4 text-center font-extrabold text-2xl border-slate-200 rounded-2xl bg-slate-50 text-emerald-800 focus:outline-none">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400 uppercase pointer-events-none" x-text="qtyInputMode"></span>
                    </div>

                    <!-- Live Conversion Equivalent & Price Calculation -->
                    <div class="flex items-center justify-between text-xs px-2 py-1.5 rounded-xl bg-emerald-50/70 border border-emerald-200/50 text-emerald-900 font-bold" x-show="qtyValue > 0">
                        <div class="flex items-center gap-1 text-[11px]">
                            <span class="text-emerald-600 font-semibold">Konversi:</span>
                            <span x-text="getQtyEquivalentText()"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-emerald-700 font-black" x-text="getQtyEstimatedPriceText()"></span>
                        </div>
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

                    <!-- Quick selectors for common weights when input is gram -->
                    <template x-if="qtyInputMode === 'gram'">
                        <div class="grid grid-cols-4 gap-1.5 text-xs font-bold text-emerald-800 mt-1">
                            <button type="button" @click="qtyValue = '100'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">100g</button>
                            <button type="button" @click="qtyValue = '250'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">250g <span class="text-[9px] font-normal block text-slate-500">¼kg</span></button>
                            <button type="button" @click="qtyValue = '500'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">500g <span class="text-[9px] font-normal block text-slate-500">½kg</span></button>
                            <button type="button" @click="qtyValue = '1000'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150">1000g <span class="text-[9px] font-normal block text-slate-500">1kg</span></button>
                        </div>
                    </template>

                    <!-- Quick selectors for common weights when input is kg -->
                    <template x-if="qtyInputMode === 'kg'">
                        <div class="grid grid-cols-4 gap-1.5 text-xs font-bold text-emerald-800 mt-1">
                            <button type="button" @click="qtyValue = '0.1'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150" title="100 gram">100g</button>
                            <button type="button" @click="qtyValue = '0.25'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150" title="250 gram">250g <span class="text-[9px] font-normal block text-slate-500">¼kg</span></button>
                            <button type="button" @click="qtyValue = '0.5'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150" title="500 gram">500g <span class="text-[9px] font-normal block text-slate-500">½kg</span></button>
                            <button type="button" @click="qtyValue = '1'" class="py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100 transition duration-150" title="1 Kilogram">1kg</button>
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
                                <option value="Operasional Toko">Operasional Toko</option>
                                <option value="Transportasi">Transportasi</option>
                                <option value="Alat Tulis & Kebersihan">Alat Tulis & Kebersihan</option>
                                <option value="Konsumsi">Konsumsi / Makan</option>
                                <option value="Lain-lain">Lain-lain</option>
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

            <!-- TOUCH WHATSAPP REPORT MODAL (Kirim Laporan Kasir ke WA) -->
            <div x-show="showWaModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm shadow-2xl"
                style="display: none;"
                @keydown.escape.window="showWaModal = false">
                
                <div class="bg-white rounded-3xl p-6 w-full max-w-lg border border-slate-100 shadow-2xl flex flex-col gap-4" 
                     @click.away="showWaModal = false">
                    
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-lg">Kirim Laporan Harian ke WhatsApp</h3>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">Kirim rekap transaksi & pengeluaran hari ini ke WhatsApp</p>
                        </div>
                        <button type="button" @click="showWaModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor WhatsApp Tujuan (Untuk Kirim Langsung)</label>
                            <input type="text" 
                                   x-model="waNumber" 
                                   placeholder="Contoh: 628123456789" 
                                   class="w-full py-2.5 px-4 border-slate-200 rounded-xl font-bold focus:border-emerald-500 focus:ring-emerald-500 shadow-inner">
                            <span class="text-[10px] text-slate-400 mt-0.5">Kosongkan/Abaikan jika ingin membagikan ke Grup atau Chat Lain.</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pratinjau Laporan</label>
                            <textarea x-model="waMessage" 
                                      rows="10" 
                                      readonly
                                      class="w-full border-slate-200 rounded-xl bg-slate-50 text-slate-700 shadow-inner px-4 py-2.5 text-xs font-mono select-all focus:outline-none"></textarea>
                        </div>

                        <!-- Tiga Tombol Aksi -->
                        <div class="grid grid-cols-3 gap-2.5 mt-2">
                            <button type="button" @click="showWaModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold text-xs transition duration-150">Batal</button>
                            
                            <!-- Tombol Bagikan ke Grup (Tanpa No Telp) -->
                            <button type="button" @click="sendWaReport(true)" class="py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-bold text-xs shadow-md shadow-teal-600/10 transition duration-150 flex items-center justify-center gap-1">
                                Bagikan ke Grup
                            </button>
                            
                            <!-- Tombol Kirim ke Nomor (Dengan No Telp) -->
                            <button type="button" @click="sendWaReport(false)" class="py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold text-xs shadow-md shadow-emerald-700/10 transition duration-150 flex items-center justify-center gap-1">
                                Kirim Nomor
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PELUNASAN DP MODAL (KASIR) -->
            <div x-show="showDpModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
                 style="display: none;"
                 @keydown.escape.window="showDpModal = false">

                <div class="bg-white rounded-3xl p-6 w-full max-w-2xl border border-slate-100 shadow-2xl flex flex-col gap-4 max-h-[90vh] overflow-y-auto"
                     @click.away="showDpModal = false">

                    <!-- Modal Header -->
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-lg">Pelunasan Tagihan DP</h3>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">Cari pesanan DP dari hari ini atau hari sebelumnya dan catat pembayaran sisa tagihan</p>
                        </div>
                        <button type="button" @click="showDpModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- VIEW A: SUKSES PEMBAYARAN / STRUK TERBARU -->
                    <template x-if="settledDpResult">
                        <div class="space-y-4">
                            <div class="p-4 rounded-2xl border text-center space-y-1.5"
                                 :class="settledDpResult.is_paid ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                                <div class="text-xs font-black uppercase tracking-wider"
                                     :class="settledDpResult.is_paid ? 'text-emerald-700' : 'text-amber-700'"
                                     x-text="settledDpResult.is_paid ? 'TAGIHAN SUDAH LUNAS' : 'PEMBAYARAN CICILAN DP TERCATAT'"></div>
                                <div class="text-xl font-black text-slate-900" x-text="'+' + formatRupiah(settledDpResult.last_payment_amount)"></div>
                                <div class="text-xs font-semibold text-slate-600">
                                    <span x-text="settledDpResult.transaction_code"></span> • <span x-text="settledDpResult.customer_name || '-'"></span> • Metode: <strong x-text="settledDpResult.last_payment_method"></strong>
                                </div>
                                <div class="text-xs font-bold pt-1"
                                     :class="settledDpResult.is_paid ? 'text-emerald-700' : 'text-amber-800'">
                                    Total Terbayar: <span x-text="formatRupiah(settledDpResult.paid_amount)"></span> |
                                    Sisa Tagihan: <span x-text="formatRupiah(settledDpResult.remaining_amount)"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <button type="button"
                                        @click="shareDigitalReceiptPdf(settledDpResult)"
                                        :disabled="isSharingPdf"
                                        class="py-3 px-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl font-bold text-xs shadow-sm transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>
                                    <span x-text="isSharingPdf ? 'Menyiapkan...' : 'Bagikan File PDF'"></span>
                                </button>
                                <a :href="settledDpResult.digital_receipt_download_url || settledDpResult.digital_receipt_url"
                                   target="_blank"
                                   class="py-3 px-3 bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 rounded-2xl font-bold text-xs transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    <span>Unduh Struk PDF</span>
                                </a>
                                <button type="button"
                                        @click="printReceiptAction(settledDpResult)"
                                        class="py-3 px-3 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-2xl font-bold text-xs transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
                                    <span>Cetak Struk</span>
                                </button>
                            </div>

                            <div class="flex justify-between gap-2 pt-2">
                                <button type="button" @click="settledDpResult = null" class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl font-bold text-xs border border-amber-200 transition">
                                    Kembali ke Daftar DP
                                </button>
                                <button type="button" @click="showDpModal = false" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition">
                                    Selesai & Tutup
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- VIEW B: FORM BAYAR SISA DP -->
                    <template x-if="!settledDpResult && selectedDpTrx">
                        <div class="space-y-4">
                            <button type="button" @click="selectedDpTrx = null" class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                                Kembali ke Daftar Pesanan DP
                            </button>

                            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="font-mono font-bold text-emerald-700" x-text="selectedDpTrx.transaction_code"></span>
                                    <span class="text-slate-500 font-semibold" x-text="selectedDpTrx.created_at_formatted"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Pemesan:</span>
                                    <span class="font-extrabold text-slate-900">
                                        <span x-text="selectedDpTrx.customer_name || '-'"></span>
                                        <template x-if="selectedDpTrx.customer_phone && selectedDpTrx.customer_phone !== '-'">
                                            <span class="text-slate-500 font-semibold" x-text="' (' + selectedDpTrx.customer_phone + ')'"></span>
                                        </template>
                                    </span>
                                </div>
                                <div class="text-slate-600 bg-white/80 p-2 rounded-xl border border-amber-100" x-text="selectedDpTrx.items_summary"></div>
                                <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                                    <div class="bg-white rounded-xl p-2 border border-amber-100">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Total</div>
                                        <div class="font-extrabold text-slate-800" x-text="formatRupiah(selectedDpTrx.total_price)"></div>
                                    </div>
                                    <div class="bg-white rounded-xl p-2 border border-emerald-100">
                                        <div class="text-[10px] text-emerald-600 font-bold uppercase">Sudah Masuk</div>
                                        <div class="font-extrabold text-emerald-700" x-text="formatRupiah(selectedDpTrx.paid_amount)"></div>
                                    </div>
                                    <div class="bg-amber-600 text-white rounded-xl p-2 shadow-sm">
                                        <div class="text-[10px] text-amber-100 font-bold uppercase">Sisa Tagihan</div>
                                        <div class="font-black text-sm" x-text="formatRupiah(selectedDpTrx.remaining_amount)"></div>
                                    </div>
                                </div>
                            </div>

                            <template x-if="selectedDpTrx.installments && selectedDpTrx.installments.length > 0">
                                <div class="space-y-1.5">
                                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Riwayat Pembayaran Masuk</div>
                                    <div class="max-h-28 overflow-y-auto space-y-1">
                                        <template x-for="inst in selectedDpTrx.installments" :key="inst.id">
                                            <div class="flex justify-between items-center text-xs bg-slate-50 border border-slate-100 rounded-xl px-3 py-1.5">
                                                <div>
                                                    <span class="font-bold text-slate-700" x-text="inst.payment_method"></span>
                                                    <span class="text-slate-400 ml-1.5" x-text="inst.date"></span>
                                                    <template x-if="inst.note">
                                                        <span class="text-slate-500 ml-1" x-text="'• ' + inst.note"></span>
                                                    </template>
                                                </div>
                                                <span class="font-extrabold text-emerald-700" x-text="formatRupiah(inst.amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nominal Pembayaran (Rp)</label>
                                        <button type="button" @click="dpSettleAmount = Math.round(selectedDpTrx.remaining_amount || 0)" class="text-[11px] font-extrabold text-amber-700 hover:underline">
                                            Isi Lunas
                                        </button>
                                    </div>
                                    <input type="number"
                                           x-model="dpSettleAmount"
                                           min="1"
                                           :max="selectedDpTrx.remaining_amount"
                                           class="w-full py-2.5 px-3.5 border-slate-200 rounded-xl text-sm font-extrabold focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Metode Pembayaran</label>
                                    <select x-model="dpSettleMethod"
                                            class="w-full py-2.5 px-3.5 border-slate-200 rounded-xl text-sm font-bold focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                                        <option value="Cash">Tunai (Cash)</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="Debit">Debit</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Catatan (Opsional)</label>
                                <input type="text"
                                       x-model="dpSettleNote"
                                       placeholder="Misal: Pelunasan diambil sore hari..."
                                       class="w-full py-2 px-3.5 border-slate-200 rounded-xl text-sm focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button type="button" @click="selectedDpTrx = null" class="py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold text-xs transition">
                                    Batal
                                </button>
                                <button type="button"
                                        @click="submitDpSettlement()"
                                        :disabled="isSettlingDp"
                                        class="py-3 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-2xl font-extrabold text-xs shadow-md transition">
                                    <span x-text="isSettlingDp ? 'Menyimpan...' : 'Simpan Pembayaran Sisa DP'"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- VIEW C: DAFTAR PESANAN DP BELUM LUNAS -->
                    <template x-if="!settledDpResult && !selectedDpTrx">
                        <div class="space-y-3">
                            <div class="relative">
                                <input type="text"
                                       x-model="dpSearch"
                                       placeholder="Cari nama pemesan, no HP, kode struk, atau produk..."
                                       class="w-full pl-9 pr-4 py-2.5 text-sm border-slate-200 rounded-xl focus:border-amber-500 focus:ring-amber-500 shadow-inner">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>

                            <div class="max-h-[52vh] overflow-y-auto space-y-2.5 pr-1">
                                <template x-if="filteredUnpaidDp.length === 0">
                                    <div class="py-10 text-center text-slate-400 text-sm font-semibold border border-dashed border-slate-200 rounded-2xl">
                                        Tidak ada tagihan DP belum lunas yang cocok.
                                    </div>
                                </template>

                                <template x-for="trx in filteredUnpaidDp" :key="trx.id">
                                    <div class="p-3.5 rounded-2xl border border-slate-200 hover:border-amber-300 bg-slate-50/70 hover:bg-amber-50/40 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-mono text-xs font-extrabold text-emerald-700" x-text="trx.transaction_code"></span>
                                                <span class="text-[11px] font-semibold text-slate-400" x-text="trx.created_at_formatted"></span>
                                            </div>
                                            <div class="text-sm font-extrabold text-slate-900">
                                                <span x-text="trx.customer_name || 'Tanpa Nama'"></span>
                                                <template x-if="trx.customer_phone && trx.customer_phone !== '-'">
                                                    <span class="text-xs font-semibold text-slate-500 ml-1" x-text="'• ' + trx.customer_phone"></span>
                                                </template>
                                            </div>
                                            <div class="text-xs text-slate-500 truncate" x-text="trx.items_summary"></div>
                                            <div class="text-xs font-bold text-slate-600 flex items-center gap-3 flex-wrap pt-0.5">
                                                <span>Total: <strong x-text="formatRupiah(trx.total_price)"></strong></span>
                                                <span class="text-emerald-700">Masuk: <strong x-text="formatRupiah(trx.paid_amount)"></strong></span>
                                                <span class="text-amber-700">Sisa: <strong x-text="formatRupiah(trx.remaining_amount)"></strong></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a :href="trx.digital_receipt_url"
                                               target="_blank"
                                               class="px-3 py-2 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition">
                                                PDF
                                            </a>
                                            <button type="button"
                                                    @click="selectDpTransaction(trx)"
                                                    class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-extrabold shadow-sm transition active:scale-95">
                                                Bayar Sisa / Lunasi
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
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

            async function printDirectBluetooth(transaction) {
                if (!transaction) return;
                try {
                    // 1. Scan for BLE Bluetooth printers
                    const device = await navigator.bluetooth.requestDevice({
                        filters: [
                            { namePrefix: 'Printer' },
                            { namePrefix: 'MTP' },
                            { namePrefix: 'PT-' },
                            { namePrefix: 'POS' },
                            { namePrefix: 'RPP' }
                        ],
                        optionalServices: [
                            '000018f0-0000-1000-8000-00805f9b34fb', // Standard BLE printer service UUID
                            '0000e914-0000-1000-8000-00805f9b34fb'  // Custom BLE printer service UUID
                        ]
                    });
                    
                    // 2. Connect GATT
                    const server = await device.gatt.connect();
                    
                    // 3. Find Primary Service
                    let service;
                    try {
                        service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                    } catch (e) {
                        try {
                            service = await server.getPrimaryService('0000e914-0000-1000-8000-00805f9b34fb');
                        } catch (err) {
                            const services = await server.getPrimaryServices();
                            if (services.length > 0) {
                                service = services[0];
                            } else {
                                throw new Error("Layanan printer Bluetooth tidak ditemukan!");
                            }
                        }
                    }
                    
                    // 4. Get Characteristic
                    const characteristics = await service.getCharacteristics();
                    const writeCharacteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);
                    if (!writeCharacteristic) {
                        throw new Error("Karakteristik penulisan data printer tidak ditemukan!");
                    }
                    
                    // 5. Build raw ESC/POS payload
                    let escpos = '';
                    
                    // ESC/POS Commands
                    const ESC_INIT = '\x1b\x40';
                    const ESC_ALIGN_CENTER = '\x1b\x61\x01';
                    const ESC_ALIGN_LEFT = '\x1b\x61\x00';
                    const ESC_ALIGN_RIGHT = '\x1b\x61\x02';
                    const ESC_BOLD_ON = '\x1b\x45\x01';
                    const ESC_BOLD_OFF = '\x1b\x45\x00';
                    const ESC_DOUBLE_SIZE = '\x1b\x21\x30'; // Double height and double width
                    const ESC_NORMAL_SIZE = '\x1b\x21\x00';
                    
                    escpos += ESC_INIT;
                    escpos += ESC_ALIGN_CENTER;
                    escpos += ESC_BOLD_ON + ESC_DOUBLE_SIZE + 'PUSAT KURMA CIANJUR\n' + ESC_NORMAL_SIZE;
                    escpos += ESC_BOLD_ON + 'Al karim\n' + ESC_BOLD_OFF;
                    escpos += 'Cianjur, Jawa Barat\n';
                    escpos += '================================\n';
                    
                    escpos += ESC_ALIGN_LEFT;
                    escpos += `No. Struk: ${transaction.transaction_code}\n`;
                    const today = new Date();
                    const dd = String(today.getDate()).padStart(2, '0');
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const yyyy = today.getFullYear();
                    const formattedDate = `${dd}/${mm}/${yyyy}`;
                    const formattedTime = transaction.time || today.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                    
                    escpos += `Tanggal  : ${formattedDate} ${formattedTime}\n`;
                    escpos += `Metode   : ${transaction.payment_method.toUpperCase()}\n`;
                    if (transaction.customer_name) {
                        escpos += `Pemesan  : ${transaction.customer_name}\n`;
                    }
                    if (transaction.customer_phone) {
                        escpos += `No. HP   : ${transaction.customer_phone}\n`;
                    }
                    escpos += '--------------------------------\n';
                    
                    // Items summary
                    const items = transaction.items_summary.split(', ');
                    items.forEach(item => {
                        escpos += ESC_BOLD_ON + item + '\n' + ESC_BOLD_OFF;
                    });
                    escpos += '--------------------------------\n';
                    
                    // Financial details
                    const discount = Number(transaction.discount || 0);
                    const total = Number(transaction.total_price || 0);
                    const subtotal = total + discount;
                    
                    const formatRupiahRaw = (num) => 'Rp ' + Math.round(num).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                    
                    escpos += `Subtotal  : ${formatRupiahRaw(subtotal)}\n`;
                    if (discount > 0) {
                        escpos += `Diskon    : -${formatRupiahRaw(discount)}\n`;
                    }
                    escpos += ESC_BOLD_ON + `TOTAL     : ${formatRupiahRaw(total)}\n` + ESC_BOLD_OFF;
                    if (transaction.payment_method === 'DP' || Number(transaction.remaining_amount || 0) > 0) {
                        escpos += `Dibayar   : ${formatRupiahRaw(transaction.paid_amount || 0)}\n`;
                        escpos += ESC_BOLD_ON + `Sisa      : ${formatRupiahRaw(transaction.remaining_amount || 0)}\n` + ESC_BOLD_OFF;
                    }
                    escpos += '================================\n';
                    
                    escpos += ESC_ALIGN_CENTER;
                    escpos += '*** TERIMA KASIH ***\n';
                    escpos += 'Terima kasih sudah berkunjung\n';
                    escpos += 'silahkan datang kembali.\n\n\n\n'; // Feed lines
                    
                    // 6. Encode and write in chunks of 20 bytes
                    const encoder = new TextEncoder();
                    const dataBytes = encoder.encode(escpos);
                    const chunk_size = 20;
                    
                    for (let i = 0; i < dataBytes.length; i += chunk_size) {
                        const chunk = dataBytes.slice(i, i + chunk_size);
                        await writeCharacteristic.writeValue(chunk);
                        await new Promise(r => setTimeout(r, 20)); // delay 20ms
                    }
                    
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: "Cetak struk via Bluetooth berhasil!", type: "success" } }));
                } catch (error) {
                    console.error("Direct Bluetooth printing failed:", error);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: "Cetak Bluetooth gagal: " + error.message, type: "error" } }));
                }
            }
        </script>
    </x-app-layout>
