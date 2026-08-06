@extends('layouts.shop')

@section('title', 'Katalog Produk Kurma Premium')
@section('meta_description', 'Temukan koleksi kurma premium pilihan terbaik dari Pusat Kurma Cianjur. Berbagai jenis kurma berkualitas dengan harga grosir dan eceran.')

@push('styles')
<style>
    /* ═══ Hero Banner ═══ */
    .hero {
        background: linear-gradient(135deg, var(--clr-primary-dark) 0%, var(--clr-primary) 50%, #047857 100%);
        padding: 48px 20px 64px;
        position: relative;
        overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 600px 400px at 80% 50%, rgba(217,119,6,.18) 0%, transparent 70%),
            radial-gradient(ellipse 400px 300px at 10% 80%, rgba(5,150,105,.3) 0%, transparent 60%);
        pointer-events: none;
    }
    .hero-grid {
        max-width: 1280px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 40px;
        position: relative;
        z-index: 1;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(251,191,36,.15);
        border: 1px solid rgba(251,191,36,.35);
        color: var(--clr-gold-light);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .8px;
        text-transform: uppercase;
        padding: 6px 14px;
        border-radius: 99px;
        margin-bottom: 20px;
    }
    .hero h1 {
        font-family: var(--font-heading);
        font-size: clamp(28px, 4vw, 52px);
        color: #fff;
        line-height: 1.15;
        margin-bottom: 16px;
    }
    .hero h1 .highlight {
        background: linear-gradient(90deg, var(--clr-gold-light), #fde68a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .hero p {
        font-size: 16px;
        color: rgba(255,255,255,.75);
        max-width: 480px;
        line-height: 1.7;
        margin-bottom: 32px;
    }
    .hero-cta-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-hero-primary {
        padding: 13px 28px;
        border-radius: 99px;
        background: linear-gradient(135deg, var(--clr-gold) 0%, var(--clr-gold-light) 100%);
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        border: none;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 8px 24px rgba(217,119,6,.4);
    }
    .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(217,119,6,.5); }
    .btn-hero-secondary {
        padding: 13px 28px;
        border-radius: 99px;
        background: rgba(255,255,255,.1);
        border: 1.5px solid rgba(255,255,255,.3);
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-hero-secondary:hover { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.5); }

    /* Stats row in hero */
    .hero-stats {
        display: flex;
        gap: 32px;
        margin-top: 40px;
        padding-top: 32px;
        border-top: 1px solid rgba(255,255,255,.15);
    }
    .hero-stat .num {
        font-size: 28px;
        font-weight: 800;
        color: #fff;
    }
    .hero-stat .lbl {
        font-size: 13px;
        color: rgba(255,255,255,.6);
        margin-top: 2px;
    }

    /* Decorative emoji on the right */
    .hero-deco {
        font-size: clamp(80px, 10vw, 160px);
        line-height: 1;
        filter: drop-shadow(0 20px 40px rgba(0,0,0,.3));
        animation: floatDeco 4s ease-in-out infinite;
        user-select: none;
    }
    @keyframes floatDeco {
        0%, 100% { transform: translateY(0px) rotate(-3deg); }
        50% { transform: translateY(-14px) rotate(3deg); }
    }

    /* ═══ Filter Bar ═══ */
    .filter-section {
        background: var(--clr-surface);
        border-bottom: 1px solid var(--clr-border);
        position: sticky;
        top: 60px; /* matches nav height */
        z-index: 500;
        box-shadow: 0 2px 12px rgba(0,0,0,.05);
    }
    .filter-inner {
        max-width: 1280px;
        margin: 0 auto;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .filter-inner::-webkit-scrollbar { display: none; }

    .filter-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--clr-text-muted);
        white-space: nowrap;
        flex-shrink: 0;
    }
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 16px;
        border-radius: 99px;
        font-size: 13px;
        font-weight: 500;
        border: 1.5px solid var(--clr-border);
        background: var(--clr-surface);
        color: var(--clr-text-muted);
        white-space: nowrap;
        transition: var(--transition);
        text-decoration: none;
        flex-shrink: 0;
        -webkit-tap-highlight-color: transparent;
    }
    .filter-chip:hover { border-color: var(--clr-primary-light); color: var(--clr-primary); }
    .filter-chip.active {
        background: var(--clr-primary);
        border-color: var(--clr-primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(6,95,70,.25);
    }
    .filter-divider {
        width: 1px;
        height: 24px;
        background: var(--clr-border);
        flex-shrink: 0;
    }
    .sort-select {
        padding: 6px 14px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        border: 1.5px solid var(--clr-border);
        background: var(--clr-surface);
        color: var(--clr-text);
        cursor: pointer;
        outline: none;
        transition: var(--transition);
        margin-left: auto;
        flex-shrink: 0;
    }
    .sort-select:focus { border-color: var(--clr-primary-light); }

    /* ═══ Featured Products ═══ */
    .featured-section {
        max-width: 1280px;
        margin: 0 auto;
        padding: 48px 16px 20px;
    }
    .featured-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        border-bottom: 1px solid var(--clr-border);
        padding-bottom: 14px;
    }
    .featured-title-wrap {
        display: flex;
        flex-direction: column;
    }
    .featured-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(217,119,6,.08);
        border: 1px solid rgba(217,119,6,.2);
        color: var(--clr-gold-dark);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 99px;
        width: fit-content;
        margin-bottom: 6px;
    }
    .featured-title {
        font-family: var(--font-heading);
        font-size: 24px;
        font-weight: 800;
        color: var(--clr-primary-dark);
    }
    .featured-card {
        border: 2px solid var(--clr-gold-light) !important;
        box-shadow: 0 10px 30px rgba(217,119,6,.08);
    }

    /* ═══ Products Grid ═══ */
    .products-section { padding: 36px 16px 56px; }
    .products-inner { max-width: 1280px; margin: 0 auto; }

    .products-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .products-count { font-size: 15px; color: var(--clr-text-muted); }
    .products-count strong { color: var(--clr-primary); }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    /* Product Card */
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
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: rgba(6,95,70,.2);
    }

    .card-img-wrap {
        position: relative;
        aspect-ratio: 1/1;
        overflow: hidden;
        background: var(--clr-surface-2);
    }
    .card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .5s ease;
    }
    .product-card:hover .card-img { transform: scale(1.06); }
    .card-img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: var(--clr-primary);
        gap: 8px;
    }
    .card-img-placeholder i { font-size: 40px; opacity: .7; }
    .card-img-placeholder span { font-size: 12px; font-weight: 600; opacity: .6; }

    /* Badges */
    .card-badges {
        position: absolute;
        top: 10px;
        left: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .4px;
    }
    .badge-wholesale { background: var(--clr-gold); color: #fff; }
    .badge-low-stock { background: #ef4444; color: #fff; }
    .badge-category {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(8px);
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
    }

    /* Card Body */
    .card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .card-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--clr-text);
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .card-unit { font-size: 12px; color: var(--clr-text-muted); margin-top: 1px; }

    .card-price-row { display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap; margin-top: 4px; }
    .card-price {
        font-size: 20px;
        font-weight: 800;
        color: var(--clr-primary);
        font-feature-settings: "tnum";
    }
    .card-price-unit { font-size: 12px; color: var(--clr-text-muted); font-weight: 400; }
    .card-wholesale-note {
        font-size: 11px;
        color: var(--clr-gold-dark);
        background: rgba(217,119,6,.08);
        border-radius: 6px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
        gap: 4px;
        width: fit-content;
    }

    /* ═══ Price Breakdown Pills ═══ */
    .price-breakdown {
        background: var(--clr-surface-2);
        border-radius: 10px;
        padding: 10px 12px;
        margin-top: 4px;
        border: 1px solid var(--clr-border);
    }
    .price-breakdown-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .7px;
        text-transform: uppercase;
        color: var(--clr-text-muted);
        margin-bottom: 7px;
    }
    .price-pills {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .price-pill {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 6px 8px;
        border-radius: 8px;
        background: var(--clr-surface);
        border: 1.5px solid var(--clr-border);
        cursor: pointer;
        transition: var(--transition);
        flex: 1;
        min-width: 60px;
        text-align: center;
    }
    .price-pill:hover,
    .price-pill.active {
        border-color: var(--clr-primary);
        background: rgba(6,95,70,.06);
    }
    .price-pill .pill-qty {
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-text);
        line-height: 1.2;
    }
    .price-pill .pill-price {
        font-size: 12px;
        font-weight: 800;
        color: var(--clr-primary);
        font-feature-settings: "tnum";
        margin-top: 2px;
    }
    .price-pill .pill-save {
        font-size: 9px;
        color: var(--clr-success);
        font-weight: 700;
        margin-top: 1px;
    }
    /* Chip info untuk pack/pcs */
    .unit-info-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
        background: rgba(6,95,70,.07);
        color: var(--clr-primary-dark);
        border: 1px solid rgba(6,95,70,.15);
    }

    .card-stock {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 500;
        margin-top: auto;
    }
    .stock-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .stock-dot.available { background: var(--clr-success); }
    .stock-dot.low       { background: var(--clr-gold); }
    .stock-dot.empty     { background: var(--clr-danger); }
    .stock-text.available { color: var(--clr-success); }
    .stock-text.low       { color: var(--clr-gold-dark); }
    .stock-text.empty     { color: var(--clr-danger); }

    /* Card Footer */
    .card-footer { padding: 0 18px 18px; display: flex; gap: 8px; }
    .btn-detail {
        flex: 1;
        padding: 10px;
        border-radius: var(--radius-sm);
        background: var(--clr-primary);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        border: none;
        text-align: center;
        transition: var(--transition);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .btn-detail:hover { background: var(--clr-primary-light); box-shadow: 0 4px 14px rgba(6,95,70,.35); }
    .btn-wa {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        background: #25d366;
        color: #fff;
        font-size: 18px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        text-decoration: none;
        flex-shrink: 0;
    }
    .btn-wa:hover { background: #128c7e; transform: scale(1.08); }

    /* ═══ Empty State ═══ */
    .empty-state {
        text-align: center;
        padding: 80px 24px;
        color: var(--clr-text-muted);
    }
    .empty-state i { font-size: 64px; margin-bottom: 20px; color: var(--clr-border); }
    .empty-state h3 { font-size: 22px; font-weight: 700; color: var(--clr-text); margin-bottom: 8px; }
    .empty-state p { font-size: 15px; max-width: 360px; margin: 0 auto 24px; }

    /* ═══ Pagination ═══ */
    .pagination-wrap {
        display: flex;
        justify-content: center;
        margin-top: 48px;
        gap: 6px;
        flex-wrap: wrap;
    }
    .pagination-wrap .page-link,
    .pagination-wrap .page-item.disabled .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        border: 1.5px solid var(--clr-border);
        color: var(--clr-text);
        background: var(--clr-surface);
        transition: var(--transition);
        text-decoration: none;
    }
    .pagination-wrap .page-link:hover { border-color: var(--clr-primary); color: var(--clr-primary); }
    .pagination-wrap .page-item.active .page-link {
        background: var(--clr-primary);
        border-color: var(--clr-primary);
        color: #fff;
    }
    .pagination-wrap .page-item.disabled .page-link { opacity: .4; pointer-events: none; }

    /* ─── Responsive (Mobile / Android) ─── */
    @media (max-width: 640px) {
        /* Hero */
        .hero { padding: 36px 16px 48px; }
        .hero-grid { grid-template-columns: 1fr; gap: 0; }
        .hero-deco { display: none; }
        .hero h1 { font-size: clamp(22px, 6vw, 32px); margin-bottom: 10px; }
        .hero p { font-size: 14px; margin-bottom: 20px; }
        .hero-badge { font-size: 11px; padding: 5px 12px; margin-bottom: 14px; }
        .hero-cta-group { gap: 8px; }
        .btn-hero-primary, .btn-hero-secondary { padding: 11px 18px; font-size: 14px; }
        .hero-stats { gap: 16px; margin-top: 24px; padding-top: 20px; flex-wrap: wrap; }
        .hero-stat .num { font-size: 22px; }
        .hero-stat .lbl { font-size: 12px; }

        /* Filter bar */
        .filter-section { top: 92px; } /* nav (56px) + mobile search (36px) */
        .filter-inner { padding: 8px 12px; gap: 6px; }
        .filter-label { font-size: 12px; }
        .filter-chip { padding: 6px 12px; font-size: 12px; }
        .sort-select { font-size: 12px; padding: 6px 10px; }

        /* Products */
        .products-section { padding: 20px 12px 48px; }
        .products-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .card-body { padding: 10px; gap: 6px; }
        .card-footer { padding: 0 10px 10px; gap: 6px; }
        .card-name { font-size: 13px; }
        .card-price { font-size: 15px; }
        .card-unit { font-size: 11px; }
        .price-breakdown { padding: 8px 9px; }
        .price-pill { padding: 5px 5px; min-width: 48px; }
        .price-pill .pill-qty { font-size: 10px; }
        .price-pill .pill-price { font-size: 11px; }
        .btn-detail { font-size: 12px; padding: 8px 6px; }
        .btn-wa { width: 36px; height: 36px; font-size: 15px; }
        .badge { font-size: 9px; padding: 3px 7px; }

        /* Pagination */
        .pagination-wrap { gap: 4px; }
        .pagination-wrap .page-link,
        .pagination-wrap .page-item.disabled .page-link {
            min-width: 34px;
            height: 34px;
            font-size: 13px;
        }

        /* Featured section */
        .featured-section { padding: 32px 12px 12px; }
        .featured-title { font-size: 20px; }
    }
    @media (max-width: 400px) {
        .products-grid { gap: 8px; }
        .card-body { padding: 8px; }
        .card-name { font-size: 12px; }
        .card-price { font-size: 14px; }
        .btn-detail { font-size: 11px; padding: 7px 4px; }
    }

    /* ═══ Banner Promo Slider ═══ */
    .promo-slider-section {
        background: var(--clr-surface-2);
        padding: 28px 0 0;
        overflow: hidden;
    }
    .promo-slider-inner {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 16px;
    }
    .promo-slider-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .promo-slider-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--clr-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .promo-slider-dots {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    .promo-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: var(--clr-border);
        transition: all .3s;
        cursor: pointer;
        border: none;
        padding: 0;
    }
    .promo-dot.active {
        width: 20px;
        border-radius: 4px;
        background: var(--clr-primary);
    }
    .promo-track-wrap {
        overflow: hidden;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }
    .promo-track {
        display: flex;
        transition: transform .5s cubic-bezier(.4,0,.2,1);
    }
    .promo-slide {
        min-width: 100%;
        position: relative;
        aspect-ratio: 16/5;
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex;
        align-items: center;
    }
    @media (max-width: 640px) {
        .promo-slide { aspect-ratio: 16/7; }
    }
    .promo-slide-bg {
        position: absolute;
        inset: 0;
    }
    .promo-slide-img {
        position: absolute;
        inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: .35;
    }
    .promo-slide-content {
        position: relative;
        z-index: 2;
        padding: 32px 48px;
        color: #fff;
    }
    @media (max-width: 640px) {
        .promo-slide-content { padding: 20px 20px; }
    }
    .promo-slide-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 99px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 10px;
    }
    .promo-slide-title {
        font-family: var(--font-heading);
        font-size: clamp(22px, 3vw, 38px);
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 8px;
        text-shadow: 0 2px 10px rgba(0,0,0,.2);
    }
    .promo-slide-subtitle {
        font-size: clamp(13px, 1.5vw, 16px);
        opacity: .85;
        margin-bottom: 18px;
        line-height: 1.5;
        max-width: 480px;
    }
    .promo-slide-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 22px;
        border-radius: 99px;
        background: #fff;
        font-size: 13px;
        font-weight: 700;
        border: none;
        transition: var(--transition);
        text-decoration: none;
    }
    .promo-slide-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.2); }
    .promo-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,.25);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.3);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        z-index: 10;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .promo-nav-btn:hover { background: rgba(255,255,255,.45); }
    .promo-nav-prev { left: 12px; }
    .promo-nav-next { right: 12px; }
