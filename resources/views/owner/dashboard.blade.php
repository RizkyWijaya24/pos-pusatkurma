<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Analisis Kinerja Penjualan') }}
        </h2>
    </x-slot>

    {{-- ============================================================
         OWNER DASHBOARD - DARK MODE STYLE OVERRIDES
         Higher specificity using html.dark to override Tailwind
         ============================================================ --}}
    <style>
        /* ── OWNER DASHBOARD METRIC CARDS DARK MODE (SOLID COLORS) ── */
        html.dark #card-omset {
            background: #0d2b1a !important; /* Solid dark green */
            border-color: rgba(52, 211, 153, 0.25) !important;
        }
        html.dark #card-omset .text-emerald-700 {
            color: #a7f3d0 !important;
        }
        html.dark #card-omset .bg-emerald-100\/50 {
            background-color: rgba(16, 185, 129, 0.15) !important;
            border-color: rgba(16, 185, 129, 0.25) !important;
            color: #a7f3d0 !important;
        }
        html.dark #card-omset .text-emerald-800 {
            color: #a7f3d0 !important;
        }
        html.dark #card-omset .w-10.h-10 {
            background-color: rgba(16, 185, 129, 0.15) !important;
            color: #34d399 !important;
            border-color: rgba(16, 185, 129, 0.25) !important;
        }
        html.dark #card-omset .w-10.h-10:hover {
            background-color: #10b981 !important;
            color: #ffffff !important;
        }
        html.dark #card-omset .text-emerald-600 {
            color: #34d399 !important;
        }

        html.dark #card-profit {
            background: #092523 !important; /* Solid dark teal */
            border-color: rgba(45, 212, 191, 0.25) !important;
        }
        html.dark #card-profit .text-teal-700 {
            color: #99f6e4 !important;
        }
        html.dark #card-profit .text-teal-600 {
            color: #2dd4bf !important;
        }
        html.dark #card-profit .bg-teal-100\/60 {
            background-color: rgba(20, 184, 166, 0.15) !important;
            border-color: rgba(20, 184, 166, 0.25) !important;
            color: #99f6e4 !important;
        }
        html.dark #card-profit .w-10.h-10 {
            background-color: rgba(20, 184, 166, 0.15) !important;
            color: #2dd4bf !important;
            border-color: rgba(20, 184, 166, 0.25) !important;
        }
        html.dark #card-profit .w-10.h-10:hover {
            background-color: #14b8a6 !important;
            color: #ffffff !important;
        }

        html.dark #card-transactions {
            background: #350914 !important; /* Solid dark rose */
            border-color: rgba(251, 113, 133, 0.25) !important;
        }
        html.dark #card-transactions .text-rose-700 {
            color: #fecdd3 !important;
        }
        html.dark #card-transactions .text-rose-600 {
            color: #fb7185 !important;
        }
        html.dark #card-transactions .w-10.h-10 {
            background-color: rgba(244, 63, 94, 0.15) !important;
            color: #fb7185 !important;
            border-color: rgba(244, 63, 94, 0.25) !important;
        }
        html.dark #card-transactions .w-10.h-10:hover {
            background-color: #f43f5e !important;
            color: #ffffff !important;
        }

        html.dark #card-expenses {
            background: #301705 !important; /* Solid dark amber */
            border-color: rgba(251, 191, 36, 0.25) !important;
        }
        html.dark #card-expenses .text-amber-700 {
            color: #fde68a !important;
        }
        html.dark #card-expenses .text-amber-600 {
            color: #fbbf24 !important;
        }
        html.dark #card-expenses .w-10.h-10 {
            background-color: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.25) !important;
        }
        html.dark #card-expenses .w-10.h-10:hover {
            background-color: #f59e0b !important;
            color: #ffffff !important;
        }
    </style>

    <!-- Chart.js and Data Islands -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="application/json" id="weekly-trend-data">@json($weeklyTrend)</script>
    <script type="application/json" id="monthly-trend-data">@json($monthlyTrend)</script>
    <script type="application/json" id="yearly-trend-data">@json($yearlyTrend)</script>

    <!-- Script containing complex functions to avoid HTML attribute parsing issues -->
    <script>
        function renderOwnerMarkdown(md) {
            if (!md) return '';
            let html = md;
            
            // Basic HTML escape to prevent raw rendering issues
            html = html
                .replace(/&/g, '&amp;')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Convert Headings
            html = html.replace(/^### (.*?)$/gm, '<h5 class="text-sm font-black text-slate-800 dark:text-violet-200 mt-4 mb-2">$1</h5>');
            html = html.replace(/^## (.*?)$/gm, '<h4 class="text-base font-black text-slate-800 dark:text-violet-100 mt-5 mb-2.5 border-b border-violet-100/30 pb-1">$1</h4>');
            html = html.replace(/^# (.*?)$/gm, '<h3 class="text-lg font-black text-slate-800 dark:text-violet-50 mt-6 mb-3 border-b border-violet-200/50 pb-1.5">$1</h3>');
            
            // Convert Bold
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-extrabold text-slate-800 dark:text-slate-200">$1</strong>');
            
            // Convert Bullet points
            html = html.replace(/^\-\s+(.*?)$/gm, '<li class="ml-4 list-disc pl-1 text-slate-600 dark:text-slate-300 text-xs font-semibold my-1.5">$1</li>');
            
            // Convert Paragraphs / Line Breaks
            html = html.split('\n\n').map(function(p) {
                const trimmed = p.trim();
                if (trimmed.startsWith('<li') || trimmed.startsWith('<h')) {
                    return p;
                }
                return '<p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-semibold mb-2.5">' + p + '</p>';
            }).join('');

            html = html.replace(/\n/g, '<br>');

            return html;
        }

        function fetchOwnerAiAnalysis(component, refresh) {
            component.showAiModal = true;
            const skill = component.activeSkill || 'general';

            if (!component.skillCache) {
                component.skillCache = {};
            }

            // Return cached analysis if available and not forcing refresh
            if (!refresh && component.skillCache[skill]) {
                component.aiAnalysis   = component.skillCache[skill].analysis;
                component.chatMessages = component.skillCache[skill].chatMessages || [];
                component.aiError      = '';
                return;
            }

            if (refresh) {
                component.chatMessages = [];
                component.chatInput    = '';
                delete component.skillCache[skill];
            }

            component.aiLoading  = true;
            component.aiError    = '';
            component.aiAnalysis = '';

            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('skill', skill);
            if (refresh) {
                urlParams.set('refresh', 'true');
            }

            fetch('{{ route("owner.performance.analysis") }}?' + urlParams.toString())
                .then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(err) { throw new Error(err.message || 'Error HTTP ' + response.status); });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        component.aiAnalysis = data.analysis;
                        component.skillCache[skill] = {
                            analysis: data.analysis,
                            chatMessages: component.chatMessages ? [...component.chatMessages] : []
                        };
                    } else {
                        throw new Error(data.message || 'Gagal mengambil analisis.');
                    }
                })
                .catch(function(err) {
                    component.aiError = err.message || 'Terjadi kesalahan saat menghubungi server.';
                })
                .finally(function() {
                    component.aiLoading = false;
                });
        }

        function fetchOwnerChatReply(component) {
            const message = component.chatInput.trim();
            if (!message || component.chatLoading || component.aiLoading) return;

            // Add user bubble
            component.chatMessages.push({ role: 'user', text: message });
            component.chatInput   = '';
            component.chatLoading = true;
            component.chatError   = '';

            const skill = component.activeSkill || 'general';
            if (component.skillCache && component.skillCache[skill]) {
                component.skillCache[skill].chatMessages = [...component.chatMessages];
            }

            // Scroll chat area to bottom after adding user bubble
            component.$nextTick(function() {
                const chatArea = document.getElementById('ai-chat-area');
                if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
            });

            // Build history from chatMessages (exclude the new message just added)
            const history = component.chatMessages.slice(0, -1).map(function(m) {
                return { role: m.role, text: m.text };
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            fetch('{{ route("owner.performance.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                },
                body: JSON.stringify({
                    initial_analysis: component.aiAnalysis,
                    history: history,
                    message: message,
                }),
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(err) { throw new Error(err.message || 'Error HTTP ' + response.status); });
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    component.chatMessages.push({ role: 'model', text: data.reply });
                    if (component.skillCache && component.skillCache[skill]) {
                        component.skillCache[skill].chatMessages = [...component.chatMessages];
                    }
                } else {
                    throw new Error(data.message || 'Gagal mendapatkan balasan.');
                }
            })
            .catch(function(err) {
                component.chatError = err.message || 'Gagal menghubungi server.';
                // Remove the user bubble that was just added on error so UX is clean
                component.chatMessages.pop();
                if (component.skillCache && component.skillCache[skill]) {
                    component.skillCache[skill].chatMessages = [...component.chatMessages];
                }
            })
            .finally(function() {
                component.chatLoading = false;
                // Auto-scroll to bottom after reply arrives
                component.$nextTick(function() {
                    const chatArea = document.getElementById('ai-chat-area');
                    if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
                });
            });
        }

        let _ownerTrendChart = null;

        function renderOwnerTrendChart(tf, alpineComponent) {
            const canvas = document.getElementById('trendChart');
            if (!canvas) return;

            if (typeof Chart === 'undefined') {
                setTimeout(function() {
                    renderOwnerTrendChart(tf, alpineComponent);
                }, 100);
                return;
            }

            const weeklyEl = document.getElementById('weekly-trend-data');
            const monthlyEl = document.getElementById('monthly-trend-data');
            const yearlyEl = document.getElementById('yearly-trend-data');
            if (!weeklyEl || !monthlyEl) return;

            const weeklyData = JSON.parse(weeklyEl.textContent || '[]');
            const monthlyData = JSON.parse(monthlyEl.textContent || '[]');
            const yearlyData = yearlyEl ? JSON.parse(yearlyEl.textContent || '[]') : [];

            const datasets = {
                'Harian': {
                    labels: weeklyData.map(d => d.day_name),
                    fullLabels: weeklyData.map(d => (d.full_day_name || d.day_name) + ' (' + (d.date || '') + ')'),
                    data: weeklyData.map(d => Number(d.omset) || 0),
                    rawItems: weeklyData
                },
                'Mingguan': {
                    labels: monthlyData.map(w => w.label),
                    fullLabels: monthlyData.map(w => (w.full_name || w.label) + ' (' + (w.date || '') + ')'),
                    data: monthlyData.map(w => Number(w.omset) || 0),
                    rawItems: monthlyData
                },
                'Bulanan': {
                    labels: yearlyData.map(m => m.label),
                    fullLabels: yearlyData.map(m => (m.full_name || m.label)),
                    data: yearlyData.map(m => Number(m.omset) || 0),
                    rawItems: yearlyData
                }
            };

            let normalizedTf = 'Harian';
            if (tf) {
                const s = tf.toString().toLowerCase();
                if (s === 'bulanan') normalizedTf = 'Bulanan';
                else if (s === 'mingguan') normalizedTf = 'Mingguan';
                else normalizedTf = 'Harian';
            }
            const target = datasets[normalizedTf] || datasets['Harian'];

            if (_ownerTrendChart) {
                _ownerTrendChart.destroy();
                _ownerTrendChart = null;
            }

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

            _ownerTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...target.labels],
                    datasets: [{
                        label: 'Omset',
                        data: [...target.data],
                        borderColor: '#059669',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#059669',
                        pointBorderWidth: 2.5,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#059669',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3,
                        fill: true,
                        backgroundColor: gradient,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    onHover: (event, chartElement) => {
                        if (event && event.native && event.native.target) {
                            event.native.target.style.cursor = (chartElement && chartElement.length) ? 'pointer' : 'default';
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements && elements.length > 0 && alpineComponent) {
                            const index = elements[0].index;
                            if (target.rawItems && target.rawItems[index]) {
                                alpineComponent.openPointDetail({
                                    ...target.rawItems[index],
                                    timeframe: normalizedTf,
                                    fullTitle: target.fullLabels[index]
                                });
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 12, weight: 'bold', family: 'Outfit, Segoe UI' },
                            bodyFont: { size: 12, weight: '600', family: 'Outfit, Segoe UI' },
                            padding: 12,
                            cornerRadius: 14,
                            displayColors: false,
                            callbacks: {
                                title: (tooltipItems) => {
                                    const index = tooltipItems[0].dataIndex;
                                    return target.fullLabels[index] || '';
                                },
                                label: (context) => {
                                    const index = context.dataIndex;
                                    const item = target.rawItems ? target.rawItems[index] : null;
                                    const lines = [
                                        'Omset: Rp ' + Number(context.raw).toLocaleString('id-ID')
                                    ];
                                    if (item && item.profit !== undefined) {
                                        lines.push('Est. Profit: Rp ' + Number(item.profit).toLocaleString('id-ID'));
                                    }
                                    if (item && item.count !== undefined) {
                                        lines.push('Transaksi: ' + item.count + ' Nota');
                                    }
                                    lines.push('Klik titik untuk rincian');
                                    return lines;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(241, 245, 249, 0.8)' },
                            ticks: {
                                font: { size: 10, weight: '600', family: 'Outfit, Segoe UI' },
                                color: '#94a3b8',
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'jt';
                                    if (value >= 1000) return (value / 1000) + 'rb';
                                    return value;
                                }
                            },
                            border: { dash: [5, 5] }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: '700', family: 'Outfit, Segoe UI' },
                                color: '#64748b'
                            }
                        }
                    }
                }
            });
        }
    </script>

    <!-- Alpine.js Owner State -->
    <div x-data="{
        revenue: {{ (int) $activeRevenue }},
        realizedCash: {{ (int) $realizedCash }},
        outstandingReceivables: {{ (int) $outstandingReceivables }},
        activePeriodReceivables: {{ (int) $activePeriodReceivables }},
        transactions: {{ (int) $activeTransactionsCount }},
        expenses: {{ (int) $activeExpenses }},
        lowStockCount: {{ (int) $lowStockCount }},
        timeframe: '{{ $filterType === "bulanan" ? "Bulanan" : ($filterType === "mingguan" ? "Mingguan" : "Harian") }}',
        chartDatasets: null,
        selectedPointData: null,
        showPointModal: false,
        showTable: localStorage.getItem('owner_active_card') !== null,
        activeCard: localStorage.getItem('owner_active_card'),
        showAiModal: false,
        aiLoading: false,
        aiAnalysis: '',
        aiError: '',
        chatMessages: [],
        chatInput: '',
        chatLoading: false,
        chatError: '',
        activeSkill: 'general',
        skillCache: {},
        showProductDetailModal: false,
        productDetailLoading: false,
        productDetailData: null,
        productDetailError: '',
        showAllBestSellersModal: false,
        allBestSellersLoading: false,
        allBestSellersData: [],
        allBestSellersPeriod: 'harian',
        allBestSellersPeriodLabel: '',
        allBestSellersLimit: 20,
        allBestSellersSearch: '',
        openBestSellerDetail(productName, periodType = 'harian') {
            this.showProductDetailModal = true;
            this.productDetailLoading = true;
            this.productDetailError = '';
            this.productDetailData = null;

            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('name', productName);
            urlParams.set('period_type', periodType);

            fetch('{{ route("owner.dashboard.best-seller-detail") }}?' + urlParams.toString())
                .then(res => {
                    if (!res.ok) throw new Error('Gagal memuat detail produk');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        this.productDetailData = data;
                    } else {
                        throw new Error(data.message || 'Gagal memuat data produk.');
                    }
                })
                .catch(err => {
                    this.productDetailError = err.message || 'Terjadi kesalahan sistem.';
                })
                .finally(() => {
                    this.productDetailLoading = false;
                });
        },
        openAllBestSellers(period = 'harian', limit = null) {
            this.showAllBestSellersModal = true;
            this.allBestSellersLoading = true;
            this.allBestSellersPeriod = period;
            if (limit !== null) {
                this.allBestSellersLimit = limit;
            }
            this.allBestSellersSearch = '';

            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('period_type', period);
            urlParams.set('limit', this.allBestSellersLimit);

            fetch('{{ route("owner.dashboard.best-sellers-all") }}?' + urlParams.toString())
                .then(res => {
                    if (!res.ok) throw new Error('Gagal mengambil daftar produk');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        this.allBestSellersData = data.items || [];
                        this.allBestSellersPeriodLabel = data.period_label || '';
                    }
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => {
                    this.allBestSellersLoading = false;
                });
        },
        changeBestSellersLimit(newLimit) {
            this.allBestSellersLimit = newLimit;
            this.openAllBestSellers(this.allBestSellersPeriod, newLimit);
        },
        switchSkill(skill) {
            if (this.activeSkill === skill) return;
            if (this.skillCache[this.activeSkill]) {
                this.skillCache[this.activeSkill].chatMessages = [...this.chatMessages];
            }
            this.activeSkill = skill;
            this.chatInput   = '';
            this.chatError   = '';
            this.fetchAiAnalysis(false);
        },
        fetchAiAnalysis(refresh = false) {
            fetchOwnerAiAnalysis(this, refresh);
        },
        fetchChatReply() {
            fetchOwnerChatReply(this);
        },
        clearChat() {
            this.chatMessages = [];
            this.chatInput    = '';
            this.chatError    = '';
            if (this.skillCache[this.activeSkill]) {
                this.skillCache[this.activeSkill].chatMessages = [];
            }
        },
        renderMarkdown(md) {
            return renderOwnerMarkdown(md);
        },
        formatQty(num) {
            const val = parseFloat(num);
            if (isNaN(val)) return '0';
            return Number(val.toFixed(2)).toLocaleString('id-ID', { maximumFractionDigits: 2 });
        },
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
        },
        selectedPointData: null,
        showPointModal: false,
        setTimeframe(tf) {
            this.timeframe = tf;
            renderOwnerTrendChart(tf, this);
        },
        openPointDetail(pointData) {
            this.selectedPointData = pointData;
            this.showPointModal = true;
        },
        initChart() {
            this.$nextTick(() => {
                renderOwnerTrendChart(this.timeframe, this);
            });
        }
    }" x-init="initChart()" class="flex flex-col gap-8 max-w-full overflow-hidden">

        <!-- Filter Toggles (Forest Green Brand Theme) -->
        <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-100 shadow-md">
            <!-- Left Side: Title and badge -->
            <div class="flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-extrabold text-slate-800 text-lg leading-tight">
                        Laporan Penjualan — {{ $titleLabel }}
                    </h3>
                    @if($selectedBranch)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                             {{ $selectedBranch }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 font-medium">Gunakan tab filter dan filter cabang untuk menganalisis performa bisnis Anda</p>
            </div>
            
            <!-- Right Side: Dropdowns and Filters Controls -->
            <div x-data="{ filterType: '{{ $filterType }}' }" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <!-- Single Merged GET Form for both Filters -->
                <form method="GET" action="{{ route('owner.dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <!-- Dropdown Filter Cabang -->
                    <select name="branch" onchange="this.form.submit()" 
                            class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">
                        <option value=""> Semua Cabang</option>
                        @foreach($branches as $branchName)
                            @if($branchName)
                                <option value="{{ $branchName }}" {{ $selectedBranch === $branchName ? 'selected' : '' }}>
                                     {{ $branchName }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Dropdown Filter Tipe Waktu -->
                    <select name="filter_type" x-model="filterType" onchange="this.form.submit()"
                            class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">
                        <option value="harian"> Harian</option>
                        <option value="mingguan"> Mingguan</option>
                        <option value="bulanan"> Bulanan</option>
                    </select>

                    <!-- Input Tanggal Spesifik (Harian) -->
                    <input type="date" name="date" value="{{ $selectedDate }}" x-show="filterType === 'harian'" onchange="this.form.submit()"
                           class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">

                    <!-- Input Minggu Spesifik (Mingguan) -->
                    <input type="week" name="week" value="{{ $selectedWeek }}" x-show="filterType === 'mingguan'" onchange="this.form.submit()"
                           class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">

                    <!-- Input Bulan Spesifik (Bulanan) -->
                    <input type="month" name="month" value="{{ $selectedMonth }}" x-show="filterType === 'bulanan'" onchange="this.form.submit()"
                           class="w-full sm:w-auto text-xs font-bold border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2 bg-slate-50 text-slate-700 hover:bg-slate-100/50 transition cursor-pointer">
                </form>
            </div>
        </div>

        <!-- Hint Banner with AI Consultation Option -->
        <div class="bg-gradient-to-r from-emerald-800 via-teal-900 to-emerald-950 text-white px-6 py-4 rounded-3xl shadow-lg border border-emerald-700/25 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center shrink-0 border border-white/10 shadow-inner">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-black tracking-wider uppercase text-emerald-300">Konsultan Bisnis AI</h4>
                    <p class="text-xs text-emerald-100 font-semibold leading-snug">Klik tombol analisis untuk mendapatkan laporan taktis, strategi produk, dan analisis performa dari Gemini AI.</p>
                </div>
            </div>
            <button @click="fetchAiAnalysis(false)" type="button" class="w-full md:w-auto px-5 py-2.5 bg-gradient-to-r from-violet-600 via-indigo-600 to-violet-700 hover:from-violet-500 hover:to-indigo-500 text-white font-black text-xs rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 flex items-center justify-center gap-2 shrink-0 border border-violet-400/20 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5 animate-spin" x-show="aiLoading" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Analisis Performa AI</span>
            </button>
        </div>

        <!-- 1. TOP ROW: Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Card 1: Total Omset & Kas Masuk Riil -->
            <div id="card-omset"
                 x-on:click="toggleCard('omset')"
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;"
                 :class="activeCard === 'omset' ? 'ring-4 ring-emerald-500/40 scale-[1.03] shadow-xl border-emerald-500' : 'hover:scale-[1.02] shadow-md border-emerald-200/50'"
                 class="p-5 sm:p-6 rounded-3xl flex flex-col justify-between gap-3.5 group transition duration-300 cursor-pointer select-none relative overflow-hidden">
                <!-- Soft Glow Backdrop Decoration -->
                <div class="absolute -right-10 -top-10 w-24 h-24 rounded-full bg-emerald-500/10 blur-2xl group-hover:bg-emerald-500/20 transition duration-300"></div>
                <div class="flex flex-col gap-1 min-w-0 flex-1 z-10 pr-10">
                    <div class="flex items-center gap-1.5 pr-10">
                        <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Total Omset Penjualan</span>
                        <span class="text-[8px] font-black text-emerald-700 leading-none bg-emerald-100/80 border border-emerald-300/70 px-1 py-0.5 rounded tracking-wide shrink-0">FAKTUR</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight mt-0.5 whitespace-nowrap" x-text="formatRupiah({{ (int) $activeRevenue }})">Rp {{ number_format($activeRevenue, 0, ',', '.') }}</h3>

                    <!-- Dual Metric Badges: Realisasi Kas Masuk & Piutang Tempo -->
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex items-center justify-between gap-1.5 select-none whitespace-nowrap bg-emerald-200/50 border border-emerald-300/60 rounded-lg px-2 py-0.5 text-[10px] font-bold text-emerald-900" title="Uang riil yang masuk dari penjualan cash/lunas serta DP dan cicilan tempo yang dibayar">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0 0a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V14.25m-17.25-9.75v10.5m17.25-10.5v10.5m0-10.5a.75.75 0 0 1 .75.75v.75m0 0a60.07 60.07 0 0 0-15.797-2.101c-.727-.198-1.453.342-1.453 1.096V4.5m17.25 0v.75" />
                                </svg>
                                Kas Masuk Riil:
                            </span>
                            <span class="font-extrabold text-emerald-800" x-text="formatRupiah({{ (int) $realizedCash }})">Rp {{ number_format($realizedCash, 0, ',', '.') }}</span>
                        </div>
                        @if($outstandingReceivables > 0)
                            <div class="flex items-center justify-between gap-1.5 select-none whitespace-nowrap bg-amber-100/70 border border-amber-300/70 rounded-lg px-2 py-0.5 text-[10px] font-bold text-amber-900" title="Total saldo piutang tempo yang belum dibayar pelanggan">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Piutang Tempo:
                                </span>
                                <span class="font-extrabold text-amber-800" x-text="formatRupiah({{ (int) $outstandingReceivables }})">Rp {{ number_format($outstandingReceivables, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-1.5 select-none whitespace-nowrap bg-white/70 border border-emerald-200/50 rounded-lg px-2 py-0.5 text-[10px] font-bold text-slate-700" title="Kas masuk riil setelah dikurangi beban pengeluaran operasional">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                                Kas Bersih Riil:
                            </span>
                            <span class="font-extrabold text-slate-900" x-text="formatRupiah({{ (int) ($realizedCash - $activeExpenses) }})">Rp {{ number_format($realizedCash - $activeExpenses, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 text-[11px] font-extrabold {{ $revenueGrowthPercent >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                        @if($revenueGrowthPercent > 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="truncate">+{{ number_format($revenueGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @elseif($revenueGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($revenueGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil {{ $comparisonLabel }}</span>
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
                            <span class="truncate">+{{ number_format($profitGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @elseif($profitGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($profitGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil {{ $comparisonLabel }}</span>
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
                            <span class="truncate">+{{ number_format($transactionGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @elseif($transactionGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($transactionGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil {{ $comparisonLabel }}</span>
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
                            <span class="truncate">+{{ number_format($expenseGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @elseif($expenseGrowthPercent < 0)
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0H11.25m-11.25 0V8.25" />
                            </svg>
                            <span class="truncate">{{ number_format($expenseGrowthPercent, 1) }}% {{ $comparisonLabel }}</span>
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                            <span class="truncate">Stabil {{ $comparisonLabel }}</span>
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

        <!-- 1.5. ROW: Metrik Efisiensi & Analisis Margin Bisnis -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Metric 1: Gross Profit Margin -->
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Gross Profit Margin</span>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-slate-800">{{ $grossMarginPercent }}%</span>
                        <span class="text-[11px] font-bold text-emerald-600">Laba Kotor</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold truncate" title="Total Rp {{ number_format($activeGrossProfit, 0, ',', '.') }}">HPP: Rp {{ number_format($activeRevenue - $activeGrossProfit, 0, ',', '.') }}</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
            </div>

            <!-- Metric 2: Net Operating Margin -->
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Net Operating Margin</span>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-slate-800">{{ $netMarginPercent }}%</span>
                        <span class="text-[11px] font-bold text-teal-600">Laba Bersih</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold truncate" title="Setelah beban operasional">Setelah biaya operasional</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                    </svg>
                </div>
            </div>

            <!-- Metric 3: Rasio Beban Operasional (OER) -->
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Rasio Beban (OER)</span>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-slate-800">{{ $oerPercent }}%</span>
                        <span class="text-[11px] font-bold text-amber-600">dari Omset</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold truncate" title="Total Rp {{ number_format($activeExpenses, 0, ',', '.') }}">Rp {{ number_format($activeExpenses, 0, ',', '.') }}</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
                    </svg>
                </div>
            </div>

            <!-- Metric 4: Average Order Value (AOV) -->
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Average Order Value (AOV)</span>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-black text-slate-800">Rp {{ number_format($activeAov, 0, ',', '.') }}</span>
                    </div>
                    <span class="text-[10px] font-bold {{ $aovGrowthPercent >= 0 ? 'text-emerald-600' : 'text-rose-600' }} truncate">
                        {{ $aovGrowthPercent >= 0 ? '+' : '' }}{{ number_format($aovGrowthPercent, 1) }}% {{ $comparisonLabel }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5h6.75M8.625 12.75h6.75" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 1.6. ROW: Saluran Penjualan & Distribusi Arus Kas Pembayaran -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Saluran Penjualan: Retail vs Wholesale -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between gap-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Segmentasi Saluran Penjualan</h4>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Komparasi kontribusi transaksi eceran (POS) vs nota partai (Grosir)</p>
                    </div>
                    <span class="text-[10px] font-extrabold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-xl">2 Saluran</span>
                </div>

                <!-- Channel Visual Bars -->
                <div class="flex flex-col gap-4">
                    <!-- Retail -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-extrabold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                Retail POS (Kasir)
                            </span>
                            <span class="font-black text-slate-900">
                                Rp {{ number_format($retailStats['omset'], 0, ',', '.') }}
                                <span class="text-slate-400 font-bold ml-1">({{ $retailStats['share_percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, $retailStats['share_percent']) }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-400 font-semibold">
                            <span>{{ number_format($retailStats['count']) }} Nota Transaksi</span>
                            <span>Margin Kotor: Rp {{ number_format($retailStats['profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Wholesale -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-extrabold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-teal-500 inline-block"></span>
                                Partai Wholesale
                            </span>
                            <span class="font-black text-slate-900">
                                Rp {{ number_format($wholesaleStats['omset'], 0, ',', '.') }}
                                <span class="text-slate-400 font-bold ml-1">({{ $wholesaleStats['share_percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-teal-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, $wholesaleStats['share_percent']) }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-400 font-semibold">
                            <span>{{ number_format($wholesaleStats['count']) }} Nota Partai</span>
                            <span>Margin Kotor: Rp {{ number_format($wholesaleStats['profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metode Pembayaran & Realisasi Kas -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between gap-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Distribusi Metode Pembayaran</h4>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Komposisi penerimaan dana dan likuiditas kas masuk</p>
                    </div>
                    <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200/60 rounded-xl px-2.5 py-1 text-[10px] font-bold text-emerald-800">
                        <span>Realisasi Kas:</span>
                        <span class="font-black">{{ $cashRealizationPercent }}%</span>
                    </div>
                </div>

                <!-- Payment Methods Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Cash -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500">Tunai (Cash)</span>
                            <span class="text-[10px] font-black text-slate-700">{{ $paymentBreakdown['Cash']['share_percent'] }}%</span>
                        </div>
                        <span class="text-sm font-black text-slate-800">Rp {{ number_format($paymentBreakdown['Cash']['omset'], 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">{{ $paymentBreakdown['Cash']['count'] }} Transaksi</span>
                    </div>

                    <!-- Transfer -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500">Transfer Bank</span>
                            <span class="text-[10px] font-black text-slate-700">{{ $paymentBreakdown['Transfer']['share_percent'] }}%</span>
                        </div>
                        <span class="text-sm font-black text-slate-800">Rp {{ number_format($paymentBreakdown['Transfer']['omset'], 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">{{ $paymentBreakdown['Transfer']['count'] }} Transaksi</span>
                    </div>

                    <!-- QRIS -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500">QRIS</span>
                            <span class="text-[10px] font-black text-slate-700">{{ $paymentBreakdown['QRIS']['share_percent'] }}%</span>
                        </div>
                        <span class="text-sm font-black text-slate-800">Rp {{ number_format($paymentBreakdown['QRIS']['omset'], 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">{{ $paymentBreakdown['QRIS']['count'] }} Transaksi</span>
                    </div>

                    <!-- Tempo -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200/60 flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-amber-700">Piutang / Tempo</span>
                            <span class="text-[10px] font-black text-amber-800">{{ $paymentBreakdown['Tempo']['share_percent'] }}%</span>
                        </div>
                        <span class="text-sm font-black text-amber-900">Rp {{ number_format($paymentBreakdown['Tempo']['omset'], 0, ',', '.') }}</span>
                        <span class="text-[10px] text-amber-700 font-semibold">{{ $paymentBreakdown['Tempo']['count'] }} Transaksi</span>
                    </div>
                </div>
            </div>
        </div>

        @if(count($branchScorecard) > 0)
        <!-- 1.7. ROW: Matriks Performa Multi-Cabang (Tampil saat Semua Cabang dipilih) -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <h4 class="font-extrabold text-slate-800 text-sm leading-tight">Matriks Performa Antar Cabang</h4>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Perbandingan performa pendapatan, laba, dan efisiensi belanja di seluruh cabang toko</p>
                </div>
                <span class="text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-xl">
                    {{ count($branchScorecard) }} Cabang Terdata
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-3">Nama Cabang</th>
                            <th class="py-3 px-3 text-right">Total Omset</th>
                            <th class="py-3 px-3 text-center">Share Omset</th>
                            <th class="py-3 px-3 text-right">Laba Bersih</th>
                            <th class="py-3 px-3 text-center">Net Margin</th>
                            <th class="py-3 px-3 text-center">Transaksi</th>
                            <th class="py-3 px-3 text-right">AOV (Rata-rata)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-700">
                        @foreach($branchScorecard as $idx => $bsc)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-3 font-extrabold text-slate-800 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black flex items-center justify-center shrink-0">
                                    {{ $idx + 1 }}
                                </span>
                                {{ $bsc['name'] }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-slate-900">
                                Rp {{ number_format($bsc['omset'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-[11px]">
                                    {{ $bsc['share_percent'] }}%
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-black {{ $bsc['net_profit'] >= 0 ? 'text-teal-700' : 'text-rose-600' }}">
                                Rp {{ number_format($bsc['net_profit'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center font-bold">
                                {{ $bsc['margin_percent'] }}%
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-600">
                                {{ number_format($bsc['count']) }} Nota
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-slate-800">
                                Rp {{ number_format($bsc['aov'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- 2. MIDDLE ROW: Sales Trends Bar Graph & Low Stock Table -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Beautiful Sales Trend Bar Graph (HTML/CSS) -->
            <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col justify-between gap-6">
                <!-- Chart Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-base leading-tight" x-text="timeframe === 'Bulanan' ? 'Tren Omset Bulanan' : (timeframe === 'Mingguan' ? 'Tren Omset Mingguan' : 'Tren Omset Harian')">Tren Omset Harian</h4>
                        <p class="text-xs text-slate-400 font-medium mt-1" x-text="timeframe === 'Bulanan' ? 'Laporan omset bulanan sepanjang tahun {{ $startOfMonth->year }} (Januari s/d Desember)' : (timeframe === 'Mingguan' ? 'Laporan omset per pekan dalam bulan berjalan (Minggu 1 s/d 5)' : 'Laporan omset harian dalam 7 hari sepekan (Senin s/d Minggu)')">Laporan omset harian dalam 7 hari sepekan</p>
                    </div>
                    <div class="flex gap-1.5 p-1 bg-slate-50 border border-slate-100 rounded-xl shrink-0">
                        <button type="button" @click="setTimeframe('Harian')" :class="timeframe === 'Harian' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Harian</button>
                        <button type="button" @click="setTimeframe('Mingguan')" :class="timeframe === 'Mingguan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Mingguan</button>
                        <button type="button" @click="setTimeframe('Bulanan')" :class="timeframe === 'Bulanan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Bulanan</button>
                    </div>
                </div>

                <!-- Live Chart.js Responsive Line Chart -->
                <div class="relative w-full h-64">
                    <canvas id="trendChart"></canvas>
                </div>

                <!-- Chart Interactive Footer Hint -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center text-[10px] text-slate-400 font-semibold pt-1 border-t border-slate-50 gap-1.5">
                    <span class="flex items-center gap-1.5 text-emerald-700 font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Titik grafik interaktif &mdash; klik titik mana pun untuk membuka rincian performa</span>
                    </span>
                    <span class="text-slate-400 font-mono" x-text="timeframe === 'Bulanan' ? 'Rentang: 12 Bulan Tahun {{ $startOfMonth->year }}' : (timeframe === 'Mingguan' ? 'Rentang: Minggu 1 s/d 5 Bulan Berjalan' : 'Rentang: 7 Hari Pekan Berjalan')"></span>
                </div>
            </div>

            <!-- Side alert section: Low Stock products detailed listing -->
            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-base leading-tight">Detail Peringatan Stok</h4>
                        <p class="text-xs text-slate-400 font-medium mt-1">Status stok kritis & valuasi modal toko:</p>
                    </div>
                    <a href="{{ route('owner.dead-stock') }}"
                       class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-extrabold uppercase tracking-wider transition shrink-0 whitespace-nowrap">
                        Cek Barang Mati &rarr;
                    </a>
                </div>

                <!-- Valuasi Modal Persediaan Summary -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Total Modal Stok (HPP)</span>
                        <span class="text-xs font-black text-slate-800">Rp {{ number_format($inventoryMetrics['total_cost_value'], 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold">{{ number_format($inventoryMetrics['total_skus']) }} SKU Terdaftar</span>
                    </div>
                    <div class="text-right flex flex-col">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Potensi Nilai Jual</span>
                        <span class="text-xs font-black text-emerald-700">Rp {{ number_format($inventoryMetrics['total_retail_value'], 0, ',', '.') }}</span>
                        <span class="text-[10px] font-bold text-rose-600">{{ $inventoryMetrics['low_stock_skus'] }} SKU Menipis</span>
                    </div>
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

        <!-- Section Header: Best Seller -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-100 shadow-md">
            <div>
                <h4 class="font-extrabold text-slate-800 text-base leading-tight">Analisis Produk Terlaris (Best Seller)</h4>
                <p class="text-xs text-slate-400 font-medium mt-1">Daftar produk paling populer berdasarkan jumlah transaksi dan volume penjualan (klik produk untuk membuka rincian)</p>
            </div>
            <div class="grid grid-cols-3 sm:flex sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <button type="button" @click="openAllBestSellers('{{ $filterType }}')" 
                   class="px-3 sm:px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5 sm:gap-2 text-center cursor-pointer">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span>Daftar Lengkap</span>
                </button>
                <a href="{{ route('owner.dashboard.export-best-sellers', ['filter_type' => $filterType, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'branch' => $selectedBranch]) }}" 
                   class="px-3 sm:px-4 py-2.5 bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5 sm:gap-2 text-center">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span class="hidden md:inline">Ekspor Excel Terlaris ({{ $filterType === 'harian' ? 'Harian' : ($filterType === 'mingguan' ? 'Mingguan' : 'Bulanan') }})</span>
                    <span class="md:hidden">Excel</span>
                </a>
                <a href="{{ route('owner.dashboard.export-best-sellers', ['filter_type' => $filterType, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'branch' => $selectedBranch, 'format' => 'pdf']) }}" 
                   class="px-3 sm:px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-800 hover:bg-rose-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5 sm:gap-2 text-center">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span class="hidden md:inline">Ekspor PDF Terlaris ({{ $filterType === 'harian' ? 'Harian' : ($filterType === 'mingguan' ? 'Mingguan' : 'Bulanan') }})</span>
                    <span class="md:hidden">PDF</span>
                </a>
            </div>
        </div>

        <!-- 2.5. Best Seller Products Section (Harian, Mingguan, Bulanan) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Column 1: Best Seller Hari Ini / Kemarin -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">
                            Terlaris Harian
                        </h4>
                        <p class="text-[10px] text-slate-400 font-medium font-mono">
                            Tanggal: {{ $bestSellerDay->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse($bestSellersToday as $item)
                        <div @click="openBestSellerDetail('{{ addslashes($item['name']) }}', 'harian')"
                             class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-amber-50/70 transition duration-150 border border-transparent hover:border-amber-200 cursor-pointer group"
                             title="Klik untuk membuka rincian transaksi {{ $item['name'] }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Rank Indicator -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold shrink-0 border
                                    @if($loop->iteration == 1) bg-amber-100 text-amber-800 border-amber-200
                                    @elseif($loop->iteration == 2) bg-slate-200 text-slate-800 border-slate-300
                                    @elseif($loop->iteration == 3) bg-orange-100 text-orange-800 border-orange-200
                                    @else bg-slate-50 text-slate-500 border-slate-100 @endif">
                                    #{{ $loop->iteration }}
                                </div>
                                
                                <!-- Product Image / Initial -->
                                @if(!empty($item['image_path']))
                                    <img src="/storage/{{ $item['image_path'] }}" class="w-10 h-10 object-cover rounded-xl border border-slate-100 shadow-sm shrink-0" alt="Foto">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 font-extrabold text-[10px] shrink-0 shadow-sm">
                                        {{ strtoupper(substr($item['name'], 0, 2)) }}
                                    </div>
                                @endif

                                <!-- Product Info -->
                                <div class="min-w-0">
                                    <h5 class="font-bold text-xs text-slate-800 truncate group-hover:text-amber-800 transition" title="{{ $item['name'] }}">{{ $item['name'] }}</h5>
                                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider block mt-0.5">{{ $item['category'] ?? 'Umum' }}</span>
                                </div>
                            </div>

                            <!-- Popularity & Qty Info -->
                            <div class="text-right shrink-0 flex flex-col items-end gap-0.5">
                                <span class="bg-amber-100/70 text-amber-800 border border-amber-200/50 rounded-lg px-2 py-0.5 text-[10px] font-black shadow-sm whitespace-nowrap">
                                    {{ $item['count'] }}x Transaksi
                                </span>
                                <span class="text-[10px] text-slate-400 font-bold">
                                    Total: {{ rtrim(rtrim(number_format($item['qty'], 2, ',', '.'), '0'), ',') }} {{ $item['unit'] }}
                                </span>
                                <span class="text-[9px] font-extrabold text-amber-700 opacity-0 group-hover:opacity-100 transition duration-150 flex items-center gap-0.5">
                                    Lihat Detail &rarr;
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs font-semibold flex flex-col items-center gap-2">
                            <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Belum ada transaksi pada tanggal ini</span>
                        </div>
                    @endforelse

                    @if(count($bestSellersToday) > 0)
                        <button type="button" @click="openAllBestSellers('harian', 20)" 
                                class="mt-2 w-full py-2.5 px-3 bg-amber-50/80 hover:bg-amber-100 text-amber-900 border border-amber-200/70 rounded-2xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                            <span>Buka Ranking Lengkap Harian (Top 20+)</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Column 2: Best Seller Pekan Ini / Pekan Lalu -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">
                            Terlaris Mingguan
                        </h4>
                        <p class="text-[10px] text-slate-400 font-medium font-mono">
                            Pekan: {{ $startOfWeek->translatedFormat('d M') }} - {{ $endOfWeek->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse($bestSellersWeekly as $item)
                        <div @click="openBestSellerDetail('{{ addslashes($item['name']) }}', 'mingguan')"
                             class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-emerald-50/70 transition duration-150 border border-transparent hover:border-emerald-200 cursor-pointer group"
                             title="Klik untuk membuka rincian transaksi {{ $item['name'] }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Rank Indicator -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold shrink-0 border
                                    @if($loop->iteration == 1) bg-amber-100 text-amber-800 border-amber-200
                                    @elseif($loop->iteration == 2) bg-slate-200 text-slate-800 border-slate-300
                                    @elseif($loop->iteration == 3) bg-orange-100 text-orange-800 border-orange-200
                                    @else bg-slate-50 text-slate-500 border-slate-100 @endif">
                                    #{{ $loop->iteration }}
                                </div>
                                
                                <!-- Product Image / Initial -->
                                @if(!empty($item['image_path']))
                                    <img src="/storage/{{ $item['image_path'] }}" class="w-10 h-10 object-cover rounded-xl border border-slate-100 shadow-sm shrink-0" alt="Foto">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 font-extrabold text-[10px] shrink-0 shadow-sm">
                                        {{ strtoupper(substr($item['name'], 0, 2)) }}
                                    </div>
                                @endif

                                <!-- Product Info -->
                                <div class="min-w-0">
                                    <h5 class="font-bold text-xs text-slate-800 truncate group-hover:text-emerald-800 transition" title="{{ $item['name'] }}">{{ $item['name'] }}</h5>
                                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider block mt-0.5">{{ $item['category'] ?? 'Umum' }}</span>
                                </div>
                            </div>

                            <!-- Popularity & Qty Info -->
                            <div class="text-right shrink-0 flex flex-col items-end gap-0.5">
                                <span class="bg-emerald-100/70 text-emerald-800 border border-emerald-200/50 rounded-lg px-2 py-0.5 text-[10px] font-black shadow-sm whitespace-nowrap">
                                    {{ $item['count'] }}x Transaksi
                                </span>
                                <span class="text-[10px] text-slate-400 font-bold">
                                    Total: {{ rtrim(rtrim(number_format($item['qty'], 2, ',', '.'), '0'), ',') }} {{ $item['unit'] }}
                                </span>
                                <span class="text-[9px] font-extrabold text-emerald-700 opacity-0 group-hover:opacity-100 transition duration-150 flex items-center gap-0.5">
                                    Lihat Detail &rarr;
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs font-semibold flex flex-col items-center gap-2">
                            <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Belum ada transaksi pada pekan ini</span>
                        </div>
                    @endforelse

                    @if(count($bestSellersWeekly) > 0)
                        <button type="button" @click="openAllBestSellers('mingguan', 20)" 
                                class="mt-2 w-full py-2.5 px-3 bg-emerald-50/80 hover:bg-emerald-100 text-emerald-900 border border-emerald-200/70 rounded-2xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                            <span>Buka Ranking Lengkap Mingguan (Top 20+)</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Column 3: Best Seller Bulan Ini / Bulan Lalu -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.173-.439.82-.439.993 0l1.905 4.821 5.215.356c.478.033.668.627.294.948l-3.927 3.374 1.22 5.097c.112.469-.4.842-.816.578L12 16.03l-4.526 2.766c-.416.264-.928-.109-.816-.578l1.22-5.097-3.927-3.374c-.374-.32-.184-.915.294-.948l5.215-.356 1.905-4.821z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm leading-tight">
                            Terlaris Bulanan
                        </h4>
                        <p class="text-[10px] text-slate-400 font-medium font-mono">
                            Bulan: {{ $startOfMonth->translatedFormat('F Y') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse($bestSellersMonthly as $item)
                        <div @click="openBestSellerDetail('{{ addslashes($item['name']) }}', 'bulanan')"
                             class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-teal-50/70 transition duration-150 border border-transparent hover:border-teal-200 cursor-pointer group"
                             title="Klik untuk membuka rincian transaksi {{ $item['name'] }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Rank Indicator -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold shrink-0 border
                                    @if($loop->iteration == 1) bg-amber-100 text-amber-800 border-amber-200
                                    @elseif($loop->iteration == 2) bg-slate-200 text-slate-800 border-slate-300
                                    @elseif($loop->iteration == 3) bg-orange-100 text-orange-800 border-orange-200
                                    @else bg-slate-50 text-slate-500 border-slate-100 @endif">
                                    #{{ $loop->iteration }}
                                </div>
                                
                                <!-- Product Image / Initial -->
                                @if(!empty($item['image_path']))
                                    <img src="/storage/{{ $item['image_path'] }}" class="w-10 h-10 object-cover rounded-xl border border-slate-100 shadow-sm shrink-0" alt="Foto">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 font-extrabold text-[10px] shrink-0 shadow-sm">
                                        {{ strtoupper(substr($item['name'], 0, 2)) }}
                                    </div>
                                @endif

                                <!-- Product Info -->
                                <div class="min-w-0">
                                    <h5 class="font-bold text-xs text-slate-800 truncate group-hover:text-teal-800 transition" title="{{ $item['name'] }}">{{ $item['name'] }}</h5>
                                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider block mt-0.5">{{ $item['category'] ?? 'Umum' }}</span>
                                </div>
                            </div>

                            <!-- Popularity & Qty Info -->
                            <div class="text-right shrink-0 flex flex-col items-end gap-0.5">
                                <span class="bg-teal-100/70 text-teal-800 border border-teal-200/50 rounded-lg px-2 py-0.5 text-[10px] font-black shadow-sm whitespace-nowrap">
                                    {{ $item['count'] }}x Transaksi
                                </span>
                                <span class="text-[10px] text-slate-400 font-bold">
                                    Total: {{ rtrim(rtrim(number_format($item['qty'], 2, ',', '.'), '0'), ',') }} {{ $item['unit'] }}
                                </span>
                                <span class="text-[9px] font-extrabold text-teal-700 opacity-0 group-hover:opacity-100 transition duration-150 flex items-center gap-0.5">
                                    Lihat Detail &rarr;
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs font-semibold flex flex-col items-center gap-2">
                            <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Belum ada transaksi pada bulan ini</span>
                        </div>
                    @endforelse

                    @if(count($bestSellersMonthly) > 0)
                        <button type="button" @click="openAllBestSellers('bulanan', 20)" 
                                class="mt-2 w-full py-2.5 px-3 bg-teal-50/80 hover:bg-teal-100 text-teal-900 border border-teal-200/70 rounded-2xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                            <span>Buka Ranking Lengkap Bulanan (Top 20+)</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    @endif
                </div>
            </div> <!-- Closes Column 3 Card -->
        </div> <!-- Closes Best Sellers Grid -->

        <!-- 3. BOTTOM ROW: Dynamic Financial Breakdown Table (Collapsible on Card Click) -->
        <div id="breakdown-table-container" x-show="showTable" x-transition style="display: none;"
             class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md flex flex-col gap-4">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h4 class="font-extrabold text-slate-800 text-base leading-tight">
                        @if($filterType === 'harian')
                            Daftar Transaksi Harian
                        @elseif($filterType === 'mingguan')
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
                        @if($filterType === 'harian')
                            Daftar transaksi kasir yang tercatat pada tanggal terpilih
                        @elseif($filterType === 'mingguan')
                            Laporan analisis kinerja keuangan harian dari hari Senin sampai Minggu
                        @else
                            Laporan analisis kinerja keuangan mingguan dalam bulan berjalan
                        @endif
                    </p>
                </div>
                
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                    <!-- Button Ekspor Excel (Visible on all filters) -->
                    <a href="{{ route('owner.dashboard.export', ['filter_type' => $filterType, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'branch' => $selectedBranch]) }}" 
                       class="flex-1 sm:flex-initial px-3 sm:px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5 sm:gap-2 text-center">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span>Ekspor Excel</span>
                    </a>

                    <!-- Button Ekspor PDF (Visible on all filters) -->
                    <a href="{{ route('owner.dashboard.export', ['filter_type' => $filterType, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'branch' => $selectedBranch, 'format' => 'pdf']) }}" 
                       class="flex-1 sm:flex-initial px-3 sm:px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-800 hover:bg-rose-800 hover:text-white text-xs font-bold rounded-xl shadow-sm transition duration-150 flex items-center justify-center gap-1.5 sm:gap-2 text-center">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span>Ekspor PDF</span>
                    </a>

                    @if($filterType === 'harian')
                        <a href="{{ route('owner.transactions.index') }}" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow transition duration-150 flex items-center justify-center gap-1.5 text-center">
                            <span>Lihat Semua Riwayat</span>
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto w-full max-w-full rounded-2xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
                    @if($filterType === 'harian')
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
                                             {{ $trx->payment_method === 'Piutang / Tempo' && $trx->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                                             {{ $trx->payment_method === 'Piutang / Tempo' && $trx->payment_status !== 'paid' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                        ">
                                            @if($trx->payment_method === 'Piutang / Tempo')
                                                Tempo {{ $trx->payment_status === 'paid' ? '(Lunas)' : '(Belum Lunas)' }}
                                            @else
                                                {{ $trx->payment_method }}
                                            @endif
                                        </span>
                                    </td>
                                    <td data-focus="omset" class="px-5 py-4 text-right font-extrabold whitespace-nowrap transition-colors duration-150 text-emerald-700" :class="activeCard === 'omset' || activeCard === 'transactions' ? 'bg-emerald-50/30 text-emerald-800' : 'text-emerald-700'">
                                        Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                        Belum ada catatan riwayat transaksi pada tanggal ini.
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

        <!-- Modal Analisis Performa AI (Premium Glassmorphism Style) -->
        <div x-show="showAiModal" 
             class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto sm:p-6 pt-10 md:pt-16 pb-10" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                 @click="showAiModal = false"></div>

            <!-- Modal Content Card -->
            <div class="relative w-full max-w-3xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xl flex flex-col max-h-[80vh] overflow-hidden transform transition-all">
                
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 dark:text-violet-100 leading-tight">Analisis Kinerja Bisnis AI</h3>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mt-0.5">Powered by Google Gemini</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Clear Chat Button (only visible when there are messages) -->
                        <button x-show="chatMessages.length > 0"
                                @click="clearChat()" 
                                :disabled="chatLoading"
                                title="Hapus riwayat chat"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 disabled:opacity-50 text-rose-500 dark:text-rose-400 rounded-xl text-xs font-bold transition cursor-pointer border border-rose-100 dark:border-rose-800/50">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            <span class="hidden sm:inline">Hapus Chat</span>
                        </button>

                        <!-- Regenerate Button -->
                        <button @click="fetchAiAnalysis(true)" 
                                :disabled="aiLoading || chatLoading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" :class="aiLoading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span class="hidden sm:inline">Regenerasi</span>
                        </button>

                        <!-- Unduh PDF Button -->
                        <a x-show="aiAnalysis && !aiLoading"
                           :href="`{{ route('owner.performance.analysis.export-pdf') }}?skill=${activeSkill}&branch=${new URLSearchParams(window.location.search).get('branch') || ''}&filter_type=${new URLSearchParams(window.location.search).get('filter_type') || 'harian'}&date=${new URLSearchParams(window.location.search).get('date') || ''}&week=${new URLSearchParams(window.location.search).get('week') || ''}&month=${new URLSearchParams(window.location.search).get('month') || ''}`"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Unduh PDF</span>
                        </a>
                        
                        <!-- Close Button -->
                        <button @click="showAiModal = false" 
                                class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <span class="text-lg font-bold leading-none">&times;</span>
                        </button>
                    </div>
                </div>

                <!-- Skill Selector Tabs -->
                <div class="px-5 py-2.5 bg-slate-100/80 dark:bg-slate-800/80 border-b border-slate-200/60 dark:border-slate-800 flex items-center gap-1.5 overflow-x-auto shrink-0 no-scrollbar">
                    <button @click="switchSkill('general')"
                            :class="activeSkill === 'general' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Analisis Umum
                    </button>
                    <button @click="switchSkill('forecast')"
                            :class="activeSkill === 'forecast' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Forecast Penjualan
                    </button>
                    <button @click="switchSkill('branch_comparison')"
                            :class="activeSkill === 'branch_comparison' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Perbandingan Cabang
                    </button>
                    <button @click="switchSkill('restock')"
                            :class="activeSkill === 'restock' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Saran Restok
                    </button>
                    <button @click="switchSkill('slow_moving')"
                            :class="activeSkill === 'slow_moving' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Slow-Moving
                    </button>
                    <button @click="switchSkill('expense_analysis')"
                            :class="activeSkill === 'expense_analysis' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Efisiensi Pengeluaran
                    </button>
                    <button @click="switchSkill('peak_hours')"
                            :class="activeSkill === 'peak_hours' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Pola Waktu
                    </button>
                    <button @click="switchSkill('product_bundling')"
                            :class="activeSkill === 'product_bundling' ? 'bg-violet-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 font-semibold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] whitespace-nowrap transition flex items-center gap-1.5 cursor-pointer border border-slate-200/50 dark:border-slate-700/50">
                        Rekomendasi Bundling
                    </button>
                </div>

                <!-- Body (Scrollable content) -->
                <div class="p-6 overflow-y-auto min-h-[300px] flex-1">
                    
                    <!-- Loading State (initial analysis) -->
                    <div x-show="aiLoading" class="flex flex-col items-center justify-center py-12 gap-4">
                        <div class="relative w-16 h-16">
                            <!-- Inner pulse -->
                            <div class="absolute inset-0 rounded-full bg-violet-500/20 animate-ping"></div>
                            <!-- Core spinning gradient ring -->
                            <div class="w-16 h-16 rounded-full border-4 border-slate-100 border-t-violet-600 animate-spin"></div>
                        </div>
                        <div class="text-center px-4">
                            <p class="text-xs font-extrabold text-slate-700 dark:text-slate-300">Menghubungi Konsultan AI...</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold mt-1 max-w-sm mx-auto">Menganalisis omset, pengeluaran, profitabilitas, serta tingkat persediaan cabang...</p>
                        </div>
                    </div>

                    <!-- Error State (initial analysis) -->
                    <div x-show="!aiLoading && aiError" class="p-5 rounded-2xl bg-rose-50 border border-rose-200/50 text-rose-800 text-xs font-semibold flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <span class="font-extrabold text-sm text-rose-800">Gagal Memuat Analisis AI</span>
                        </div>
                        <p class="text-rose-600 font-semibold" x-text="aiError"></p>
                        <button @click="fetchAiAnalysis(true)" class="mt-2 self-start px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold shadow transition cursor-pointer">
                            Coba Lagi
                        </button>
                    </div>

                    <!-- Content State (Initial Report) -->
                    <div x-show="!aiLoading && !aiError && aiAnalysis" 
                         class="prose prose-slate dark:prose-invert max-w-none prose-sm"
                         x-html="renderMarkdown(aiAnalysis)">
                    </div>

                    <!-- ── Chat Messages Area ────────────────────────────────── -->
                    <div x-show="!aiLoading && !aiError && aiAnalysis && chatMessages.length > 0"
                         id="ai-chat-area"
                         class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
                        
                        <!-- Section Label -->
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Sesi Tanya Jawab</span>
                            <div class="flex-1 h-px bg-slate-100 dark:bg-slate-800"></div>
                        </div>

                        <!-- Chat Bubbles -->
                        <template x-for="(msg, index) in chatMessages" :key="index">
                            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                
                                <!-- AI bubble (left) -->
                                <template x-if="msg.role === 'model'">
                                    <div class="flex items-start gap-2.5 max-w-[88%]">
                                        <div class="w-7 h-7 shrink-0 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-sm mt-0.5"></div>
                                        <div class="rounded-2xl rounded-tl-none bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-4 py-3 text-xs text-slate-700 dark:text-slate-300 font-semibold leading-relaxed shadow-sm prose prose-slate dark:prose-invert prose-xs max-w-none"
                                             x-html="renderMarkdown(msg.text)">
                                        </div>
                                    </div>
                                </template>

                                <!-- User bubble (right) -->
                                <template x-if="msg.role === 'user'">
                                    <div class="flex items-end gap-2.5 max-w-[80%]">
                                        <div class="rounded-2xl rounded-br-none bg-emerald-500 dark:bg-emerald-600 px-4 py-3 text-xs text-white font-semibold leading-relaxed shadow-sm" x-text="msg.text"></div>
                                        <div class="w-7 h-7 shrink-0 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-sm mb-0.5"></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Chat reply loading indicator -->
                        <div x-show="chatLoading" class="flex justify-start">
                            <div class="flex items-start gap-2.5 max-w-[88%]">
                                <div class="w-7 h-7 shrink-0 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-sm mt-0.5"></div>
                                <div class="rounded-2xl rounded-tl-none bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-4 py-3 shadow-sm">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-bounce" style="animation-delay: 0ms"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-bounce" style="animation-delay: 150ms"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-bounce" style="animation-delay: 300ms"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chat error -->
                        <div x-show="chatError" class="flex justify-center">
                            <div class="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200/60 dark:border-rose-800/40 rounded-xl text-[10px] font-bold text-rose-600 dark:text-rose-400">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <span x-text="chatError"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!aiLoading && !aiError && !aiAnalysis" class="text-center py-12 text-slate-400 font-semibold text-xs">
                        Belum ada analisis yang dimuat.
                    </div>
                </div>

                <!-- ── Chat Input Footer ──────────────────────────────────────── -->
                <div x-show="!aiLoading && !aiError && aiAnalysis"
                     class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                    
                    <!-- Hint text -->
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-2 px-1">
                         Tanyakan lebih lanjut tentang laporan ini
                    </p>

                    <div class="flex items-end gap-2">
                        <!-- Text input -->
                        <div class="flex-1 relative">
                            <textarea
                                id="ai-chat-input"
                                x-model="chatInput"
                                @keydown.enter.prevent="if (!$event.shiftKey) fetchChatReply()"
                                :disabled="chatLoading || aiLoading"
                                placeholder="Contoh: Kenapa profit bisa turun? Apa yang harus dilakukan bulan depan?..."
                                rows="1"
                                class="w-full resize-none rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs text-slate-700 dark:text-slate-200 font-semibold placeholder-slate-300 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-400 dark:focus:border-emerald-600 disabled:opacity-50 transition leading-relaxed"
                                style="max-height: 100px; overflow-y: auto;"
                                @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 100) + 'px'"
                            ></textarea>
                        </div>
                        
                        <!-- Send button -->
                        <button
                            @click="fetchChatReply()"
                            :disabled="chatLoading || aiLoading || !chatInput.trim()"
                            class="shrink-0 w-10 h-10 rounded-2xl bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-200 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white disabled:text-slate-400 dark:disabled:text-slate-500 flex items-center justify-center transition shadow-sm shadow-emerald-500/30 cursor-pointer">
                            <svg x-show="!chatLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <svg x-show="chatLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Keyboard hint -->
                    <p class="text-[9px] text-slate-300 dark:text-slate-600 font-semibold mt-1.5 px-1">
                        Enter untuk kirim &nbsp;·&nbsp; Shift+Enter untuk baris baru &nbsp;·&nbsp; Maks. 10 pesan terakhir dikingat
                    </p>
                </div>

                <!-- Static footer (shown when analysis not loaded yet) -->
                <div x-show="aiLoading || aiError || !aiAnalysis"
                     class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 font-bold shrink-0">
                    <span> Tips: Filter tanggal atau cabang di dashboard untuk menganalisis data spesifik.</span>
                    <span class="text-slate-500">Pusat Kurma Premium AI</span>
                </div>
            </div>
        </div>

        <!-- Modal Detail Analisis Produk Terlaris -->
        <div x-show="showProductDetailModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" 
             aria-modal="true">
            <!-- Backdrop -->
            <div x-show="showProductDetailModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showProductDetailModal = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Content Card -->
            <div x-show="showProductDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden z-10 flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-800 via-teal-900 to-emerald-950 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center border border-white/10 shrink-0">
                            <svg class="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white leading-tight">Detail Kinerja Produk Terlaris</h3>
                            <p class="text-[11px] text-emerald-300 font-semibold" x-text="productDetailData ? productDetailData.period_label : 'Memuat data...'"></p>
                        </div>
                    </div>
                    <button type="button" @click="showProductDetailModal = false" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1">
                    <!-- Loading State -->
                    <template x-if="productDetailLoading">
                        <div class="py-12 flex flex-col items-center justify-center gap-3">
                            <svg class="w-8 h-8 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-bold text-slate-500">Mengambil data penjualan & riwayat transaksi...</span>
                        </div>
                    </template>

                    <!-- Error State -->
                    <template x-if="productDetailError && !productDetailLoading">
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold" x-text="productDetailError"></div>
                    </template>

                    <!-- Loaded Content -->
                    <template x-if="productDetailData && !productDetailLoading">
                        <div class="space-y-6">
                            <!-- Product Profile Card -->
                            <div class="p-5 rounded-3xl bg-slate-50 border border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <template x-if="productDetailData.product.image_path">
                                        <img :src="productDetailData.product.image_path" class="w-16 h-16 object-cover rounded-2xl border border-slate-200 shadow-sm shrink-0" alt="Foto">
                                    </template>
                                    <template x-if="!productDetailData.product.image_path">
                                        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-800 font-black text-lg flex items-center justify-center shrink-0 border border-emerald-200 shadow-sm" x-text="productDetailData.product.name.substring(0, 2).toUpperCase()"></div>
                                    </template>
                                    <div>
                                        <h4 class="text-base font-black text-slate-800" x-text="productDetailData.product.name"></h4>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="text-[10px] font-black bg-slate-200 text-slate-700 px-2 py-0.5 rounded-lg" x-text="'SKU: ' + productDetailData.product.sku"></span>
                                            <span class="text-[10px] font-black bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-lg" x-text="'Kategori: ' + productDetailData.product.category"></span>
                                            <span class="text-[10px] font-extrabold text-slate-500" x-text="'Stok Saat Ini: ' + productDetailData.product.stock + ' ' + productDetailData.product.price_unit"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right shrink-0">
                                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Harga Jual Satuan</span>
                                    <span class="text-base font-black text-slate-900" x-text="'Rp ' + Number(productDetailData.product.selling_price).toLocaleString('id-ID')"></span>
                                </div>
                            </div>

                            <!-- Key Metrics Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100 flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-amber-700 uppercase tracking-wider">Volume Terjual</span>
                                    <span class="text-xl font-black text-amber-900" x-text="formatQty(productDetailData.metrics.total_qty) + ' ' + productDetailData.metrics.unit"></span>
                                    <span class="text-[10px] font-bold text-amber-700">Total volume produk</span>
                                </div>
                                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider">Total Transaksi</span>
                                    <span class="text-xl font-black text-emerald-900" x-text="productDetailData.metrics.trx_count + 'x Transaksi'"></span>
                                    <span class="text-[10px] font-bold text-emerald-700">Frekuensi belanja</span>
                                </div>
                                <div class="p-4 rounded-2xl bg-teal-50 border border-teal-100 flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-teal-700 uppercase tracking-wider">Omset Produk</span>
                                    <span class="text-base font-black text-teal-900" x-text="'Rp ' + Number(productDetailData.metrics.total_revenue).toLocaleString('id-ID')"></span>
                                    <span class="text-[10px] font-bold text-teal-700">Kontribusi penjualan</span>
                                </div>
                                <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-100 flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-indigo-700 uppercase tracking-wider">Estimasi Laba</span>
                                    <span class="text-base font-black text-indigo-900" x-text="'Rp ' + Number(productDetailData.metrics.estimated_profit).toLocaleString('id-ID')"></span>
                                    <span class="text-[10px] font-bold text-indigo-700" x-text="'Margin: ' + productDetailData.metrics.profit_margin + '%'"></span>
                                </div>
                            </div>

                            <!-- Branch Breakdown (if multiple branches) -->
                            <template x-if="productDetailData.branches && productDetailData.branches.length > 1">
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                                    <h5 class="text-xs font-black text-slate-800">Distribusi Penjualan per Cabang:</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <template x-for="b in productDetailData.branches" :key="b.branch">
                                            <div class="p-2.5 rounded-xl bg-white border border-slate-100 flex justify-between items-center text-xs">
                                                <span class="font-bold text-slate-700" x-text="b.branch"></span>
                                                <span class="font-black text-slate-900" x-text="formatQty(b.qty) + ' ' + b.unit + ' (' + b.trx_count + 'x)'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Recent Transactions Table -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <h5 class="text-xs font-black text-slate-800 uppercase tracking-wider">Riwayat Transaksi Penjualan Produk Ini</h5>
                                    <span class="text-[10px] font-bold text-slate-400" x-text="'Menampilkan ' + (productDetailData.transactions ? productDetailData.transactions.length : 0) + ' transaksi terbaru'"></span>
                                </div>

                                <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase">
                                                <th class="py-2.5 px-3">Waktu</th>
                                                <th class="py-2.5 px-3">Kode Nota</th>
                                                <th class="py-2.5 px-3">Cabang / Kasir</th>
                                                <th class="py-2.5 px-3 text-center">Qty</th>
                                                <th class="py-2.5 px-3 text-right">Nilai Item</th>
                                                <th class="py-2.5 px-3 text-center">Metode</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                                            <template x-for="trx in productDetailData.transactions" :key="trx.id">
                                                <tr class="hover:bg-slate-50 transition">
                                                    <td class="py-2 px-3 text-slate-500 whitespace-nowrap" x-text="trx.created_at"></td>
                                                    <td class="py-2 px-3 font-mono font-bold text-slate-800 whitespace-nowrap" x-text="trx.transaction_code"></td>
                                                    <td class="py-2 px-3 whitespace-nowrap">
                                                        <span class="block text-slate-800 font-bold" x-text="trx.branch"></span>
                                                        <span class="text-[10px] text-slate-400" x-text="'Kasir: ' + trx.cashier"></span>
                                                    </td>
                                                    <td class="py-2 px-3 text-center font-black text-amber-800 whitespace-nowrap" x-text="formatQty(trx.qty) + ' ' + trx.unit"></td>
                                                    <td class="py-2 px-3 text-right font-black text-slate-900 whitespace-nowrap" x-text="'Rp ' + Number(trx.line_total).toLocaleString('id-ID')"></td>
                                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700" x-text="trx.payment_method"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-if="!productDetailData.transactions || productDetailData.transactions.length === 0">
                                                <tr>
                                                    <td colspan="6" class="py-6 text-center text-slate-400 font-bold">Tidak ada riwayat transaksi pada rentang filter ini.</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="showProductDetailModal = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Daftar Lengkap Best Sellers -->
        <div x-show="showAllBestSellersModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" 
             aria-modal="true">
            <div x-show="showAllBestSellersModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showAllBestSellersModal = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <div x-show="showAllBestSellersModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden z-10 flex flex-col max-h-[90vh]">
                
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-800 via-teal-900 to-emerald-950 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center border border-white/10 shrink-0">
                            <svg class="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white leading-tight">Daftar Lengkap Produk Terlaris (Top <span x-text="allBestSellersLimit"></span>)</h3>
                            <p class="text-[11px] text-emerald-300 font-semibold" x-text="allBestSellersPeriodLabel || 'Ranking penjualan produk'"></p>
                        </div>
                    </div>
                    <button type="button" @click="showAllBestSellersModal = false" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tab Periode, Limit Selector & Search -->
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-3 shrink-0">
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <!-- Periode Tabs -->
                        <div class="flex gap-1 p-1 bg-white border border-slate-200 rounded-xl shadow-xs">
                            <button type="button" @click="openAllBestSellers('harian', allBestSellersLimit)" :class="allBestSellersPeriod === 'harian' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Harian</button>
                            <button type="button" @click="openAllBestSellers('mingguan', allBestSellersLimit)" :class="allBestSellersPeriod === 'mingguan' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Mingguan</button>
                            <button type="button" @click="openAllBestSellers('bulanan', allBestSellersLimit)" :class="allBestSellersPeriod === 'bulanan' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Bulanan</button>
                        </div>

                        <!-- Limit Selector: 20, 50, 100 -->
                        <div class="flex items-center gap-1 p-1 bg-white border border-slate-200 rounded-xl shadow-xs">
                            <span class="text-[10px] font-black text-slate-400 uppercase px-1.5">Tampilkan:</span>
                            <button type="button" @click="changeBestSellersLimit(20)" :class="allBestSellersLimit === 20 ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer">Top 20</button>
                            <button type="button" @click="changeBestSellersLimit(50)" :class="allBestSellersLimit === 50 ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer">Top 50</button>
                            <button type="button" @click="changeBestSellersLimit(100)" :class="allBestSellersLimit === 100 ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer">Top 100</button>
                        </div>
                    </div>

                    <div class="w-full md:w-64">
                        <input type="text" x-model="allBestSellersSearch" placeholder="Cari nama produk..." class="w-full text-xs font-semibold px-3.5 py-2 rounded-xl border border-slate-200 focus:ring-emerald-500 focus:border-emerald-500 bg-white shadow-xs">
                    </div>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <template x-if="allBestSellersLoading">
                        <div class="py-12 flex flex-col items-center justify-center gap-3">
                            <svg class="w-8 h-8 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-bold text-slate-500">Memuat peringkat produk...</span>
                        </div>
                    </template>

                    <template x-if="!allBestSellersLoading">
                        <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                        <th class="py-3 px-3 text-center">Rank</th>
                                        <th class="py-3 px-3">Nama Produk</th>
                                        <th class="py-3 px-3">Kategori</th>
                                        <th class="py-3 px-3 text-center">Frekuensi Trx</th>
                                        <th class="py-3 px-3 text-right">Total Terjual</th>
                                        <th class="py-3 px-3 text-right">Est. Omset</th>
                                        <th class="py-3 px-3 text-right">Est. Laba</th>
                                        <th class="py-3 px-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                                    <template x-for="(item, index) in allBestSellersData.filter(i => !allBestSellersSearch || i.name.toLowerCase().includes(allBestSellersSearch.toLowerCase()))" :key="item.name">
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="py-2.5 px-3 text-center font-black">
                                                <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px]"
                                                      :class="index === 0 ? 'bg-amber-100 text-amber-800' : (index === 1 ? 'bg-slate-200 text-slate-800' : (index === 2 ? 'bg-orange-100 text-orange-800' : 'bg-slate-50 text-slate-500'))"
                                                      x-text="'#' + (index + 1)"></span>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <div class="flex items-center gap-2.5">
                                                    <template x-if="item.image_path">
                                                        <img :src="'/storage/' + item.image_path" class="w-8 h-8 rounded-lg object-cover border border-slate-100 shrink-0" alt="">
                                                    </template>
                                                    <template x-if="!item.image_path">
                                                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 font-black text-[10px] flex items-center justify-center shrink-0" x-text="item.name.substring(0, 2).toUpperCase()"></div>
                                                    </template>
                                                    <div>
                                                        <span class="font-extrabold text-slate-800 block" x-text="item.name"></span>
                                                        <span class="text-[9px] text-slate-400 font-bold" x-text="'SKU: ' + (item.sku || '-')"></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-500 font-bold" x-text="item.category || 'Umum'"></td>
                                            <td class="py-2.5 px-3 text-center font-black text-emerald-700" x-text="item.count + 'x Transaksi'"></td>
                                            <td class="py-2.5 px-3 text-right font-black text-slate-900" x-text="formatQty(item.qty) + ' ' + item.unit"></td>
                                            <td class="py-2.5 px-3 text-right font-black text-slate-800" x-text="'Rp ' + Number(item.estimated_revenue || 0).toLocaleString('id-ID')"></td>
                                            <td class="py-2.5 px-3 text-right font-black text-teal-700" x-text="'Rp ' + Number(item.estimated_profit || 0).toLocaleString('id-ID')"></td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button type="button" 
                                                        @click="showAllBestSellersModal = false; openBestSellerDetail(item.name, allBestSellersPeriod)" 
                                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[11px] font-bold transition cursor-pointer">
                                                    Detail &rarr;
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="!allBestSellersData || allBestSellersData.length === 0">
                                        <tr>
                                            <td colspan="8" class="py-8 text-center text-slate-400 font-bold">
                                                Tidak ada data produk terlaris pada rentang periode ini.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="showAllBestSellersModal = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Rincian Titik Grafik Penjualan -->
        <div x-show="showPointModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" 
             aria-modal="true">
            <div x-show="showPointModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showPointModal = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <div x-show="showPointModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden z-10 flex flex-col">
                
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-800 via-teal-900 to-emerald-950 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center border border-white/10 shrink-0">
                            <svg class="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white leading-tight" x-text="selectedPointData ? selectedPointData.fullTitle : 'Rincian Titik Grafik'"></h3>
                            <p class="text-[11px] text-emerald-300 font-semibold">Analisis performa penjualan periode terpilih</p>
                        </div>
                    </div>
                    <button type="button" @click="showPointModal = false" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5" x-show="selectedPointData">
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3.5">
                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 flex flex-col gap-1">
                            <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider">Total Omset</span>
                            <span class="text-lg font-black text-emerald-900" x-text="formatRupiah(selectedPointData ? selectedPointData.omset : 0)"></span>
                            <span class="text-[10px] text-emerald-700 font-semibold">Pendapatan kotor</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-teal-50 border border-teal-100 flex flex-col gap-1">
                            <span class="text-[10px] font-black text-teal-700 uppercase tracking-wider">Estimasi Profit</span>
                            <span class="text-lg font-black text-teal-900" x-text="formatRupiah(selectedPointData ? selectedPointData.profit : 0)"></span>
                            <span class="text-[10px] text-teal-700 font-semibold" x-text="'Margin: ' + ((selectedPointData && selectedPointData.omset > 0) ? ((selectedPointData.profit / selectedPointData.omset) * 100).toFixed(1) : 0) + '%'"></span>
                        </div>
                        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100 flex flex-col gap-1">
                            <span class="text-[10px] font-black text-amber-700 uppercase tracking-wider">Volume Transaksi</span>
                            <span class="text-lg font-black text-amber-900" x-text="(selectedPointData ? selectedPointData.count : 0) + ' Transaksi'"></span>
                            <span class="text-[10px] text-amber-700 font-semibold">Total nota terbit</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-100 flex flex-col gap-1">
                            <span class="text-[10px] font-black text-indigo-700 uppercase tracking-wider">Rata-rata Nota (AOV)</span>
                            <span class="text-lg font-black text-indigo-900" x-text="formatRupiah(selectedPointData ? selectedPointData.aov : 0)"></span>
                            <span class="text-[10px] text-indigo-700 font-semibold">Belanja per pelanggan</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600 flex items-center justify-between gap-3">
                        <span class="font-semibold text-slate-500">Ingin melihat rincian nota transaksi periode ini?</span>
                        <template x-if="selectedPointData && selectedPointData.raw_date">
                            <a :href="'{{ route('owner.dashboard') }}?filter_type=harian&date=' + selectedPointData.raw_date + (selectedBranch ? '&branch=' + selectedBranch : '')"
                               class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-xs shadow-sm transition whitespace-nowrap cursor-pointer">
                                Filter Hari Ini &rarr;
                            </a>
                        </template>
                        <template x-if="selectedPointData && selectedPointData.raw_month">
                            <a :href="'{{ route('owner.dashboard') }}?filter_type=bulanan&month=' + selectedPointData.raw_month + (selectedBranch ? '&branch=' + selectedBranch : '')"
                               class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-xs shadow-sm transition whitespace-nowrap cursor-pointer">
                                Filter Bulan Ini &rarr;
                            </a>
                        </template>
                        <template x-if="selectedPointData && !selectedPointData.raw_date && !selectedPointData.raw_month">
                            <button type="button" 
                                    @click="showPointModal = false; showTable = true; activeCard = 'omset'"
                                    class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-xs shadow-sm transition whitespace-nowrap cursor-pointer">
                                Buka Tabel &rarr;
                            </button>
                        </template>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="showPointModal = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
