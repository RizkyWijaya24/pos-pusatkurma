@php
    $stock      = $product->display_stock ?? 0;
    $priceUnit  = $product->price_unit;
    $basePrice  = $product->selling_price;
    $hasTiers   = !empty($product->price_tiers) && count($product->price_tiers) > 1;
    $stockClass = $stock > 20 ? 'available' : ($stock > 0 ? 'low' : 'empty');

    // Stocklabel: untuk gram tampilkan dalam kg jika stok besar
    if ($priceUnit === 'gram') {
        if ($stock > 20) $stockLabel = 'Stok Tersedia';
        elseif ($stock > 0) $stockLabel = 'Sisa ±' . ($stock >= 1000 ? round($stock/1000,1).'kg' : $stock.'g');
        else $stockLabel = 'Stok Habis';
    } elseif ($priceUnit === 'kg') {
        if ($stock > 20) $stockLabel = 'Stok Tersedia';
        elseif ($stock > 0) $stockLabel = "Sisa ±{$stock} kg";
        else $stockLabel = 'Stok Habis';
    } else {
        if ($stock > 20) $stockLabel = 'Stok Tersedia';
        elseif ($stock > 0) $stockLabel = "Sisa ±{$stock} {$priceUnit}";
        else $stockLabel = 'Stok Habis';
    }

    // === Breakdown harga per satuan ===
    // gram: tampilkan 250g / 500g / 1kg
    $gramBreakdowns = [];
    if ($priceUnit === 'gram') {
        $gramBreakdowns = [
            ['qty' => 250,  'label' => '250g',  'abbr' => '¼ kg'],
            ['qty' => 500,  'label' => '500g',  'abbr' => '½ kg'],
            ['qty' => 1000, 'label' => '1 kg',  'abbr' => ''],
        ];
        foreach ($gramBreakdowns as $i => $br) {
            $qty = $br['qty'];
            // Cek apakah ada harga tier yang berlaku
            $price = $basePrice;
            if ($hasTiers) {
                foreach ($product->price_tiers as $tier) {
                    $min = floatval($tier['min_qty'] ?? 0);
                    $max = isset($tier['max_qty']) && $tier['max_qty'] !== '' ? floatval($tier['max_qty']) : INF;
                    if ($qty >= $min && $qty <= $max) {
                        $price = intval($tier['price']);
                        break;
                    }
                }
            }
            $gramBreakdowns[$i]['price'] = $price * $qty;
            $gramBreakdowns[$i]['per_gram_price'] = $price;
            $normalTotal = $basePrice * $qty;
            $gramBreakdowns[$i]['save_pct'] = ($normalTotal > 0 && $price * $qty < $normalTotal)
                ? round((($normalTotal - $price * $qty) / $normalTotal) * 100)
                : 0;
        }
    }

    // kg: tampilkan ½ kg
    $kgHalfPrice = null;
    if ($priceUnit === 'kg') {
        $kgHalfPrice = (int) round($basePrice / 2);
    }
@endphp