</style>
@endpush

@section('content')

{{-- ══════════════════ HERO ══════════════════ --}}
<section class="hero" id="shop-hero">
    <div class="hero-grid">
        <div>
            <div class="hero-badge">
                <i class="fa-solid fa-star" style="font-size:10px;"></i>
                {{ $shop_settings['shop_hero_badge'] ?? 'Kurma Premium Berkualitas Tinggi' }}
            </div>
            <h1>
                {!! $shop_settings['shop_hero_title'] ?? 'Temukan <span class="highlight">Kurma Terbaik</span><br>Langsung dari Sumbernya' !!}
            </h1>
            <p>
                {{ $shop_settings['shop_hero_desc'] ?? 'Pilihan kurma premium pilihan untuk keluarga Anda — dari Madinah, Irak, hingga Tunisia. Kualitas terjamin, harga transparan, pengiriman ke seluruh Indonesia.' }}
            </p>
            <div class="hero-cta-group">
                <a href="#products-grid" class="btn-hero-primary" id="hero-browse-btn">
                    <i class="fa-solid fa-basket-shopping"></i> Lihat Katalog
                </a>
                <a href="https://wa.me/{{ $shop_settings['shop_whatsapp'] ?? '6281234567890' }}?text=Halo%2C%20saya%20ingin%20memesan%20kurma%20dari%20{{ urlencode($shop_settings['shop_name'] ?? 'Pusat Kurma') }}"
                   target="_blank" rel="noopener"
                   class="btn-hero-secondary"
                   id="hero-wa-btn">
                    <i class="fa-brands fa-whatsapp"></i> Pesan via WhatsApp
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="num">{{ $products->total() }}+</div>
                    <div class="lbl">Produk Tersedia</div>
                </div>
                <div class="hero-stat">
                    <div class="num">{{ $categories->count() }}+</div>
                    <div class="lbl">Jenis Kurma</div>
                </div>
                <div class="hero-stat">
                    <div class="num">100%</div>
                    <div class="lbl">Kualitas Terjamin</div>
                </div>
            </div>
        </div>
        <div class="hero-deco" aria-hidden="true">🌴</div>
    </div>
