<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Penjualan #{{ $transaction->transaction_code }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.4;
            background: #ffffff;
        }

        /* ─── KOP HEADER TABLE ─── */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-col {
            width: 70px;
            padding-right: 12px;
        }
        .company-logo {
            width: 65px;
            height: 65px;
            border-radius: 8px;
        }
        .company-logo-placeholder {
            width: 65px;
            height: 65px;
            border-radius: 8px;
            background-color: #047857;
            text-align: center;
            line-height: 65px;
            font-size: 22pt;
            font-weight: bold;
            color: #ffffff;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .company-tagline {
            font-size: 9.5pt;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .company-addr {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }
        .invoice-title-col {
            text-align: right;
            border-left: 2px solid #e2e8f0;
            padding-left: 15px;
            width: 180px;
        }
        .invoice-main-title {
            font-size: 18pt;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1;
        }
        .invoice-code {
            font-size: 10pt;
            font-weight: bold;
            color: #047857;
            margin-top: 4px;
        }

        /* ─── META INFO TABLE ─── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta-table td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .meta-box-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .meta-row-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .meta-row-table td {
            padding: 2px 0;
            border: none;
        }
        .meta-lbl {
            color: #64748b;
            width: 45%;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            width: 55%;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .status-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        /* ─── ITEMS TABLE ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #047857;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            padding: 7px 8px;
            border: 1px solid #047857;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }
        .items-table tr.even {
            background-color: #f8fafc;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }

        /* ─── BOTTOM SPLIT TABLE (PAYMENT + SUMMARY) ─── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .bottom-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            margin-right: 12px;
        }
        .payment-box-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #047857;
            margin-bottom: 4px;
        }
        .bank-num {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin: 2px 0;
        }
        .terms-text {
            font-size: 7.5pt;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
            line-height: 1.35;
        }

        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background-color: #ffffff;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .summary-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-lbl {
            color: #64748b;
            font-weight: 600;
        }
        .summary-val {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
        }
        .grand-row {
            background-color: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }
        .grand-lbl {
            font-weight: bold;
            font-size: 9.5pt;
            color: #0f172a;
            text-transform: uppercase;
        }
        .grand-val {
            text-align: right;
            font-weight: bold;
            font-size: 11pt;
            color: #047857;
        }
        .remaining-row {
            background-color: #fff1f2;
        }
        .remaining-lbl {
            font-weight: bold;
            color: #b91c1c;
        }
        .remaining-val {
            text-align: right;
            font-weight: bold;
            color: #e11d48;
            font-size: 9.5pt;
        }

        /* ─── SIGNATURE TABLE ─── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .sig-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 0 20px;
        }
        .sig-header {
            font-size: 9pt;
            font-weight: bold;
            color: #475569;
        }
        .sig-space {
            height: 60px;
        }
        .sig-line {
            border-bottom: 1px solid #0f172a;
            width: 180px;
            margin: 0 auto 4px auto;
        }
        .sig-name {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 8pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Kop Header -->
    <table class="kop-table">
        <tr>
            @php
                $logoPath = public_path('images/logo.png');
                $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            <td class="logo-col">
                @if($logoBase64)
                    <img class="company-logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                @else
                    <div class="company-logo-placeholder">PK</div>
                @endif
            </td>
            <td>
                <div class="company-name">{{ $settings['shop_name'] ?? 'PUSAT KURMA INDONESIA' }}</div>
                <div class="company-tagline">{{ $settings['shop_tagline'] ?? 'Supplier Kurma & Oleh-oleh Haji / Umroh' }}</div>
                <div class="company-addr">
                    {{ $settings['shop_address'] ?? 'Tanah Abang, Jakarta Pusat' }}<br>
                    Telp / WhatsApp: {{ $settings['shop_whatsapp'] ?? '0877-7160-7774' }}
                </div>
            </td>
            <td class="invoice-title-col">
                <div class="invoice-main-title">FAKTUR</div>
                <div class="invoice-code">#{{ $transaction->transaction_code }}</div>
            </td>
        </tr>
    </table>

    <!-- Info Pelanggan & Transaksi -->
    <table class="meta-table">
        <tr>
            <td style="padding-right: 8px;">
                <div class="meta-box">
                    <div class="meta-box-title">Kepada Yth. (Pelanggan)</div>
                    <table class="meta-row-table">
                        <tr>
                            <td class="meta-lbl">Nama Pelanggan:</td>
                            <td class="meta-val">{{ $transaction->customer_name }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">No. HP / WA:</td>
                            <td class="meta-val">{{ $transaction->customer_phone ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="padding-left: 8px;">
                <div class="meta-box">
                    <div class="meta-box-title">Detail Faktur & Status</div>
                    <table class="meta-row-table">
                        <tr>
                            <td class="meta-lbl">Tanggal Transaksi:</td>
                            <td class="meta-val">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Metode Bayar:</td>
                            <td class="meta-val">{{ $transaction->payment_method ?: 'Tunai' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-lbl">Status Pembayaran:</td>
                            <td class="meta-val">
                                @if($remaining <= 0)
                                    <span class="status-badge status-paid">LUNAS</span>
                                @else
                                    <span class="status-badge status-unpaid">TEMPO / BELUM LUNAS</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Barang -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="center">No</th>
                <th style="width: 47%;">Nama Produk / Barang</th>
                <th style="width: 12%;" class="center">Qty</th>
                <th style="width: 10%;" class="center">Satuan</th>
                <th style="width: 13%;" class="right">Harga</th>
                <th style="width: 13%;" class="right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                <tr class="{{ $index % 2 == 1 ? 'even' : '' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="bold">{{ $item['name'] }}</td>
                    <td class="center bold">{{ rtrim(rtrim(number_format($item['qty'], 3, ',', '.'), '0'), ',') }}</td>
                    <td class="center">{{ strtolower($item['unit']) === 'kg' ? 'Kg' : $item['unit'] }}</td>
                    <td class="right">{{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                    <td class="right bold">{{ number_format($item['total_price'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center" style="padding: 12px; color: #94a3b8;">Tidak ada rincian item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Ringkasan & Pembayaran -->
    <table class="bottom-table">
        <tr>
            <!-- Kolom Kiri: Transfer & Ketentuan -->
            <td style="width: 52%;">
                <div class="payment-box">
                    <div class="payment-box-title">Instruksi Pembayaran Transfer:</div>
                    <div style="font-size: 8.5pt; color: #334155;">
                        Bank BCA - Rekening Toko:<br>
                        <div class="bank-num">1831794211</div>
                        Atas Nama: <strong>Muhammad Irshad</strong>
                    </div>
                    <div class="terms-text">
                        <strong>Catatan:</strong><br>
                        - Mohon periksa barang saat diterima.<br>
                        - Tagihan tempo wajib dilunasi sebelum tanggal jatuh tempo.<br>
                        - Konfirmasi transfer via WA: {{ $settings['shop_whatsapp'] ?? '0877-7160-7774' }}
                    </div>
                </div>
            </td>

            <!-- Kolom Kanan: Rekap Angka -->
            <td style="width: 48%;">
                <div class="summary-box">
                    <table class="summary-table">
                        <tr>
                            <td class="summary-lbl">Total Kuantitas:</td>
                            <td class="summary-val">{{ collect($items)->sum('qty') }} item</td>
                        </tr>
                        <tr>
                            <td class="summary-lbl">Subtotal Barang:</td>
                            <td class="summary-val">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($transaction->discount > 0)
                            <tr>
                                <td class="summary-lbl" style="color: #e11d48;">Diskon:</td>
                                <td class="summary-val" style="color: #e11d48;">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($transaction->shipping_cost > 0)
                            <tr>
                                <td class="summary-lbl">Ongkos Kirim:</td>
                                <td class="summary-val">+Rp {{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="grand-row">
                            <td class="grand-lbl">GRAND TOTAL:</td>
                            <td class="grand-val">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="summary-lbl">Uang Diterima / DP:</td>
                            <td class="summary-val">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
                        </tr>
                        @if($remaining > 0)
                            <tr class="remaining-row">
                                <td class="remaining-lbl">SISA PIUTANG (TEMPO):</td>
                                <td class="remaining-val">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="summary-lbl" style="color: #15803d; font-weight: bold;">Sisa Tagihan:</td>
                                <td class="summary-val" style="color: #15803d;">Rp 0 (LUNAS)</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tanda Tangan -->
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-header">Tanda Terima / Pembeli</div>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $transaction->customer_name }}</div>
                <div class="sig-sub">Stempel / Tanda Tangan</div>
            </td>
            <td>
                <div class="sig-header">Hormat Kami / Pengirim</div>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $settings['shop_name'] ?? 'Pusat Kurma Indonesia' }}</div>
                <div class="sig-sub">Petugas: {{ $transaction->cashier->name ?? 'Admin' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
