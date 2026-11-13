<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Penjualan #{{ $transaction->transaction_code }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            background: #f1f5f9;
            line-height: 1.45;
            padding: 20px 10px 40px;
        }

        /* Top control bar (Screen only) */
        .preview-controls {
            position: sticky;
            top: 15px;
            max-width: 210mm;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #cbd5e1;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 100;
            border-radius: 12px;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08);
        }

        .preview-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-primary {
            background: #047857;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(4, 120, 87, 0.25);
        }
        .btn-primary:hover {
            background: #065f46;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn-amber {
            background: #d97706;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(217, 119, 6, 0.25);
        }
        .btn-amber:hover {
            background: #b45309;
        }

        /* Faktur Container (A4 Portrait Standard) */
        .invoice-sheet {
            background: #ffffff;
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm 20mm 20mm 20mm;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* ─── HEADER SECTION ─── */
        .header-grid {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
        }

        .company-logo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 12px;
        }

        .company-logo-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background-color: #047857;
            text-align: center;
            line-height: 72px;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1px;
        }

        .company-info {
            padding-left: 4px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: 800;
            color: #047857;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .company-tagline {
            font-size: 10pt;
            font-weight: 600;
            color: #475569;
            margin-top: 3px;
        }

        .company-address {
            font-size: 9.5pt;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.35;
        }

        .invoice-title-block {
            text-align: right;
            border-left: 2px solid #e2e8f0;
            padding-left: 20px;
        }

        .invoice-badge-title {
            font-size: 20pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .invoice-subcode {
            font-size: 11pt;
            font-weight: 700;
            color: #047857;
            margin-top: 4px;
        }

        /* ─── META INFO SECTION ─── */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 18px;
            margin-bottom: 20px;
        }

        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .meta-card-title {
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 8px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-bottom: 4px;
        }
        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            color: #64748b;
            font-weight: 500;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-paid {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .status-unpaid {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        /* ─── ITEMS TABLE ─── */
        .table-wrapper {
            margin-bottom: 24px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .items-table th {
            background-color: #047857;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.6px;
            padding: 9px 12px;
            border: 1px solid #047857;
        }

        .items-table td {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .col-center {
            text-align: center;
        }
        .col-right {
            text-align: right;
        }
        .col-bold {
            font-weight: 700;
        }

        /* ─── SUMMARY & PAYMENT SECTION ─── */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: start;
            margin-bottom: 28px;
        }

        .payment-info-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .payment-info-title {
            font-size: 9pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #047857;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bank-details {
            font-size: 9.5pt;
            color: #334155;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .bank-account-num {
            font-size: 13pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1px;
            margin: 2px 0;
        }

        .terms-note {
            font-size: 8.5pt;
            color: #64748b;
            line-height: 1.4;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }

        .summary-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .summary-table td {
            padding: 8px 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-label {
            color: #64748b;
            font-weight: 600;
        }

        .summary-val {
            text-align: right;
            font-weight: 700;
            color: #1e293b;
        }

        .grand-total-row {
            background-color: #f1f5f9;
            border-top: 2px solid #cbd5e1 !important;
            border-bottom: 2px solid #cbd5e1 !important;
        }
        .grand-total-row td {
            padding: 10px 14px;
        }
        .grand-total-label {
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .grand-total-val {
            font-size: 12.5pt;
            font-weight: 900;
            color: #047857;
            text-align: right;
        }

        .remaining-row {
            background-color: #fff1f2;
        }
        .remaining-val {
            color: #e11d48;
            font-size: 11pt;
            font-weight: 800;
        }

        /* ─── SIGNATURE SECTION ─── */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: center;
            margin-top: auto;
            padding-top: 20px;
        }

        .sig-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 110px;
        }

        .sig-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #475569;
        }

        .sig-line {
            width: 75%;
            border-bottom: 1px solid #0f172a;
            margin-top: 65px;
            margin-bottom: 4px;
        }

        .sig-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: #0f172a;
        }

        .sig-sub {
            font-size: 8.5pt;
            color: #64748b;
        }

        /* ─── PRINT MEDIA SPECIFIC ─── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 15mm 15mm 15mm;
            }

            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                border: none !important;
            }

            .header-grid {
                border-bottom: 2px solid #000000 !important;
            }

            .company-name {
                color: #000000 !important;
            }

            .invoice-badge-title {
                color: #000000 !important;
            }

            .items-table th {
                background-color: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #64748b !important;
            }

            .items-table td {
                border: 1px solid #cbd5e1 !important;
                color: #000000 !important;
            }

            .grand-total-val {
                color: #000000 !important;
            }

            .meta-card, .payment-info-box, .summary-card {
                border: 1px solid #cbd5e1 !important;
                background: #ffffff !important;
            }

            .grand-total-row {
                background-color: #f8fafc !important;
            }
        }
    </style>
</head>
<body>

    <!-- Control bar (Screen Only) -->
    <div class="preview-controls no-print">
        <div class="preview-title">
            <svg style="width: 20px; height: 20px; color: #047857;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Pratinjau Faktur Penjualan (A4 Portrait)</span>
        </div>
        <div class="btn-group">
            <button onclick="window.history.back()" class="btn btn-secondary">
                Kembali
            </button>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-amber">
                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Unduh PDF (A4)
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Faktur
            </button>
        </div>
    </div>

    <!-- Faktur Paper Sheet -->
    <div class="invoice-sheet">
        <div>
            <!-- Kop Perusahaan & Judul Faktur -->
            <div class="header-grid">
                @php
                    $logoPath = public_path('images/logo.png');
                    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                @endphp
                <div>
                    @if($logoBase64)
                        <img class="company-logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                    @else
                        <div class="company-logo-placeholder">PK</div>
                    @endif
                </div>
                <div class="company-info">
                    <div class="company-name">{{ $settings['shop_name'] ?? 'PUSAT KURMA INDONESIA' }}</div>
                    <div class="company-tagline">{{ $settings['shop_tagline'] ?? 'Supplier Kurma & Oleh-oleh Haji / Umroh' }}</div>
                    <div class="company-address">
                        {{ $settings['shop_address'] ?? 'Tanah Abang, Jakarta Pusat' }}<br>
                        Telp / WhatsApp: {{ $settings['shop_whatsapp'] ?? '0877-7160-7774' }}
                    </div>
                </div>
                <div class="invoice-title-block">
                    <div class="invoice-badge-title">FAKTUR</div>
                    <div class="invoice-subcode">#{{ $transaction->transaction_code }}</div>
                </div>
            </div>

            <!-- Informasi Transaksi & Pelanggan -->
            <div class="meta-grid">
                <!-- Info Pelanggan -->
                <div class="meta-card">
                    <div class="meta-card-title">Kepada Yth. (Pelanggan)</div>
                    <div class="meta-row">
                        <span class="meta-label">Nama Toko/Penerima:</span>
                        <span class="meta-val">{{ $transaction->customer_name }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">No. Telepon / WhatsApp:</span>
                        <span class="meta-val">{{ $transaction->customer_phone ?: '-' }}</span>
                    </div>
                </div>

                <!-- Info Faktur -->
                <div class="meta-card">
                    <div class="meta-card-title">Detail Faktur & Pembayaran</div>
                    <div class="meta-row">
                        <span class="meta-label">Tanggal Transaksi:</span>
                        <span class="meta-val">{{ $transaction->created_at->format('d/m/Y H:i') }} WIB</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Metode Pembayaran:</span>
                        <span class="meta-val">{{ $transaction->payment_method ?: 'Tunai' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Status Tagihan:</span>
                        <span class="meta-val">
                            @if($remaining <= 0)
                                <span class="status-pill status-paid">LUNAS</span>
                            @else
                                <span class="status-pill status-unpaid">TEMPO / BELUM LUNAS</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabel Rincian Barang -->
            <div class="table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;" class="col-center">No</th>
                            <th style="width: 45%;">Nama Produk / Barang</th>
                            <th style="width: 12%;" class="col-center">Kuantitas</th>
                            <th style="width: 10%;" class="col-center">Satuan</th>
                            <th style="width: 14%;" class="col-right">Harga Satuan</th>
                            <th style="width: 14%;" class="col-right">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $item)
                            <tr>
                                <td class="col-center">{{ $index + 1 }}</td>
                                <td class="col-bold">{{ $item['name'] }}</td>
                                <td class="col-center col-bold">{{ rtrim(rtrim(number_format($item['qty'], 3, ',', '.'), '0'), ',') }}</td>
                                <td class="col-center" style="text-transform: capitalize;">{{ strtolower($item['unit']) === 'kg' ? 'Kg' : $item['unit'] }}</td>
                                <td class="col-right">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                <td class="col-right col-bold">Rp {{ number_format($item['total_price'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="col-center" style="padding: 16px; color: #94a3b8;">Tidak ada rincian item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Ringkasan Keuangan & Info Pembayaran -->
            <div class="bottom-grid">
                <!-- Kolom Kiri: Instruksi Rekening & Catatan -->
                <div class="payment-info-box">
                    <div class="payment-info-title">
                        <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Pembayaran Transfer Bank
                    </div>
                    <div class="bank-details">
                        Bank BCA - Rekening Resmi:<br>
                        <div class="bank-account-num">1831794211</div>
                        Atas Nama: <strong>Muhammad Irshad</strong>
                    </div>
                    <div class="terms-note">
                        <strong>Ketentuan:</strong><br>
                        1. Barang yang diterima mohon diperiksa secara teliti.<br>
                        2. Untuk pembayaran tempo, mohon melunasi sebelum batas waktu yang disepakati.<br>
                        3. Bukti transfer mohon dikirimkan via WhatsApp ke {{ $settings['shop_whatsapp'] ?? '0877-7160-7774' }}.
                    </div>
                </div>

                <!-- Kolom Kanan: Rincian Angka -->
                <div class="summary-card">
                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">Total Item / Qty:</td>
                            <td class="summary-val">{{ collect($items)->sum('qty') }} item</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Subtotal Nilai Barang:</td>
                            <td class="summary-val">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($transaction->discount > 0)
                            <tr>
                                <td class="summary-label" style="color: #e11d48;">Potongan Diskon:</td>
                                <td class="summary-val" style="color: #e11d48;">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($transaction->shipping_cost > 0)
                            <tr>
                                <td class="summary-label">Biaya Pengiriman (Ongkir):</td>
                                <td class="summary-val">+Rp {{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="grand-total-row">
                            <td class="grand-total-label">Grand Total Faktur:</td>
                            <td class="grand-total-val">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Uang Diterima / DP:</td>
                            <td class="summary-val">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
                        </tr>
                        @if($remaining > 0)
                            <tr class="remaining-row">
                                <td class="summary-label" style="color: #b91c1c; font-weight: 800;">SISA PIUTANG (TEMPO):</td>
                                <td class="summary-val remaining-val">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="summary-label" style="color: #15803d; font-weight: 800;">Sisa Tagihan:</td>
                                <td class="summary-val" style="color: #15803d; font-weight: 800;">Rp 0 (LUNAS)</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Tanda Tangan Resmi -->
        <div class="signatures-grid">
            <div class="sig-box">
                <div class="sig-title">Tanda Terima / Pembeli</div>
                <div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $transaction->customer_name }}</div>
                    <div class="sig-sub">Stempel / Tanda Tangan</div>
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Hormat Kami / Pengirim</div>
                <div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $settings['shop_name'] ?? 'Pusat Kurma Indonesia' }}</div>
                    <div class="sig-sub">Petugas: {{ $transaction->cashier->name ?? 'Admin' }}</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
