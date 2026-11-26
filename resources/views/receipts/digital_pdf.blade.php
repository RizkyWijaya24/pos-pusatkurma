<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Digital #{{ $transaction->transaction_code }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 10mm 9mm 10mm 9mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.35;
            background: #ffffff;
        }

        /* ─── KOP HEADER ─── */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #047857;
            padding-bottom: 7px;
            margin-bottom: 9px;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-col {
            width: 44px;
            padding-right: 8px;
        }
        .company-logo {
            width: 38px;
            height: 38px;
            border-radius: 6px;
        }
        .company-logo-placeholder {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            background-color: #047857;
            text-align: center;
            line-height: 38px;
            font-size: 13pt;
            font-weight: bold;
            color: #ffffff;
        }
        .company-name {
            font-size: 10.5pt;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.15;
        }
        .company-sub {
            font-size: 7pt;
            color: #475569;
            margin-top: 1px;
        }
        .doc-col {
            text-align: right;
            width: 135px;
            border-left: 1px solid #cbd5e1;
            padding-left: 8px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .doc-code {
            font-size: 7.5pt;
            font-weight: bold;
            color: #047857;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .status-unpaid {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        /* ─── METADATA CARDS ─── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
        }
        .meta-table > tbody > tr > td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 8px;
        }
        .meta-box-amber {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 5px;
            padding: 6px 8px;
        }
        .meta-box-title {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .meta-row-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2pt;
        }
        .meta-row-table td {
            padding: 1.5px 0;
            border: none;
        }
        .meta-lbl {
            color: #64748b;
            width: 42%;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            width: 58%;
        }

        /* ─── ITEMS TABLE ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7.5pt;
        }
        .items-table th {
            background-color: #047857;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 6.5pt;
            padding: 4px 5px;
            border: 1px solid #047857;
            text-align: left;
        }
        .items-table td {
            padding: 4px 5px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: top;
        }
        .items-table tr.even {
            background-color: #f8fafc;
        }
        .center { text-align: center !important; }
        .right { text-align: right !important; }
        .bold { font-weight: bold; }

        /* ─── BOTTOM SPLIT (INSTALLMENTS & TOTALS) ─── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .bottom-table > tbody > tr > td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 8px;
            margin-right: 8px;
            font-size: 7pt;
        }
        .notes-title {
            font-size: 6.8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #047857;
            margin-bottom: 3px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
        }
        .inst-row-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .inst-row-table td {
            padding: 2px 0;
            border-bottom: 1px dotted #cbd5e1;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background-color: #ffffff;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .summary-table td {
            padding: 3.5px 7px;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-table tr.grand-row td {
            background-color: #ecfdf5;
            border-top: 1px solid #a7f3d0;
            border-bottom: 1px solid #a7f3d0;
            font-weight: bold;
            font-size: 8.5pt;
            color: #065f46;
            padding: 5px 7px;
        }
        .summary-table tr.remaining-row td {
            background-color: #fffbeb;
            border-top: 1px dashed #fcd34d;
            font-weight: bold;
            font-size: 8pt;
            color: #b45309;
            padding: 4.5px 7px;
        }

        /* ─── FOOTER ─── */
        .footer-box {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
            font-size: 6.8pt;
            color: #64748b;
            line-height: 1.4;
        }
        .footer-verify {
            margin-top: 4px;
            font-family: monospace;
            font-size: 6.8pt;
            color: #334155;
            font-weight: bold;
        }
    </style>
