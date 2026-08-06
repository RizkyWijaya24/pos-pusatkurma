@extends('layouts.shop')

@section('title', 'Detail Pesanan #' . $order->order_code)

@push('styles')
<style>
    .success-section { padding: 60px 24px 80px; }
    .success-inner {
        max-width: 750px;
        margin: 0 auto;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
    .success-header {
        padding: 40px 24px;
        background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-primary-dark) 100%);
        color: #fff;
        text-align: center;
        position: relative;
    }
    .success-header::after {
        content: '🌴';
        position: absolute;
        bottom: -15px;
        right: 20px;
        font-size: 80px;
        opacity: .1;
    }
    .success-icon-wrap {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 16px;
        color: var(--clr-gold-light);
        animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes scaleIn {
        from { transform: scale(0); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .success-header h1 { font-family: var(--font-heading); font-size: 26px; font-weight: 700; margin-bottom: 8px; }
    .success-header p { font-size: 14px; opacity: .8; max-width: 420px; margin: 0 auto; line-height: 1.6; }

    .success-body { padding: 40px 30px; }
    
    .status-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: var(--radius-md);
        margin-bottom: 30px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-alert.paid { background: rgba(16,185,129,.08); border: 1.5px solid rgba(16,185,129,.25); color: #065f46; }
    .status-alert.pending { background: rgba(217,119,6,.08); border: 1.5px solid rgba(217,119,6,.25); color: var(--clr-gold-dark); }
    .status-alert.failed { background: rgba(239,68,68,.08); border: 1.5px solid rgba(239,68,68,.25); color: #991b1b; }

    .order-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
        font-size: 14px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--clr-border);
    }
    .info-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--clr-text-muted); margin-bottom: 4px; }
    .info-value { font-weight: 600; color: var(--clr-text); line-height: 1.5; }

    .table-order-items { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
    .table-order-items th {
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--clr-text-muted);
        text-align: left;
        border-bottom: 1.5px solid var(--clr-border);
        background: var(--clr-surface-2);
    }
    .table-order-items td {
        padding: 14px 16px;
        font-size: 14px;
        border-bottom: 1px solid rgba(0,0,0,.05);
    }
    .table-order-items tr:last-child td { border-bottom: none; }
    
    .table-total-row { font-weight: 800; font-size: 16px; color: var(--clr-primary-dark); }
    .table-total-row td { border-top: 1.5px solid var(--clr-border); padding-top: 16px; }

    .btn-success-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        border-radius: var(--radius-md);
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }
    .btn-success-primary {
        background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
        color: #fff;
        box-shadow: 0 4px 14px rgba(6,95,70,.25);
    }
    .btn-success-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(6,95,70,.35); }
    .btn-success-secondary {
        background: var(--clr-surface-2);
        border: 1.5px solid var(--clr-border);
        color: var(--clr-text);
    }
    .btn-success-secondary:hover { background: var(--clr-border); }
    
    .btn-resume-pay {
        background: linear-gradient(135deg, var(--clr-gold), var(--clr-gold-light));
        color: #fff;
        box-shadow: 0 4px 14px rgba(217,119,6,.25);
        margin-bottom: 16px;
        width: 100%;
    }
    .btn-resume-pay:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(217,119,6,.35); }

    .success-actions-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 14px;
    }

    /* Responsive styling for Mobile / Android */
    @media (max-width: 640px) {
        .success-section {
            padding: 24px 12px 48px;
        }
        .success-header {
            padding: 30px 16px;
        }
        .success-header h1 {
            font-size: 20px;
        }
        .success-body {
            padding: 24px 16px;
        }
        .status-alert {
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .order-info-grid {
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
        }
        .table-order-items th {
            padding: 8px 10px;
            font-size: 11px;
        }
        .table-order-items td {
            padding: 10px 10px;
            font-size: 12px;
        }
        .table-total-row {
            font-size: 14px;
        }
        .success-actions-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .btn-success-action {
            padding: 12px 16px;
            font-size: 13px;
        }
    }
</style>
@endpush

@section('content')
<section class="success-section" x-data="successState">
    <div class="success-inner">
        <!-- Header -->
        <div class="success-header">
            <div class="success-icon-wrap">
                @if($order->payment_status === 'paid')
                    <i class="fa-solid fa-circle-check"></i>
                @elseif($order->payment_status === 'failed')
                    <i class="fa-solid fa-circle-xmark"></i>
                @else
                    <i class="fa-solid fa-clock"></i>
                @endif
            </div>
            <h1>
                @if($order->payment_status === 'paid')
                    Pembayaran Berhasil!
                @elseif($order->payment_status === 'failed')
                    Transaksi Gagal
                @else
                    Pesanan Telah Dicatat
                @endif
            </h1>
            <p>
                @if($order->payment_status === 'paid')
                    Terima kasih atas pesanan Anda. Kami telah memproses pembayaran Anda secara otomatis.
                @elseif($order->payment_status === 'failed')
                    Maaf, transaksi pembayaran Anda tidak berhasil diselesaikan.
                @else
                    Silakan selesaikan pembayaran Anda agar kami dapat segera mengirim pesanan Anda.
                @endif
            </p>
        </div>

        <!-- Body -->
        <div class="success-body">
            
            <!-- Alert Status -->
            @if($order->payment_status === 'paid')
                <div class="status-alert paid">
                    <i class="fa-solid fa-circle-check" style="font-size:18px;"></i>
                    <span>Status Pembayaran: <strong>LUNAS (PAID)</strong></span>
                </div>
            @elseif($order->payment_status === 'failed' || $order->payment_status === 'expired')
                <div class="status-alert failed">
                    <i class="fa-solid fa-circle-xmark" style="font-size:18px;"></i>
                    <span>Status Pembayaran: <strong>BATAL / GAGAL (FAILED)</strong></span>
                </div>
            @else
                <div class="status-alert pending">
                    <i class="fa-solid fa-clock" style="font-size:18px;"></i>
                    <span>Status Pembayaran: <strong>MENUNGGU PEMBAYARAN (PENDING)</strong></span>
                </div>

                <!-- Tombol Bayar Sekarang (Jika Masih Pending) -->
                @if($order->snap_token)
                    <button @click="payNow()" class="btn-success-action btn-resume-pay">
                        <i class="fa-solid fa-credit-card"></i> Bayar Sekarang (Lanjutkan Pembayaran)
                    </button>
                @endif
            @endif

            <!-- Detail Informasi -->
            <div class="order-info-grid">
                <div>
                    <div class="info-label">Kode Pesanan</div>
                    <div class="info-value">{{ $order->order_code }}</div>
                </div>
                <div>
                    <div class="info-label">Tanggal Transaksi</div>
                    <div class="info-value">{{ $order->created_at->format('d M Y, H:i') }} WIB</div>
                </div>
                <div>
                    <div class="info-label">Penerima</div>
                    <div class="info-value">
                        {{ $order->customer_name }}<br>
                        {{ $order->customer_phone }}<br>
                        {{ $order->customer_email }}
                    </div>
                </div>
                <div>
                    <div class="info-label">Alamat Pengiriman</div>
                    <div class="info-value">{{ $order->shipping_address }}</div>
                </div>
            </div>

            <!-- Detail Barang -->
            <div class="info-label" style="margin-bottom:12px;">Rincian Produk Belanja</div>
            <table class="table-order-items">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align: center;">Kuantitas</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>
                                <span style="font-weight:700; color:var(--clr-text);">{{ $item->product->name }}</span>
                                <span style="font-size:11px; color:var(--clr-text-muted); display:block;">SKU: {{ $item->product->sku }}</span>
                            </td>
                            <td style="text-align: center;">{{ (float) $item->qty }} {{ $item->product->price_unit }}</td>
                            <td style="text-align: right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: 700;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight:600; color:var(--clr-text-muted);">Subtotal Produk:</td>
                        <td style="text-align: right; font-weight:700;">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($order->shipping_cost > 0)
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight:600; color:var(--clr-text-muted);">
                            Ongkos Kirim
                            @if($order->shipping_courier)
                                <span style="font-size:11px; font-weight:400;">({{ $order->shipping_courier }} {{ $order->shipping_service }})</span>
                            @endif
                            :
                        </td>
                        <td style="text-align: right; font-weight:700;">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(($order->payment_fee ?? 0) > 0)
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight:600; color:var(--clr-text-muted);">Biaya Transaksi:</td>
                        <td style="text-align: right; font-weight:700;">Rp {{ number_format($order->payment_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="table-total-row">
                        <td colspan="3" style="text-align: right;">Total Pembayaran:</td>
                        <td style="text-align: right;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            <!-- Navigasi -->
            @php
                $itemsText = "";
                foreach($order->orderItems as $index => $item) {
                    $productName = $item->product->name ?? 'Produk';
                    $priceUnit = $item->product->price_unit ?? 'pcs';
                    $itemsText .= ($index + 1) . ". *" . $productName . "* - " . (float)$item->qty . " " . $priceUnit . " (Rp " . number_format($item->price, 0, ',', '.') . ") = *Rp " . number_format($item->subtotal, 0, ',', '.') . "*\n";
                }

                $waMessage = "Halo, saya ingin mengonfirmasi pesanan saya:\n\n"
                           . "*Kode Pesanan:* " . $order->order_code . "\n"
                           . "*Nama Penerima:* " . $order->customer_name . "\n"
                           . "*Alamat Pengiriman:* " . $order->shipping_address . "\n\n"
                           . "*Rincian Produk:*\n" . $itemsText . "\n"
                           . "*Total Belanja:* *Rp " . number_format($order->total_amount, 0, ',', '.') . "*\n\n"
                           . "Mohon segera diproses. Terima kasih! 🌴";

                $waUrl = "https://wa.me/" . ($shop_settings['shop_whatsapp'] ?? '6281234567890') . "?text=" . rawurlencode($waMessage);
            @endphp
            <div class="success-actions-grid">
                <a href="{{ route('shop.index') }}" class="btn-success-action btn-success-secondary">
                    <i class="fa-solid fa-store"></i> Belanja Lagi
                </a>
                <a href="{{ $waUrl }}"
                   target="_blank" rel="noopener"
                   class="btn-success-action btn-success-primary">
                    <i class="fa-brands fa-whatsapp"></i> Hubungi CS Toko
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
@if($order->payment_status === 'pending' && $order->snap_token)
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('successState', () => ({
            init() {
                // Auto trigger payment modal on load
                this.payNow();
            },
            payNow() {
                if (typeof loadJokulCheckout === 'function') {
                    loadJokulCheckout('{{ $order->snap_token }}');
                } else {
                    window.location.href = '{{ $order->snap_token }}';
                }
            }
        }));
    });
</script>
@else
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('successState', () => ({
            // No action needed
        }));
    });
</script>
@endif
@endpush
