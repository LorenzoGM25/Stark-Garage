<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            max-width: 340px;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #444; }
        .border-bottom { border-bottom: 1px dashed #444; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        @media print {
            body { max-width: 100%; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print text-center" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 16px; cursor: pointer; font-weight: bold;">[ CETAK STRUK ]</button>
    </div>

    <div class="text-center">
        <h3 style="margin: 0 0 4px 0;">{{ strtoupper($business['name'] ?? 'STARK-GARAGE') }}</h3>
        <div>BENGKEL MOBIL &amp; MOTOR MODERN</div>
        @if(!empty($business['address']))
            <div style="font-size: 11px;">{{ $business['address'] }}</div>
        @endif
        @if(!empty($business['phone']))
            <div style="font-size: 11px;">Telp: {{ $business['phone'] }}</div>
        @endif
        @if(!empty($business['email']))
            <div style="font-size: 11px;">{{ $business['email'] }}</div>
        @endif
    </div>

    <div class="border-top border-bottom py-1 my-2" style="font-size: 11px;">
        <div>No: #{{ $order->order_number }}</div>
        <div>Waktu: {{ $order->created_at->format('d/m/Y H:i') }}</div>
        <div>Kasir: {{ $order->cashier?->name ?? 'Kasir Bengkel' }}</div>
        @if($order->customer_name)
            <div>Pelanggan: {{ $order->customer_name }}</div>
        @endif
        @if($order->vehicle_plate)
            <div>Plat: {{ $order->vehicle_plate }}</div>
        @endif
    </div>

    <table>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td colspan="2" class="fw-bold">{{ $item->product_name }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom: 4px;">{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-end" style="padding-bottom: 4px;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-top py-2 my-2">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-end">{{ number_format($order->subtotal ?? $order->items->sum('subtotal'), 0, ',', '.') }}</td>
            </tr>
            @if($order->discount > 0)
                <tr>
                    <td>Diskon</td>
                    <td class="text-end">-{{ number_format($order->discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($order->tax_amount > 0)
                <tr>
                    <td>PPN</td>
                    <td class="text-end">{{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="fw-bold" style="font-size: 15px;">
                <td style="padding-top: 6px;">TOTAL</td>
                <td class="text-end" style="padding-top: 6px;">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="font-size: 11px;">Pembayaran</td>
                <td class="text-end" style="font-size: 11px;">{{ $order->payment_method?->label() ?? 'Tunai' }}</td>
            </tr>
            @if($order->paid_amount !== null)
                <tr>
                    <td style="font-size: 11px;">Dibayar</td>
                    <td class="text-end" style="font-size: 11px;">{{ number_format($order->paid_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="font-size: 11px;">Kembalian</td>
                    <td class="text-end" style="font-size: 11px;">{{ number_format($order->change_amount ?? max(0, $order->paid_amount - $order->final_amount), 0, ',', '.') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="text-center py-2 border-top" style="font-size: 11px;">
        <div>{{ $business['receipt_footer'] ?? 'Terima kasih atas kepercayaan Anda.' }}</div>
        <div>Garansi servis berlaku 7 hari kerja.</div>
    </div>
</body>
</html>