</section>

@if(isset($banners) && $banners->isNotEmpty() && !request('search') && !request('category'))
{{-- ══════════════════ BANNER PROMO SLIDER ══════════════════ --}}
<section class="promo-slider-section" id="promo-slider-section">
    <div class="promo-slider-inner">
        <div class="promo-slider-header">
            <div class="promo-slider-title">
                <i class="fa-solid fa-bullhorn"></i> Promo Spesial
            </div>
            @if($banners->count() > 1)
            <div class="promo-slider-dots" id="promo-dots">
                @foreach($banners as $i => $b)
                    <button class="promo-dot {{ $i === 0 ? 'active' : '' }}"
                            onclick="gotoSlide({{ $i }})" aria-label="Slide {{ $i+1 }}"></button>
                @endforeach
            </div>
            @endif
        </div>

        <div class="promo-track-wrap" style="position:relative;">
            <div class="promo-track" id="promo-track">
                @foreach($banners as $banner)
                <div class="promo-slide">
                    {{-- Background Gradient --}}
                    <div class="promo-slide-bg" style="background: linear-gradient(135deg, {{ $banner->bg_from ?? '#065f46' }} 0%, {{ $banner->bg_to ?? '#047857' }} 100%);"></div>

                    {{-- Background Image (if any) --}}
                    @if($banner->image_path)
                        <img src="/storage/{{ $banner->image_path }}"
                             alt="{{ $banner->title }}"
                             class="promo-slide-img"
                             loading="lazy">
                    @endif

                    {{-- Content --}}
                    <div class="promo-slide-content">
                        @if($banner->badge_text)
                            <div class="promo-slide-badge">
                                <i class="fa-solid fa-tag"></i>
                                {{ $banner->badge_text }}
                            </div>
                        @endif
                        <div class="promo-slide-title">{!! nl2br(e($banner->title)) !!}</div>
                        @if($banner->subtitle)
                            <div class="promo-slide-subtitle">{{ $banner->subtitle }}</div>
                        @endif
                        @if($banner->button_text && $banner->button_url)
                            <a href="{{ $banner->button_url }}"
                               class="promo-slide-btn"
                               style="color:{{ $banner->bg_from ?? '#065f46' }};">
                                {{ $banner->button_text }}
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            @if($banners->count() > 1)
            <button class="promo-nav-btn promo-nav-prev" onclick="prevSlide()" aria-label="Slide sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button class="promo-nav-btn promo-nav-next" onclick="nextSlide()" aria-label="Slide berikutnya">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            @endif
        </div>
    </div>
