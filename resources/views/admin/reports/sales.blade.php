@extends('layouts.admin')

@section('title', 'Laporan Keuangan & Penjualan - STARK-GARAGE')
@section('page-title', 'Laporan Penjualan & Laba')
@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan Penjualan</li>
@endsection

@section('content')
    <!-- Date Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Dari:</span>
                        <input type="date" name="date_from" class="form-control" value="{{ $from->toDateString() }}">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Sampai:</span>
                        <input type="date" name="date_to" class="form-control" value="{{ $to->toDateString() }}">
                    </div>
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter Periode</button>
                    <a href="{{ route('admin.reports.pdf.sales', ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer"></i> Cetak PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Performance Cards -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Omzet Penjualan</span>
                    <div class="stat-value text-primary">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">{{ $summary['transactions'] }} Transaksi Selesai</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Harga Pokok (Modal/HPP)</span>
                    <div class="stat-value text-warning">Rp {{ number_format($summary['cost'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Modal sparepart &amp; bahan</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Biaya Operasional</span>
                    <div class="stat-value text-danger">Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Pengeluaran operasional periode ini</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Laba Bersih (Net Profit)</span>
                    <div class="stat-value text-success">Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-success-soft">Margin: {{ $summary['revenue'] > 0 ? round(($summary['net_profit'] / $summary['revenue']) * 100, 1) : 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Detail Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h5 class="fw-bold mb-0">Rincian Transaksi Penjualan Periode Ini</h5>
                <small class="text-secondary">Daftar pesanan selesai dan kontribusi terhadap pendapatan</small>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Mobile: kartu transaksi --}}
            <div class="d-lg-none p-3 d-flex flex-column gap-2">
                @forelse($orders as $order)
                    <div class="border rounded-3 p-3" style="background: var(--sg-surface-subtle);">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div style="min-width: 0;">
                                <div class="fw-bold text-primary">#{{ $order->order_number }}</div>
                                <small class="text-secondary d-block">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</small>
                            </div>
                            <span class="badge-pill-soft badge-info-soft flex-shrink-0">{{ $order->payment_method?->label() ?? 'Tunai' }}</span>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-secondary d-block">Kasir</small>
                                <span class="small fw-medium">{{ $order->cashier?->name ?? 'Kasir' }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-secondary d-block">Total Akhir</small>
                                <span class="fw-bold text-primary">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</span>
                            </div>
                            @if($order->discount > 0)
                                <div class="col-6">
                                    <small class="text-secondary d-block">Diskon</small>
                                    <span class="small text-danger">-Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($order->tax_amount > 0)
                                <div class="col-6 text-end">
                                    <small class="text-secondary d-block">Pajak</small>
                                    <span class="small">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-bar-chart fs-2 d-block mb-2 text-muted"></i>
                        Tidak ada data transaksi pada rentang tanggal ini.
                    </div>
                @endforelse
            </div>

            {{-- Desktop: tabel transaksi --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Pajak</th>
                            <th class="text-end">Total Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="fw-bold">#{{ $order->order_number }}</td>
                                <td>{{ $order->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td>{{ $order->cashier?->name ?? 'Kasir' }}</td>
                                <td><span class="badge-pill-soft badge-info-soft">{{ $order->payment_method?->label() ?? 'Tunai' }}</span></td>
                                <td class="text-end text-secondary small">Rp {{ number_format($order->discount, 0, ',', '.') }}</td>
                                <td class="text-end text-secondary small">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    Tidak ada data transaksi pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-secondary">Menampilkan {{ $orders->firstItem() }} - {{ $orders->lastItem() }} transaksi</small>
                <div>{{ $orders->links() }}</div>
            </div>
        @endif
    </div>
@endsection

