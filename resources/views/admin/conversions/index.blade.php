@extends('layouts.app')

@section('title', 'Aturan Konversi Produk')

@section('content')
<div class="flex flex-col gap-6 max-w-full overflow-hidden" x-data="conversionState()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100 flex items-center gap-2">
                🔄 Aturan Konversi Produk
            </h1>
            <p class="text-slate-400 dark:text-purple-400 text-sm font-medium">Hubungkan produk bulk/dus ke produk retail eceran untuk alur repacking otomatis</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-2xl text-sm font-bold flex items-center gap-2">
        <span>✅</span> {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-2xl text-sm font-bold">
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- List Table (Kiri) --}}
        <div class="lg:col-span-8 space-y-4">
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-slate-800 dark:text-purple-100 font-extrabold flex items-center gap-2">
                        📋 Aturan Konversi Terdaftar
                    </h2>
                    <span class="text-xs bg-slate-100 dark:bg-dp-900 text-slate-600 dark:text-purple-300 px-2.5 py-1 rounded-full font-bold">
                        Total: {{ $conversions->total() }}
                    </span>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-dp-700 text-xs text-slate-400 uppercase font-black tracking-wider">
                                <th class="py-3 px-4">Produk Asal (Bulk)</th>
                                <th class="py-3 px-4 text-center">Rasio Konversi</th>
                                <th class="py-3 px-4">Produk Target (Eceran)</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-dp-700/50 text-sm">
                            @forelse($conversions as $conv)
                            <tr class="hover:bg-slate-55/30 dark:hover:bg-dp-900/20 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-purple-250">
                                    <div class="font-extrabold text-slate-800 dark:text-purple-100">{{ $conv->sourceProduct->name }}</div>
                                    <div class="text-xs text-slate-400 font-bold flex items-center gap-1.5 mt-0.5">
                                        <span class="bg-slate-100 dark:bg-dp-900 px-2 py-0.5 rounded text-[10px]">{{ $conv->sourceProduct->sku }}</span>
                                        <span>Satuan: <strong class="text-slate-600 dark:text-purple-300 uppercase">{{ $conv->sourceProduct->price_unit }}</strong></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/35 px-3 py-1 rounded-full text-xs font-black text-emerald-800 dark:text-emerald-400">
                                        1 <span class="uppercase font-bold text-[10px] text-slate-400">{{ $conv->sourceProduct->price_unit }}</span> 
                                        ➔ 
                                        {{ (float) $conv->conversion_rate }} <span class="uppercase font-bold text-[10px] text-slate-400">{{ $conv->targetProduct->price_unit }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-purple-250">
                                    <div class="font-extrabold text-slate-800 dark:text-purple-100">{{ $conv->targetProduct->name }}</div>
                                    <div class="text-xs text-slate-400 font-bold flex items-center gap-1.5 mt-0.5">
                                        <span class="bg-slate-100 dark:bg-dp-900 px-2 py-0.5 rounded text-[10px]">{{ $conv->targetProduct->sku }}</span>
                                        <span>Satuan: <strong class="text-slate-600 dark:text-purple-300 uppercase">{{ $conv->targetProduct->price_unit }}</strong></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <form action="{{ route('admin.conversions.destroy', $conv->id) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus aturan konversi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-rose-600 hover:text-rose-800 bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-950/40 p-2 rounded-xl text-xs font-extrabold transition-colors">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 font-semibold">
                                    📭 Belum ada aturan konversi terdaftar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div class="mt-4">
                    {{ $conversions->links() }}
                </div>
            </div>
        </div>

        {{-- Add Form (Kanan) --}}
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700/50 rounded-3xl p-6 shadow-md sticky top-6">
                <h2 class="text-slate-800 dark:text-purple-100 font-extrabold mb-4 flex items-center gap-2">
                    ➕ Tambah Aturan Baru
                </h2>

                <form action="{{ route('admin.conversions.store') }}" method="POST" class="space-y-4">
                    @csrf

                    {{-- Source Product Selection --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">📦 Produk Asal (Bulk/Dus/Pack) <span class="text-rose-500">*</span></label>
                        <div class="relative" @click.outside="showSourceDropdown = false">
                            <input type="hidden" name="source_product_id" :value="sourceProductId">
                            <input type="text" 
                                   x-model="sourceSearch" 
                                   @focus="showSourceDropdown = true"
                                   @input="filterSource()"
                                   placeholder="🔍 Cari nama atau SKU produk asal..."
                                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner"
                                   autocomplete="off" required>
                            
                            {{-- Autocomplete Dropdown --}}
                            <div x-show="showSourceDropdown && filteredSource.length > 0"
                                 class="absolute z-50 left-0 right-0 bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700 shadow-xl rounded-xl mt-1 max-h-60 overflow-y-auto">
                                <template x-for="p in filteredSource" :key="p.id">
                                    <button type="button" 
                                            @click="selectSource(p)"
                                            class="w-full text-left px-4 py-2.5 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-slate-700 dark:text-purple-200 hover:text-emerald-800 dark:hover:text-emerald-300 border-b border-slate-100 dark:border-dp-700/50 font-bold transition flex justify-between items-center">
                                        <span>
                                            <span x-text="p.name"></span>
                                            <span class="text-slate-400 font-normal" x-text="' (' + p.sku + ')'"></span>
                                        </span>
                                        <span class="text-[10px] bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-400 px-2 py-0.5 rounded font-bold" x-text="p.price_unit"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Target Product Selection --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">🎯 Produk Target (Eceran/Retail) <span class="text-rose-500">*</span></label>
                        <div class="relative" @click.outside="showTargetDropdown = false">
                            <input type="hidden" name="target_product_id" :value="targetProductId">
                            <input type="text" 
                                   x-model="targetSearch" 
                                   @focus="showTargetDropdown = true"
                                   @input="filterTarget()"
                                   placeholder="🔍 Cari nama atau SKU produk target..."
                                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner"
                                   autocomplete="off" required>
                            
                            {{-- Autocomplete Dropdown --}}
                            <div x-show="showTargetDropdown && filteredTarget.length > 0"
                                 class="absolute z-50 left-0 right-0 bg-white dark:bg-dp-900 border border-slate-200 dark:border-dp-700 shadow-xl rounded-xl mt-1 max-h-60 overflow-y-auto">
                                <template x-for="p in filteredTarget" :key="p.id">
                                    <button type="button" 
                                            @click="selectTarget(p)"
                                            class="w-full text-left px-4 py-2.5 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-slate-700 dark:text-purple-200 hover:text-emerald-800 dark:hover:text-emerald-300 border-b border-slate-100 dark:border-dp-700/50 font-bold transition flex justify-between items-center">
                                        <span>
                                            <span x-text="p.name"></span>
                                            <span class="text-slate-400 font-normal" x-text="' (' + p.sku + ')'"></span>
                                        </span>
                                        <span class="text-[10px] bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-400 px-2 py-0.5 rounded font-bold" x-text="p.price_unit"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Conversion Rate Input --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider mb-2">🔢 Rasio Konversi</label>
                        <div class="relative">
                            <input type="number" step="0.0001" min="0.0001" name="conversion_rate" value="{{ old('conversion_rate', '10.00') }}" required
                                   placeholder="Contoh: 10 atau 3"
                                   class="w-full bg-slate-50 dark:bg-dp-900 border border-slate-200 dark:border-dp-700 text-slate-800 dark:text-purple-100 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 focus:outline-none shadow-inner">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 font-semibold leading-relaxed">
                            Berapa unit target yang dihasilkan dari 1 unit produk asal. <br>
                            <em>(Contoh: Jika 1 Dus = 10 Kg, maka rasionya adalah 10)</em>
                        </p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-emerald-700 hover:bg-emerald-800 text-white py-3.5 rounded-xl font-bold uppercase tracking-wider text-xs transition duration-150 shadow-sm mt-2">
                        💾 Simpan Aturan Konversi
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function conversionState() {
    return {
        sourceSearch: '',
        sourceProductId: '',
        showSourceDropdown: false,
        
        targetSearch: '',
        targetProductId: '',
        showTargetDropdown: false,
        
        allProducts: {!! json_encode($products->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price_unit' => $p->price_unit,
            ];
        })) !!},
        
        filteredSource: [],
        filteredTarget: [],
        
        init() {
            this.filteredSource = [...this.allProducts];
            this.filteredTarget = [...this.allProducts];
            
            // Handle old session values if validation fails
            const oldSourceId = '{{ old('source_product_id') }}';
            const oldTargetId = '{{ old('target_product_id') }}';
            
            if (oldSourceId) {
                const found = this.allProducts.find(p => p.id == oldSourceId);
                if (found) {
                    this.selectSource(found);
                }
            }
            if (oldTargetId) {
                const found = this.allProducts.find(p => p.id == oldTargetId);
                if (found) {
                    this.selectTarget(found);
                }
            }
        },
        
        filterSource() {
            const val = this.sourceSearch.toLowerCase().trim();
            if (!val) {
                this.filteredSource = [...this.allProducts];
            } else {
                this.filteredSource = this.allProducts.filter(p => {
                    return p.name.toLowerCase().includes(val) || p.sku.toLowerCase().includes(val);
                });
            }
        },
        
        filterTarget() {
            const val = this.targetSearch.toLowerCase().trim();
            if (!val) {
                this.filteredTarget = [...this.allProducts];
            } else {
                this.filteredTarget = this.allProducts.filter(p => {
                    return p.name.toLowerCase().includes(val) || p.sku.toLowerCase().includes(val);
                });
            }
        },
        
        selectSource(product) {
            this.sourceProductId = product.id;
            this.sourceSearch = `${product.name} (${product.sku}) [${product.price_unit.toUpperCase()}]`;
            this.showSourceDropdown = false;
        },
        
        selectTarget(product) {
            this.targetProductId = product.id;
            this.targetSearch = `${product.name} (${product.sku}) [${product.price_unit.toUpperCase()}]`;
            this.showTargetDropdown = false;
        }
    };
}
</script>
@endsection