</head>
<body>
@php
    $paymentStatus = $transaction->payment_status ?? 'paid';
    $total         = (int) $transaction->total_price;
    $discount      = (int) ($transaction->discount ?? 0);
    $shippingCost  = (int) ($transaction->shipping_cost ?? 0);
    $subtotal      = max(0, $total + $discount - $shippingCost);
    $paidAmount    = (int) round($transaction->paid_amount);
    $remainingAmt  = (int) round($transaction->remaining_amount);

    $statusText = match ($paymentStatus) {
        'cancelled' => 'DIBATALKAN',
        'unpaid'    => $transaction->payment_method === 'DP' ? 'DP - BELUM LUNAS' : 'TEMPO - BELUM LUNAS',
        default     => 'LUNAS',
    };
    $statusClass = match ($paymentStatus) {
        'cancelled' => 'status-cancelled',
        'unpaid'    => 'status-unpaid',
        default     => 'status-paid',
    };

    $rawAddress  = (string) ($settings['shop_address'] ?? '');
    $isDummySeed = str_contains($rawAddress, 'Jl. Contoh No. 123');

    $shopName    = ($isDummySeed || empty($settings['shop_name'])) ? 'PUSAT KURMA PREMIUM CIANJUR' : $settings['shop_name'];
    $shopTagline = !empty($settings['shop_tagline']) ? $settings['shop_tagline'] : 'Cianjur - Premium Dates';
    $shopAddress = ($isDummySeed || $rawAddress === '') ? 'Jl. Dr. Muwardi No.48, Muka, Kec. Cianjur, Kabupaten Cianjur, Jawa Barat 43215' : $rawAddress;
    $shopPhone   = ($isDummySeed || empty($settings['shop_whatsapp']) || $settings['shop_whatsapp'] === '6281234567890')
        ? '082396572250'
        : $settings['shop_whatsapp'];

    // DomPDF Helvetica built-in font tidak mendukung simbol unicode seperti '✦' atau '•' (muncul sebagai '?')
    $pdfSanitize = static fn(?string $text): string => str_replace(['✦', '•'], '-', (string) $text);

    $logoPath   = public_path('images/logo.png');
    $logoBase64 = (file_exists($logoPath) && extension_loaded('gd'))
        ? base64_encode(file_get_contents($logoPath))
        : null;
