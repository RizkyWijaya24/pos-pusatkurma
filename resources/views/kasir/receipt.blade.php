<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk POS #{{ $transaction->transaction_code }}</title>
    <style>
        /* Modern, clean styling for screen rendering on Android & mobile browsers */
        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #1e293b;
            font-family: 'Courier New', Courier, monospace;
            font-size: 9.5px;
            line-height: 1.35;
        }
        
        /* Floating mobile control bar for screen preview */
        .preview-controls {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            display: flex;
            gap: 10px;
            justify-content: center;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-family: sans-serif;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: #059669;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #047857;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
        }
        
        /* Receipt container optimized strictly for small 58mm paper */
        .receipt-container {
            background: #ffffff;
            width: 100%;
            max-width: 48mm; /* Strictly matches 58mm paper width boundaries */
            margin: 15px auto;
            padding: 10px 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            box-sizing: border-box;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .brand-title {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #000;
        }
        .brand-subtitle {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #000;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
            height: 0;
        }
        
        .meta-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
            color: #000;
        }
        
        .meta-table td {
            font-size: 8.5px;
            padding: 1px 0;
        }
        
        .items-table th {
            text-align: left;
            font-size: 8.5px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
            font-weight: bold;
        }
        
        .items-table td {
            font-size: 8.5px;
            padding: 3px 0;
            vertical-align: top;
        }
        
        .totals-table td {
            font-size: 8.5px;
            padding: 1.5px 0;
        }
        
        .totals-table .grand-total {
            font-size: 10px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
        }

        .footer {
            font-size: 8px;
            margin-top: 12px;
            text-align: center;
            line-height: 1.35;
            color: #000;
        }

        .barcode-container {
            margin-top: 10px;
            letter-spacing: 1.5px;
            font-size: 8.5px;
            color: #000;
        }

        /* Screen vs Print CSS controls */
        .no-print {
            display: flex;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                background: #fff !important;
                color: #000 !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                padding: 2mm 0mm !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Navigation Bar (Screen Only) -->
    <div class="preview-controls no-print">
        <button onclick="window.history.back()" class="btn btn-secondary">
            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </button>
        <button onclick="window.print()" class="btn btn-primary">
            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" />
            </svg>
            Cetak Struk
        </button>
    </div>

    <!-- Printable Receipt Structure -->
    <div class="receipt-container">
        <div class="text-center">
            <div class="brand-title">Pusat Kurma Al karim</div>
            <div class="brand-subtitle">{{ $transaction->branch ?? 'Cabang Cianjur' }}</div>
        </div>
        
        <div class="divider"></div>
        
        <table class="meta-table">
            <tr>
                <td>No. Struk:</td>
                <td class="text-right font-bold">{{ $transaction->transaction_code }}</td>
            </tr>
            <tr>
                <td>Tanggal  :</td>
                <td class="text-right">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir    :</td>
                <td class="text-right">{{ $transaction->cashier->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Metode   :</td>
                <td class="text-right font-bold uppercase">{{ $transaction->payment_method }}</td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 32mm;">Item</th>
                    <th class="text-right" style="width: 16mm;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td colspan="2" style="font-size: 8.5px; padding: 4px 0; border-bottom: 1px dotted #eee;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <span class="font-bold">{{ $item['name'] }}</span>
                                    <span style="font-size: 8px; color: #444; margin-left: 2px;">{{ $item['qty'] }} {{ $item['unit'] }}</span>
                                </span>
                                <span class="font-bold">
                                    @if (isset($item['total_price']) && $item['total_price'] > 0)
                                        Rp {{ number_format($item['total_price'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            @if (isset($item['unit_price']) && $item['unit_price'] > 0)
                                <div style="font-size: 7.5px; color: #666; margin-top: 1px;">
                                    (Harga: Rp {{ number_format($item['unit_price'], 0, ',', '.') }}/{{ $item['unit'] }})
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="divider"></div>
        
        <table class="totals-table">
            @php
                $total = $transaction->total_price;
                $discount = $transaction->discount ?? 0;
                $subtotal = $total + $discount;
            @endphp
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($discount > 0)
                <tr>
                    <td>Diskon:</td>
                    <td class="text-right">-Rp {{ number_format($discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="font-bold">TOTAL BELANJA:</td>
                <td class="text-right font-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <div class="footer">
            *** TERIMA KASIH ***<br>
            Silahkan Datang Kembali<br>
            .<br>
            
            <div class="barcode-container text-center">
                *{{ $transaction->transaction_code }}*<br>
                <span style="font-size: 11px; font-weight: normal; letter-spacing: -0.5px;">||| || ||| || ||| || ||| || |||</span>
            </div>
        </div>
    </div>

    <!-- Autostart print only when loaded inside iframe context -->
    <script>
        window.onload = function() {
            // Check if loaded inside an iframe
            if (window.self !== window.top) {
                // Focus and print immediately
                setTimeout(function() {
                    window.focus();
                    window.print();
                }, 100);
            }
        }
    </script>
</body>
</html>
