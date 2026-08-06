<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Pusat Kurma Cianjur - Jual kurma premium berkualitas tinggi, langsung dari importir. Tersedia berbagai jenis kurma pilihan dengan harga terbaik.')">
    <meta name="keywords" content="kurma, kurma premium, kurma cianjur, beli kurma, kurma medjool, kurma ajwa">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif Pusat Kurma Cianjur</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* ═══════════════════════════════════════════════
           CSS Variables — Emerald Gold Theme
        ═══════════════════════════════════════════════ */
        :root {
            --clr-primary:       #065f46;   /* emerald-800 */
            --clr-primary-light: #059669;   /* emerald-600 */
            --clr-primary-dark:  #022c22;   /* emerald-950 */
            --clr-gold:          #d97706;   /* amber-600   */
            --clr-gold-light:    #fbbf24;   /* amber-400   */
            --clr-gold-dark:     #92400e;   /* amber-800   */
            --clr-bg:            #fdfaf5;   /* warm cream  */
            --clr-surface:       #ffffff;
            --clr-surface-2:     #f1f5f0;
            --clr-text:          #1a1a1a;
            --clr-text-muted:    #6b7280;
            --clr-border:        #d1d5db;
            --clr-success:       #10b981;
            --clr-danger:        #ef4444;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 30px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 20px rgba(0,0,0,.10);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.14);
            --shadow-xl: 0 24px 60px rgba(0,0,0,.18);
            --font-body:    'Outfit', sans-serif;
            --font-heading: 'Playfair Display', serif;
            --transition: all .25s cubic-bezier(.4,0,.2,1);
        }

        /* ─── Reset & Base ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-family: var(--font-body); background: var(--clr-bg); color: var(--clr-text); }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; display: block; }
        button { cursor: pointer; font-family: inherit; }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--clr-surface-2); }
        ::-webkit-scrollbar-thumb { background: var(--clr-primary-light); border-radius: 99px; }

        /* ═══════════════════════════════════════════════
           NAVIGATION
        ═══════════════════════════════════════════════ */
        .shop-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(6,95,70,.12);
            box-shadow: 0 2px 20px rgba(6,95,70,.06);
        }
        .nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 16px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .nav-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }
        .nav-logo-text {
            line-height: 1.1;
        }
        .nav-logo-text .brand { font-family: var(--font-heading); font-size: 15px; font-weight: 700; color: var(--clr-primary-dark); }
        .nav-logo-text .tagline { font-size: 10px; color: var(--clr-gold); font-weight: 500; letter-spacing: .5px; }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: 16px;
        }
        .nav-links a {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--clr-text-muted);
            transition: var(--transition);
        }
        .nav-links a:hover, .nav-links a.active {
            background: rgba(6,95,70,.08);
            color: var(--clr-primary);
        }

        .nav-search {
            flex: 1;
            max-width: 380px;
            margin-left: auto;
            min-width: 0;
        }
        .search-form {
            display: flex;
            align-items: center;
            background: var(--clr-surface-2);
            border: 1.5px solid var(--clr-border);
            border-radius: 99px;
            padding: 6px 6px 6px 16px;
            transition: var(--transition);
        }
        .search-form:focus-within {
            border-color: var(--clr-primary-light);
            box-shadow: 0 0 0 3px rgba(5,150,105,.12);
            background: #fff;
        }
        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 14px;
            color: var(--clr-text);
            outline: none;
        }
        .search-input::placeholder { color: var(--clr-text-muted); }
        .search-btn {
            width: 34px;
            height: 34px;
            border-radius: 99px;
            background: var(--clr-primary);
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: var(--transition);
            flex-shrink: 0;
        }
        .search-btn:hover { background: var(--clr-primary-light); transform: scale(1.05); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .nav-wa-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 99px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            text-decoration: none;
        }
        .nav-wa-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,211,102,.4);
        }

        /* Mobile nav toggle */
        .nav-mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            color: var(--clr-primary);
            padding: 4px;
            flex-shrink: 0;
        }

        /* Mobile search bar shown below nav */
        .nav-mobile-search {
            display: none;
            padding: 8px 16px 10px;
            background: rgba(255,255,255,.97);
            border-top: 1px solid rgba(6,95,70,.08);
        }

        /* ═══════════════════════════════════════════════
           MAIN CONTENT
        ═══════════════════════════════════════════════ */
        .shop-main { flex: 1; }

        /* ═══════════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════════ */
        .shop-footer {
            background: var(--clr-primary-dark);
            color: rgba(255,255,255,.85);
            padding: 60px 24px 0;
            margin-top: 80px;
        }
        .footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1fr;
            gap: 40px;
            padding-bottom: 48px;
        }
        .footer-brand .logo-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .footer-brand .logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--clr-gold-dark), var(--clr-gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
        }
        .footer-brand .brand-name {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        .footer-brand p { font-size: 14px; line-height: 1.7; color: rgba(255,255,255,.6); max-width: 260px; }

        .footer-social {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .footer-social a {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,.7);
            font-size: 15px;
            transition: var(--transition);
        }
        .footer-social a:hover { background: var(--clr-gold); color: #fff; transform: translateY(-2px); }

        .footer-col h4 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--clr-gold-light);
            margin-bottom: 16px;
        }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a {
            font-size: 14px;
            color: rgba(255,255,255,.6);
            transition: var(--transition);
        }
        .footer-col ul li a:hover { color: #fff; padding-left: 4px; }

        .footer-contact-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
            color: rgba(255,255,255,.65);
        }
        .footer-contact-item i { color: var(--clr-gold-light); margin-top: 2px; flex-shrink: 0; }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.1);
            padding: 20px 0;
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,.4);
        }

        /* ═══════════════════════════════════════════════
           UTILITIES
        ═══════════════════════════════════════════════ */
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        .section-title {
            font-family: var(--font-heading);
            font-size: 32px;
            color: var(--clr-primary-dark);
            margin-bottom: 8px;
        }
        .section-subtitle {
            font-size: 15px;
            color: var(--clr-text-muted);
            margin-bottom: 32px;
        }

        /* ─── Alert / Toast ─── */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #065f46; }
        .alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #991b1b; }

        /* ─── Responsive ─── */
        @media (max-width: 900px) {
            .footer-inner { grid-template-columns: 1fr 1fr; }
            .nav-links { display: none; }
        }
        @media (max-width: 640px) {
            .shop-nav {
                position: sticky;
                top: 0;
            }
            .nav-inner {
                height: 56px;
                padding: 0 12px;
                gap: 8px;
            }
            .nav-logo-icon {
                width: 36px;
                height: 36px;
                font-size: 15px;
            }
            .nav-logo-text .brand { font-size: 13px; }
            .nav-logo-text .tagline { display: none; }
            /* Hide main search in nav on mobile, show below */
            .nav-search { display: none; }
            .nav-mobile-search { display: block; }
            .nav-mobile-toggle { display: block; }
            /* WA button: icon only */
            .nav-wa-btn span { display: none; }
            .nav-wa-btn { padding: 8px 10px; border-radius: 12px; }
            /* Footer */
            .footer-inner { grid-template-columns: 1fr; gap: 28px; }
            .shop-footer { padding: 40px 16px 0; margin-top: 48px; }
        }
        @media (max-width: 400px) {
            .nav-inner { gap: 6px; }
            .nav-logo-icon { width: 32px; height: 32px; font-size: 13px; }
            .nav-logo-text .brand { font-size: 12px; }
        }

        /* ═══════════════════════════════════════════════
           CART DRAWER / OVERLAY
        ═══════════════════════════════════════════════ */
        .overflow-hidden {
            overflow: hidden;
        }
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .cart-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .cart-panel {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 420px;
            max-width: 100vw;
            background: #fff;
            box-shadow: var(--shadow-xl);
            z-index: 2001;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 640px) {
            .cart-panel {
                width: 100vw;
                border-radius: 0;
            }
        }
        .cart-panel.active {
            transform: translateX(0);
        }
        .cart-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--clr-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-title {
            font-family: var(--font-heading);
            font-size: 20px;
            font-weight: 700;
            color: var(--clr-primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-close-btn {
            background: none;
            border: none;
            font-size: 22px;
            color: var(--clr-text-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        .cart-close-btn:hover {
            color: var(--clr-danger);
            transform: rotate(90deg);
        }
        .cart-body-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            color: var(--clr-text-muted);
            text-align: center;
            gap: 16px;
        }
        .cart-body-empty i {
            font-size: 56px;
            color: var(--clr-border);
        }
        .cart-items-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cart-item {
            display: flex;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--clr-surface-2);
            align-items: center;
        }
        .cart-item-img {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--clr-surface-2);
            flex-shrink: 0;
            border: 1px solid var(--clr-border);
        }
        .cart-item-img-placeholder {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: var(--clr-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            border: 1px solid var(--clr-border);
        }
        .cart-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .cart-item-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--clr-text);
            line-height: 1.3;
        }
        .cart-item-price-unit {
            font-size: 12px;
            color: var(--clr-text-muted);
        }
        .cart-item-price {
            font-size: 14px;
            font-weight: 800;
            color: var(--clr-primary);
            margin-top: 2px;
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
        }
        .cart-qty-control {
            display: flex;
            align-items: center;
            border: 1px solid var(--clr-border);
            border-radius: 6px;
            overflow: hidden;
            background: var(--clr-surface-2);
        }
        .cart-qty-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: var(--clr-text-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        .cart-qty-btn:hover {
            background: var(--clr-border);
            color: var(--clr-text);
        }
        .cart-qty-val {
            width: 30px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            border: none;
            background: none;
            outline: none;
        }
        .cart-item-delete-btn {
            background: none;
            border: none;
            color: var(--clr-text-muted);
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
            padding: 4px;
        }
        .cart-item-delete-btn:hover {
            color: var(--clr-danger);
        }
        .cart-footer {
            padding: 24px;
            border-top: 1px solid var(--clr-border);
            background: var(--clr-surface-2);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-total-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--clr-text-muted);
        }
        .cart-total-val {
            font-size: 20px;
            font-weight: 800;
            color: var(--clr-primary-dark);
        }
        .cart-checkout-btn {
            width: 100%;
            padding: 13px 20px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37,211,102,.3);
            transition: var(--transition);
            text-decoration: none;
        }
        .cart-checkout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,211,102,.45);
        }

        /* ─── Guide Modal & Float Button ─── */
        .float-guide-btn {
            position: fixed;
            bottom: 24px;
            left: 24px;
            z-index: 999;
            background: linear-gradient(135deg, var(--clr-gold) 0%, var(--clr-gold-light) 100%);
            color: #fff;
            padding: 12px 22px;
            border-radius: 99px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 8px 24px rgba(217,119,6,.35);
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .float-guide-btn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 32px rgba(217,119,6,.5);
        }
        .guide-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2,44,34,.7);
            z-index: 2000;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .guide-modal {
            background: #fff;
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            border: 1px solid rgba(6,95,70,.12);
        }
        .guide-modal-header {
            background: linear-gradient(135deg, var(--clr-primary-dark), var(--clr-primary));
            padding: 20px 24px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .guide-modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-heading);
            font-size: 19px;
            font-weight: 700;
        }
        .guide-modal-title i {
            color: var(--clr-gold-light);
        }
        .guide-modal-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
            transition: var(--transition);
        }
        .guide-modal-close-btn:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        .guide-modal-body {
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .guide-step-card {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .guide-step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(6,95,70,.2);
        }
        .guide-step-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--clr-primary-dark);
            margin-bottom: 4px;
        }
        .guide-step-desc {
            font-size: 13.5px;
            color: var(--clr-text-muted);
            line-height: 1.5;
        }
        .guide-step-highlight {
            background: var(--clr-surface-2);
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            color: var(--clr-primary-dark);
            border: 1px solid var(--clr-border);
        }
        .guide-wa-card {
            background: rgba(37,211,102,.08);
            border: 1.5px dashed rgba(37,211,102,.35);
            border-radius: 14px;
            padding: 18px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            margin-top: 10px;
        }
        .guide-wa-card i.wa-main-icon {
            font-size: 26px;
            color: #25d366;
            margin-top: 2px;
        }
        .guide-wa-title {
            font-size: 14px;
            font-weight: 700;
            color: #128c7e;
            margin-bottom: 4px;
        }
        .guide-wa-desc {
            font-size: 12.5px;
            color: var(--clr-text-muted);
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .btn-guide-wa {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #25d366;
            color: #fff;
            padding: 8px 18px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37,211,102,.2);
            transition: var(--transition);
        }
        .btn-guide-wa:hover {
            background: #128c7e;
            transform: translateY(-1px);
        }
        .guide-modal-footer {
            background: var(--clr-surface-2);
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid var(--clr-border);
        }
        .btn-guide-close {
            background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(6,95,70,.15);
            transition: var(--transition);
        }
        .btn-guide-close:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(6,95,70,.25);
        }
        .nav-guide-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--clr-text-muted);
            transition: var(--transition);
        }
        .nav-guide-btn:hover {
            background: rgba(6,95,70,.08);
            color: var(--clr-primary);
        }
    </style>

    <!-- Alpine.js Cart Store (must be defined BEFORE Alpine initializes) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cartStore', () => ({
                items: [],
                isOpen: false,
                openGuide: false,
                whatsappNumber: '{{ $shop_settings["shop_whatsapp"] ?? "6281234567890" }}',

                init() {
                    const stored = localStorage.getItem('pk_cart');
                    if (stored) {
                        try {
                            const parsed = JSON.parse(stored);
                            const savedAt = localStorage.getItem('pk_cart_saved_at');
                            const expiry = 24 * 60 * 60 * 1000;
                            if (savedAt && (Date.now() - parseInt(savedAt)) > expiry) {
                                this.items = [];
                                localStorage.removeItem('pk_cart');
                                localStorage.removeItem('pk_cart_saved_at');
                            } else {
                                this.items = parsed;
                            }
                        } catch (e) {
                            this.items = [];
                        }
                    }
                    this.$watch('items', val => {
                        localStorage.setItem('pk_cart', JSON.stringify(val));
                        localStorage.setItem('pk_cart_saved_at', Date.now().toString());
                    });

                    // Expose global addToCart bridge
                    window.addToCart = (product, qty) => this.addToCart(product, qty);
                    window.__cartStore = this;
                },

                addToCart(product, qty = 1) {
                    qty = parseFloat(qty);
                    if (isNaN(qty) || qty <= 0) qty = 1;

                    const index = this.items.findIndex(item => item.id === product.id);
                    if (index > -1) {
                        this.items[index].qty += qty;
                    } else {
                        this.items.push({
                            id: product.id,
                            sku: product.sku,
                            name: product.name,
                            price_unit: product.price_unit,
                            selling_price: parseInt(product.selling_price),
                            image_path: product.image_path,
                            weight_grams: parseInt(product.weight_grams) || 500,
                            price_tiers: product.price_tiers || null,
                            qty: qty
                        });
                    }
                    this.isOpen = true;
                },

                updateQty(productId, newQty) {
                    const index = this.items.findIndex(item => item.id === productId);
                    if (index > -1) {
                        newQty = parseFloat(newQty);
                        if (isNaN(newQty) || newQty <= 0) {
                            this.removeItem(productId);
                        } else {
                            this.items[index].qty = newQty;
                        }
                    }
                },

                removeItem(productId) {
                    this.items = this.items.filter(item => item.id !== productId);
                },

                clearCart() {
                    this.items = [];
                },

                get totalItems() {
                    return this.items.reduce((total, item) => {
                        if (item.price_unit === 'gram') {
                            return total + 1;
                        }
                        return total + Math.ceil(item.qty);
                    }, 0);
                },

                getItemPrice(item) {
                    let price = item.selling_price;
                    if (item.price_tiers && Array.isArray(item.price_tiers)) {
                        for (const tier of item.price_tiers) {
                            const min = tier.min_qty !== '' ? parseFloat(tier.min_qty) : 0;
                            const max = (tier.max_qty !== undefined && tier.max_qty !== null && tier.max_qty !== '') ? parseFloat(tier.max_qty) : Infinity;
                            if (item.qty >= min && item.qty <= max) {
                                price = parseInt(tier.price);
                                break;
                            }
                        }
                    }
                    return price;
                },

                get subtotal() {
                    return this.items.reduce((total, item) => total + (this.getItemPrice(item) * item.qty), 0);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                },

                checkoutWhatsApp() {
                    if (this.items.length === 0) return;

                    let msg = "Halo Pusat Kurma Cianjur, saya ingin memesan produk berikut:\n\n";
                    this.items.forEach((item, index) => {
                        const price = this.getItemPrice(item);
                        const itemTotal = price * item.qty;
                        msg += `${index + 1}. *${item.name}* - ${item.qty} ${item.price_unit} (Rp ${this.formatNumber(price)}/${item.price_unit}) = *Rp ${this.formatNumber(itemTotal)}*\n`;
                    });

                    msg += `\n*Total Belanja:* *Rp ${this.formatNumber(this.subtotal)}*\n\n`;
                    msg += "Detail Penerima:\n";
                    msg += "- Nama: [Tulis Nama Anda]\n";
                    msg += "- Alamat: [Tulis Alamat Kirim Lengkap]\n";
                    msg += "- Catatan: [Tulis Catatan / Request khusus]\n\n";
                    msg += "Terima kasih.";

                    const encoded = encodeURIComponent(msg);
                    window.open(`https://wa.me/${this.whatsappNumber}?text=${encoded}`, '_blank');
                }
            }));
        });
    </script>

    <!-- Alpine.js (loaded AFTER cartStore is registered) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- DOKU Jokul Checkout SDK — tampilkan payment form sebagai popup di halaman kita --}}
    <script src="https://jokul.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js"></script>

    @stack('styles')