</section>
@endif

@if(isset($featuredProducts) && $featuredProducts->isNotEmpty() && !request('search') && !request('category'))
    {{-- ══════════════════ PRODUK UNGGULAN ══════════════════ --}}
    <section class="featured-section">
        <div class="featured-header">
            <div class="featured-title-wrap">
                <div class="featured-badge">
                    <i class="fa-solid fa-star"></i> Rekomendasi Terbaik
                </div>
                <h2 class="featured-title">Pilihan Kurma Unggulan</h2>
            </div>
        </div>
        <div class="products-grid">
            @foreach($featuredProducts as $product)
                @include('shop.partials.product_card', ['product' => $product, 'isFeatured' => true])
            @endforeach
        </div>
    </section>
@endif

{{-- ══════════════════ FILTER BAR ══════════════════ --}}
<div class="filter-section" id="shop-filter-bar">
    <form action="{{ route('shop.index') }}" method="GET" id="filter-form">
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
        <input type="hidden" name="sort" value="{{ $sort }}" id="sort-hidden">

        <div class="filter-inner">
            <span class="filter-label"><i class="fa-solid fa-filter" style="margin-right:4px;"></i>Filter:</span>

            <a href="{{ route('shop.index', array_merge(request()->except('category', 'page'), ['sort' => $sort])) }}"
               class="filter-chip {{ !$category ? 'active' : '' }}"
               id="filter-all">
                Semua
            </a>

            @foreach($categories as $cat)
                <a href="{{ route('shop.index', array_merge(request()->except('category', 'page'), ['category' => $cat, 'sort' => $sort])) }}"
                   class="filter-chip {{ $category === $cat ? 'active' : '' }}"
                   id="filter-cat-{{ Str::slug($cat) }}">
                    {{ $cat }}
                </a>
            @endforeach

            <div class="filter-divider"></div>

            <select class="sort-select" id="sort-select" onchange="applySort(this.value)" title="Urutkan">
                <option value="name_asc"   {{ $sort === 'name_asc'   ? 'selected' : '' }}>Nama A–Z</option>
                <option value="name_desc"  {{ $sort === 'name_desc'  ? 'selected' : '' }}>Nama Z–A</option>
                <option value="price_asc"  {{ $sort === 'price_asc'  ? 'selected' : '' }}>Harga Terendah</option>
                <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Harga Tertinggi</option>
            </select>
        </div>
    </form>
