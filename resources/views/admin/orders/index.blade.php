@extends('layouts.admin')

@section('title', 'Transaksi Kasir & Servis - STARK-GARAGE')
@section('page-title', 'Transaksi Kasir & Servis')
@section('breadcrumb')
    <li class="breadcrumb-item active">Transaksi</li>
@endsection

@section('content')
    <!-- KPI Summary Row -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Transaksi Hari Ini</span>
                    <div class="stat-value text-primary">{{ number_format($stats['today_count'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Order sukses tercatat</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Omzet Penjualan Hari Ini</span>
                    <div class="stat-value text-success">Rp {{ number_format($stats['today_revenue'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-success-soft">Real-time Hari Ini</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Order Bulan Ini</span>
                    <div class="stat-value">{{ number_format($stats['month_count'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Volume akumulasi</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Omzet Bulan Ini</span>
                    <div class="stat-value text-primary">Rp {{ number_format($stats['month_revenue'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-neutral-soft">{{ now()->translatedFormat('F Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Riwayat Transaksi Penjualan &amp; Servis</h5>
                <small class="text-secondary">Daftar invoice, status pelunasan, metode pembayaran, dan mekanik terkait</small>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="No. invoice, plat nomor..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Metode Bayar</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->value }}" {{ request('payment_method') === $method->value ? 'selected' : '' }}>
                                {{ $method->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" title="Dari tanggal">
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" title="Sampai tanggal">
                </div>
                <div class="col-12 col-sm-4 col-lg-1 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1"><i class="bi bi-filter"></i></button>
                    @if(request()->hasAny(['search', 'status', 'payment_method', 'date_from', 'date_to']))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost btn-sm" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            {{-- Mobile: kartu transaksi --}}
            <div class="d-lg-none p-3 d-flex flex-column gap-2">
                @forelse($orders as $order)
                    @php
                        $statusClass = match(strtolower($order->status?->value ?? '')) {
                            'completed' => 'badge-success-soft',
                            'voided' => 'badge-danger-soft',
                            'refunded' => 'badge-warning-soft',
                            default => 'badge-neutral-soft'
                        };
                    @endphp
                    <div class="border rounded-3 p-3" style="background: var(--sg-surface-subtle);">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div style="min-width: 0;">
                                <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-primary text-decoration-none">
                                    #{{ $order->order_number }}
                                </a>
                                <small class="text-secondary d-block">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</small>
                            </div>
                            <span class="badge-pill-soft {{ $statusClass }} flex-shrink-0">
                                {{ $order->status?->label() ?? 'Selesai' }}
                            </span>
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-7">
                                <small class="text-secondary d-block">Pelanggan</small>
                                <span class="fw-semibold text-truncate d-block">{{ $order->customer_name ?? 'Pelanggan Umum' }}</span>
                                @if($order->vehicle_plate)
                                    <span class="badge-pill-soft badge-neutral-soft font-monospace mt-1" style="font-size: 0.7rem;">{{ $order->vehicle_plate }}</span>
                                @endif
                            </div>
                            <div class="col-5 text-end">
                                <small class="text-secondary d-block">Total Bayar</small>
                                <span class="fw-bold text-primary">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Kasir</small>
                                <span class="small fw-medium">{{ $order->cashier?->name ?? 'Kasir Bengkel' }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-secondary d-block">Metode</small>
                                <span class="badge-pill-soft badge-info-soft">{{ $order->payment_method?->label() ?? 'Tunai' }}</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-2 pt-2 border-top">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-ghost btn-sm">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                            <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" class="btn btn-ghost btn-sm text-primary">
                                <i class="bi bi-printer"></i> Nota
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-muted"></i>
                        Tidak ada data transaksi yang sesuai filter.
                    </div>
                @endforelse
            </div>

            {{-- Desktop: tabel transaksi --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Order / Invoice</th>
                            <th>Tanggal &amp; Waktu</th>
                            <th>Kasir</th>
                            <th>Pelanggan / Kendaraan</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Total Bayar</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-primary text-decoration-none">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="small fw-medium">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</div>
                                    <small class="text-secondary">{{ $order->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="small fw-semibold">{{ $order->cashier?->name ?? 'Kasir Bengkel' }}</span>
                                </td>
                                <td>
                                    <div class="small fw-medium">{{ $order->customer_name ?? 'Pelanggan Umum' }}</div>
                                    @if($order->vehicle_plate)
                                        <span class="badge-pill-soft badge-neutral-soft font-monospace" style="font-size: 0.7rem;">{{ $order->vehicle_plate }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-pill-soft badge-info-soft">
                                        {{ $order->payment_method?->label() ?? 'Tunai' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">
                                    Rp {{ number_format($order->final_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = match(strtolower($order->status?->value ?? '')) {
                                            'completed' => 'badge-success-soft',
                                            'voided' => 'badge-danger-soft',
                                            'refunded' => 'badge-warning-soft',
                                            default => 'badge-neutral-soft'
                                        };
                                    @endphp
                                    <span class="badge-pill-soft {{ $statusClass }}">
                                        {{ $order->status?->label() ?? 'Selesai' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-icon btn-ghost btn-sm" title="Detail Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" class="btn btn-icon btn-ghost btn-sm text-primary" title="Cetak Nota">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada data transaksi yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($orders->hasPages())
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-secondary">Menampilkan {{ $orders->firstItem() }} sampai {{ $orders->lastItem() }} dari {{ $orders->total() }} transaksi</small>
                <div>{{ $orders->links() }}</div>
            </div>
        @endif
    </div>
@endsection

