@extends('layouts.shop')

@section('title', $product->name)
@section('meta_description', 'Beli ' . $product->name . ' harga Rp ' . number_format($product->selling_price,0,',','.') . ' per ' . $product->price_unit . '. Tersedia di Pusat Kurma Cianjur dengan kualitas premium terjamin.')

@push('styles')
<style>
    /* ═══ Breadcrumb ═══ */
    .breadcrumb-wrap {
        background: var(--clr-surface);
        border-bottom: 1px solid var(--clr-border);
        padding: 12px 24px;
    }
    .breadcrumb {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--clr-text-muted);
        list-style: none;
    }
    .breadcrumb a { color: var(--clr-primary); transition: var(--transition); }
    .breadcrumb a:hover { color: var(--clr-primary-light); }
    .breadcrumb-sep { color: var(--clr-border); }

    /* ═══ Product Detail Layout ═══ */
    .product-detail-section { padding: 40px 24px 64px; }
    .product-detail-inner {
        max-width: 1280px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 56px;
        align-items: start;
    }

    /* ─── Image Gallery ─── */
    .product-gallery {}
    .gallery-main {
        aspect-ratio: 1/1;
        border-radius: var(--radius-lg);
        overflow: hidden;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        position: relative;
        border: 1px solid var(--clr-border);
        box-shadow: var(--shadow-md);
    }
    .gallery-main img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-main-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: var(--clr-primary);
    }
    .gallery-main-placeholder i { font-size: 80px; opacity: .5; }
    .gallery-main-placeholder span { font-size: 16px; font-weight: 600; opacity: .6; }

    .gallery-badges-overlay {
        position: absolute;
        top: 16px;
        left: 16px;
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    /* ─── Product Info ─── */
    .product-info {}

    .info-category {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: var(--clr-primary-light);
        background: rgba(5,150,105,.08);
        padding: 5px 12px;
        border-radius: 99px;
        margin-bottom: 14px;
    }

    .info-name {
        font-family: var(--font-heading);
        font-size: clamp(24px, 3vw, 38px);
        font-weight: 700;
        color: var(--clr-primary-dark);
        line-height: 1.2;
        margin-bottom: 8px;
    }
    .info-sku { font-size: 13px; color: var(--clr-text-muted); margin-bottom: 20px; }
    .info-sku span { font-weight: 600; color: var(--clr-text); }

    /* Price Card */
    .price-card {
        background: linear-gradient(135deg, var(--clr-primary-dark) 0%, var(--clr-primary) 100%);
        border-radius: var(--radius-md);
        padding: 24px;
        margin-bottom: 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .price-card::after {
        content: '🌴';
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 80px;
        opacity: .12;
        line-height: 1;
    }
    .price-label { font-size: 12px; font-weight: 600; letter-spacing: .8px; text-transform: uppercase; color: rgba(255,255,255,.6); margin-bottom: 6px; }
    .price-main { font-size: 38px; font-weight: 900; font-feature-settings: "tnum"; }
    .price-unit { font-size: 16px; font-weight: 400; opacity: .7; }
    .price-wholesale-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        background: rgba(251,191,36,.2);
        border: 1px solid rgba(251,191,36,.35);
        color: var(--clr-gold-light);
        padding: 5px 12px;
        border-radius: 99px;
        margin-top: 10px;
    }

    /* Stock indicator */
    .stock-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        border-radius: var(--radius-md);
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    .stock-indicator.available { background: rgba(16,185,129,.08); border: 1.5px solid rgba(16,185,129,.25); color: #065f46; }
    .stock-indicator.low       { background: rgba(217,119,6,.08);  border: 1.5px solid rgba(217,119,6,.25);  color: var(--clr-gold-dark); }
    .stock-indicator.empty     { background: rgba(239,68,68,.08);  border: 1.5px solid rgba(239,68,68,.25);  color: #991b1b; }
    .stock-indicator .stock-icon { font-size: 18px; }

    /* Action buttons */
    .action-buttons { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
    .btn-order-wa {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 32px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        border: none;
        transition: var(--transition);
        text-decoration: none;
        box-shadow: 0 8px 24px rgba(37,211,102,.35);
    }
    .btn-order-wa:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(37,211,102,.45); }
    .btn-back-catalog {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 24px;
        border-radius: var(--radius-md);
        background: var(--clr-surface);
        border: 1.5px solid var(--clr-border);
        color: var(--clr-text-muted);
        font-size: 14px;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
    }
    .btn-back-catalog:hover { border-color: var(--clr-primary); color: var(--clr-primary); background: rgba(6,95,70,.04); }

    /* Info chips */
    .info-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 20px;
        border-top: 1px solid var(--clr-border);
    }
    .info-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        background: var(--clr-surface-2);
        font-size: 13px;
        font-weight: 500;
        color: var(--clr-text-muted);
    }
    .info-chip i { color: var(--clr-primary); }

    /* ═══ Pricing Tiers Table ═══ */
    .tiers-section {
        margin-top: 32px;
        background: var(--clr-surface);
        border-radius: var(--radius-md);
        border: 1px solid var(--clr-border);
        overflow: hidden;
    }
    .tiers-header {
        padding: 16px 20px;
        background: linear-gradient(90deg, rgba(6,95,70,.05) 0%, rgba(217,119,6,.04) 100%);
        border-bottom: 1px solid var(--clr-border);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tiers-header h3 { font-size: 15px; font-weight: 700; color: var(--clr-primary-dark); }
    .tiers-header i { color: var(--clr-gold); font-size: 18px; }
    .tiers-table { width: 100%; border-collapse: collapse; }
    .tiers-table th {
        padding: 12px 20px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--clr-text-muted);
        background: var(--clr-surface-2);
        text-align: left;
        border-bottom: 1px solid var(--clr-border);
    }
    .tiers-table td {
        padding: 14px 20px;
        font-size: 14px;
        border-bottom: 1px solid rgba(0,0,0,.05);
    }
    .tiers-table tr:last-child td { border-bottom: none; }
    .tiers-table tr:hover td { background: rgba(6,95,70,.03); }
    .tier-price { font-weight: 700; color: var(--clr-primary); font-size: 16px; font-feature-settings: "tnum"; }
    .tier-saving { font-size: 12px; color: var(--clr-success); font-weight: 600; }
    .tier-badge-best {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        background: var(--clr-gold);
        color: #fff;
        padding: 2px 8px;
        border-radius: 99px;
    }

    /* ═══ Related Products ═══ */
    .related-section { padding: 0 24px 64px; }
    .related-inner { max-width: 1280px; margin: 0 auto; }
    .related-header { margin-bottom: 24px; }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    /* Reuse card styles from index */
    .product-card {
        background: var(--clr-surface);
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid var(--clr-border);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: rgba(6,95,70,.2); }
    .card-img-wrap { position: relative; aspect-ratio: 1/1; overflow: hidden; background: var(--clr-surface-2); }
    .card-img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
    .product-card:hover .card-img { transform: scale(1.06); }
    .card-img-placeholder { width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:var(--clr-primary);gap:8px; }
    .card-img-placeholder i { font-size:40px;opacity:.7; }
    .badge-category { position:absolute;top:10px;right:10px;background:rgba(0,0,0,.5);backdrop-filter:blur(8px);color:#fff;font-size:10px;font-weight:600;padding:3px 9px;border-radius:99px; }
    .card-body { padding: 14px; flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .card-name { font-size:15px;font-weight:700;color:var(--clr-text);line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
    .card-price-row { display:flex;align-items:baseline;gap:6px; }
    .card-price { font-size:17px;font-weight:800;color:var(--clr-primary);font-feature-settings:"tnum"; }
    .card-price-unit { font-size:12px;color:var(--clr-text-muted); }
    .card-footer { padding:0 14px 14px;display:flex;gap:8px; }
    .btn-detail { flex:1;padding:9px;border-radius:var(--radius-sm);background:var(--clr-primary);color:#fff;font-size:13px;font-weight:600;border:none;text-align:center;transition:var(--transition);text-decoration:none;display:flex;align-items:center;justify-content:center;gap:5px; }
    .btn-detail:hover { background:var(--clr-primary-light); }
    .btn-wa { width:38px;height:38px;border-radius:var(--radius-sm);background:#25d366;color:#fff;font-size:17px;border:none;display:flex;align-items:center;justify-content:center;transition:var(--transition);text-decoration:none;flex-shrink:0; }
    .btn-wa:hover { background:#128c7e; }
    .badge { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700; }
    .badge-wholesale { background:var(--clr-gold);color:#fff; }
    .card-badges { position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:5px; }

    @media (max-width: 900px) {
        .product-detail-inner { grid-template-columns: 1fr; gap: 32px; }
    }
    @media (max-width: 600px) {
        .related-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* ═══ Review / Ulasan Section ═══ */
    .reviews-section {
        background: var(--clr-surface);
        border-top: 1px solid var(--clr-border);
        padding: 56px 24px;
    }
    .reviews-inner {
        max-width: 1000px;
        margin: 0 auto;
    }
    .reviews-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 32px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--clr-border);
    }
    .reviews-title {
        font-family: var(--font-heading);
        font-size: 22px;
        font-weight: 800;
        color: var(--clr-primary-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .reviews-avg {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--clr-surface-2);
        border: 1px solid var(--clr-border);
        border-radius: 16px;
        padding: 12px 20px;
    }
    .reviews-avg-num {
        font-size: 36px;
        font-weight: 900;
        color: var(--clr-primary-dark);
        line-height: 1;
    }
    .reviews-avg-stars { display: flex; gap: 3px; }
    .reviews-avg-count { font-size: 12px; color: var(--clr-text-muted); margin-top: 2px; }
    .star-icon {
        font-size: 16px;
        color: var(--clr-border);
    }
    .star-icon.filled { color: #f59e0b; }
    .star-icon.half { color: #f59e0b; }

    /* Review Cards */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
        margin-bottom: 36px;
    }
    .review-card {
        background: var(--clr-surface-2);
        border: 1px solid var(--clr-border);
        border-radius: var(--radius-md);
        padding: 20px;
        transition: var(--transition);
    }
    .review-card:hover { border-color: rgba(6,95,70,.2); box-shadow: var(--shadow-sm); }
    .review-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .review-author {
        font-size: 14px;
        font-weight: 700;
        color: var(--clr-text);
    }
    .review-verified {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        color: var(--clr-success);
        background: rgba(16,185,129,.08);
        border-radius: 99px;
        padding: 2px 8px;
        margin-left: 6px;
    }
    .review-stars { display: flex; gap: 2px; }
    .review-star { font-size: 13px; color: var(--clr-border); }
    .review-star.filled { color: #f59e0b; }
    .review-date { font-size: 11px; color: var(--clr-text-muted); }
    .review-comment {
        font-size: 14px;
        color: var(--clr-text);
        line-height: 1.65;
    }
    .reviews-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--clr-text-muted);
    }
    .reviews-empty i { font-size: 40px; margin-bottom: 12px; opacity: .4; }
    .reviews-empty p { font-size: 14px; }

    /* Write Review Form */
    .review-form-card {
        background: var(--clr-surface-2);
        border: 1.5px dashed var(--clr-border);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-top: 32px;
    }
    .review-form-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--clr-primary-dark);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .review-form-group { margin-bottom: 14px; }
    .review-form-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--clr-text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 5px;
    }
    .review-form-input,
    .review-form-textarea {
        width: 100%;
        padding: 10px 13px;
        border-radius: 9px;
        border: 1.5px solid var(--clr-border);
        font-family: inherit;
        font-size: 13px;
        background: var(--clr-surface);
        color: var(--clr-text);
        outline: none;
        transition: var(--transition);
    }
    .review-form-input:focus,
    .review-form-textarea:focus {
        border-color: var(--clr-primary-light);
        box-shadow: 0 0 0 3px rgba(5,150,105,.1);
    }
    .review-form-textarea { resize: vertical; min-height: 90px; }
    .star-rating-input {
        display: flex;
        gap: 6px;
        margin-bottom: 4px;
    }
    .star-btn {
        font-size: 28px;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--clr-border);
        transition: color .15s, transform .1s;
        line-height: 1;
        padding: 0;
    }
    .star-btn:hover,
    .star-btn.active { color: #f59e0b; transform: scale(1.15); }
    .btn-submit-review {
        padding: 11px 24px;
        border-radius: 99px;
        background: var(--clr-primary);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .btn-submit-review:hover { background: var(--clr-primary-light); transform: translateY(-1px); }
    .btn-submit-review:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    .review-success-msg {
        background: rgba(16,185,129,.08);
        border: 1px solid rgba(16,185,129,.2);
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 13px;
        color: #065f46;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .review-code-help {
        font-size: 11px;
        color: var(--clr-text-muted);
        margin-top: 5px;
    }
    @media (max-width: 640px) {
        .reviews-section { padding: 36px 16px; }
        .reviews-header { flex-direction: column; }
    }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb-wrap">
    <ol class="breadcrumb">
        <li><a href="{{ route('shop.index') }}">Katalog</a></li>
        <li class="breadcrumb-sep"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></li>
        @if($product->category)
            <li><a href="{{ route('shop.index', ['category' => $product->category]) }}">{{ $product->category }}</a></li>
            <li class="breadcrumb-sep"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></li>
        @endif
        <li style="color:var(--clr-text);font-weight:600;">{{ Str::limit($product->name, 40) }}</li>
    </ol>
</div>

{{-- Product Detail --}}
<section class="product-detail-section">
    <div class="product-detail-inner">

        {{-- ─── Left: Gallery ─── --}}
        <div class="product-gallery">
            <div class="gallery-main" id="product-main-image">
                @if($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}"
                         alt="{{ $product->name }}"
                         id="main-product-img"
                         onerror="this.style.display='none'; this.parentElement.querySelector('.gallery-main-placeholder').style.display='flex';">
                @endif
                <div class="gallery-main-placeholder" style="{{ $product->image_path ? 'display: none;' : '' }}">
                    <i class="fa-solid fa-seedling"></i>
                    <span>Pusat Kurma Cianjur</span>
                </div>

                <div class="gallery-badges-overlay">
                    @if(!empty($product->price_tiers) && count($product->price_tiers) > 1)
                        <span class="badge badge-wholesale">
                            <i class="fa-solid fa-tags" style="font-size:10px;"></i> Harga Grosir
                        </span>
                    @endif
                    @if($displayStock > 0 && $displayStock <= 10)
                        <span class="badge" style="background:#ef4444;color:#fff;">
                            <i class="fa-solid fa-fire" style="font-size:9px;"></i> Hampir Habis
                        </span>
                    @endif
                </div>
            </div>

            {{-- Trust badges --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px;">
                @foreach([
                    ['fa-shield-halved', 'Kualitas Terjamin', 'Kurma pilihan grade A'],
                    ['fa-truck-fast', 'Pengiriman Cepat', 'Seluruh Indonesia'],
                    ['fa-headset', 'Layanan 24/7', 'WhatsApp responsif'],
                ] as $trust)
                    <div style="text-align:center;padding:14px 8px;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-md);">
                        <i class="fa-solid {{ $trust[0] }}" style="font-size:22px;color:var(--clr-primary);margin-bottom:6px;display:block;"></i>
                        <div style="font-size:12px;font-weight:700;color:var(--clr-text);margin-bottom:2px;">{{ $trust[1] }}</div>
                        <div style="font-size:11px;color:var(--clr-text-muted);">{{ $trust[2] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ─── Right: Info ─── --}}
        <div class="product-info">
            @if($product->category)
                <div class="info-category">
                    <i class="fa-solid fa-tag"></i> {{ $product->category }}
                </div>
            @endif

            <h1 class="info-name" id="product-name-title">{{ $product->name }}</h1>
            <div class="info-sku">SKU: <span>{{ $product->sku ?? 'N/A' }}</span></div>

            {{-- Price Card --}}
            @php
                $basePrice  = $product->selling_price;
                $priceUnit  = $product->price_unit;
                $hasTiers   = !empty($product->price_tiers) && count($product->price_tiers) > 1;

                // === Hitung breakdown harga untuk gram ===
                $showBreakdowns = [];
                if ($priceUnit === 'gram') {
                    $portionSizes = [250, 500, 1000, 2000];
                    $portionLabels = [250 => '250g', 500 => '500g', 1000 => '1 kg', 2000 => '2 kg'];
                    $portionAbbr  = [250 => '¼ kg', 500 => '½ kg', 1000 => '1 kg penuh', 2000 => '2 kg'];
                    foreach ($portionSizes as $qty) {
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
                        $total = $price * $qty;
                        $normalTotal = $basePrice * $qty;
                        $savePct = ($normalTotal > 0 && $total < $normalTotal)
                            ? round((($normalTotal - $total) / $normalTotal) * 100) : 0;
                        $showBreakdowns[] = [
                            'qty'      => $qty,
                            'label'    => $portionLabels[$qty],
                            'abbr'     => $portionAbbr[$qty],
                            'price'    => $price,
                            'total'    => $total,
                            'save_pct' => $savePct,
                        ];
                    }
                }

                // kg: harga setengah kg
                $kgHalfPrice = ($priceUnit === 'kg') ? (int) round($basePrice / 2) : null;
            @endphp

            <div class="price-card" id="product-price-card">
                <div class="price-label">
                    @if($priceUnit === 'gram') Harga Eceran per Gram
                    @elseif($priceUnit === 'kg') Harga per Kilogram
                    @elseif($priceUnit === 'pack') Harga per Pack
                    @else Harga Eceran @endif
                </div>
                <div>
                    <span class="price-main">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                    <span class="price-unit">/ {{ $priceUnit }}</span>
                </div>
                @if($hasTiers)
                    <div class="price-wholesale-note">
                        <i class="fa-solid fa-tags"></i>
                        Harga grosir tersedia — lihat tabel di bawah
                    </div>
                @endif
            </div>

            @php
                $freeShippingIds = json_decode($shop_settings['free_shipping_product_ids'] ?? '[]', true) ?: [];
                $isFreeShipping = in_array($product->id, $freeShippingIds);
            @endphp
            @if($isFreeShipping)
                <div style="margin-bottom:20px;padding:12px 16px;background:linear-gradient(135deg, rgba(5,150,105,.1), rgba(16,185,129,.15));border:1.5px solid #059669;border-radius:12px;display:flex;align-items:center;gap:12px;color:#065f46;">
                    <i class="fa-solid fa-truck-fast" style="font-size:24px;color:#059669;"></i>
                    <div>
                        <div style="font-weight:800;font-size:14px;">🚚 Gratis Ongkos Kirim!</div>
                        <div style="font-size:12px;color:var(--clr-text-muted);">Produk ini dibebaskan dari biaya pengiriman saat checkout.</div>
                    </div>
                </div>
            @endif

            {{-- === BREAKDOWN HARGA GRAM === --}}
            @if($priceUnit === 'gram' && count($showBreakdowns) > 0)
                <div style="margin-bottom:20px;">
                    <div style="font-size:12px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--clr-text-muted);margin-bottom:10px;">
                        <i class="fa-solid fa-scale-balanced" style="color:var(--clr-primary);"></i>
                        Perbandingan Harga per Porsi
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                        @foreach($showBreakdowns as $br)
                            <div style="
                                padding:12px;border-radius:10px;
                                background:{{ $br['qty'] === 500 ? 'rgba(6,95,70,.07)' : 'var(--clr-surface-2)' }};
                                border:{{ $br['qty'] === 500 ? '2px solid var(--clr-primary)' : '1.5px solid var(--clr-border)' }};
                                text-align:center;position:relative;
                            ">
                                @if($br['qty'] === 500)
                                    <div style="position:absolute;top:-9px;left:50%;transform:translateX(-50%);background:var(--clr-primary);color:#fff;font-size:9px;font-weight:700;padding:2px 9px;border-radius:99px;white-space:nowrap;">Paling Populer</div>
                                @endif
                                <div style="font-size:13px;font-weight:800;color:var(--clr-text);">{{ $br['label'] }}</div>
                                <div style="font-size:10px;color:var(--clr-text-muted);margin-bottom:5px;">{{ $br['abbr'] }}</div>
                                <div style="font-size:18px;font-weight:900;color:var(--clr-primary);font-feature-settings:'tnum';">Rp {{ number_format($br['total'], 0, ',', '.') }}</div>
                                <div style="font-size:10px;color:var(--clr-text-muted);">(Rp {{ number_format($br['price'], 0, ',', '.') }}/gram)</div>
                                @if($br['save_pct'] > 0)
                                    <div style="margin-top:4px;font-size:10px;font-weight:700;color:#059669;background:rgba(5,150,105,.1);border-radius:99px;padding:2px 8px;display:inline-block;">
                                        Hemat {{ $br['save_pct'] }}%
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            {{-- === INFO HARGA SETENGAH KG === --}}
            @elseif($priceUnit === 'kg' && $kgHalfPrice)
                <div style="margin-bottom:20px;padding:14px;background:var(--clr-surface-2);border-radius:10px;border:1px solid var(--clr-border);">
                    <div style="font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--clr-text-muted);margin-bottom:8px;">
                        <i class="fa-solid fa-weight-scale" style="color:var(--clr-primary);"></i> Tersedia juga
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="flex:1;padding:10px;background:var(--clr-surface);border:1.5px solid var(--clr-border);border-radius:8px;text-align:center;">
                            <div style="font-size:12px;font-weight:700;color:var(--clr-text);">500 gram</div>
                            <div style="font-size:9px;color:var(--clr-text-muted);">(½ kg)</div>
                            <div style="font-size:17px;font-weight:800;color:var(--clr-primary);">Rp {{ number_format($kgHalfPrice, 0, ',', '.') }}</div>
                        </div>
                        <div style="flex:1;padding:10px;background:rgba(6,95,70,.06);border:2px solid var(--clr-primary);border-radius:8px;text-align:center;position:relative;">
                            <div style="position:absolute;top:-9px;left:50%;transform:translateX(-50%);background:var(--clr-primary);color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:99px;">Standar</div>
                            <div style="font-size:12px;font-weight:700;color:var(--clr-text);">1 kg</div>
                            <div style="font-size:9px;color:var(--clr-text-muted);">(1 kilogram)</div>
                            <div style="font-size:17px;font-weight:800;color:var(--clr-primary);">Rp {{ number_format($basePrice, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Stock indicator --}}
            @php
                $stockClass  = $displayStock > 20 ? 'available' : ($displayStock > 0 ? 'low' : 'empty');
                $stockIcon   = $displayStock > 20 ? 'fa-circle-check' : ($displayStock > 0 ? 'fa-triangle-exclamation' : 'fa-circle-xmark');
                $stockMsg    = $displayStock > 20
                    ? 'Stok tersedia'
                    : ($displayStock > 0 ? "Stok hampir habis (±{$displayStock} {$product->price_unit})" : 'Stok saat ini habis – hubungi kami untuk pre-order');
            @endphp
            <div class="stock-indicator {{ $stockClass }}" id="product-stock-indicator">
                <i class="fa-solid {{ $stockIcon }} stock-icon"></i>
                <span>{{ $stockMsg }}</span>
            </div>

            {{-- Kuantitas & Tambah ke Keranjang --}}
            <div style="margin-bottom: 24px;">
                <label style="font-size: 13px; font-weight: 600; color: var(--clr-text-muted); display: block; margin-bottom: 8px;">Kuantitas:</label>

                @if($priceUnit === 'gram')
                {{-- Pilihan porsi gram dalam bentuk tombol --}}
                <div x-data="{ detailQty: 500 }">
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                        @foreach([250 => '250g', 500 => '500g', 1000 => '1 kg', 2000 => '2 kg'] as $gQty => $gLabel)
                            <button type="button"
                                x-on:click="detailQty = {{ $gQty }}"
                                :class="detailQty === {{ $gQty }} ? 'price-pill-detail active' : 'price-pill-detail'"
                                style="padding:8px 14px;border-radius:8px;font-size:13px;font-weight:700;border:1.5px solid var(--clr-border);background:var(--clr-surface);cursor:pointer;transition:all .2s;"
                                :style="detailQty === {{ $gQty }} ? 'border-color:var(--clr-primary);background:rgba(6,95,70,.07);color:var(--clr-primary);' : ''"
                                id="detail-gram-btn-{{ $gQty }}">
                                {{ $gLabel }}
                            </button>
                        @endforeach
                    </div>
                    <div style="font-size:12px;color:var(--clr-text-muted);margin-bottom:10px;">
                        Total: <strong x-text="'Rp ' + ({{ $basePrice }} * detailQty).toLocaleString('id-ID')" style="color:var(--clr-primary);"></strong>
                    </div>
                    <button @click="addToCart({
                                id: {{ $product->id }},
                                sku: '{{ $product->sku }}',
                                name: '{{ addslashes($product->name) }}',
                                selling_price: {{ $basePrice }},
                                price_unit: '{{ $priceUnit }}',
                                image_path: '{{ $product->image_path }}',
                                weight_grams: {{ $product->weight_grams ?? 500 }},
                                price_tiers: {{ json_encode($product->price_tiers) }}
                            }, detailQty)"
                            class="btn-order-wa"
                            style="width:100%;height:50px;background:var(--clr-primary);margin:0;box-shadow:0 4px 14px rgba(6,95,70,.25);cursor:pointer;"
                            id="product-add-cart-detail-btn">
                        <i class="fa-solid fa-cart-plus" style="font-size:18px;"></i>
                        Tambah ke Keranjang
                    </button>
                </div>

                @else
                {{-- Kuantitas normal (angka) untuk kg / pack / pcs --}}
                <div x-data="{ detailQty: 1 }" style="display: flex; gap: 12px; align-items: center; max-width: 100%; flex-wrap: wrap;">
                    <div class="cart-qty-control" style="height: 48px; border: 1.5px solid var(--clr-border);">
                        <button class="cart-qty-btn" style="width: 36px; height: 100%; font-size: 16px;" @click="if(detailQty > 1) detailQty--">-</button>
                        <input type="text" class="cart-qty-val" style="width: 48px; height: 100%; font-size: 15px; font-weight: 700;" :value="detailQty" @change="detailQty = Math.max(1, parseFloat($event.target.value) || 1)">
                        <button class="cart-qty-btn" style="width: 36px; height: 100%; font-size: 16px;" @click="detailQty++">+</button>
                    </div>
                    <button @click="addToCart({
                                id: {{ $product->id }},
                                sku: '{{ $product->sku }}',
                                name: '{{ addslashes($product->name) }}',
                                selling_price: {{ $basePrice }},
                                price_unit: '{{ $priceUnit }}',
                                image_path: '{{ $product->image_path }}',
                                weight_grams: {{ $product->weight_grams ?? 500 }},
                                price_tiers: {{ json_encode($product->price_tiers) }}
                            }, detailQty)"
                            class="btn-order-wa"
                            style="flex: 1; height: 48px; background: var(--clr-primary); margin: 0; box-shadow: 0 4px 14px rgba(6,95,70,.25); cursor: pointer; min-width: 180px;"
                            id="product-add-cart-detail-btn">
                        <i class="fa-solid fa-cart-plus" style="font-size:18px;"></i>
                        Tambah ke Keranjang
                    </button>
                </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="action-buttons">
                <button @click="isOpen = true"
                        class="btn-back-catalog"
                        style="background: var(--clr-surface-2); border-color: var(--clr-border); color: var(--clr-text); width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; height: 44px; cursor: pointer;"
                        id="product-open-cart-btn">
                    <i class="fa-solid fa-basket-shopping"></i> Lihat Keranjang Belanja
                </button>
                <a href="{{ route('shop.index') }}" class="btn-back-catalog" id="back-to-catalog-btn" style="width: 100%; text-align: center; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog
                </a>
            </div>

            {{-- Info Chips --}}
            <div class="info-chips">
                <div class="info-chip">
                    <i class="fa-solid fa-box"></i>
                    Satuan: {{ $product->price_unit }}
                </div>
                @if($product->category)
                    <div class="info-chip">
                        <i class="fa-solid fa-layer-group"></i>
                        Kategori: {{ $product->category }}
                    </div>
                @endif
                <div class="info-chip">
                    <i class="fa-solid fa-shield-halved"></i>
                    Kualitas Terjamin
                </div>
                <div class="info-chip">
                    <i class="fa-brands fa-whatsapp"></i>
                    Order via WA
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Pricing Tiers Table ─── --}}
    @if(!empty($product->price_tiers) && count($product->price_tiers) > 1)
        <div style="max-width:1280px;margin:48px auto 0;padding:0;">
            <div class="tiers-section" id="pricing-tiers-table">
                <div class="tiers-header">
                    <i class="fa-solid fa-tags"></i>
                    <h3>Tabel Harga Grosir</h3>
                </div>
                <table class="tiers-table">
                    <thead>
                        <tr>
                            <th>Kuantitas Minimum</th>
                            <th>Kuantitas Maksimum</th>
                            <th>Harga per {{ $product->price_unit }}</th>
                            <th>Hemat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->price_tiers as $i => $tier)
                            @php
                                $minQty  = $tier['min_qty'] ?? '0';
                                $maxQty  = isset($tier['max_qty']) && $tier['max_qty'] !== '' ? $tier['max_qty'] : '∞';
                                $price   = intval($tier['price'] ?? $product->selling_price);
                                $saving  = $product->selling_price - $price;
                                $savePct = $product->selling_price > 0 ? round(($saving / $product->selling_price) * 100) : 0;
                                $isBest  = ($i === count($product->price_tiers) - 1);
                            @endphp
                            <tr>
                                <td>{{ $minQty }} {{ $product->price_unit }}</td>
                                <td>
                                    @if($maxQty === '∞') <span style="font-size:18px;">∞</span>
                                    @else {{ $maxQty }} {{ $product->price_unit }}
                                    @endif
                                    @if($isBest)
                                        <span class="tier-badge-best" style="margin-left:6px;">
                                            <i class="fa-solid fa-crown" style="font-size:9px;"></i> Terbaik
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tier-price">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    @if($saving > 0)
                                        <span class="tier-saving">
                                            <i class="fa-solid fa-arrow-down"></i>
                                            {{ $savePct }}% (Rp {{ number_format($saving, 0, ',', '.') }})
                                        </span>
                                    @else
                                        <span style="color:var(--clr-text-muted);font-size:13px;">Harga Normal</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

{{-- ═══ REVIEW / ULASAN SECTION ═══ --}}
<section class="reviews-section" id="reviews-section">
    <div class="reviews-inner">
        <div class="reviews-header">
            <h2 class="reviews-title">
                <i class="fa-solid fa-star" style="color:#f59e0b;"></i>
                Ulasan Pembeli
            </h2>

            @if($avgRating)
                <div class="reviews-avg">
                    <div class="reviews-avg-num">{{ $avgRating }}</div>
                    <div>
                        <div class="reviews-avg-stars">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($avgRating))
                                    <i class="fa-solid fa-star star-icon filled"></i>
                                @elseif($i - 0.5 <= $avgRating)
                                    <i class="fa-solid fa-star-half-stroke star-icon filled"></i>
                                @else
                                    <i class="fa-regular fa-star star-icon"></i>
                                @endif
                            @endfor
                        </div>
                        <div class="reviews-avg-count">{{ $totalReviewsCount ?? $reviews->count() }} ulasan</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Review List --}}
        @if($reviews->isEmpty())
            <div class="reviews-empty">
                <div><i class="fa-regular fa-comment-dots"></i></div>
                <p>Belum ada ulasan untuk produk ini.<br>Jadilah yang pertama memberikan ulasan!</p>
            </div>
        @else
            <div class="reviews-list" id="reviews-list">
                @foreach($reviews as $review)
                    <div class="review-card">
                        <div class="review-card-top">
                            <div>
                                <span class="review-author">{{ $review->reviewer_name }}</span>
                                @if($review->order_code)
                                    <span class="review-verified">
                                        <i class="fa-solid fa-circle-check"></i> Pembeli Terverifikasi
                                    </span>
                                @endif
                                <div class="review-stars" style="margin-top:4px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star review-star {{ $i <= $review->rating ? 'filled' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="review-date">{{ $review->created_at->diffForHumans() }}</div>
                        </div>
                        @if($review->comment)
                            <div class="review-comment">{{ $review->comment }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Write Review Form --}}
        <div class="review-form-card" id="review-form-card">
            <div class="review-form-title">
                <i class="fa-solid fa-pencil" style="color:var(--clr-gold);"></i>
                Tulis Ulasan
            </div>

            <div id="review-success-msg" class="review-success-msg" style="display:none;">
                <i class="fa-solid fa-circle-check"></i>
                <span>Terima kasih! Ulasan Anda sedang menunggu persetujuan dan akan tampil segera.</span>
            </div>

            <form id="review-form" style="display:block;">
                <div class="review-form-group">
                    <label class="review-form-label">Rating <span style="color:var(--clr-danger);">*</span></label>
                    <div class="star-rating-input" id="star-rating-input">
                        @for($s = 1; $s <= 5; $s++)
                            <button type="button" class="star-btn" data-val="{{ $s }}" onclick="setRating({{ $s }})">
                                &#9733;
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" id="review-rating" value="0">
                </div>
                <div class="review-form-group">
                    <label class="review-form-label">Nama Anda <span style="color:var(--clr-danger);">*</span></label>
                    <input type="text" class="review-form-input" id="review-name" placeholder="Nama atau inisial Anda..." maxlength="60">
                </div>
                <div class="review-form-group">
                    <label class="review-form-label">Kode Pesanan <span style="color:var(--clr-text-muted);font-weight:400;">(opsional, untuk verifikasi pembeli)</span></label>
                    <input type="text" class="review-form-input" id="review-order-code" placeholder="Contoh: PKM-20240801-ABC" maxlength="40" style="text-transform:uppercase;">
                    <div class="review-code-help">Kode pesanan bisa ditemukan di email konfirmasi atau halaman <a href="{{ route('shop.track') }}" style="color:var(--clr-primary);">Lacak Pesanan</a>.</div>
                </div>
                <div class="review-form-group">
                    <label class="review-form-label">Komentar <span style="color:var(--clr-text-muted);font-weight:400;">(opsional)</span></label>
                    <textarea class="review-form-textarea" id="review-comment" placeholder="Bagaimana pengalaman Anda dengan produk ini?" maxlength="600"></textarea>
                </div>
                <div id="review-error" style="font-size:12px;color:#dc2626;margin-bottom:10px;display:none;"></div>
                <button type="submit" class="btn-submit-review" id="review-submit-btn">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Ulasan
                </button>
            </form>
        </div>
    </div>
