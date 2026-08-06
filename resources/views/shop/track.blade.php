@extends('layouts.shop')

@section('title', 'Lacak Pesanan — Pusat Kurma')
@section('meta_description', 'Lacak status pesanan Anda di Pusat Kurma Cianjur menggunakan kode pesanan, nomor WhatsApp, atau email.')

@push('styles')
<style>
    .track-section {
        min-height: 80vh;
        padding: 60px 24px 80px;
        background: linear-gradient(160deg, #f0fdf4 0%, #fdfaf5 60%);
    }
    .track-inner { max-width: 780px; margin: 0 auto; }

    /* Hero */
    .track-hero {
        text-align: center;
        margin-bottom: 40px;
    }
    .track-hero-icon {
        width: 72px; height: 72px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
        display: flex; align-items: center; justify-content: center;
        font-size: 30px; color: #fff;
        margin: 0 auto 20px;
        box-shadow: 0 8px 24px rgba(6,95,70,.25);
    }
    .track-hero h1 { font-family: var(--font-heading); font-size: 32px; color: var(--clr-primary-dark); margin-bottom: 8px; }
    .track-hero p  { font-size: 15px; color: var(--clr-text-muted); }

    /* Search Card */
    .track-search-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--clr-border);
        box-shadow: var(--shadow-md);
        padding: 32px;
        margin-bottom: 36px;
    }
    .track-input-group {
        display: flex;
        gap: 12px;
    }
    .track-input {
        flex: 1;
        height: 52px;
        border: 2px solid var(--clr-border);
        border-radius: var(--radius-md);
        padding: 0 18px;
        font-size: 15px;
        font-family: inherit;
        color: var(--clr-text);
        outline: none;
        transition: var(--transition);
    }
    .track-input:focus { border-color: var(--clr-primary-light); box-shadow: 0 0 0 3px rgba(5,150,105,.12); }
    .track-btn {
        height: 52px;
        padding: 0 28px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        border: none;
        transition: var(--transition);
        white-space: nowrap;
        display: flex; align-items: center; gap: 8px;
        cursor: pointer;
    }
    .track-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(6,95,70,.35); }

    .track-hint {
        font-size: 12px;
        color: var(--clr-text-muted);
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Error Alert */
    .track-error {
        background: rgba(239,68,68,.08);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: var(--radius-md);
        padding: 14px 18px;
        color: #991b1b;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
    }

    /* Multiple Orders Found List */
    .multiple-orders-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--clr-border);
        box-shadow: var(--shadow-md);
        padding: 24px;
        margin-bottom: 30px;
    }
    .multiple-orders-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--clr-primary-dark);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .order-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        border: 1px solid var(--clr-border);
        border-radius: var(--radius-md);
        margin-bottom: 12px;
        transition: var(--transition);
        background: #fafafa;
    }
    .order-list-item:hover { border-color: var(--clr-primary); background: #f0fdf4; }

    /* Result Card */
    .order-result-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--clr-border);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        animation: slideUp .3s ease;
    }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

    .order-result-header {
        background: linear-gradient(135deg, var(--clr-primary-dark), var(--clr-primary));
        padding: 24px 28px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .order-result-code {
        font-family: var(--font-heading);
        font-size: 22px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-copy-code {
        background: rgba(255,255,255,.2);
        border: none;
        color: #fff;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 99px;
        cursor: pointer;
        transition: var(--transition);
    }
    .btn-copy-code:hover { background: rgba(255,255,255,.35); }
    .order-result-date { font-size: 13px; color: rgba(255,255,255,.75); margin-top: 4px; }

    .order-status-badge {
        padding: 7px 16px;
        border-radius: 99px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .3px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-pending  { background: rgba(251,191,36,.25); color: #b45309; border: 1px solid rgba(217,119,6,.4); }
    .badge-paid     { background: rgba(16,185,129,.2); color: #047857; border: 1px solid rgba(16,185,129,.4); }
    .badge-failed   { background: rgba(239,68,68,.2); color: #b91c1c; border: 1px solid rgba(239,68,68,.4); }

    /* Timeline */
    .order-timeline {
        padding: 28px;
        border-bottom: 1px solid var(--clr-surface-2);
        background: #fff;
    }
    .timeline-title { font-weight: 700; font-size: 13px; color: var(--clr-text-muted); letter-spacing: .5px; text-transform: uppercase; margin-bottom: 24px; }
    .timeline-steps {
        display: flex;
        align-items: center;
        gap: 0;
    }
    .timeline-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }
    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        width: 100%;
        height: 3px;
        background: var(--clr-border);
        z-index: 0;
    }
    .timeline-step.done:not(:last-child)::after { background: var(--clr-success); }

    .timeline-dot {
        width: 40px; height: 40px;
        border-radius: 50%;
        border: 3px solid var(--clr-border);
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
        color: var(--clr-border);
        position: relative;
        z-index: 1;
        transition: var(--transition);
    }
    .timeline-step.done .timeline-dot  { border-color: var(--clr-success); color: var(--clr-success); background: #f0fdf4; }
    .timeline-step.active .timeline-dot { border-color: var(--clr-gold); color: var(--clr-gold); background: #fffbeb; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(217,119,6,.4); } 50% { box-shadow: 0 0 0 8px rgba(217,119,6,0); } }

    .timeline-label { font-size: 11px; font-weight: 600; color: var(--clr-text-muted); margin-top: 8px; text-align: center; }
    .timeline-step.done .timeline-label   { color: var(--clr-success); }
    .timeline-step.active .timeline-label { color: var(--clr-gold); }

    /* Details */
    .order-details { padding: 28px; }
    .order-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .order-info-item label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--clr-text-muted); display: block; margin-bottom: 4px; }
    .order-info-item span  { font-size: 14px; font-weight: 600; color: var(--clr-text); }

    /* Items */
    .order-items-title { font-weight: 700; font-size: 14px; color: var(--clr-primary-dark); margin-bottom: 14px; }
    .order-item-row {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--clr-surface-2);
    }
    .order-item-row:last-child { border-bottom: none; }
    .order-item-img {
        width: 52px; height: 52px;
        border-radius: 10px;
        object-fit: cover;
        background: var(--clr-surface-2);
        flex-shrink: 0;
        border: 1px solid var(--clr-border);
    }
    .order-item-info { flex: 1; }
    .order-item-name  { font-size: 14px; font-weight: 600; color: var(--clr-text); }
    .order-item-meta  { font-size: 12px; color: var(--clr-text-muted); margin-top: 2px; }
    .order-item-price { font-size: 14px; font-weight: 700; color: var(--clr-primary); text-align: right; }

    /* Total Section */
    .order-total-section {
        background: var(--clr-surface-2);
        border-radius: var(--radius-md);
        padding: 18px;
        margin-top: 20px;
    }
    .order-total-row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
    .order-total-row.grand { font-size: 18px; font-weight: 800; color: var(--clr-primary-dark); border-top: 1px solid var(--clr-border); padding-top: 12px; margin-top: 8px; }

    .track-actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }
    .btn-track-wa {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px;
        border-radius: 99px;
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: #fff; font-size: 14px; font-weight: 700; border: none;
        box-shadow: 0 4px 14px rgba(37,211,102,.3);
        transition: var(--transition); text-decoration: none;
    }
    .btn-track-wa:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,211,102,.45); }
    .btn-track-shop {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px;
        border-radius: 99px;
        background: var(--clr-surface-2);
        border: 1.5px solid var(--clr-border);
        color: var(--clr-text); font-size: 14px; font-weight: 600;
        transition: var(--transition); text-decoration: none;
    }
    .btn-track-shop:hover { background: var(--clr-border); }

    @media (max-width: 640px) {
        .track-input-group { flex-direction: column; }
        .order-info-grid   { grid-template-columns: 1fr; }
        .timeline-label    { font-size: 10px; }
    }
