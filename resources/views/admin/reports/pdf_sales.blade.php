<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan &amp; Laba - STARK-GARAGE</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1e293b; padding: 25px; line-height: 1.5; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; }
        .kpi-box { display: inline-block; width: 23%; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div style="border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2563eb;">{{ strtoupper($business['name'] ?? 'STARK-GARAGE') }}</h2>
        @if(!empty($business['address']))
            <div style="color: #64748b; font-size: 11px;">{{ $business['address'] }}</div>
        @endif
        <div style="color: #64748b; font-size: 12px;">LAPORAN PENJUALAN &amp; LABA BERSIH &bull; {{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}</div>
    </div>

    <div style="margin-bottom: 25px;">
        <div class="kpi-box">
            <div style="font-size: 11px; color: #64748b;">OMZET TOTAL</div>
            <div style="font-size: 16px; font-weight: bold; color: #2563eb;">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-box">
            <div style="font-size: 11px; color: #64748b;">HPP (MODAL)</div>
            <div style="font-size: 16px; font-weight: bold; color: #d97706;">Rp {{ number_format($summary['cost'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-box">
            <div style="font-size: 11px; color: #64748b;">BIAYA OPERASIONAL</div>
            <div style="font-size: 16px; font-weight: bold; color: #dc2626;">Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-box">
            <div style="font-size: 11px; color: #64748b;">LABA BERSIH</div>
            <div style="font-size: 16px; font-weight: bold; color: #16a34a;">Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}</div>
        </div>
    </div>

    <h4>Daftar Transaksi ({{ $orders->count() }} Transaksi)</h4>
    <table>
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Metode</th>
                <th class="text-end">Diskon</th>
                <th class="text-end">Pajak</th>
                <th class="text-end">Total Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $o)
                <tr>
                    <td>#{{ $o->order_number }}</td>
                    <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $o->cashier?->name ?? 'Kasir' }}</td>
                    <td>{{ $o->payment_method?->label() ?? 'Tunai' }}</td>
                    <td class="text-end">Rp {{ number_format($o->discount, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($o->tax_amount, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($o->final_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

