<div class="px-5 py-4 border-b border-slate-100 dark:border-dp-700 flex items-center justify-between flex-wrap gap-2">
    <div class="flex items-center gap-2">
        <h2 class="text-slate-800 dark:text-purple-100 font-extrabold text-md">Matriks Stok Produk × Lokasi</h2>
        <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-bold">
            {{ number_format($products->total(), 0, ',', '.') }} Produk
        </span>
    </div>
    <p class="text-slate-400 dark:text-purple-400 text-xs font-semibold">
        🔴 Kritis (≤5) &nbsp; 🟡 Rendah (≤10) &nbsp; 🟢 Aman (&gt;10)
        @if(Auth::user()->isAdmin())
        &nbsp;|&nbsp; <span class="text-purple-500 dark:text-purple-400 font-bold">✏️ Klik angka untuk edit</span>
        @endif
    </p>
</div>
<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-slate-100 dark:divide-dp-700 text-left text-sm text-slate-700">
        <thead class="bg-slate-50 dark:bg-dp-900 font-bold text-slate-500 dark:text-purple-300 uppercase tracking-wider text-xs">
            <tr class="border-b border-slate-100 dark:border-dp-700">
                <th class="px-4 py-3 sticky left-0 bg-white dark:bg-dp-800 z-10 border-r border-slate-100 dark:border-dp-700">Produk</th>
                <th class="px-3 py-3 w-20">Satuan</th>
                @foreach($locations as $loc)
                <th class="text-center px-3 py-3 w-28 whitespace-nowrap {{ $loc->type === 'gudang' ? 'text-blue-600 dark:text-blue-400' : '' }}">
                    {{ $loc->type === 'gudang' ? '🏭' : ($loc->type === 'online' ? '🌐' : '🏪') }}
                    <br>{{ Str::limit($loc->name, 14) }}
                </th>
                @endforeach
                <th class="text-center px-3 py-3 w-24">TOTAL</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-dp-700 font-semibold text-slate-800 dark:text-purple-100">
            @forelse($products as $product)
            @php
                $stockByLoc = $product->productStocks->keyBy('location_id');
                $rowTotal   = $product->productStocks->sum('stock');
            @endphp
            <tr class="hover:bg-slate-50/50 dark:hover:bg-dp-700/30 transition duration-150 group" id="row-product-{{ $product->id }}">
                <td class="px-4 py-3 sticky left-0 bg-white dark:bg-dp-800 border-r border-slate-100 dark:border-dp-700 z-10 group-hover:bg-slate-50 dark:group-hover:bg-dp-700">
                    <p class="text-slate-900 dark:text-purple-100 font-bold leading-tight">{{ $product->name }}</p>
                    <p class="text-slate-400 dark:text-purple-400 font-mono text-[10px] mt-0.5">{{ $product->sku }}</p>
                </td>
                <td class="px-3 py-3 text-slate-500 dark:text-purple-400 text-xs font-semibold uppercase tracking-wider">{{ $product->price_unit }}</td>
                @foreach($locations as $loc)
                @php
                    $s = isset($stockByLoc[$loc->id]) ? (float) $stockByLoc[$loc->id]->stock : 0;
                    $cellClass = $s <= 0 ? 'text-slate-400 dark:text-purple-505 bg-slate-50/50 dark:bg-dp-900/10' :
                                ($s <= 5  ? 'text-rose-700 dark:text-rose-400 font-extrabold bg-rose-50 dark:bg-rose-950/20' :
                                ($s <= 10 ? 'text-amber-700 dark:text-amber-400 font-extrabold bg-amber-50 dark:bg-amber-950/20' :
                                            'text-emerald-700 dark:text-emerald-400 font-bold'));
                    $displayVal = number_format($s, 2, ',', '.');
                    if ($product->price_unit === 'gram' && $s >= 1000) {
                        $displayVal = number_format($s / 1000, 2, ',', '.') . ' kg';
                    }
                @endphp
                <td class="px-1 py-2 text-center {{ $cellClass }} relative stock-cell"
                    id="cell-{{ $product->id }}-{{ $loc->id }}"
                    data-product-id="{{ $product->id }}"
                    data-location-id="{{ $loc->id }}"
                    data-product-name="{{ $product->name }}"
                    data-location-name="{{ $loc->name }}"
                    data-stock="{{ $s }}"
                    data-unit="{{ $product->price_unit }}">
                    {{-- Mode tampil --}}
                    <div class="stock-display flex items-center justify-center gap-1 py-1 @if(Auth::user()->isAdmin()) cursor-pointer hover:bg-purple-500/10 rounded-lg @endif"
                         @if(Auth::user()->isAdmin()) onclick="startEdit({{ $product->id }}, {{ $loc->id }})" @endif>
                        <span class="stock-value">{{ $s > 0 ? $displayVal : '—' }}</span>
                        @if(Auth::user()->isAdmin())
                        <button
                            class="edit-btn opacity-0 group-hover:opacity-100 transition-opacity duration-150 text-purple-400 hover:text-purple-600 dark:text-purple-500 dark:hover:text-purple-300 ml-0.5 flex-shrink-0"
                            title="Edit stok">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                    {{-- Mode edit (hanya admin) --}}
                    @if(Auth::user()->isAdmin())
                    <div class="stock-edit hidden">
                        <div class="flex flex-col items-center gap-1 py-0.5">
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                class="stock-input w-20 text-center bg-white dark:bg-dp-900 border-2 border-purple-400 dark:border-purple-500 text-slate-800 dark:text-purple-100 rounded-lg px-2 py-1 text-xs font-bold focus:outline-none focus:border-purple-600 shadow-md"
                                onkeydown="handleEditKey(event, {{ $product->id }}, {{ $loc->id }})"
                            />
                            <div class="flex gap-1">
                                <button onclick="saveEdit({{ $product->id }}, {{ $loc->id }})"
                                    class="save-btn bg-emerald-600 hover:bg-emerald-700 text-white rounded-md px-2 py-0.5 text-[10px] font-bold transition-colors">✓</button>
                                <button onclick="cancelEdit({{ $product->id }}, {{ $loc->id }})"
                                    class="bg-slate-200 hover:bg-slate-300 dark:bg-dp-700 dark:hover:bg-dp-600 text-slate-600 dark:text-purple-300 rounded-md px-2 py-0.5 text-[10px] font-bold transition-colors">✕</button>
                            </div>
                        </div>
                    </div>
                    @endif
                </td>
                @endforeach
                @php
                    $displayTotal = number_format($rowTotal, 2, ',', '.');
                    if ($product->price_unit === 'gram' && $rowTotal >= 1000) {
                        $displayTotal = number_format($rowTotal / 1000, 2, ',', '.') . ' kg';
                    }
                @endphp
                <td class="px-3 py-3 text-center text-slate-800 dark:text-purple-100 font-black" id="row-total-{{ $product->id }}">
                    {{ $displayTotal }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($locations) + 3 }}" class="px-6 py-12 text-center text-slate-400 dark:text-purple-400">
                    <div class="text-3xl mb-2">🔍</div>
                    <p class="font-extrabold text-sm text-slate-700 dark:text-purple-200">Tidak ada produk yang ditemukan</p>
                    <p class="text-xs text-slate-400 dark:text-purple-400 mt-1">Coba gunakan kata kunci pencarian lain atau pilih kategori berbeda.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($products->hasPages())
<div class="bg-slate-50/50 dark:bg-dp-900/50 border-t border-slate-100 dark:border-dp-700 px-5 py-4 stock-pagination">
    {{ $products->links() }}
</div>
@endif

