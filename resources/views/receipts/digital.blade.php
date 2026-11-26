<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Digital #{{ $transaction->transaction_code }} - Pusat Kurma</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            padding: 16px 12px 48px;
            line-height: 1.45;
        }
        .actions-bar {
            max-width: 440px;
            margin: 0 auto 14px;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: #047857;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(4, 120, 87, 0.25);
        }
        .btn-primary:hover {
            background: #065f46;
        }
        .btn-secondary {
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }
        .receipt-card {
            max-width: 440px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .receipt-header {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            color: #ffffff;
            padding: 24px 20px 20px;
            text-align: center;
        }
        .brand-logo {
            height: 48px;
            width: auto;
            margin: 0 auto 10px;
            display: block;
            border-radius: 10px;
            background: #ffffff;
            padding: 4px;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }
        .brand-branch {
            font-size: 12px;
            color: #a7f3d0;
            font-weight: 600;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .receipt-body {
            padding: 20px;
        }
        .meta-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 4px 0;
        }
        .meta-label {
            color: #64748b;
            font-weight: 500;
        }
        .meta-val {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }
        .customer-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 16px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .section-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .items-list {
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px dashed #cbd5e1;
            padding: 10px 0;
            margin-bottom: 14px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 7px 0;
        }
        .item-row + .item-row {
            border-top: 1px dotted #e2e8f0;
        }
        .item-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .item-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .item-price {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #475569;
            padding: 4px 0;
        }
        .summary-row.total {
            margin-top: 6px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .payment-box {
            margin-top: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 12px;
        }
        .payment-box.has-balance {
            background: #fffbeb;
            border-color: #fcd34d;
        }
        .installment-item {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 5px 0;
            border-top: 1px dotted #cbd5e1;
            font-size: 11px;
        }
        .remaining-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d97706;
            font-size: 14px;
            font-weight: 800;
            color: #b45309;
        }
        .receipt-footer {
            text-align: center;
            padding: 16px 20px 22px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
        }
        .code-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: 100%;
            }
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
        'unpaid'    => $transaction->payment_method === 'DP' ? 'DP / BELUM LUNAS' : 'TEMPO / BELUM LUNAS',
        default     => 'LUNAS',
    };
    $statusClass = match ($paymentStatus) {
        'cancelled' => 'status-cancelled',
        'unpaid'    => 'status-unpaid',
        default     => 'status-paid',
    };

    $logoPath   = public_path('images/logo.png');
    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $rawAddress  = (string) ($settings['shop_address'] ?? '');
    $isDummySeed = str_contains($rawAddress, 'Jl. Contoh No. 123');
    $shopName    = ($isDummySeed || empty($settings['shop_name'])) ? 'PUSAT KURMA PREMIUM CIANJUR' : $settings['shop_name'];
    $shopTagline = !empty($settings['shop_tagline']) ? $settings['shop_tagline'] : 'Cianjur • Premium Dates';
    $shopAddress = ($isDummySeed || $rawAddress === '') ? 'Jl. Dr. Muwardi No.48, Muka, Kec. Cianjur, Kabupaten Cianjur, Jawa Barat 43215' : $rawAddress;
    $shopPhone   = ($isDummySeed || empty($settings['shop_whatsapp']) || $settings['shop_whatsapp'] === '6281234567890')
        ? '082396572250'
        : $settings['shop_whatsapp'];
