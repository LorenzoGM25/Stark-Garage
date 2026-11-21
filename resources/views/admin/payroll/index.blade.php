@extends('layouts.admin')

@section('title', 'Manajemen Penggajian (Payroll) - STARK-GARAGE')
@section('page-title', 'Penggajian (Payroll)')
@section('breadcrumb')
    <li class="breadcrumb-item active">Penggajian</li>
@endsection

@section('content')
    <!-- Header Controls & Period Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <form method="GET" action="{{ route('admin.payroll.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold small text-secondary">Periode Gaji:</span>
                    <select name="month" class="form-select form-select-sm" style="width: 140px;">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm" style="width: 100px;">
                        @for($y = 2024; $y <= 2027; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Ganti Periode</button>
                </form>

                <div class="d-flex align-items-center gap-2">
                    <form action="{{ route('admin.payroll.generate') }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Kalkulasi ulang slip gaji periode ini?');">
                            <i class="bi bi-arrow-repeat"></i> Generate / Hitung Gaji
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Slip Gaji</span>
                    <div class="stat-value text-primary">{{ $stats['total_payrolls'] }} <span class="fs-6 fw-normal text-secondary">Slip</span></div>
                    <span class="text-secondary small">Karyawan terproses</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Nominal Gaji Bersih</span>
                    <div class="stat-value text-primary">Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-info-soft">Termasuk Komisi &amp; Lembur</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Sudah Dibayarkan</span>
                    <div class="stat-value text-success">{{ $stats['paid_count'] }} <span class="fs-6 fw-normal text-secondary">Slip</span></div>
                    <span class="badge-pill-soft badge-success-soft">Status Lunas</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Menunggu Pembayaran</span>
                    <div class="stat-value text-warning">{{ $stats['draft_count'] }} <span class="fs-6 fw-normal text-secondary">Slip</span></div>
                    <span class="badge-pill-soft badge-warning-soft">Status Draft / Siap Bayar</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Table Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Rincian Slip Gaji Periode {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</h5>
                <small class="text-secondary">Gaji pokok, komisi pengerjaan servis, uang lembur, dan potongan kehadiran</small>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th class="text-center">Presensi (H / T / A)</th>
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Komisi Servis</th>
                            <th class="text-end">Lembur</th>
                            <th class="text-end">Potongan</th>
                            <th class="text-end">Gaji Bersih (THP)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle avatar-sm">
                                            {{ strtoupper(mb_substr($payroll->user?->name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $payroll->user?->name }}</div>
                                            <small class="text-secondary">{{ $payroll->user?->role_label }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-pill-soft badge-neutral-soft font-monospace">
                                        {{ $payroll->present_days }}H / {{ $payroll->late_days }}T / {{ $payroll->absent_days }}A
                                    </span>
                                </td>
                                <td class="text-end text-secondary small">
                                    Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-success fw-medium">
                                    +Rp {{ number_format($payroll->total_commission, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-info small">
                                    +Rp {{ number_format($payroll->total_overtime, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-danger small">
                                    -Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-primary fs-6">
                                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($payroll->status->value === 'paid')
                                        <span class="badge-pill-soft badge-success-soft"><i class="bi bi-check-circle-fill"></i> Lunas</span>
                                    @else
                                        <span class="badge-pill-soft badge-warning-soft">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        @if($payroll->status->value !== 'paid')
                                            <form action="{{ route('admin.payroll.pay', $payroll) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary py-1 px-2" style="font-size: 0.75rem;" onclick="return confirm('Tandai gaji ini lunas dibayarkan?');">
                                                    Bayar
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.payroll.show', $payroll) }}" class="btn btn-icon btn-ghost btn-sm" title="Lihat Slip">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-secondary">
                                    <i class="bi bi-wallet2 fs-2 d-block mb-2 text-muted"></i>
                                    Belum ada slip gaji yang digenerate untuk periode {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}.
                                    <div class="mt-2">
                                        <form action="{{ route('admin.payroll.generate') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ $month }}">
                                            <input type="hidden" name="year" value="{{ $year }}">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-magic"></i> Generate Slip Gaji Sekarang
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($payrolls->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">Menampilkan {{ $payrolls->firstItem() }} - {{ $payrolls->lastItem() }} dari {{ $payrolls->total() }} slip gaji</small>
                <div>{{ $payrolls->links() }}</div>
            </div>
        @endif
    </div>
@endsection