</style>
@endpush

@section('content')
<section class="track-section">
    <div class="track-inner">
        <div class="track-hero">
            <div class="track-hero-icon"><i class="fas fa-search-location"></i></div>
            <h1>Lacak Pesanan</h1>
            <p>Masukkan Kode Pesanan, Nomor WhatsApp, atau Email Anda</p>
        </div>

        {{-- Search Form (Supports GET & POST) --}}
        <div class="track-search-card">
            <form method="GET" action="{{ route('shop.track') }}">
                <div class="track-input-group">
                    <input
                        id="order_code"
                        name="order_code"
                        type="text"
                        class="track-input"
                        placeholder="Contoh: PK-ORD-20260727-A1B2 atau 08123456789"
                        value="{{ $orderCode ?? '' }}"
                        autocomplete="off"
                        required
                    >
                    <button type="submit" class="track-btn">
                        <i class="fas fa-search"></i>
                        Lacak
                    </button>
                </div>
                <div class="track-hint">
                    <i class="fas fa-info-circle"></i>
                    Tips: Anda dapat memasukkan kode pesanan unik atau nomor HP/email yang terdaftar saat pesanan dibuat.
                </div>
                @if ($error)
                    <div class="track-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $error }}
                    </div>
                @endif
            </form>
        </div>

        {{-- List Multiple Orders (jika pencarian via nomor HP / email menemukan beberapa order) --}}
        @if (isset($multipleOrders) && $multipleOrders->count() > 0)
        <div class="multiple-orders-card">
            <div class="multiple-orders-title">
                <i class="fas fa-list-check" style="color:var(--clr-primary);"></i>
                Ditemukan {{ $multipleOrders->count() }} Pesanan untuk Kontak Ini
            </div>
            <div class="orders-list">
                @foreach ($multipleOrders as $item)
                <div class="order-list-item">
                    <div>
                        <div style="font-weight:700;font-size:15px;color:var(--clr-primary-dark);">{{ $item->order_code }}</div>
                        <div style="font-size:12px;color:var(--clr-text-muted);margin-top:2px;">
                            {{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB
                            &bull; Total: <strong>Rp {{ number_format($item->total_amount, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('shop.track', ['order_code' => $item->order_code]) }}"
                           class="btn-track-shop"
                           style="padding: 6px 14px; font-size: 13px;">
                            <i class="fas fa-eye"></i> Lihat Status
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Result Detail Order --}}
        @if ($order)
        @php
            $isPaid      = $order->payment_status === 'paid';
            $isFailed    = in_array($order->payment_status, ['failed', 'expired', 'cancelled']) || $order->status === 'cancelled';
            $isPending   = $order->payment_status === 'pending' && $order->status === 'pending';

            $currentStep = $order->step_number;
            $waNumber    = \App\Models\Setting::get('shop_whatsapp', '6281234567890');
            $waMsg       = urlencode("Halo Pusat Kurma, saya ingin konfirmasi/menanyakan pesanan " . $order->order_code);
        @endphp
        <div class="order-result-card">
            {{-- Header --}}
            <div class="order-result-header">
                <div>
                    <div class="order-result-code">
                        <span>{{ $order->order_code }}</span>
                        <button type="button" class="btn-copy-code" onclick="copyOrderCode('{{ $order->order_code }}')">
                            <i class="fas fa-copy"></i> Salin
                        </button>
                    </div>
                    <div class="order-result-date">Dipesan pada {{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</div>
                </div>
                @if($currentStep === 5)
                    <span class="order-status-badge badge-paid"><i class="fas fa-circle-check"></i> Selesai</span>
                @elseif($currentStep === 4)
                    <span class="order-status-badge badge-paid"><i class="fas fa-truck-fast"></i> Dikirim</span>
                @elseif($currentStep === 3)
                    <span class="order-status-badge badge-paid"><i class="fas fa-box-open"></i> Diproses</span>
                @elseif($isPaid)
                    <span class="order-status-badge badge-paid"><i class="fas fa-check-circle"></i> Pembayaran Lunas</span>
                @elseif($isFailed)
                    <span class="order-status-badge badge-failed"><i class="fas fa-times-circle"></i> Dibatalkan / Gagal</span>
                @else
                    <span class="order-status-badge badge-pending"><i class="fas fa-clock"></i> Menunggu Pembayaran</span>
                @endif
            </div>

            {{-- Timeline Status --}}
            <div class="order-timeline">
                <div class="timeline-title">Progress Status Pesanan</div>
                <div class="timeline-steps">
                    @php
                        $steps = [
                            ['icon' => 'fa-shopping-cart', 'label' => 'Pesanan Dibuat'],
                            ['icon' => 'fa-credit-card',   'label' => 'Pembayaran'],
                            ['icon' => 'fa-box-open',      'label' => 'Diproses'],
                            ['icon' => 'fa-truck-fast',    'label' => 'Dikirim'],
                            ['icon' => 'fa-circle-check',  'label' => 'Selesai'],
                        ];
                    @endphp
                    @foreach($steps as $i => $step)
                        @php
                            $stepNum  = $i + 1;
                            $isDone   = $currentStep > 0 && $stepNum < $currentStep;
                            $isActive = $currentStep > 0 && $stepNum === $currentStep;
                            $class    = $isDone ? 'done' : ($isActive ? 'active' : '');
                        @endphp
                        <div class="timeline-step {{ $class }}">
                            <div class="timeline-dot"><i class="fas {{ $step['icon'] }}"></i></div>
                            <div class="timeline-label">{{ $step['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                @if($currentStep === 5)
                <p style="font-size:13px;color:var(--clr-success);margin-top:20px;text-align:center;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid rgba(16,185,129,.3);">
                    <i class="fas fa-circle-check"></i>
                    Pesanan Anda telah selesai dan sampai di tujuan. Terima kasih telah berbelanja di Pusat Kurma! 🌴
                </p>
                @elseif($currentStep === 4)
                <p style="font-size:13px;color:var(--clr-primary-dark);margin-top:20px;text-align:center;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid rgba(16,185,129,.3);">
                    <i class="fas fa-truck-fast"></i>
                    Pesanan Anda sedang dalam perjalanan bersama kurir {{ strtoupper($order->shipping_courier ?: 'JNE') }}.
                </p>
                @elseif($currentStep === 3)
                <p style="font-size:13px;color:var(--clr-primary-dark);margin-top:20px;text-align:center;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid rgba(16,185,129,.3);">
                    <i class="fas fa-box-open"></i>
                    Pesanan Anda sedang dikemas dan disiapkan untuk diserahkan ke kurir pengiriman.
                </p>
                @elseif($isPaid)
                <p style="font-size:13px;color:var(--clr-text-muted);margin-top:20px;text-align:center;background:#f0fdf4;padding:12px;border-radius:8px;">
                    <i class="fas fa-circle-check" style="color:var(--clr-success);"></i>
                    Pembayaran Anda telah diverifikasi! Pesanan Anda sedang disiapkan dan diserahkan ke kurir pengiriman.
                </p>
                @elseif($isPending)
                <p style="font-size:13px;color:#b45309;margin-top:20px;text-align:center;background:#fffbeb;padding:12px;border-radius:8px;border:1px dashed #f59e0b;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Segera selesaikan pembayaran Anda agar pesanan langsung diproses.
                    @if($order->snap_token)
                        <a href="{{ $order->snap_token }}" style="color:var(--clr-primary-dark);font-weight:700;margin-left:6px;text-decoration:underline;" target="_blank">
                            Bayar Sekarang &rarr;
                        </a>
                    @endif
                </p>
                @elseif($isFailed)
                <p style="font-size:13px;color:#b91c1c;margin-top:20px;text-align:center;background:#fef2f2;padding:10px;border-radius:8px;">
                    <i class="fas fa-times-circle"></i>
                    Pesanan ini telah dibatalkan atau waktu pembayaran telah kedaluwarsa. Silakan buat pesanan baru.
                </p>
                @endif
            </div>

            {{-- Detail Pengiriman --}}
            <div class="order-details">
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <label>Nama Penerima</label>
                        <span>{{ $order->customer_name }}</span>
                    </div>
                    <div class="order-info-item">
                        <label>No. Telepon / WA</label>
                        <span>{{ $order->customer_phone }}</span>
                    </div>
                    <div class="order-info-item" style="grid-column:1/-1">
                        <label>Alamat Pengiriman</label>
                        <span>{{ $order->shipping_address }}</span>
                    </div>
                    <div class="order-info-item">
                        <label>Kurir & Layanan Pengiriman</label>
                        <span>{{ strtoupper($order->shipping_courier ?: 'JNE') }} &mdash; {{ $order->shipping_service_name ?: 'Reguler' }}</span>
                    </div>
                    <div class="order-info-item">
                        <label>Estimasi Tiba (ETD)</label>
                        <span>{{ $order->shipping_etd ?: '1-3 Hari Kerja' }}</span>
                    </div>
                </div>

                {{-- Item Produk --}}
                <div class="order-items-title">
                    <i class="fas fa-boxes-packing" style="color:var(--clr-gold);margin-right:6px;"></i>
                    Rincian Produk yang Dipesan
                </div>
                @foreach($order->orderItems as $item)
                <div class="order-item-row">
                    @if($item->product && $item->product->image_path)
                        <img src="{{ Storage::url($item->product->image_path) }}" alt="{{ $item->product->name }}" class="order-item-img" onerror="this.style.display='none'">
                    @else
                        <div class="order-item-img" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);display:flex;align-items:center;justify-content:center;color:var(--clr-primary);font-size:20px;">
                            <i class="fas fa-seedling"></i>
                        </div>
                    @endif
                    <div class="order-item-info">
                        <div class="order-item-name">{{ $item->product->name ?? 'Produk dihapus' }}</div>
                        <div class="order-item-meta">{{ number_format($item->qty, 0) }} {{ $item->product->price_unit ?? 'pcs' }} × Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                    </div>
                    <div class="order-item-price">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                </div>
                @endforeach

                {{-- Total Ringkasan Biaya --}}
                <div class="order-total-section">
                    <div class="order-total-row">
                        <span>Subtotal Produk</span>
                        <span>Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="order-total-row">
                        <span>Ongkos Kirim</span>
                        <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    @if($order->coupon_discount > 0)
                    <div class="order-total-row" style="color:var(--clr-success);">
                        <span>Diskon Kupon ({{ $order->coupon_code }})</span>
                        <span>- Rp {{ number_format($order->coupon_discount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($order->referral_discount > 0)
                    <div class="order-total-row" style="color:var(--clr-success);">
                        <span>Diskon Referral ({{ $order->referral_code }})</span>
                        <span>- Rp {{ number_format($order->referral_discount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($order->payment_fee > 0)
                    <div class="order-total-row" style="color:var(--clr-text-muted);">
                        <span>Biaya Transaksi</span>
                        <span>Rp {{ number_format($order->payment_fee, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="order-total-row grand">
                        <span>Total Pembayaran</span>
                        <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="track-actions">
                    <a href="https://wa.me/{{ $waNumber }}?text={{ $waMsg }}" target="_blank" class="btn-track-wa">
                        <i class="fab fa-whatsapp"></i>
                        Tanya Admin via WhatsApp
                    </a>
                    <a href="{{ route('shop.index') }}" class="btn-track-shop">
                        <i class="fas fa-store"></i>
                        Kembali ke Katalog Toko
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
function copyOrderCode(code) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(() => {
            alert('Kode pesanan "' + code + '" berhasil disalin!');
        });
    } else {
        const input = document.createElement('input');
        input.value = code;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Kode pesanan "' + code + '" berhasil disalin!');
    }
}
</script>
@endpush
@endsection