@endphp

    <!-- KOP HEADER -->
    <table class="kop-table">
        <tr>
            <td class="logo-col">
                @if($logoBase64)
                    <img class="company-logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                @else
                    <div class="company-logo-placeholder">PK</div>
                @endif
            </td>
            <td>
                <div class="company-name">{{ $pdfSanitize($shopName) }}</div>
                <div class="company-sub" style="font-weight: bold;">{{ $pdfSanitize($shopTagline) }}</div>
                <div class="company-sub">{{ $pdfSanitize(str_replace(["\r\n", "\r", "\n"], ', ', $shopAddress)) }}</div>
                <div class="company-sub">Telp / WhatsApp: {{ $shopPhone ?: '-' }}</div>
            </td>
            <td class="doc-col">
                <div class="doc-title">STRUK DIGITAL</div>
                <div class="doc-code">#{{ $transaction->transaction_code }}</div>
                <div class="status-badge {{ $statusClass }}">{{ $statusText }}</div>
            </td>
        </tr>
    </table>

    <!-- METADATA -->
    <table class="meta-table">
        <tr>
            <td style="padding-right: 6px;">
                <div class="meta-box">
                    <div class="meta-box-title">Detail Transaksi</div>
                    <table class="meta-row-table">
                        <tr>
                            <td class="meta-lbl">Tanggal</td>
                            <td class="meta-val">{{ $transaction->created_at->translatedFormat('d M Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Kasir</td>
                            <td class="meta-val">{{ $transaction->cashier->name ?? 'Kasir' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Cabang</td>
                            <td class="meta-val">{{ $transaction->branch ?? 'Pusat Cianjur' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Metode Bayar</td>
                            <td class="meta-val">{{ strtoupper($transaction->payment_method) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="padding-left: 6px;">
                <div class="{{ !empty($transaction->customer_name) || $paymentStatus === 'unpaid' ? 'meta-box-amber' : 'meta-box' }}">
                    <div class="meta-box-title">Identitas Pemesan</div>
                    <table class="meta-row-table">
                        <tr>
                            <td class="meta-lbl">Nama Pemesan</td>
                            <td class="meta-val">{{ $transaction->customer_name ?: 'Pelanggan Umum' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">No. HP / WA</td>
                            <td class="meta-val">{{ $transaction->customer_phone ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Status</td>
                            <td class="meta-val">{{ $statusText }}</td>
                        </tr>
                        @if($remainingAmt > 0)
                            <tr>
                                <td class="meta-lbl">Sisa Tagihan</td>
                                <td class="meta-val" style="color: #b45309;">Rp {{ number_format($remainingAmt, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="center" style="width: 24px;">No</th>
                <th>Produk / Barang</th>
                <th class="center" style="width: 58px;">Qty</th>
                <th class="right" style="width: 72px;">Harga</th>
                <th class="right" style="width: 78px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                @php
                    $qtyDisplay = rtrim(rtrim(number_format((float)($item['qty'] ?? 1), 2, ',', '.'), '0'), ',') . ' ' . ($item['unit'] ?? 'pcs');
                @endphp
                <tr class="{{ $idx % 2 === 1 ? 'even' : '' }}">
                    <td class="center">{{ $idx + 1 }}</td>
                    <td class="bold">{{ $item['name'] }}</td>
                    <td class="center">{{ $qtyDisplay }}</td>
                    <td class="right">
                        {{ !empty($item['unit_price']) ? 'Rp ' . number_format((float)$item['unit_price'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="right bold">
                        {{ !empty($item['total_price']) ? 'Rp ' . number_format((float)$item['total_price'], 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ $transaction->items_summary }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- RINGKASAN & RIWAYAT PEMBAYARAN -->
    <table class="bottom-table">
        <tr>
            <td style="width: 50%;">
                <div class="notes-box">
                    @if($transaction->installments->isNotEmpty())
                        <div class="notes-title">Riwayat Pembayaran / DP</div>
                        <table class="inst-row-table">
                            @foreach($transaction->installments as $inst)
                                <tr>
                                    <td>
                                        <span class="bold">{{ $inst->payment_method }}</span>
                                        <br><span style="color:#64748b; font-size:6.3pt;">{{ $inst->created_at->translatedFormat('d/m/Y H:i') }}@if($inst->note) - {{ $pdfSanitize($inst->note) }}@endif</span>
                                    </td>
                                    <td class="right bold" style="color:#047857; white-space:nowrap;">
                                        Rp {{ number_format((int)$inst->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="notes-title">Keterangan Resmi</div>
                        <div style="color:#475569; line-height:1.45;">
                            1. Struk digital ini merupakan bukti transaksi resmi yang diterbitkan sistem POS {{ $pdfSanitize($shopName) }}.<br>
                            2. Mohon periksa kembali kesesuaian barang dan nominal tagihan Anda.
                        </div>
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="summary-box">
                    <table class="summary-table">
                        <tr>
                            <td style="color:#64748b;">Subtotal</td>
                            <td class="right bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($discount > 0)
                            <tr>
                                <td style="color:#be123c;">Diskon</td>
                                <td class="right bold" style="color:#be123c;">-Rp {{ number_format($discount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($shippingCost > 0)
                            <tr>
                                <td style="color:#64748b;">Ongkos Kirim</td>
                                <td class="right bold">Rp {{ number_format($shippingCost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="grand-row">
                            <td>TOTAL TAGIHAN</td>
                            <td class="right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                        @if($transaction->payment_method === 'DP' || $transaction->installments->isNotEmpty() || $paymentStatus === 'unpaid')
                            <tr>
                                <td style="color:#047857; font-weight:bold;">Sudah Dibayar</td>
                                <td class="right bold" style="color:#047857;">Rp {{ number_format($paidAmount, 0, ',', '.') }}</td>
                            </tr>
                            @if($remainingAmt > 0)
                                <tr class="remaining-row">
                                    <td>SISA PELUNASAN</td>
                                    <td class="right">Rp {{ number_format($remainingAmt, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-box">
        Terima kasih telah berbelanja di <strong>{{ $pdfSanitize($shopName) }}</strong>. Simpan file PDF ini sebagai bukti transaksi resmi Anda.
        <div class="footer-verify">Kode Verifikasi: {{ $transaction->transaction_code }} | Dicetak Digital: {{ now()->translatedFormat('d M Y H:i') }} WIB</div>
    </div>
</body>
</html>