</div>

{{-- ══════════════════ PRODUCTS ══════════════════ --}}
<section class="products-section" id="products-grid">
    <div class="products-inner">
        <div class="products-header">
            <div>
                @if($search || $category)
                    <h2 style="font-size:22px;font-weight:700;margin-bottom:4px;">
                        Hasil pencarian
                        @if($search) untuk "<strong>{{ $search }}</strong>" @endif
                        @if($category) dalam kategori "<strong>{{ $category }}</strong>" @endif
                    </h2>
                @else
                    <h2 style="font-size:22px;font-weight:700;margin-bottom:4px;">Semua Produk</h2>
                @endif
                <p class="products-count">
                    Menampilkan <strong>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</strong>
                    dari <strong>{{ $products->total() }}</strong> produk
                </p>
            </div>
            @if($search || $category)
                <a href="{{ route('shop.index') }}" class="filter-chip" style="margin-left:auto;" id="clear-filter-btn">
                    <i class="fa-solid fa-xmark"></i> Hapus Filter
                </a>
            @endif
        </div>

        @if($products->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>Produk tidak ditemukan</h3>
                <p>Coba ubah kata kunci pencarian atau hapus filter yang aktif.</p>
                <a href="{{ route('shop.index') }}" class="btn-detail" style="max-width:200px;margin:0 auto;">
                    Lihat Semua Produk
                </a>
            </div>
        @else
            <div class="products-grid">
                @foreach($products as $product)
                    @include('shop.partials.product_card', ['product' => $product])
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
                <div class="pagination-wrap">
                    {{-- Previous --}}
                    @if($products->onFirstPage())
                        <span class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="page-link" id="pagination-prev"><i class="fa-solid fa-chevron-left"></i></a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($products->getUrlRange(max(1,$products->currentPage()-2), min($products->lastPage(),$products->currentPage()+2)) as $page => $url)
                        @if($page == $products->currentPage())
                            <span class="page-item active"><span class="page-link">{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}" class="page-link" id="pagination-page-{{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="page-link" id="pagination-next"><i class="fa-solid fa-chevron-right"></i></a>
                    @else
                        <span class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></span>
                    @endif
                </div>
            @endif
        @endif
    </div>
</section>

@endsection

@push('scripts')
<script>
function applySort(value) {
    const params = new URLSearchParams(window.location.search);
    params.set('sort', value);
    params.delete('page');
    window.location.href = '{{ route("shop.index") }}?' + params.toString();
}

// Smooth scroll to products when clicking hero CTA
document.getElementById('hero-browse-btn')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('products-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

// Auto-scroll to products grid if search or category is active in URL query parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search') || urlParams.has('category')) {
        const grid = document.getElementById('products-grid');
        if (grid) {
            const yOffset = -90;
            const y = grid.getBoundingClientRect().top + window.pageYOffset + yOffset;
            window.scrollTo({ top: y, behavior: 'auto' });
        }
    }
});

/**
 * Simpan pilihan porsi (gram) aktif per produk.
 * Key: product_id, Value: { qty, price }
 */
const selectedPortions = {};

/**
 * Dipanggil saat user klik salah satu pill porsi (gram).
 */
function selectPill(el, productId, qty, label, price) {
    // Hapus kelas active dari semua pill di produk ini
    const card = document.getElementById('product-card-' + productId);
    if (!card) return;
    card.querySelectorAll('.price-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');

    // Simpan pilihan
    selectedPortions[productId] = { qty, price, label };
}

/**
 * Tambah ke keranjang dari kartu katalog.
 * Untuk produk gram, gunakan pilihan pill aktif; untuk lainnya qty=1.
 */
function addToCartFromCard(productId, product) {
    let qty = 1;

    if (product.price_unit === 'gram') {
        // Ambil dari selectedPortions, default 500g jika belum pilih
        const sel = selectedPortions[productId];
        qty = sel ? sel.qty : 500;
    } else if (product.price_unit === 'kg') {
        qty = 1;
    }

    // Panggil fungsi addToCart global dari layout shop
    if (typeof addToCart === 'function') {
        addToCart(product, qty);
    } else {
        console.warn('addToCart function not found');
    }
}

/* ─── Promo Banner Slider ─── */
(function () {
    const track   = document.getElementById('promo-track');
    const dotsWrap = document.getElementById('promo-dots');
    if (!track) return;

    const slides = track.querySelectorAll('.promo-slide');
    const total  = slides.length;
    if (total <= 1) return;

    let current = 0;
    let autoTimer = null;

    function updateSlider() {
        track.style.transform = `translateX(-${current * 100}%)`;
        dotsWrap?.querySelectorAll('.promo-dot').forEach((d, i) => {
            d.classList.toggle('active', i === current);
        });
    }

    window.gotoSlide = function(index) {
        current = index;
        updateSlider();
        resetAuto();
    };

    window.nextSlide = function() {
        current = (current + 1) % total;
        updateSlider();
        resetAuto();
    };

    window.prevSlide = function() {
        current = (current - 1 + total) % total;
        updateSlider();
        resetAuto();
    };

    function resetAuto() {
        clearInterval(autoTimer);
        autoTimer = setInterval(() => { current = (current + 1) % total; updateSlider(); }, 5000);
    }

    // Start auto-slide
    autoTimer = setInterval(() => { current = (current + 1) % total; updateSlider(); }, 5000);

    // Touch / swipe support
    let touchStartX = 0;
    track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) diff > 0 ? nextSlide() : prevSlide();
    }, { passive: true });

    // Pause on hover
    track.closest('.promo-track-wrap')?.addEventListener('mouseenter', () => clearInterval(autoTimer));
    track.closest('.promo-track-wrap')?.addEventListener('mouseleave', resetAuto);
})();
</script>
@endpush