<div class="product-card {{ isset($isFeatured) && $isFeatured ? 'featured-card' : '' }}" id="product-card-{{ $product->id }}"
     x-data="{ selectedQty: {{ $priceUnit === 'gram' ? 250 : 1 }} }">

    <div class="card-img-wrap">
        @if($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}"
                 alt="{{ $product->name }}"
                 class="card-img"
                 loading="lazy"
                 onerror="this.style.display='none'; this.parentElement.querySelector('.card-img-placeholder').style.display='flex';">
        @endif
        <div class="card-img-placeholder" style="{{ $product->image_path ? 'display: none;' : '' }}">
            <i class="fa-solid fa-seedling"></i>
            <span>Pusat Kurma</span>
        </div>

        <!-- Badges -->
        <div class="card-badges">
            @php
                $freeShippingIds = json_decode($shop_settings['free_shipping_product_ids'] ?? '[]', true) ?: [];
            @endphp
            @if(in_array($product->id, $freeShippingIds))
                <span class="badge" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
                    🚚 Gratis Ongkir
                </span>
            @endif
            @if(isset($isFeatured) && $isFeatured)
                <span class="badge" style="background: linear-gradient(135deg, var(--clr-gold), var(--clr-gold-dark)); color: #fff;">
                    🌟 UNGGULAN
                </span>
            @endif
            @if($priceUnit === 'gram')
                <span class="badge" style="background:var(--clr-primary);color:#fff;">
                    <i class="fa-solid fa-scale-balanced" style="font-size:9px;"></i> Beli per Gram
                </span>
            @elseif($hasTiers)
                <span class="badge badge-wholesale">
                    <i class="fa-solid fa-tag" style="font-size:9px;"></i> Harga Grosir
                </span>
            @endif
            @if($stock > 0 && $stock <= 10)
                <span class="badge badge-low-stock">
                    <i class="fa-solid fa-fire" style="font-size:9px;"></i> Hampir Habis
                </span>
            @endif
        </div>

        @if($product->category)
            <span class="badge-category">{{ $product->category }}</span>
        @endif
    </div>

    <!-- Body -->
    <div class="card-body">
        <div class="card-name">{{ $product->name }}</div>

        {{-- ── GRAM: Tampilkan harga dasar per gram + 3 pill pilihan porsi ── --}}
        @if($priceUnit === 'gram')
            <div style="display:flex;align-items:baseline;gap:5px;">
                <span class="card-price" style="font-size:16px;">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                <span class="card-price-unit">/ gram</span>
            </div>

            <div class="price-breakdown">
                <div class="price-breakdown-label">Pilih Porsi & Lihat Harga</div>
                <div class="price-pills">
                    @foreach($gramBreakdowns as $br)
                        <button type="button"
                            class="price-pill {{ $loop->index === 1 ? 'active' : '' }}"
                            onclick="selectPill(this, {{ $product->id }}, {{ $br['qty'] }}, '{{ $br['label'] }}', {{ $br['price'] }})"
                            data-qty="{{ $br['qty'] }}"
                            data-price="{{ $br['price'] }}"
                            id="pill-{{ $product->id }}-{{ $br['qty'] }}">
                            <span class="pill-qty">{{ $br['label'] }}
                                @if($br['abbr']) <br><span style="font-size:9px;color:var(--clr-text-muted);font-weight:500;">{{ $br['abbr'] }}</span>@endif
                            </span>
                            <span class="pill-price">Rp {{ number_format($br['price'], 0, ',', '.') }}</span>
                            @if($br['save_pct'] > 0)
                                <span class="pill-save">Hemat {{ $br['save_pct'] }}%</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            @if($hasTiers)
                <div class="card-wholesale-note">
                    <i class="fa-solid fa-tags" style="font-size:10px;"></i>
                    Makin banyak makin murah!
                </div>
            @endif

        {{-- ── KG: Tampilkan harga per kg + info setengah kg ── --}}
        @elseif($priceUnit === 'kg')
            <div class="card-price-row">
                <span class="card-price">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                <span class="card-price-unit">/ kg</span>
            </div>
            @if($kgHalfPrice)
            <div class="price-breakdown" style="margin-top:4px;">
                <div class="price-breakdown-label">Juga tersedia</div>
                <div style="display:flex;gap:6px;">
                    <div class="price-pill active" style="flex:1;">
                        <span class="pill-qty">500g <br><span style="font-size:9px;color:var(--clr-text-muted);font-weight:500;">½ kg</span></span>
                        <span class="pill-price">Rp {{ number_format($kgHalfPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="price-pill" style="flex:1;border-color:var(--clr-primary);background:rgba(6,95,70,.04);">
                        <span class="pill-qty">1 kg</span>
                        <span class="pill-price">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                        @if($hasTiers)
                            <span class="pill-save">Grosir tersedia</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @if($hasTiers)
                <div class="card-wholesale-note">
                    <i class="fa-solid fa-tags" style="font-size:10px;"></i>
                    Harga grosir tersedia
                </div>
            @endif

        {{-- ── PACK: Info isi pack ── --}}
        @elseif($priceUnit === 'pack')
            <div class="card-price-row">
                <span class="card-price">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                <span class="card-price-unit">/ pack</span>
            </div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:3px;">
                <span class="unit-info-chip">
                    <i class="fa-solid fa-box-open" style="font-size:10px;"></i> 1 Pack Siap Kirim
                </span>
                @if($hasTiers)
                    <span class="unit-info-chip" style="background:rgba(217,119,6,.08);color:var(--clr-gold-dark);border-color:rgba(217,119,6,.2);">
                        <i class="fa-solid fa-tags" style="font-size:10px;"></i> Grosir Tersedia
                    </span>
                @endif
            </div>

        {{-- ── PCS / LAINNYA ── --}}
        @else
            <div class="card-price-row">
                <span class="card-price">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                <span class="card-price-unit">/ {{ $priceUnit }}</span>
            </div>
            @if($hasTiers)
                <div class="card-wholesale-note">
                    <i class="fa-solid fa-tags" style="font-size:10px;"></i>
                    Harga grosir tersedia
                </div>
            @endif
        @endif

        <div class="card-stock" style="margin-top:6px;">
            <span class="stock-dot {{ $stockClass }}"></span>
            <span class="stock-text {{ $stockClass }}">{{ $stockLabel }}</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer" style="flex-wrap: wrap; gap: 8px;">
        {{-- Wishlist + Compare icon buttons (before detail/cart) --}}
        @php
            $productForJs = json_encode([
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => $product->selling_price,
                'unit'     => $product->price_unit,
                'img'      => $product->image_path ? asset('storage/' . $product->image_path) : '',
                'category' => $product->category ?? '',
            ]);
        @endphp
        <button
            data-wishlist-id="{{ $product->id }}"
            onclick="toggleWishlist({{ $productForJs }})"
            class="btn-detail"
            style="width:38px;min-width:0;flex:0 0 auto;padding:0;display:flex;align-items:center;justify-content:center;background:var(--clr-surface-2);color:var(--clr-text-muted);border:1px solid var(--clr-border);"
            title="Tambah ke Wishlist"
            id="wishlist-btn-{{ $product->id }}">
            <i class="fa-regular fa-heart"></i>
        </button>
        <button
            data-compare-id="{{ $product->id }}"
            onclick="toggleCompare({{ $productForJs }})"
            class="btn-detail"
            style="width:38px;min-width:0;flex:0 0 auto;padding:0;display:flex;align-items:center;justify-content:center;background:var(--clr-surface-2);color:var(--clr-text-muted);border:1px solid var(--clr-border);"
            title="Bandingkan"
            id="compare-btn-{{ $product->id }}">
            <i class="fas fa-balance-scale" style="font-size:12px;"></i>
        </button>
        <a href="{{ route('shop.product.show', $product) }}"
           class="btn-detail"
           style="background: var(--clr-surface-2); color: var(--clr-text); border: 1px solid var(--clr-border); flex: 1; min-width: 80px;"
           id="product-detail-btn-{{ $product->id }}">
            <i class="fa-solid fa-eye"></i> Detail
        </a>
        <button onclick="addToCartFromCard({{ $product->id }}, {
                    id: {{ $product->id }},
                    sku: '{{ $product->sku }}',
                    name: '{{ addslashes($product->name) }}',
                    selling_price: {{ $basePrice }},
                    price_unit: '{{ $priceUnit }}',
                    image_path: '{{ $product->image_path }}',
                    weight_grams: {{ $product->weight_grams ?? 500 }},
                    price_tiers: {{ json_encode($product->price_tiers) }}
                })"
                class="btn-detail"
                style="flex: 1.2; min-width: 100px;"
                id="product-add-cart-btn-{{ $product->id }}">
            <i class="fa-solid fa-cart-plus"></i>
            @if($priceUnit === 'gram') + Keranjang
            @elseif($priceUnit === 'kg') + Keranjang
            @else + Keranjang @endif
        </button>
    </div>
</div>