@endphp

    <div class="actions-bar no-print">
        <button type="button" onclick="window.print()" class="btn btn-secondary">
            Cetak / Simpan PDF
        </button>
        <button type="button" onclick="downloadReceiptImage()" class="btn btn-primary">
            Unduh Gambar Struk
        </button>
    </div>

    <div class="receipt-card" id="digital-receipt-card">
        <div class="receipt-header">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" class="brand-logo" alt="Logo Pusat Kurma">
            @endif
            <div class="brand-name">{{ strtoupper($shopName) }}</div>
            <div class="brand-branch">{{ $shopTagline }}</div>
            <div class="brand-branch" style="font-size: 11px; margin-top: 3px;">{{ str_replace(["\r\n", "\r", "\n"], ', ', $shopAddress) }}</div>
            <div class="brand-branch" style="font-size: 11px;">Telp / WhatsApp: {{ $shopPhone }}</div>
            <div class="status-badge {{ $statusClass }}">{{ $statusText }}</div>
        </div>

        <div class="receipt-body">
            <div class="meta-box">
                <div class="meta-row">
                    <span class="meta-label">No. Struk</span>
                    <span class="meta-val" style="font-family: monospace;">{{ $transaction->transaction_code }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Tanggal</span>
                    <span class="meta-val">{{ $transaction->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Kasir</span>
                    <span class="meta-val">{{ $transaction->cashier->name ?? 'Kasir' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Metode Pembayaran</span>
                    <span class="meta-val">{{ strtoupper($transaction->payment_method) }}</span>
                </div>
            </div>

            @if(!empty($transaction->customer_name) || !empty($transaction->customer_phone))
                <div class="customer-box">
                    <div class="section-title" style="color: #92400e;">Identitas Pemesan</div>
                    @if(!empty($transaction->customer_name))
                        <div class="meta-row">
                            <span class="meta-label" style="color: #78350f;">Nama Pemesan</span>
                            <span class="meta-val" style="color: #78350f;">{{ $transaction->customer_name }}</span>
                        </div>
                    @endif
                    @if(!empty($transaction->customer_phone))
                        <div class="meta-row">
                            <span class="meta-label" style="color: #78350f;">No. HP / WhatsApp</span>
                            <span class="meta-val" style="color: #78350f;">{{ $transaction->customer_phone }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <div class="section-title">Rincian Belanja</div>
            <div class="items-list">
                @forelse($items as $item)
                    <div class="item-row">
                        <div>
                            <div class="item-name">{{ $item['name'] }}</div>
                            <div class="item-sub">
                                {{ rtrim(rtrim(number_format((float)$item['qty'], 2, ',', '.'), '0'), ',') }} {{ $item['unit'] }}
                                @if(!empty($item['unit_price']))
                                    x Rp {{ number_format((float)$item['unit_price'], 0, ',', '.') }}
                                @endif
                            </div>
                        </div>
                        <div class="item-price">
                            @if(!empty($item['total_price']))
                                Rp {{ number_format((float)$item['total_price'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="item-sub" style="padding: 8px 0;">{{ $transaction->items_summary }}</div>
                @endforelse
            </div>

            <div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="summary-row" style="color: #be123c;">
                        <span>Diskon</span>
                        <span>-Rp {{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($shippingCost > 0)
                    <div class="summary-row">
                        <span>Ongkos Kirim</span>
                        <span>Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="summary-row total">
                    <span>Total Tagihan</span>
                    <span style="color: #047857;">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($transaction->payment_method === 'DP' || $transaction->installments->isNotEmpty() || $paymentStatus === 'unpaid')
                <div class="payment-box {{ $remainingAmt > 0 ? 'has-balance' : '' }}">
                    <div class="section-title" style="{{ $remainingAmt > 0 ? 'color: #92400e;' : '' }}">Rincian Pembayaran / DP</div>
                    @foreach($transaction->installments as $inst)
                        <div class="installment-item">
                            <div>
                                <div style="font-weight: 700; color: #0f172a;">
                                    {{ $inst->payment_method }}
                                    @if($inst->note)
                                        <span style="font-weight: 500; color: #64748b;">({{ $inst->note }})</span>
                                    @endif
                                </div>
                                <div style="color: #64748b;">{{ $inst->created_at->translatedFormat('d M Y, H:i') }}</div>
                            </div>
                            <div style="font-weight: 800; color: #047857; white-space: nowrap;">
                                Rp {{ number_format((int)$inst->amount, 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                    <div class="summary-row" style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-weight: 700; color: #0f172a;">
                        <span>Total Sudah Dibayar</span>
                        <span style="color: #047857;">Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
                    </div>
                    @if($remainingAmt > 0)
                        <div class="remaining-row">
                            <span>Sisa Pelunasan</span>
                            <span>Rp {{ number_format($remainingAmt, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="receipt-footer">
            <div>Terima kasih telah berbelanja di Pusat Kurma.</div>
            <div>Simpan struk digital ini sebagai bukti transaksi resmi.</div>
            <div class="code-pill">{{ $transaction->transaction_code }}</div>
        </div>
    </div>

    <script>
        function downloadReceiptImage() {
            const data = @json([
                'code' => $transaction->transaction_code,
                'branch' => $transaction->branch ?? 'Pusat Cianjur',
                'date' => $transaction->created_at->translatedFormat('d M Y, H:i'),
                'cashier' => $transaction->cashier->name ?? 'Kasir',
                'method' => strtoupper($transaction->payment_method),
                'status' => $statusText,
                'customerName' => $transaction->customer_name,
                'customerPhone' => $transaction->customer_phone,
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shippingCost,
                'total' => $total,
                'paidAmount' => $paidAmount,
                'remainingAmt' => $remainingAmt,
                'isUnpaid' => $remainingAmt > 0,
            ]);

            const fmt = (n) => 'Rp ' + Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const width = 720;
            const padding = 44;
            const baseHeight = 460 + (data.items.length * 64) + (data.customerName ? 95 : 0) + (data.isUnpaid ? 105 : 0);
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = Math.max(700, baseHeight);
            const ctx = canvas.getContext('2d');

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Header band
            ctx.fillStyle = '#047857';
            ctx.fillRect(0, 0, canvas.width, 145);

            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.font = 'bold 28px sans-serif';
            ctx.fillText('PUSAT KURMA AL KARIM', width / 2, 56);
            ctx.font = '600 18px sans-serif';
            ctx.fillStyle = '#a7f3d0';
            ctx.fillText(data.branch, width / 2, 86);

            ctx.font = 'bold 16px sans-serif';
            ctx.fillStyle = data.isUnpaid ? '#fef3c7' : '#d1fae5';
            ctx.fillText('STATUS: ' + data.status, width / 2, 122);

            let y = 185;
            ctx.textAlign = 'left';
            ctx.font = '600 17px monospace';
            ctx.fillStyle = '#334155';
            const drawPair = (label, val, bold = false, color = '#0f172a') => {
                ctx.font = (bold ? 'bold ' : '') + '18px sans-serif';
                ctx.fillStyle = '#475569';
                ctx.textAlign = 'left';
                ctx.fillText(label, padding, y);
                ctx.fillStyle = color;
                ctx.textAlign = 'right';
                ctx.fillText(val, width - padding, y);
                y += 30;
            };

            drawPair('No. Struk', data.code, true);
            drawPair('Tanggal', data.date);
            drawPair('Kasir', data.cashier);
            drawPair('Metode', data.method, true);

            if (data.customerName || data.customerPhone) {
                y += 8;
                ctx.strokeStyle = '#e2e8f0';
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(width - padding, y);
                ctx.stroke();
                y += 26;
                if (data.customerName) drawPair('Pemesan', data.customerName, true);
                if (data.customerPhone) drawPair('No. WA/HP', data.customerPhone);
            }

            y += 10;
            ctx.strokeStyle = '#94a3b8';
            ctx.setLineDash([6, 6]);
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(width - padding, y);
            ctx.stroke();
            ctx.setLineDash([]);
            y += 32;

            data.items.forEach((item) => {
                ctx.textAlign = 'left';
                ctx.font = 'bold 18px sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(item.name.slice(0, 38), padding, y);

                ctx.textAlign = 'right';
                ctx.fillText(item.total_price ? fmt(item.total_price) : '-', width - padding, y);
                y += 24;

                ctx.textAlign = 'left';
                ctx.font = '15px sans-serif';
                ctx.fillStyle = '#64748b';
                const unitInfo = item.unit_price ? `${item.qty} ${item.unit} x ${fmt(item.unit_price)}` : `${item.qty} ${item.unit}`;
                ctx.fillText(unitInfo, padding, y);
                y += 34;
            });

            ctx.strokeStyle = '#94a3b8';
            ctx.setLineDash([6, 6]);
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(width - padding, y);
            ctx.stroke();
            ctx.setLineDash([]);
            y += 34;

            drawPair('Subtotal', fmt(data.subtotal));
            if (data.discount > 0) drawPair('Diskon', '-' + fmt(data.discount), false, '#be123c');
            if (data.shipping > 0) drawPair('Ongkos Kirim', fmt(data.shipping));
            drawPair('TOTAL TAGIHAN', fmt(data.total), true, '#047857');

            if (data.method === 'DP' || data.isUnpaid) {
                y += 6;
                drawPair('Sudah Dibayar (DP)', fmt(data.paidAmount), true, '#047857');
                if (data.isUnpaid) {
                    drawPair('SISA PELUNASAN', fmt(data.remainingAmt), true, '#b45309');
                }
            }

            y += 20;
            ctx.textAlign = 'center';
            ctx.font = '600 16px sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText('Terima kasih telah berbelanja di Pusat Kurma', width / 2, y);

            const link = document.createElement('a');
            link.download = 'Struk-' + data.code + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    </script>
</body>
</html>
