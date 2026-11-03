<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Online Sedang Pemeliharaan | {{ $shopName ?? 'Pusat Kurma' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --clr-primary: #065f46;
            --clr-primary-light: #059669;
            --clr-primary-dark: #022c22;
            --clr-gold: #d97706;
            --clr-gold-light: #fbbf24;
            --clr-bg: #fdfaf5;
            --clr-surface: #ffffff;
            --clr-text: #1e293b;
            --clr-text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--clr-bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(6, 95, 70, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(217, 119, 6, 0.06) 0%, transparent 40%);
            color: var(--clr-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 24px 16px;
        }

        .container {
            width: 100%;
            max-width: 620px;
            background: var(--clr-surface);
            border-radius: 28px;
            padding: 44px 32px;
            box-shadow: 0 20px 40px -15px rgba(6, 95, 70, 0.12), 0 0 1px 1px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(6, 95, 70, 0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--clr-primary), var(--clr-gold), var(--clr-primary-light));
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fef3c7;
            color: #92400e;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .badge-status .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #d97706;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(217, 119, 6, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(217, 119, 6, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(217, 119, 6, 0); }
        }

        .icon-wrapper {
            width: 84px;
            height: 84px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(6, 95, 70, 0.1), rgba(217, 119, 6, 0.15));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--clr-primary);
            font-size: 38px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--clr-primary-dark);
            margin-bottom: 12px;
            line-height: 1.25;
        }

        .notice-text {
            font-size: 15px;
            color: var(--clr-text-muted);
            line-height: 1.6;
            margin-bottom: 28px;
            padding: 0 10px;
        }

        .info-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 14px;
            text-align: left;
        }

        .info-card i {
            font-size: 20px;
            color: var(--clr-primary);
        }

        .info-card h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--clr-text);
            margin-bottom: 2px;
        }

        .info-card p {
            font-size: 12px;
            color: var(--clr-text-muted);
            line-height: 1.4;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-whatsapp {
            background-color: #25D366;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.3);
        }

        .btn-whatsapp:hover {
            background-color: #20ba59;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.4);
        }

        .btn-login {
            background-color: #f1f5f9;
            color: #475569;
        }

        .btn-login:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .footer {
            margin-top: 28px;
            font-size: 12px;
            color: #94a3b8;
        }

        .footer a {
            color: var(--clr-primary);
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .container {
                padding: 32px 20px;
                border-radius: 20px;
            }
            h1 {
                font-size: 23px;
            }
            .notice-text {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Status Badge -->
        <div class="badge-status">
            <span class="pulse-dot"></span>
            <span>Pemeliharaan Sistem</span>
        </div>

        <!-- Brand / Feature Icon -->
        <div class="icon-wrapper">
            <i class="fa-solid fa-store-slash"></i>
        </div>

        <!-- Heading -->
        <h1>{{ $shopName ?? 'Pusat Kurma' }}</h1>
        
        <p class="notice-text">
            {{ $notice ?? 'Website toko online saat ini sedang dalam pemeliharaan berkala untuk peningkatan performa. Anda tetap dapat melakukan pemesanan langsung melalui WhatsApp customer service kami.' }}
        </p>

        <!-- Offline Store Reminder -->
        <div class="info-card">
            <i class="fa-solid fa-store"></i>
            <div>
                <h4>Toko Fisik Buka Normal</h4>
                <p>Operasional toko fisik, kasir, dan pengiriman antar cabang tetap berjalan lancar seperti biasa.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            @php
                $cleanWa = preg_replace('/[^0-9]/', '', $whatsapp ?? '6281234567890');
                if (str_starts_with($cleanWa, '0')) {
                    $cleanWa = '62' . substr($cleanWa, 1);
                }
            @endphp
            <a href="https://wa.me/{{ $cleanWa }}?text=Halo%20{{ urlencode($shopName ?? 'Pusat Kurma') }},%20saya%20ingin%20tanya%20produk%20dan%20pemesanan%20kurma." 
               target="_blank" 
               rel="noopener noreferrer" 
               class="btn btn-whatsapp">
                <i class="fa-brands fa-whatsapp text-lg"></i>
                <span>Pesan Cepat via WhatsApp</span>
            </a>

            <a href="{{ route('login') }}" class="btn btn-login">
                <i class="fa-solid fa-lock text-sm"></i>
                <span>Login Pegawai (POS Kasir & Admin)</span>
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $shopName ?? 'Pusat Kurma' }}. All rights reserved.
        </div>
    </div>

</body>
</html>

