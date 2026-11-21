<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $payroll->user?->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1e293b; padding: 25px; line-height: 1.5; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; }
        .total-row { font-size: 15px; font-weight: bold; background: #f1f5f9; }
    </style>
</head>
<body onload="window.print()">
    <div style="border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2563eb;">{{ strtoupper($business['name'] ?? 'STARK-GARAGE') }}</h2>
        <div style="color: #64748b; font-size: 12px;">SLIP GAJI KARYAWAN &bull; PERIODE {{ $payroll->period_label }}</div>
    </div>

    <table style="border: none; margin-bottom: 15px;">
        <tr style="border: none;">
            <td style="border: none; padding-left: 0;">
                <div><strong>Karyawan:</strong> {{ $payroll->user?->name }}</div>
                <div><strong>Jabatan:</strong> {{ $payroll->user?->role_label }}</div>
            </td>
            <td style="border: none; text-align: right; padding-right: 0;">
                <div><strong>Status:</strong> {{ $payroll->status->label() }}</div>
                <div><strong>Kehadiran:</strong> {{ $payroll->present_days }} Hadir | {{ $payroll->late_days }} Telat | {{ $payroll->absent_days }} Alpa</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Komponen Gaji</th>
                <th class="text-end">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-end">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Komisi Pengerjaan Jasa Servis</td>
                <td class="text-end">+Rp {{ number_format($payroll->total_commission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Uang Lembur</td>
                <td class="text-end">+Rp {{ number_format($payroll->total_overtime, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Potongan Presensi</td>
                <td class="text-end">-Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Gaji Bersih Diterima (THP)</td>
                <td class="text-end">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right;">
        <div style="font-size: 11px; color: #64748b;">Dicetak otomatis oleh {{ $business['name'] ?? 'STARK-GARAGE' }} pada {{ now()->translatedFormat('d F Y, H:i') }}</div>
    </div>
</body>
</html>

