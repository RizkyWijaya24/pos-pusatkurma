<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Partai #{{ $transaction->transaction_code }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #334155;
            margin: 0;
            padding: 20px;
            background: #f8fafc;
            line-height: 1.5;
        }
        
        .preview-controls {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 8px;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: #047857;
            color: #ffffff;
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
        
        .invoice-container {
            background: #ffffff;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
        }
        
        .header-grid {
            display: grid;
            grid-template-cols: 1fr 1fr;
            gap: 20px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: 800;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .invoice-title-container {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 22px;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .invoice-meta {
            margin-top: 10px;
            font-size: 12px;
            color: #475569;
            display: inline-block;
            text-align: left;
        }
        
        .invoice-meta table {
            border-collapse: collapse;
        }
        
        .invoice-meta td {
            padding: 2px 0 2px 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-cols: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .info-section-title {
            font-size: 11px;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .customer-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }
        
        .customer-details {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
            white-space: pre-wrap;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .items-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        
        .items-table tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }
        
        .totals-grid {
            display: grid;
            grid-template-cols: 1.2fr 0.8fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .payment-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            font-size: 12px;
        }
        
        .payment-title {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 6px 0;
            font-size: 13px;
            color: #475569;
        }
        
        .totals-table .grand-total-row td {
            font-size: 16px;
            font-weight: bold;
            color: #064e3b;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 6px;
        }
        
        .signature-grid {
            display: grid;
            grid-template-cols: 1fr 1fr;
            gap: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        .signature-title {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 60px;
        }
        
        .signature-line {
            border-top: 1px solid #cbd5e1;
            width: 180px;
            margin: 0 auto;
            padding-top: 5px;
            font-weight: bold;
            color: #0f172a;
        }
        
        .no-print {
            display: flex;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000 !important;
            }
            .invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                padding: 0 !important;
                border: none !important;
            }
            .items-table th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .payment-info {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Control bar (Screen Only) -->
    <div class="preview-controls no-print">
        <button onclick="window.history.back()" class="btn btn-secondary">
            Kembali
        </button>
        <button onclick="window.print()" class="btn btn-primary">
            Cetak Nota A4
        </button>
    </div>

    <!-- Invoice Container -->
    <div class="invoice-container">
        <!-- Header -->
        <div class="header-grid">
            <div>
                <div class="company-name">Pusat Kurma Cianjur</div>
                <div class="company-details">
                    Grosir & Eceran Kurma Premium, Herbal, dan Oleh-Oleh Haji<br>
                    Cianjur, Jawa Barat, Indonesia<br>
                    Telepon: +62 812-3456-7890 | Email: info@pusatkurma.com
                </div>
            </div>
            <div class="invoice-title-container">
                <div class="invoice-title">Nota Penjualan</div>
                <div class="invoice-meta">
                    <table>
                        <tr>
                            <td class="font-bold">No. Nota</td>
                            <td>: {{ $transaction->transaction_code }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tanggal</td>
                            <td>: {{ $transaction->created_at->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Metode</td>
                            <td style="text-transform: uppercase;">: {{ $transaction->payment_method }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Admin</td>
                            <td>: {{ $transaction->cashier->name ?? 'Administrator' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div>
                <div class="info-section-title">Informasi Penerima</div>
                <div class="customer-name">{{ $transaction->customer_name }}</div>
                <div class="customer-details">
                    @if($transaction->customer_phone)
                        WhatsApp: {{ $transaction->customer_phone }}<br>
                    @endif
                    Status Pembayaran: <strong style="text-transform: uppercase;">{{ $transaction->payment_method }}</strong>
                </div>
            </div>
            <div>
                <div class="info-section-title">Asal Barang / Cabang</div>
                <div class="customer-name">{{ $transaction->branch ?? 'Pusat Cianjur' }}</div>
                <div class="customer-details">
                    Barang dikirim dari gudang utama.<br>
                    Status Nota: <strong>Partai / Grosir</strong>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 55%;">Nama Barang</th>
                    <th style="width: 10%; text-align: center;">Jumlah</th>
                    <th style="width: 15%; text-align: right;">Harga Satuan</th>
                    <th style="width: 15%; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $item['name'] }}</td>
                        <td style="text-align: center;">{{ $item['qty'] }} {{ $item['unit'] }}</td>
                        <td style="text-align: right;">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: bold;">Rp {{ number_format($item['total_price'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Grid -->
        <div class="totals-grid">
            <div class="payment-info">
                <div class="payment-title">Informasi Pembayaran / Transfer:</div>
                Rekening Resmi Pusat Kurma Premium:<br>
                <strong>Bank Mandiri:</strong> 182-000-888-9990 a/n Pusat Kurma Indonesia<br>
                <strong>Bank BCA:</strong> 379-000-777-1110 a/n Rizky Wijaya<br>
                <div style="margin-top: 10px; font-size: 11px; color: #64748b; font-style: italic;">
                    *Harap sertakan bukti transfer yang sah dan tunjukkan kepada petugas saat pengambilan/pengiriman barang.
                </div>
            </div>
            <div>
                <table class="totals-table">
                    <tr>
                        <td>Subtotal Barang:</td>
                        <td style="text-align: right; font-weight: bold;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($transaction->discount > 0)
                        <tr style="color: #e11d48;">
                            <td>Potongan Diskon:</td>
                            <td style="text-align: right; font-weight: bold;">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($transaction->shipping_cost > 0)
                        <tr>
                            <td>Ongkos Kirim:</td>
                            <td style="text-align: right; font-weight: bold;">Rp {{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td>Total Akhir:</td>
                        <td style="text-align: right;">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signature Grid -->
        <div class="signature-grid">
            <div>
                <div class="signature-title">Tanda Terima Pelanggan,</div>
                <div class="signature-line">(...........................................)</div>
            </div>
            <div>
                <div class="signature-title">Hormat Kami,</div>
                <div class="signature-line">{{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

</body>
</html>
