<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}"
                   class="p-2 rounded-xl text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 transition duration-150">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">Upload Foto Produk Cepat</h2>
                    <p class="text-sm text-slate-400 font-medium mt-0.5">Isi foto produk dengan mudah — unggah biasa atau rebuild otomatis dengan Gemini AI</p>
                </div>
            </div>
            {{-- AI Mode Global Toggle --}}
            <div id="ai-toggle-wrapper"
                 class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-700 leading-tight">Rebuild dengan Gemini AI</span>
                    <span class="text-[10px] text-slate-400 font-medium" id="ai-toggle-label">Nonaktif — unggah biasa</span>
                </div>
                <button type="button" id="global-ai-toggle" role="switch" aria-checked="false"
                        onclick="toggleGlobalAI(this)"
                        class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <span class="pointer-events-none inline-block h-6 w-6 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }

        .upload-card {
            background: #fff;
            border-radius: 20px;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s ease;
            overflow: hidden;
            box-shadow: 0 1px 6px 0 rgba(0,0,0,0.05);
        }
        .upload-card:hover { box-shadow: 0 8px 28px 0 rgba(16,185,129,0.10); transform: translateY(-2px); }
        .upload-card.has-photo { border-color: #6ee7b7; background: #f0fdf4; }
        .upload-card.uploading { border-color: #60a5fa; background: #eff6ff; }
        .upload-card.ai-processing { border-color: #a78bfa; background: #f5f3ff; }
        .upload-card.success { border-color: #10b981; background: #ecfdf5; }
        .upload-card.error { border-color: #f87171; background: #fff1f2; }

        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .upload-zone:hover { border-color: #10b981; background: #f0fdf4; }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .badge-none    { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .badge-has     { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-ai      { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }

        .spinner { width: 20px; height: 20px; border: 2.5px solid #e0e7ef; border-top-color: #10b981; border-radius: 50%; animation: spin 0.75s linear infinite; display: inline-block; }
        .spinner-purple { border-top-color: #7c3aed; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .progress-step { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #6b7280; font-weight: 600; padding: 4px 0; }
        .progress-step.active { color: #059669; }
        .progress-step.active-ai { color: #7c3aed; }
        .progress-step.done { color: #10b981; }

        .ai-badge-active { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; }
    </style>

    {{-- JSON DATA ISLANDS --}}
    <script type="application/json" id="products-data">@json($products->items())</script>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

        {{-- Stats Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            @php
                $total     = \App\Models\Product::count();
                $hasPhoto  = \App\Models\Product::whereNotNull('image_path')->where('image_path', '<>', '')->count();
                $noPhoto   = $total - $hasPhoto;
                $pctDone   = $total > 0 ? round(($hasPhoto / $total) * 100) : 0;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Produk</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ $total }}</p>
            </div>
            <div class="bg-emerald-50 rounded-2xl border border-emerald-100 shadow-sm p-4">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Sudah Ada Foto</p>
                <p class="text-2xl font-extrabold text-emerald-700">{{ $hasPhoto }}</p>
            </div>
            <div class="bg-amber-50 rounded-2xl border border-amber-100 shadow-sm p-4">
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Belum Ada Foto</p>
                <p class="text-2xl font-extrabold text-amber-700">{{ $noPhoto }}</p>
            </div>
            <div class="bg-slate-50 rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Progres Foto</p>
                <p class="text-2xl font-extrabold text-slate-700">{{ $pctDone }}%</p>
                <div class="mt-2 bg-slate-200 rounded-full h-1.5">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $pctDone }}%"></div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Bar --}}
        <form method="GET" action="{{ route('admin.products.fast-upload') }}" id="filter-form"
              class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6 flex flex-col sm:flex-row gap-4 items-center">

            {{-- Filter Status Foto --}}
            <div class="flex items-center gap-2">
                @foreach(['no_photo' => '📷 Belum Ada Foto', 'has_photo' => '✅ Sudah Ada Foto', 'all' => 'Semua'] as $val => $label)
                    <button type="button"
                            onclick="setFilter('{{ $val }}')"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition duration-150 filter-btn {{ $filter === $val ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}"
                            data-filter="{{ $val }}">
                        {{ $label }}
                    </button>
                @endforeach
                <input type="hidden" name="filter" id="filter-input" value="{{ $filter }}">
            </div>

            {{-- Category Filter --}}
            <select name="category" onchange="document.getElementById('filter-form').submit()"
                    class="border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 font-semibold">
                <option value="Semua" {{ $category === '' || $category === 'Semua' ? 'selected' : '' }}>Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->name }}" {{ $category === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            {{-- Search --}}
            <div class="relative flex-1 max-w-sm">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama produk atau SKU..."
                       class="w-full pl-9 pr-4 py-2 text-sm border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500"
                       onkeydown="if(event.key==='Enter'){document.getElementById('filter-form').submit()}">
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition duration-150">
                Cari
            </button>
        </form>

        {{-- AI Mode Info Banner (shown when AI enabled) --}}
        <div id="ai-banner"
             class="hidden mb-6 bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl p-4 flex items-center gap-4 text-white shadow-lg">
            <div class="text-2xl">✨</div>
            <div class="flex-1">
                <p class="font-extrabold text-sm">Mode Gemini AI Aktif</p>
                <p class="text-xs text-violet-200 mt-0.5">Setiap foto yang Anda unggah akan dianalisis oleh Gemini AI dan dibangun ulang menjadi foto produk studio premium secara otomatis.</p>
            </div>
            <div class="text-xs bg-white/20 rounded-xl px-3 py-1.5 font-bold whitespace-nowrap">
                🚀 Powered by Gemini + Pollinations.ai
            </div>
        </div>

        {{-- Product Cards Grid --}}
        @if($products->count() === 0)
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-16 text-center">
                <div class="text-5xl mb-4">📦</div>
                <h3 class="font-extrabold text-slate-700 text-lg mb-1">Tidak Ada Produk Ditemukan</h3>
                <p class="text-slate-400 text-sm">Coba ubah filter atau kata kunci pencarian Anda.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="products-grid">
                @foreach($products as $product)
                    @php $hasImg = !empty($product->image_path); @endphp
                    <div class="upload-card {{ $hasImg ? 'has-photo' : '' }}" id="card-{{ $product->id }}"
                         data-product-id="{{ $product->id }}"
                         data-product-name="{{ $product->name }}"
                         data-has-photo="{{ $hasImg ? '1' : '0' }}">

                        {{-- Preview Image Area --}}
                        <div class="relative bg-slate-50 flex items-center justify-center overflow-hidden" style="height:160px;">
                            @if($hasImg)
                                <img src="/storage/{{ $product->image_path }}"
                                     id="preview-{{ $product->id }}"
                                     class="w-full h-full object-cover"
                                     alt="{{ $product->name }}">
                            @else
                                <div id="placeholder-{{ $product->id }}"
                                     class="w-16 h-16 rounded-2xl bg-emerald-800 text-white font-extrabold flex items-center justify-center text-2xl uppercase shadow-md">
                                    {{ strtoupper(substr($product->name, 0, 2)) }}
                                </div>
                                <img src="" id="preview-{{ $product->id }}" class="w-full h-full object-cover hidden" alt="">
                            @endif

                            {{-- Loading Overlay --}}
                            <div id="overlay-{{ $product->id }}"
                                 class="absolute inset-0 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center gap-2 hidden">
                                <div class="spinner" id="spinner-{{ $product->id }}"></div>
                                <div id="status-steps-{{ $product->id }}" class="text-center px-4"></div>
                            </div>

                            {{-- Success Check --}}
                            <div id="success-check-{{ $product->id }}"
                                 class="absolute top-2 right-2 hidden w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4 flex flex-col gap-3">
                            {{-- Product Info --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-bold text-slate-400 font-mono">{{ $product->sku }}</span>
                                    @if($hasImg)
                                        <span class="status-badge badge-has" id="badge-{{ $product->id }}">✅ Ada Foto</span>
                                    @else
                                        <span class="status-badge badge-none" id="badge-{{ $product->id }}">📷 Belum Ada</span>
                                    @endif
                                </div>
                                <h4 class="font-extrabold text-slate-800 text-sm leading-tight">{{ $product->name }}</h4>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $product->category }} · Stok: {{ $product->stock }} {{ $product->price_unit }}</p>
                            </div>

                            {{-- Status Text --}}
                            <p id="status-text-{{ $product->id }}" class="text-xs text-slate-400 font-medium hidden"></p>

                            {{-- Upload Buttons --}}
                            <div class="flex gap-2">
                                {{-- Input: Gallery / File --}}
                                <input type="file"
                                       id="file-{{ $product->id }}"
                                       accept="image/*"
                                       class="hidden"
                                       onchange="handleFileSelected(this, {{ $product->id }}, '{{ addslashes($product->name) }}', '{{ addslashes($product->category) }}')">

                                {{-- Input: Camera (rear camera directly) --}}
                                <input type="file"
                                       id="camera-{{ $product->id }}"
                                       accept="image/*"
                                       capture="environment"
                                       class="hidden"
                                       onchange="handleFileSelected(this, {{ $product->id }}, '{{ addslashes($product->name) }}', '{{ addslashes($product->category) }}')">

                                {{-- Button: Pilih Foto (dari galeri) --}}
                                <button type="button"
                                        id="upload-btn-{{ $product->id }}"
                                        onclick="document.getElementById('file-{{ $product->id }}').click()"
                                        class="flex-1 py-2.5 rounded-xl text-xs font-bold transition duration-150 flex items-center justify-center gap-1.5
                                               {{ $hasImg ? 'bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700' : 'bg-emerald-700 hover:bg-emerald-800 text-white shadow-md shadow-emerald-700/10' }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                    {{ $hasImg ? 'Ganti' : 'Galeri' }}
                                </button>

                                {{-- Button: Kamera langsung --}}
                                <button type="button"
                                        id="camera-btn-{{ $product->id }}"
                                        onclick="document.getElementById('camera-{{ $product->id }}').click()"
                                        style="background:#0ea5e9;color:#fff;padding:0 14px;border-radius:12px;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:5px;border:none;cursor:pointer;box-shadow:0 2px 8px rgba(14,165,233,0.3);transition:background 0.15s;"
                                        onmouseover="this.style.background='#0284c7'"
                                        onmouseout="this.style.background='#0ea5e9'"
                                        title="Foto langsung dari kamera">
                                    <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                    </svg>
                                    Kamera
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $products->links() }}
                </div>
            @endif
        @endif

        {{-- Toast Notification --}}
        <div id="toast"
             class="fixed bottom-6 right-6 z-50 hidden max-w-sm bg-white border border-slate-200 rounded-2xl shadow-2xl p-4 flex items-center gap-3 transition-all duration-300">
            <div id="toast-icon" class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"></div>
            <div class="flex-1">
                <p id="toast-title" class="font-extrabold text-slate-800 text-sm"></p>
                <p id="toast-msg" class="text-xs text-slate-400 mt-0.5"></p>
            </div>
            <button onclick="hideToast()" class="text-slate-300 hover:text-slate-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <script>
        // ─── Global State ──────────────────────────────────────────────────────────
        let globalAIEnabled = false;
        const CSRF_TOKEN    = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        // ─── AI Toggle ────────────────────────────────────────────────────────────
        function toggleGlobalAI(btn) {
            globalAIEnabled = !globalAIEnabled;
            btn.setAttribute('aria-checked', globalAIEnabled ? 'true' : 'false');

            // Move the slider thumb
            btn.querySelector('span').style.transform = globalAIEnabled ? 'translateX(20px)' : 'translateX(0)';
            btn.classList.toggle('bg-violet-600', globalAIEnabled);
            btn.classList.toggle('bg-slate-200', !globalAIEnabled);

            // Update label
            document.getElementById('ai-toggle-label').textContent = globalAIEnabled
                ? 'Aktif — rebuild studio AI ✨'
                : 'Nonaktif — unggah biasa';

            // Show/hide banner
            const banner = document.getElementById('ai-banner');
            if (globalAIEnabled) {
                banner.classList.remove('hidden');
                banner.classList.add('flex');
            } else {
                banner.classList.add('hidden');
                banner.classList.remove('flex');
            }

            // Update all upload buttons appearance
            document.querySelectorAll('[id^="upload-btn-"]').forEach(btn => {
                if (globalAIEnabled) {
                    btn.classList.add('ring-2', 'ring-violet-400');
                } else {
                    btn.classList.remove('ring-2', 'ring-violet-400');
                }
            });
        }

        // ─── Filter Buttons ───────────────────────────────────────────────────────
        function setFilter(val) {
            document.getElementById('filter-input').value = val;
            document.querySelectorAll('.filter-btn').forEach(b => {
                if (b.dataset.filter === val) {
                    b.className = b.className.replace('bg-slate-50 text-slate-600 hover:bg-slate-100', 'bg-emerald-700 text-white shadow-sm');
                } else {
                    b.className = b.className.replace('bg-emerald-700 text-white shadow-sm', 'bg-slate-50 text-slate-600 hover:bg-slate-100');
                }
            });
            document.getElementById('filter-form').submit();
        }

        // ─── Handle File Selected ─────────────────────────────────────────────────
        function handleFileSelected(input, productId, productName, productCategory) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            // Local preview first (instant)
            const previewImg    = document.getElementById('preview-' + productId);
            const placeholderEl = document.getElementById('placeholder-' + productId);
            const reader        = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                if (placeholderEl) placeholderEl.classList.add('hidden');
            };
            reader.readAsDataURL(file);

            // Start upload
            uploadPhoto(productId, file);

            // Reset file input so the same file can be re-selected if needed
            input.value = '';
        }

        // ─── Upload (with or without AI rebuild) ──────────────────────────────────
        async function uploadPhoto(productId, file) {
            const useAI   = globalAIEnabled;
            const card    = document.getElementById('card-' + productId);
            const overlay = document.getElementById('overlay-' + productId);
            const spinner = document.getElementById('spinner-' + productId);
            const steps   = document.getElementById('status-steps-' + productId);
            const btn     = document.getElementById('upload-btn-' + productId);

            // Show loading overlay
            overlay.classList.remove('hidden');
            btn.disabled = true;
            const camBtn = document.getElementById('camera-btn-' + productId);
            if (camBtn) camBtn.disabled = true;

            if (useAI) {
                card.className = card.className.replace('has-photo', '').replace('upload-card', '').trim();
                card.classList.add('upload-card', 'ai-processing');
                spinner.classList.add('spinner-purple');

                // Step-by-step progress for AI
                const stepMessages = [
                    { icon: '📤', text: 'Mengunggah foto ke server...', delay: 0 },
                    { icon: '🧠', text: 'Menganalisis produk dengan Gemini AI...', delay: 1200 },
                    { icon: '🎨', text: 'Membangun ulang foto studio dengan AI...', delay: 3000 },
                ];

                stepMessages.forEach(({ icon, text, delay }) => {
                    setTimeout(() => {
                        steps.innerHTML = `<div class="progress-step active-ai">${icon} <span>${text}</span></div>`;
                    }, delay);
                });
            } else {
                card.classList.add('uploading');
                steps.innerHTML = `<div class="progress-step active">📤 <span>Mengunggah & mengompres foto...</span></div>`;
            }

            const formData = new FormData();
            formData.append('image', file);
            formData.append('rebuild_ai', useAI ? '1' : '0');
            formData.append('_method', 'POST');

            try {
                const resp = await fetch(`/admin/products/${productId}/upload-photo`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await resp.json();

                overlay.classList.add('hidden');
                btn.disabled = false;
                if (camBtn) camBtn.disabled = false;

                if (resp.ok && data.success) {
                    // Update preview with actual saved image
                    const previewImg = document.getElementById('preview-' + productId);
                    previewImg.src   = data.image_url + '?t=' + Date.now();
                    previewImg.classList.remove('hidden');
                    const placeholder = document.getElementById('placeholder-' + productId);
                    if (placeholder) placeholder.classList.add('hidden');

                    // Update card style to "has-photo"
                    card.classList.remove('uploading', 'ai-processing', 'error');
                    card.classList.add('has-photo', 'success');

                    // Update badge
                    const badge = document.getElementById('badge-' + productId);
                    if (badge) {
                        badge.className   = 'status-badge badge-has';
                        badge.textContent = useAI ? '✨ AI Photo' : '✅ Ada Foto';
                    }

                    // Update button
                    btn.className = btn.className
                        .replace('bg-emerald-700 hover:bg-emerald-800 text-white shadow-md shadow-emerald-700/10', '')
                        .replace('bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700', '');
                    btn.classList.add('bg-slate-100', 'hover:bg-emerald-50', 'text-slate-600', 'hover:text-emerald-700');
                    btn.innerHTML = `
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Ganti Foto`;

                    // Show success checkmark
                    const check = document.getElementById('success-check-' + productId);
                    check.classList.remove('hidden');
                    setTimeout(() => check.classList.add('hidden'), 3000);

                    showToast('success', useAI ? '✨ Foto AI Berhasil!' : '✅ Foto Berhasil Diunggah', data.message);
                } else {
                    card.classList.remove('uploading', 'ai-processing');
                    card.classList.add('error');
                    showToast('error', '❌ Gagal Mengunggah', data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                    setTimeout(() => { card.classList.remove('error'); }, 3000);
                }

            } catch (err) {
                overlay.classList.add('hidden');
                btn.disabled = false;
                if (camBtn) camBtn.disabled = false;
                card.classList.remove('uploading', 'ai-processing');
                card.classList.add('error');
                showToast('error', '❌ Koneksi Error', 'Gagal menghubungi server. Periksa koneksi internet Anda.');
                setTimeout(() => { card.classList.remove('error'); }, 3000);
            }
        }

        // ─── Toast Notification ────────────────────────────────────────────────────
        let toastTimer;
        function showToast(type, title, message) {
            const toast = document.getElementById('toast');
            const icon  = document.getElementById('toast-icon');
            document.getElementById('toast-title').textContent = title;
            document.getElementById('toast-msg').textContent   = message;

            if (type === 'success') {
                icon.className    = 'w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 bg-emerald-100 text-emerald-600';
                icon.textContent  = '✅';
                toast.className   = toast.className.replace('border-red-200', 'border-emerald-200');
            } else {
                icon.className    = 'w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 bg-red-100 text-red-600';
                icon.textContent  = '❌';
                toast.className   = toast.className.replace('border-emerald-200', 'border-red-200');
            }

            toast.classList.remove('hidden');
            toast.classList.add('flex');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(hideToast, 5000);
        }

        function hideToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('hidden');
            toast.classList.remove('flex');
        }
    </script>
</x-app-layout>
