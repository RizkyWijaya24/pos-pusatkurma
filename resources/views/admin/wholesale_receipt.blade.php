<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Partai #{{ $transaction->transaction_code }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12.5px;
            color: #000000;
            margin: 0;
            padding: 20px;
            background: #f8fafc;
            line-height: 1.4;
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
            max-width: 420px;
            margin: 0 auto;
            padding: 25px 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
        }

        .header-section {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .company-logo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .company-logo-placeholder {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background-color: #059669;
            text-align: center;
            line-height: 55px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            flex-shrink: 0;
        }

        .company-title {
            font-size: 14.5px;
            font-weight: bold;
            line-height: 1.3;
        }

        .company-subtitle {
            font-size: 12px;
            color: #333333;
            margin-top: 3px;
        }
        
        .divider {
            border: 0;
            border-top: 1px solid #000000;
            margin: 8px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            line-height: 1.5;
            color: #000000;
        }

        .totals-table {
            width: 100%;
            font-size: 12.5px;
            border-collapse: collapse;
            color: #000000;
        }

        .footer-section {
            text-align: center;
            font-size: 12px;
            line-height: 1.5;
            color: #000000;
            margin-top: 35px;
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
                color: #000000 !important;
            }
            .invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                padding: 0 !important;
                border: none !important;
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
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-primary" style="background-color: #d97706; text-decoration: none;">
            Unduh PDF
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            Cetak Nota
        </button>
    </div>

    <!-- Invoice Container -->
    <div class="invoice-container">
        <!-- Header Section -->
        <div class="header-section">
            @php
                $logoPath = public_path('images/logo.png');
                $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
                <img class="company-logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
            @else
                <div class="company-logo-placeholder">PK</div>
            @endif
            <div>
                <div class="company-title">Supliyer Kurma & Oleh oleh Haji / Umroh</div>
                <div class="company-subtitle">
                    Tanah Abang<br>
                    Jakarta<br>
                    087771607774
                </div>
            </div>
        </div>

        <div style="font-size: 12.5px; font-weight: normal; margin-top: 10px;">
            {{ $transaction->created_at->format('d/m/Y H:i') }}
        </div>

        <hr class="divider">

        <div style="font-size: 12.5px; font-weight: bold; line-height: 1.4;">
            <div>{{ $transaction->customer_name }}</div>
            <div>{{ $transaction->customer_phone ?? $transaction->branch ?? 'Cianjur' }}</div>
        </div>

        <hr class="divider">

        <!-- Items List -->
        <table class="items-table">
            @foreach ($items as $index => $item)
                <tr>
                    <td colspan="3" style="font-weight: bold; padding-top: 6px;">{{ $index + 1 }}) {{ $item['name'] }}</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding-left: 15px; vertical-align: top;">{{ $item['qty'] }} {{ $item['unit'] }}</td>
                    <td style="width: 35%; vertical-align: top;">@ {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                    <td style="width: 40%; text-align: right; font-weight: bold; vertical-align: top;">{{ number_format($item['total_price'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <hr class="divider" style="margin: 12px 0 8px 0;">

        <!-- Totals Table -->
        <table class="totals-table">
            <tr>
                <td style="width: 45%; font-weight: bold; vertical-align: top; padding-top: 4px;">
                    Total Qty: {{ collect($items)->sum('qty') }}
                </td>
                <td style="width: 55%; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        @if($transaction->discount > 0)
                            <tr>
                                <td style="font-weight: bold; color: #e11d48; padding-bottom: 4px;">DISKON</td>
                                <td style="text-align: right; font-weight: bold; color: #e11d48; padding-bottom: 4px;">-{{ number_format($transaction->discount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($transaction->shipping_cost > 0)
                            <tr>
                                <td style="font-weight: bold; padding-bottom: 4px;">ONGKIR</td>
                                <td style="text-align: right; font-weight: bold; padding-bottom: 4px;">{{ number_format($transaction->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold; padding-bottom: 4px;">TOTAL</td>
                            <td style="text-align: right; font-weight: bold; font-size: 14px; padding-bottom: 4px;">{{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $displayPaid = isset($totalPaid) ? $totalPaid : (($transaction->payment_status ?? 'paid') === 'paid' ? $transaction->total_price : 0);
                            $displayRemaining = isset($remaining) ? $remaining : (($transaction->payment_status ?? 'paid') === 'paid' ? 0 : $transaction->total_price);
                        @endphp
                        <tr>
                            <td style="padding-bottom: 4px;">BAYAR</td>
                            <td style="text-align: right; padding-bottom: 4px;">
                                {{ number_format($displayPaid, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding-bottom: 4px;">SISA</td>
                            <td style="text-align: right; font-weight: bold; padding-bottom: 4px;">
                                {{ number_format($displayRemaining, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <hr class="divider" style="margin: 8px 0 15px 0;">

        <!-- Footer Section -->
        <div class="footer-section">
            <div style="font-weight: bold;">*** Thank You ***</div>
            <div style="font-weight: bold;">kami tunggu orderan berikutnya 🥰</div>
            <div style="font-weight: bold; margin-top: 10px;">Barakallahufikum 🙏🙏</div>
            <div style="font-weight: bold; margin-top: 12px;">Pembayaran via transfer</div>
            <div style="font-weight: bold;">ke rekening BCA-</div>
            <div style="font-weight: 900; font-size: 14.5px; margin: 3px 0;">1831794211</div>
            <div style="font-weight: bold;">an. Muhammad Irshad</div>
            <div style="font-size: 10.5px; color: #555555; margin-top: 20px;">
                {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

</body>
</html>
