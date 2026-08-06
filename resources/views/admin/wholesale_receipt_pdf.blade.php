<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Partai #{{ $transaction->transaction_code }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm 1.5cm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12pt;
            color: #000000;
            line-height: 1.45;
            width: 100%;
            padding-bottom: 270px; /* Space for the fixed footer at the bottom */
        }

        /* receipt container constrained to 90mm (small receipt size) and left-aligned with margins */
        .receipt-container {
            width: 90mm;
            text-align: left;
            margin-left: 10mm;
            margin-top: 12mm;
        }

        /* ─── HEADER ─── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .logo-cell {
            width: 56px;
            padding-right: 8px;
        }
        .company-logo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }
        .company-logo-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #059669;
            text-align: center;
            line-height: 48px;
            font-size: 15pt;
            font-weight: bold;
            color: #ffffff;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.35;
        }
        .company-addr {
            font-size: 10.5pt;
            color: #333333;
            margin-top: 2px;
            line-height: 1.35;
        }

        /* ─── DIVIDER ─── */
        .divider {
            border: none;
            border-top: 1px dashed #000000;
            margin: 6px 0;
            width: 100%;
        }

        /* ─── META ─── */
        .meta-date {
            font-size: 11pt;
            margin-top: 6px;
        }
        .meta-customer {
            font-size: 11.5pt;
            font-weight: bold;
            margin-top: 4px;
            line-height: 1.4;
        }

        /* ─── ITEMS TABLE ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table td {
            padding: 0;
            vertical-align: top;
            border: none;
        }
        .item-name {
            font-size: 12pt;
            font-weight: bold;
            padding-top: 5px;
            padding-bottom: 1px;
        }
        .item-detail-qty {
            font-size: 11pt;
            width: 25%;
            padding-left: 12px;
            white-space: nowrap;
        }
        .item-detail-price {
            font-size: 11pt;
            width: 35%;
            white-space: nowrap;
        }
        .item-detail-total {
            font-size: 11pt;
            font-weight: bold;
            width: 40%;
            text-align: right;
            white-space: nowrap;
        }

        /* ─── TOTALS TABLE ─── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .totals-table td {
            border: none;
            padding: 0;
        }
        .total-qty-cell {
            font-size: 11.5pt;
            font-weight: bold;
            vertical-align: top;
            width: 40%;
            padding-top: 4px;
        }
        .total-amounts-cell {
            width: 60%;
            vertical-align: top;
        }
        .amounts-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .amounts-inner td {
            padding: 2px 0;
            font-size: 11.5pt;
        }
        .lbl-col {
            font-weight: bold;
            padding-right: 5px;
        }
        .val-col {
            text-align: right;
            font-weight: bold;
        }
        .grand-total-val {
            font-size: 14pt;
            font-weight: bold;
            text-align: right;
        }

        /* ─── FOOTER (Fixed at the absolute bottom of the page) ─── */
        .footer-section {
            position: fixed;
            bottom: 0;
            left: 10mm;
            width: 90mm;
            text-align: center;
            font-size: 11pt;
            line-height: 1.5;
            color: #000000;
        }
        .footer-section .bca-number {
            font-size: 14pt;
            font-weight: bold;
            margin: 2px 0;
        }
        .footer-section .timestamp {
            font-size: 10pt;
            color: #555555;
            margin-top: 12px;
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <!-- ═══ HEADER ═══ -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @php
                        $logoPath = public_path('images/logo.png');
                        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                    @endphp
                    @if($logoBase64)
                        <img class="company-logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                    @else
                        <div class="company-logo-placeholder">PK</div>
                    @endif
                </td>
                <td>
                    <div class="company-name">Supliyer Kurma &amp; Oleh oleh Haji / Umroh</div>
                    <div class="company-addr">
                        Tanah Abang<br>
                        Jakarta<br>
                        087771607774
                    </div>
                </td>
            </tr>
        </table>

        <!-- ═══ DATE ═══ -->
        <div class="meta-date">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>

        <hr class="divider">

        <!-- ═══ CUSTOMER ═══ -->
        <div class="meta-customer">
            {{ $transaction->customer_name }}<br>
            {{ $transaction->customer_phone ?? $transaction->branch ?? '' }}
        </div>

        <hr class="divider">

        <!-- ═══ ITEMS ═══ -->
        <table class="items-table">
            @foreach ($items as $index => $item)
                {{-- Item name row --}}
                <tr>
                    <td colspan="3" class="item-name">{{ $index + 1 }}) {{ $item['name'] }}</td>
                </tr>
                {{-- Item detail row --}}
                <tr>
                    <td class="item-detail-qty">{{ $item['qty'] }} {{ $item['unit'] }}</td>
                    <td class="item-detail-price">@ {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                    <td class="item-detail-total">{{ number_format($item['total_price'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <hr class="divider" style="margin: 10px 0 6px 0;">

        <!-- ═══ TOTALS ═══ -->
        <table class="totals-table">
            <tr>
                <td class="total-qty-cell">Total Qty: {{ collect($items)->sum('qty') }}</td>
                <td class="total-amounts-cell">
                    <table class="amounts-inner">
                        @if($transaction->discount > 0)
                            <tr>
                                <td class="lbl-col" style="color:#e11d48;">DISKON</td>
                                <td class="val-col" style="color:#e11d48;">
                                    -{{ number_format($transaction->discount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        @if($transaction->shipping_cost > 0)
                            <tr>
                                <td class="lbl-col">ONGKIR</td>
                                <td class="val-col">{{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="lbl-col">TOTAL</td>
                            <td class="grand-total-val">{{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $displayPaid = isset($totalPaid) ? $totalPaid : (($transaction->payment_status ?? 'paid') === 'paid' ? $transaction->total_price : 0);
                            $displayRemaining = isset($remaining) ? $remaining : (($transaction->payment_status ?? 'paid') === 'paid' ? 0 : $transaction->total_price);
                        @endphp
                        <tr>
                            <td class="lbl-col" style="font-weight:normal;">BAYAR</td>
                            <td class="val-col" style="font-weight:normal;">
                                {{ number_format($displayPaid, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl-col">SISA</td>
                            <td class="val-col">
                                {{ number_format($displayRemaining, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- ═══ FOOTER (Fixed at the bottom of the page) ═══ -->
    <div class="footer-section">
        <hr class="divider" style="margin: 0 0 10px 0;">
        <div style="font-weight:bold;">*** Thank You ***</div>
        <div style="font-weight:bold;">kami tunggu orderan berikutnya</div>
        <br>
        <div style="font-weight:bold;">Barakallahufikum</div>
        <br>
        <div style="font-weight:bold;">Pembayaran via transfer</div>
        <div style="font-weight:bold;">ke rekening BCA-</div>
        <div class="bca-number">1831794211</div>
        <div style="font-weight:bold;">an. Muhammad Irshad</div>
        <div class="timestamp">{{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

</body>
</html>