</section>

{{-- ═══ Related Products ═══ --}}
@if($relatedProducts->isNotEmpty())
    <section class="related-section" id="related-products-section">
        <div class="related-inner">
            <div class="related-header">
                <h2 class="section-title">Produk Sejenis</h2>
                <p class="section-subtitle">Temukan lebih banyak pilihan kurma dari kategori yang sama.</p>
            </div>
            <div class="related-grid">
                @foreach($relatedProducts as $related)
                    @php
                        $rStock    = $related->display_stock ?? 0;
                        $rHasTiers = !empty($related->price_tiers) && count($related->price_tiers) > 1;
                        $rWaText   = urlencode("Halo, saya ingin memesan *{$related->name}* (Rp " . number_format($related->selling_price,0,',','.') . "/{$related->price_unit}). Apakah stok tersedia?");
                    @endphp
                    <div class="product-card" id="related-product-{{ $related->id }}">
                        <div class="card-img-wrap">
                            @if($related->image_path)
                                <img src="{{ asset('storage/' . $related->image_path) }}" alt="{{ $related->name }}" class="card-img" loading="lazy" onerror="this.style.display='none'; this.parentElement.querySelector('.card-img-placeholder').style.display='flex';">
                            @endif
                            <div class="card-img-placeholder" style="{{ $related->image_path ? 'display: none;' : '' }}"><i class="fa-solid fa-seedling"></i></div>
                            @if($rHasTiers)
                                <div class="card-badges"><span class="badge badge-wholesale"><i class="fa-solid fa-tag" style="font-size:9px;"></i> Grosir</span></div>
                            @endif
                            @if($related->category)
                                <span class="badge-category">{{ $related->category }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="card-name">{{ $related->name }}</div>
                            <div class="card-price-row">
                                <span class="card-price">Rp {{ number_format($related->selling_price, 0, ',', '.') }}</span>
                                <span class="card-price-unit">/ {{ $related->price_unit }}</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('shop.product.show', $related) }}"
                               class="btn-detail"
                               style="background: var(--clr-surface-2); color: var(--clr-text); border: 1px solid var(--clr-border);"
                               id="related-detail-btn-{{ $related->id }}">
                                <i class="fa-solid fa-eye"></i> Detail
                            </a>
                            <button @click="addToCart({
                                        id: {{ $related->id }},
                                        sku: '{{ $related->sku }}',
                                        name: '{{ addslashes($related->name) }}',
                                        selling_price: {{ $related->selling_price }},
                                        price_unit: '{{ $related->price_unit }}',
                                        image_path: '{{ $related->image_path }}',
                                        weight_grams: {{ $related->weight_grams ?? 500 }},
                                        price_tiers: {{ json_encode($related->price_tiers) }}
                                    })"
                                    class="btn-detail"
                                    id="related-add-cart-btn-{{ $related->id }}">
                                <i class="fa-solid fa-cart-plus"></i> + Keranjang
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection

@push('scripts')
<script>
/* ─── Star Rating Input ─── */
let selectedRating = 0;

function setRating(val) {
    selectedRating = val;
    document.getElementById('review-rating').value = val;
    document.querySelectorAll('#star-rating-input .star-btn').forEach((btn, i) => {
        btn.classList.toggle('active', i < val);
    });
}

// Hover preview
document.querySelectorAll('#star-rating-input .star-btn').forEach((btn, i) => {
    btn.addEventListener('mouseenter', () => {
        document.querySelectorAll('#star-rating-input .star-btn').forEach((b, j) => {
            b.classList.toggle('active', j <= i);
        });
    });
    btn.addEventListener('mouseleave', () => {
        document.querySelectorAll('#star-rating-input .star-btn').forEach((b, j) => {
            b.classList.toggle('active', j < selectedRating);
        });
    });
});

/* ─── Review Form Submit ─── */
document.getElementById('review-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const rating    = parseInt(document.getElementById('review-rating').value);
    const name      = document.getElementById('review-name').value.trim();
    const orderCode = document.getElementById('review-order-code').value.trim().toUpperCase();
    const comment   = document.getElementById('review-comment').value.trim();
    const errorEl   = document.getElementById('review-error');
    const submitBtn = document.getElementById('review-submit-btn');

    errorEl.style.display = 'none';

    if (rating < 1) {
        errorEl.textContent = 'Silakan pilih rating bintang (1–5).';
        errorEl.style.display = 'block';
        return;
    }
    if (!name) {
        errorEl.textContent = 'Nama wajib diisi.';
        errorEl.style.display = 'block';
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

    try {
        const res = await fetch('{{ route("shop.review.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                product_id : {{ $product->id }},
                rating     : rating,
                reviewer_name: name,
                order_code : orderCode || null,
                comment    : comment || null,
            }),
        });

        const data = await res.json();

        if (res.ok && data.success) {
            document.getElementById('review-form').style.display = 'none';
            document.getElementById('review-success-msg').style.display = 'flex';
        } else {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'Terjadi kesalahan. Silakan coba lagi.');
            errorEl.textContent = msgs;
            errorEl.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Ulasan';
        }
    } catch (err) {
        errorEl.textContent = 'Gagal mengirim. Periksa koneksi internet Anda.';
        errorEl.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Ulasan';
    }
});
</script>
@endpush
