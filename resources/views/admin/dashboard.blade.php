@extends('layouts.admin')

@section('title', 'Dashboard - STARK-GARAGE ERP')
@section('page-title', 'Ringkasan Eksekutif')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Dashboard Header Action Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2">
        <div>
            <h4 class="fw-bold mb-1">Selamat Datang Kembali, {{ auth()->user()?->name ?? 'Admin' }}! 👋</h4>
            <p class="text-secondary small mb-0">Berikut adalah ringkasan operasional dan performa keuangan bengkel terkini.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-pill-soft badge-neutral-soft d-none d-sm-inline-flex">
                <i class="bi bi-calendar3"></i> {{ now()->translatedFormat('l, d F Y') }}
            </span>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Transaksi Baru
            </a>
            <a href="{{ route('admin.reports.sales') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan
            </a>
        </div>
    </div>

    <!-- Executive Stat Cards -->
    <div class="row g-3 g-xl-4">
        <!-- Pendapatan Hari Ini -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="stat-label">Pendapatan Hari Ini</span>
                        <span class="stat-icon"><i class="bi bi-wallet-fill"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($summary['today_revenue'], 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="badge-pill-soft {{ $summary['revenue_trend'] >= 0 ? 'badge-success-soft' : 'badge-danger-soft' }}">
                            <i class="bi {{ $summary['revenue_trend'] >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                            {{ abs($summary['revenue_trend']) }}%
                        </span>
                        <span class="text-secondary small">vs hari kemarin</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaksi Hari Ini -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="stat-label">Transaksi Selesai</span>
                        <span class="stat-icon" style="background: var(--sg-info-soft); color: var(--sg-info);"><i class="bi bi-receipt"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($summary['today_orders'], 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Order</span></div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="badge-pill-soft badge-info-soft">Rata-rata Order</span>
                        <span class="text-secondary small fw-medium">Rp {{ number_format($summary['today_average'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laba Kotor Bulan Ini -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="stat-label">Laba Kotor Bulan Ini</span>
                        <span class="stat-icon" style="background: var(--sg-success-soft); color: var(--sg-success);"><i class="bi bi-graph-up-arrow"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($summary['month_gross_profit'], 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="badge-pill-soft badge-success-soft">Laba Bersih</span>
                        <span class="text-secondary small fw-semibold">Rp {{ number_format($summary['month_net_profit'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presensi Karyawan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="stat-label">Presensi Karyawan</span>
                        <span class="stat-icon" style="background: var(--sg-warning-soft); color: var(--sg-warning);"><i class="bi bi-person-badge"></i></span>
                    </div>
                    <div class="stat-value">{{ $summary['attendance_today']['on_time'] + $summary['attendance_today']['late'] }} <span class="fs-6 fw-normal text-secondary">/ {{ $summary['active_staff'] }} Hadir</span></div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <span class="badge-pill-soft badge-success-soft">{{ $summary['attendance_today']['on_time'] }} Tepat Waktu</span>
                        <span class="badge-pill-soft badge-warning-soft">{{ $summary['attendance_today']['late'] }} Telat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 g-xl-4">
        <!-- Tren Penjualan 14 Hari -->
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Tren Penjualan 14 Hari Terakhir</h5>
                        <small class="text-secondary">Fluktuasi omzet harian dan frekuensi pesanan masuk</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-secondary active" data-series-toggle="revenue">Pendapatan</button>
                        <button class="btn btn-secondary" data-series-toggle="orders">Transaksi</button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 310px; position: relative;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribusi Metode Pembayaran -->
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Metode Pembayaran</h5>
                        <small class="text-secondary">Distribusi nominal transaksi 30 hari</small>
                    </div>
                    <span class="badge-pill-soft badge-neutral-soft">30 Hari</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div style="height: 200px; width: 100%; position: relative;">
                        <canvas id="paymentMixChart"></canvas>
                    </div>
                    <div class="w-100 mt-4 d-flex flex-column gap-2">
                        @foreach($paymentMix as $method => $total)
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-2" style="background: var(--sg-surface-subtle);">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: {{ match(strtolower($method)) { 'cash' => '#2563eb', 'qris' => '#10b981', 'transfer' => '#06b6d4', default => '#f59e0b' } }};"></span>
                                    <span class="text-capitalize small fw-semibold">{{ $method }}</span>
                                </div>
                                <span class="fw-bold small">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Data Tables Row -->
    <div class="row g-3 g-xl-4">
        <!-- Top Products -->
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Produk &amp; Layanan Terlaris</h5>
                        <small class="text-secondary">Berdasarkan volume penjualan dan pendapatan</small>
                    </div>
                    <a class="btn btn-sm btn-ghost" href="{{ route('admin.products.index') }}">
                        Kelola Katalog <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item / Layanan</th>
                                    <th class="text-end">Terjual</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $product)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="stat-icon avatar-sm" style="font-size: 0.9rem;">
                                                    <i class="bi bi-wrench"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $product['name'] }}</div>
                                                    <small class="text-secondary">SKU Aktif</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($product['quantity'], 0, ',', '.') }} pcs</td>
                                        <td class="text-end fw-bold text-primary">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-secondary py-4">Belum ada data penjualan tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Transaksi Bengkel Terbaru</h5>
                        <small class="text-secondary">Pesanan servis dan pembelian sparepart terkini</small>
                    </div>
                    <a class="btn btn-sm btn-ghost" href="{{ route('admin.orders.index') }}">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Order</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Total Pembayaran</th>
                                    <th class="text-end">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a class="fw-semibold text-primary text-decoration-none" href="{{ route('admin.orders.show', $order) }}">
                                                #{{ $order->order_number }}
                                            </a>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="badge-pill-soft badge-info-soft" style="font-size: 0.68rem;">
                                                    {{ $order->payment_method?->label() ?? 'Tunai' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-circle avatar-sm">
                                                    {{ strtoupper(mb_substr($order->cashier?->name ?? 'K', 0, 1)) }}
                                                </div>
                                                <span class="small fw-medium">{{ $order->cashier?->name ?? 'Kasir Bengkel' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</td>
                                        <td class="text-end text-secondary small">{{ $order->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-4">Belum ada data transaksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const salesData = @json($salesSeries);
        const paymentData = @json($paymentMix);
        const ctxSales = document.getElementById('salesTrendChart');
        const ctxPayment = document.getElementById('paymentMixChart');

        function getChartTheme() {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            return {
                gridColor: isDark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.18)',
                textColor: isDark ? '#94a3b8' : '#64748b',
                primaryColor: isDark ? '#3b82f6' : '#2563eb',
                secondaryColor: isDark ? '#38bdf8' : '#0284c7',
                areaColor: isDark ? 'rgba(59, 130, 246, 0.15)' : 'rgba(37, 99, 235, 0.08)'
            };
        }

        let theme = getChartTheme();

        const salesChart = new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: salesData.labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: salesData.revenue,
                    borderColor: theme.primaryColor,
                    backgroundColor: theme.areaColor,
                    borderWidth: 2.5,
                    pointBackgroundColor: theme.primaryColor,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'y',
                }, {
                    label: 'Jumlah Transaksi',
                    data: salesData.orders,
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3,
                    tension: 0.35,
                    yAxisID: 'y1',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        grid: { color: theme.gridColor, drawBorder: false },
                        ticks: { color: theme.textColor, font: { family: 'Inter', size: 11 } }
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        grid: { color: theme.gridColor, drawBorder: false },
                        ticks: {
                            color: theme.textColor,
                            font: { family: 'Inter', size: 11 },
                            callback: (v) => 'Rp ' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'Jt' : v.toLocaleString('id-ID'))
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { color: theme.textColor, font: { family: 'Inter', size: 11 } }
                    }
                },
                plugins: {
                    legend: { labels: { color: theme.textColor, font: { family: 'Inter', size: 12, weight: 600 } } },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Inter', size: 13, weight: 700 },
                        bodyFont: { family: 'Inter', size: 12 }
                    }
                }
            }
        });

        document.querySelectorAll('[data-series-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-series-toggle]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const type = btn.dataset.seriesToggle;
                salesChart.setDatasetVisibility(0, type === 'revenue');
                salesChart.setDatasetVisibility(1, type === 'orders');
                salesChart.update();
            });
        });
        salesChart.setDatasetVisibility(1, false);
        salesChart.update();

        const paymentChart = new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: Object.keys(paymentData),
                datasets: [{
                    data: Object.values(paymentData),
                    backgroundColor: ['#2563eb', '#10b981', '#06b6d4', '#f59e0b', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: 'transparent'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '76%'
            }
        });

        // Dynamic theme change listener
        window.addEventListener('themeChanged', () => {
            const newTheme = getChartTheme();
            salesChart.options.scales.x.grid.color = newTheme.gridColor;
            salesChart.options.scales.x.ticks.color = newTheme.textColor;
            salesChart.options.scales.y.grid.color = newTheme.gridColor;
            salesChart.options.scales.y.ticks.color = newTheme.textColor;
            salesChart.options.scales.y1.ticks.color = newTheme.textColor;
            salesChart.options.plugins.legend.labels.color = newTheme.textColor;
            salesChart.data.datasets[0].borderColor = newTheme.primaryColor;
            salesChart.data.datasets[0].backgroundColor = newTheme.areaColor;
            salesChart.update();
        });
    </script>
@endpush