</head>
<body x-data="cartStore" :class="isOpen ? 'overflow-hidden' : ''">

    <!-- ═══════════════ NAVIGATION ═══════════════ -->
    <nav class="shop-nav" id="shop-navbar">
        <div class="nav-inner">
            <a href="{{ route('shop.index') }}" class="nav-logo" title="{{ $shop_settings['shop_name'] ?? 'Pusat Kurma' }}">
                <div class="nav-logo-icon"><i class="fa-solid fa-seedling"></i></div>
                <div class="nav-logo-text">
                    <div class="brand">{{ $shop_settings['shop_name'] ?? 'Pusat Kurma' }}</div>
                    <div class="tagline">{{ $shop_settings['shop_tagline'] ?? 'Cianjur ✦ Premium Dates' }}</div>
                </div>
            </a>

            <div class="nav-links">
                <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-store me-1"></i> Katalog
                </a>
                <a href="{{ route('shop.hampers') }}" class="{{ request()->routeIs('shop.hampers') ? 'active' : '' }}">
                    <i class="fa-solid fa-gift me-1"></i> Hampers
                </a>
                <a href="{{ route('shop.track') }}" class="{{ request()->routeIs('shop.track') ? 'active' : '' }}">
                    <i class="fa-solid fa-search-location me-1"></i> Lacak Pesanan
                </a>
                <a href="#" onclick="openWishlist(); return false;" class="nav-guide-btn" id="nav-wishlist-link">
                    <i class="fa-regular fa-heart"></i> Wishlist <span id="nav-wishlist-count" style="display:none;background:var(--clr-gold);color:#fff;font-size:9px;font-weight:800;min-width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;padding:2px;margin-left:2px;"></span>
                </a>
                <a href="#" @click.prevent="openGuide = true" class="nav-guide-btn" id="nav-guide-toggle">
                    <i class="fa-solid fa-circle-question"></i> Cara Belanja
                </a>
            </div>

            <!-- Search bar -->
            <div class="nav-search">
                <form action="{{ route('shop.index') }}" method="GET" class="search-form" id="nav-search-form">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    <input
                        type="text"
                        class="search-input"
                        name="search"
                        id="nav-search-input"
                        placeholder="Cari kurma favorit Anda..."
                        value="{{ request('search', '') }}"
                        autocomplete="off"
                    >
                    <button type="submit" class="search-btn" title="Cari">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>

            <div class="nav-actions">
                <!-- Cart Button -->
                <button @click="isOpen = true"
                        class="nav-cart-btn"
                        id="nav-cart-toggle-btn"
                        title="Keranjang Belanja"
                        style="position: relative; background: none; border: none; font-size: 20px; color: var(--clr-primary); padding: 8px 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); outline: none;">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="cart-badge"
                          x-show="totalItems > 0"
                          x-text="totalItems"
                          style="position: absolute; top: -1px; right: -1px; background: var(--clr-gold); color: #fff; font-size: 9px; font-weight: 800; min-width: 17px; height: 17px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 2px; box-shadow: 0 2px 6px rgba(217,119,6,.3);">
                    </span>
                </button>

                <a href="https://wa.me/{{ $shop_settings['shop_whatsapp'] ?? '6281234567890' }}?text=Halo%2C%20saya%20ingin%20memesan%20kurma%20dari%20{{ urlencode($shop_settings['shop_name'] ?? 'Pusat Kurma') }}"
                   target="_blank" rel="noopener"
                   class="nav-wa-btn"
                   id="nav-whatsapp-btn"
                   title="Chat via WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Search Bar (visible on small screens only) -->
    <div class="nav-mobile-search" id="mobile-search-bar">
        <form action="{{ route('shop.index') }}" method="GET" class="search-form" id="mobile-search-form">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input
                type="text"
                class="search-input"
                name="search"
                id="mobile-search-input"
                placeholder="Cari kurma favorit Anda..."
                value="{{ request('search', '') }}"
                autocomplete="off"
            >
            <button type="submit" class="search-btn" title="Cari">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
    </div>

    <!-- ═══════════════ FLASH MESSAGES ═══════════════ -->
    @if(session('success'))
        <div class="container" style="padding-top:16px;">
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container" style="padding-top:16px;">
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
    <main class="shop-main">
        @yield('content')
    </main>

    <!-- ═══════════════ FOOTER ═══════════════ -->
    <footer class="shop-footer">
        <div class="footer-inner">
            <!-- Brand -->
            <div class="footer-brand">
                <div class="logo-wrap">
                    <div class="logo-icon"><i class="fa-solid fa-seedling"></i></div>
                    <div class="brand-name">{{ $shop_settings['shop_name'] ?? 'Pusat Kurma' }}</div>
                </div>
                <p>{{ $shop_settings['shop_description'] ?? 'Distributor kurma premium terpercaya sejak 2010. Langsung dari importir, kualitas terjamin, harga bersaing untuk ritel dan grosir.' }}</p>
                <div class="footer-social">
                    <a href="{{ $shop_settings['shop_social_instagram'] ?? '#' }}" title="Instagram" id="footer-instagram-link"><i class="fa-brands fa-instagram"></i></a>
                    <a href="{{ $shop_settings['shop_social_facebook'] ?? '#' }}" title="Facebook" id="footer-facebook-link"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://wa.me/{{ $shop_settings['shop_whatsapp'] ?? '6281234567890' }}" target="_blank" rel="noopener" title="WhatsApp" id="footer-whatsapp-link"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="{{ $shop_settings['shop_social_tiktok'] ?? '#' }}" title="TikTok" id="footer-tiktok-link"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Produk -->
            <div class="footer-col">
                <h4>Produk</h4>
                <ul>
                    <li><a href="{{ route('shop.index', ['category' => 'Kurma']) }}">Kurma Pilihan</a></li>
                    <li><a href="{{ route('shop.index', ['category' => 'Premium']) }}">Kurma Premium</a></li>
                    <li><a href="{{ route('shop.index') }}">Lihat Semua</a></li>
                </ul>
            </div>

            <!-- Informasi -->
            <div class="footer-col">
                <h4>Informasi</h4>
                <ul>
                    <li><a href="{{ route('shop.about') }}">Tentang Kami</a></li>
                    <li><a href="{{ route('shop.terms') }}">Cara Pemesanan</a></li>
                    <li><a href="{{ route('shop.privacy') }}">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('shop.terms') }}">Syarat &amp; Ketentuan</a></li>
                    <li><a href="{{ route('shop.refund') }}">Kebijakan Pengembalian</a></li>
                </ul>
            </div>

            <!-- Kontak -->
            <div class="footer-col">
                <h4>Kontak</h4>
                <div class="footer-contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{!! nl2br(e($shop_settings['shop_address'] ?? "Jl. Contoh No. 123,\nCianjur, Jawa Barat 43200")) !!}</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>{{ $shop_settings['shop_phone'] ?? '+62 812-3456-7890' }}</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa-regular fa-clock"></i>
                    <span>{{ $shop_settings['shop_operational_hours'] ?? 'Senin–Sabtu: 08.00–17.00' }}</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
                &copy; {{ date('Y') }} {{ $shop_settings['shop_copyright'] ?? 'Pusat Kurma. Semua hak dilindungi.' }}
                &nbsp;·&nbsp;
                <a href="{{ url('/login') }}" style="color:rgba(255,255,255,.4);transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,.7)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                    Login Admin
                </a>
            </div>
        </div>
    </footer>

    <!-- ═══════════════ CART OVERLAY & PANEL (DRAWER) ═══════════════ -->
    <div class="cart-overlay" :class="isOpen ? 'active' : ''" @click="isOpen = false"></div>
    
    <div class="cart-panel" :class="isOpen ? 'active' : ''">
        <!-- Header -->
        <div class="cart-header">
            <div class="cart-title">
                <i class="fa-solid fa-basket-shopping"></i>
                Keranjang Belanja
            </div>
            <button class="cart-close-btn" @click="isOpen = false" title="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body: Empty State -->
        <div class="cart-body-empty" x-show="items.length === 0">
            <i class="fa-solid fa-cart-shopping"></i>
            <h3>Keranjang Kosong</h3>
            <p>Anda belum menambahkan kurma pilihan ke keranjang.</p>
            <button @click="isOpen = false" class="btn-detail" style="max-width:180px; margin-top: 10px;">
                Belanja Sekarang
            </button>
        </div>

        <!-- Body: Items List -->
        <div class="cart-items-list" x-show="items.length > 0">
            <template x-for="item in items" :key="item.id">
                <div class="cart-item">
                    <!-- Image -->
                    <template x-if="item.image_path">
                        <img :src="'/storage/' + item.image_path" :alt="item.name" class="cart-item-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    </template>
                    <div class="cart-item-img-placeholder" x-show="!item.image_path" style="display: none;">
                        <i class="fa-solid fa-seedling"></i>
                    </div>

                    <!-- Info -->
                    <div class="cart-item-info">
                        <div class="cart-item-name" x-text="item.name"></div>
                        <div class="cart-item-price-unit" x-text="'per ' + item.price_unit"></div>
                        <div class="cart-item-price" x-text="'Rp ' + formatNumber(getItemPrice(item))"></div>
                        
                        <!-- Actions -->
                        <div class="cart-item-actions">
                            <div class="cart-qty-control">
                                <button class="cart-qty-btn" @click="updateQty(item.id, item.qty - (item.price_unit === 'gram' ? 250 : 1))">-</button>
                                <input type="text" class="cart-qty-val" :value="item.qty" @change="updateQty(item.id, $event.target.value)">
                                <button class="cart-qty-btn" @click="updateQty(item.id, item.qty + (item.price_unit === 'gram' ? 250 : 1))">+</button>
                            </div>
                            <button class="cart-item-delete-btn" @click="removeItem(item.id)" title="Hapus">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="cart-footer" x-show="items.length > 0">
            <div class="cart-total-row">
                <span class="cart-total-label">Subtotal</span>
                <span class="cart-total-val" x-text="'Rp ' + formatNumber(subtotal)"></span>
            </div>
            <a href="{{ route('shop.checkout') }}" class="cart-checkout-btn" style="text-align: center; text-decoration: none; background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light)); box-shadow: 0 4px 14px rgba(6,95,70,.25);">
                <i class="fa-solid fa-credit-card"></i>
                Lanjut ke Pembayaran
            </a>
            <button class="cart-checkout-btn" @click="checkoutWhatsApp()" style="background: #25d366; box-shadow: 0 4px 14px rgba(37,211,102,.2); margin-top: -6px; font-size: 13px; padding: 10px 14px;">
                <i class="fa-brands fa-whatsapp" style="font-size:16px;"></i>
                Alternatif: Pesan via WhatsApp
            </button>
        </div>
    </div>

    <!-- ═══════════════ FLOATING GUIDE BUTTON ═══════════════ -->
    <button @click="openGuide = true" class="float-guide-btn" id="float-guide-btn" title="Panduan Belanja" style="bottom:90px;">
        <i class="fa-solid fa-circle-question" style="font-size:16px;"></i>
        <span>Panduan Belanja</span>
    </button>

    <!-- ═══════════════ FLOATING WHATSAPP BUTTON ═══════════════ -->
    <a href="https://wa.me/{{ $shop_settings['shop_whatsapp'] ?? '6281234567890' }}?text={{ urlencode('Halo Pusat Kurma, saya butuh bantuan') }}"
       target="_blank" rel="noopener"
       id="float-wa-btn"
       title="Chat WhatsApp"
       style="
           position:fixed; bottom:24px; right:24px; z-index:998;
           width:56px; height:56px; border-radius:50%;
           background:linear-gradient(135deg,#25d366,#128c7e);
           color:#fff; font-size:26px;
           display:flex; align-items:center; justify-content:center;
           box-shadow:0 6px 24px rgba(37,211,102,.45);
           transition:all .25s cubic-bezier(.4,0,.2,1);
           text-decoration:none;
       "
       onmouseover="this.style.transform='scale(1.12) translateY(-3px)'"
       onmouseout="this.style.transform=''"
    >
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- ═══════════════ GUIDE MODAL (CARA BELANJA) ═══════════════ -->
    <div class="guide-modal-backdrop" x-show="openGuide" x-transition.opacity @click="openGuide = false" style="display:none;">
        <div class="guide-modal" @click.stop x-show="openGuide" x-transition.scale>
            <div class="guide-modal-header">
                <div class="guide-modal-title">
                    <i class="fa-solid fa-circle-question"></i>
                    <span>Cara Pemesanan Kurma</span>
                </div>
                <button class="guide-modal-close-btn" @click="openGuide = false" title="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="guide-modal-body">
                <!-- Step 1 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">1</div>
                    <div>
                        <h4 class="guide-step-title">Pilih Kurma &amp; Masukkan Keranjang</h4>
                        <p class="guide-step-desc">
                            Lihat katalog kami, tentukan jumlah/porsi kurma yang Anda inginkan, lalu klik tombol 
                            <span class="guide-step-highlight"><i class="fa-solid fa-cart-plus"></i> + Keranjang</span>.
                        </p>
                    </div>
                </div>
                
                <!-- Step 2 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">2</div>
                    <div>
                        <h4 class="guide-step-title">Buka Keranjang Belanja</h4>
                        <p class="guide-step-desc">
                            Klik tombol gambar keranjang <span class="guide-step-highlight"><i class="fa-solid fa-basket-shopping"></i></span> di kanan atas layar untuk meninjau belanjaan Anda, lalu klik 
                            <strong style="color: var(--clr-primary);">Lanjut ke Pembayaran</strong>.
                        </p>
                    </div>
                </div>
                
                <!-- Step 3 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">3</div>
                    <div>
                        <h4 class="guide-step-title">Lengkapi Alamat (Pakai GPS)</h4>
                        <p class="guide-step-desc">
                            Isi nama penerima dan WhatsApp. Klik tombol 
                            <strong style="color: var(--clr-primary-light);"><i class="fa-solid fa-location-crosshairs"></i> Gunakan Lokasi Saat Ini</strong> 
                            agar alamat terisi otomatis. Anda tinggal melengkapi kolom <strong>RT / RW / No. Rumah</strong> yang kosong.
                        </p>
                    </div>
                </div>
                
                <!-- Step 4 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">4</div>
                    <div>
                        <h4 class="guide-step-title">Pilih Ekspedisi &amp; Bayar</h4>
                        <p class="guide-step-desc">
                            Pilih jenis pengiriman (JNE/Pos/J&amp;T), lalu pilih metode pembayaran dan bayar. Pesanan Anda siap dikirim!
                        </p>
                    </div>
                </div>
                
                <!-- Alternatif WA -->
                <div class="guide-wa-card">
                    <i class="fa-brands fa-whatsapp wa-main-icon"></i>
                    <div>
                        <h5 class="guide-wa-title">Butuh Bantuan Operator?</h5>
                        <p class="guide-wa-desc">
                            Jika Anda bingung mengisi formulir online, Anda bisa memesan secara manual via WhatsApp. Operator kami siap membantu Anda dari awal sampai akhir.
                        </p>
                        <a href="https://wa.me/{{ $shop_settings['shop_whatsapp'] ?? '6281234567890' }}?text=Halo%2C%20saya%20ingin%20memesan%20kurma%20secara%20manual." 
                           target="_blank" rel="noopener" class="btn-guide-wa">
                            <i class="fa-brands fa-whatsapp"></i>
                            <span>Pesan via WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="guide-modal-footer">
                <button class="btn-guide-close" @click="openGuide = false">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    {{-- Chatbot Widget Asisten Rekomendasi --}}
    @include('shop.partials.chatbot')

    {{-- ═══ Wishlist Drawer ═══ --}}
    <div id="wishlist-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:2000;" onclick="closeWishlist()"></div>
    <div id="wishlist-panel" style="display:none;position:fixed;top:0;right:0;bottom:0;width:380px;max-width:100vw;background:#fff;box-shadow:0 0 40px rgba(0,0,0,.15);z-index:2001;flex-direction:column;transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);">
        <div style="padding:20px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-family:var(--font-heading);font-size:20px;font-weight:700;color:var(--clr-primary-dark);display:flex;align-items:center;gap:10px;">
                <i class="fa-regular fa-heart" style="color:var(--clr-danger);"></i> Wishlist
            </div>
            <button onclick="closeWishlist()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="wishlist-body" style="flex:1;overflow-y:auto;padding:20px 24px;"></div>
    </div>

    {{-- ═══ Compare Drawer ═══ --}}
    <div id="compare-bar"
         style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:1500;background:var(--clr-primary-dark);color:#fff;padding:14px 24px;box-shadow:0 -4px 20px rgba(0,0,0,.25);"
    >
        <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="font-weight:700;font-size:14px;flex-shrink:0;"><i class="fas fa-balance-scale" style="color:var(--clr-gold-light);margin-right:6px;"></i>Bandingkan Produk (<span id="compare-count">0</span>/3)</div>
            <div id="compare-items" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;"></div>
            <button onclick="doCompare()" id="compare-btn"
                style="padding:10px 20px;border-radius:99px;background:var(--clr-gold);color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.2s;white-space:nowrap;"
            ><i class="fas fa-balance-scale"></i> Bandingkan</button>
            <button onclick="clearCompare()" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:13px;cursor:pointer;">Hapus Semua</button>
        </div>
    </div>

    {{-- ═══ Compare Modal ═══ --}}
    <div id="compare-modal-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(8px);z-index:3000;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:900px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.3);">
            <div style="padding:20px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                <div style="font-family:var(--font-heading);font-size:20px;font-weight:700;color:var(--clr-primary-dark);"><i class="fas fa-balance-scale" style="color:var(--clr-gold);margin-right:8px;"></i>Perbandingan Produk</div>
                <button onclick="closeCompareModal()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="compare-modal-body" style="padding:24px;overflow-x:auto;"></div>
        </div>
    </div>

    @stack('scripts')

    <script>
    // ═══════════════════════════════════════
    // GLOBAL CartManager — bridge for Hampers Builder
    // ═══════════════════════════════════════
    window.CartManager = {
        addItem(product) {
            if (window.__cartStore) {
                window.__cartStore.addToCart({
                    id: product.id,
                    name: product.name,
                    price_unit: product.unit,
                    selling_price: product.price,
                    image_path: product.img ? product.img.replace('/storage/', '') : null,
                    weight_grams: 500,
                    price_tiers: null,
                }, product.qty || 1);
            }
        }
    };
    window.openCart = function() {
        if (window.__cartStore) window.__cartStore.isOpen = true;
    };

    // ═══════════════════════════════════════
    // WISHLIST (localStorage-based)
    // ═══════════════════════════════════════
    function getWishlist() {
        try { return JSON.parse(localStorage.getItem('pk_wishlist') || '[]'); } catch(e) { return []; }
    }
    function saveWishlist(list) {
        localStorage.setItem('pk_wishlist', JSON.stringify(list));
        updateWishlistBadge();
    }
    function updateWishlistBadge() {
        const count = getWishlist().length;
        const badge = document.getElementById('nav-wishlist-count');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
    function toggleWishlist(product) {
        let list = getWishlist();
        const idx = list.findIndex(p => p.id === product.id);
        if (idx > -1) {
            list.splice(idx, 1);
            showWishlistToast('Dihapus dari wishlist', 'remove');
        } else {
            list.push(product);
            showWishlistToast('Ditambahkan ke wishlist ❤️', 'add');
        }
        saveWishlist(list);
        updateAllWishlistButtons();
    }
    function isInWishlist(id) {
        return getWishlist().some(p => p.id == id);
    }
    function updateAllWishlistButtons() {
        document.querySelectorAll('[data-wishlist-id]').forEach(btn => {
            const id = btn.dataset.wishlistId;
            const icon = btn.querySelector('i');
            if (icon) {
                if (isInWishlist(id)) {
                    icon.className = 'fa-solid fa-heart';
                    btn.title = 'Hapus dari Wishlist';
                    btn.style.color = 'var(--clr-danger)';
                } else {
                    icon.className = 'fa-regular fa-heart';
                    btn.title = 'Tambah ke Wishlist';
                    btn.style.color = '';
                }
            }
        });
    }
    function openWishlist() {
        const panel   = document.getElementById('wishlist-panel');
        const overlay = document.getElementById('wishlist-overlay');
        const body    = document.getElementById('wishlist-body');
        const list    = getWishlist();

        if (list.length === 0) {
            body.innerHTML = '<div style="text-align:center;padding:48px 0;color:#9ca3af;"><i class="fa-regular fa-heart" style="font-size:52px;display:block;margin-bottom:12px;color:#d1d5db;"></i><p>Wishlist Anda kosong.<br>Klik ❤ pada produk untuk menyimpannya.</p></div>';
        } else {
            body.innerHTML = list.map(p => `
                <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #f3f4f6;">
                    <div style="width:56px;height:56px;border-radius:10px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        ${p.img ? `<img src="${p.img}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">` : '<i class="fas fa-seedling" style="color:#065f46;"></i>'}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:700;color:#1a1a1a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${p.name}</div>
                        <div style="font-size:13px;color:#065f46;font-weight:700;">Rp ${parseInt(p.price).toLocaleString('id-ID')} / ${p.unit}</div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                        <button onclick="if(window.addToCart) addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')}, 1)" style="padding:6px 12px;border-radius:8px;background:#065f46;color:#fff;font-size:11px;font-weight:700;border:none;cursor:pointer;"><i class="fas fa-cart-plus"></i></button>
                        <button onclick="toggleWishlist(${JSON.stringify(p).replace(/"/g,'&quot;')}); renderWishlistBody()" style="padding:6px 12px;border-radius:8px;background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700;border:none;cursor:pointer;"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }

        overlay.style.display = 'block';
        panel.style.display = 'flex';
        setTimeout(() => panel.style.transform = 'translateX(0)', 10);
    }
    function renderWishlistBody() {
        const body = document.getElementById('wishlist-body');
        if (body) {
            openWishlist();
        }
    }
    function closeWishlist() {
        const panel   = document.getElementById('wishlist-panel');
        const overlay = document.getElementById('wishlist-overlay');
        panel.style.transform = 'translateX(100%)';
        setTimeout(() => { panel.style.display = 'none'; overlay.style.display = 'none'; }, 300);
    }
    function showWishlistToast(msg, type) {
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:${type==='add'?'#065f46':'#6b7280'};color:#fff;padding:10px 20px;border-radius:99px;font-size:13px;font-weight:700;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 2000);
    }
    document.addEventListener('DOMContentLoaded', () => { updateWishlistBadge(); updateAllWishlistButtons(); });

    // ═══════════════════════════════════════
    // COMPARE (localStorage-based, max 3)
    // ═══════════════════════════════════════
    let compareList = [];
    function toggleCompare(product) {
        const idx = compareList.findIndex(p => p.id === product.id);
        if (idx > -1) {
            compareList.splice(idx, 1);
        } else {
            if (compareList.length >= 3) {
                alert('Maksimal 3 produk dapat dibandingkan.');
                return;
            }
            compareList.push(product);
        }
        updateCompareBar();
        updateAllCompareButtons();
    }
    function updateCompareBar() {
        const bar   = document.getElementById('compare-bar');
        const count = document.getElementById('compare-count');
        const items = document.getElementById('compare-items');
        count.textContent = compareList.length;
        if (compareList.length === 0) {
            bar.style.display = 'none';
            return;
        }
        bar.style.display = 'block';
        items.innerHTML = compareList.map(p => `
            <div style="display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);padding:6px 10px;border-radius:8px;">
                <span style="font-size:12px;font-weight:600;">${p.name.substring(0,20)}${p.name.length>20?'...':''}</span>
                <button onclick="toggleCompare(${JSON.stringify(p).replace(/"/g,'&quot;')})" style="background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:13px;"><i class="fas fa-times"></i></button>
            </div>
        `).join('');
    }
    function clearCompare() { compareList = []; updateCompareBar(); updateAllCompareButtons(); }
    function updateAllCompareButtons() {
        document.querySelectorAll('[data-compare-id]').forEach(btn => {
            const id = btn.dataset.compareId;
            const inList = compareList.some(p => p.id == id);
            btn.style.background = inList ? 'var(--clr-primary)' : '';
            btn.style.color = inList ? '#fff' : '';
            btn.title = inList ? 'Hapus dari Perbandingan' : 'Bandingkan';
        });
    }
    function doCompare() {
        if (compareList.length < 2) { alert('Pilih minimal 2 produk untuk dibandingkan.'); return; }
        const modal   = document.getElementById('compare-modal-backdrop');
        const body    = document.getElementById('compare-modal-body');
        const cols    = compareList.map(p => `
            <td style="padding:12px;text-align:center;vertical-align:top;min-width:200px;">
                <div style="width:80px;height:80px;border-radius:12px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);margin:0 auto 10px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    ${p.img ? `<img src="${p.img}" style="width:100%;height:100%;object-fit:cover;">` : '<i class="fas fa-seedling" style="font-size:30px;color:#065f46;"></i>'}
                </div>
                <div style="font-weight:800;font-size:15px;margin-bottom:8px;color:#022c22;">${p.name}</div>
                <div style="font-size:18px;font-weight:800;color:#065f46;margin-bottom:6px;">Rp ${parseInt(p.price).toLocaleString('id-ID')}</div>
                <div style="font-size:12px;color:#6b7280;">${p.unit}</div>
                ${p.category ? `<div style="margin-top:8px;"><span style="background:#f0fdf4;color:#065f46;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">${p.category}</span></div>` : ''}
                <button onclick="if(window.addToCart) addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')}, 1)" style="margin-top:12px;width:100%;padding:10px;border-radius:10px;background:#065f46;color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;"><i class="fas fa-cart-plus"></i> Tambah ke Keranjang</button>
            </td>
        `).join('<td style="width:1px;background:#f3f4f6;"></td>');
        body.innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr>${cols}</tr></table>`;
        modal.style.display = 'flex';
    }
    function closeCompareModal() {
        document.getElementById('compare-modal-backdrop').style.display = 'none';
    }
    </script>
</body>
</html>
