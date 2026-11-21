@extends('layouts.admin')

@section('title', 'Laporan Pengeluaran Gaji Tahunan - STARK-GARAGE')
@section('page-title', 'Laporan Rekap Gaji')
@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan Gaji</li>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.payroll') }}" class="d-flex align-items-center gap-2">
                <span class="fw-semibold small text-secondary">Tahun:</span>
                <select name="year" class="form-select form-select-sm" style="width: 120px;">
                    @for($y = 2024; $y <= 2027; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h5 class="fw-bold mb-0">Rekapitulasi Gaji Karyawan Tahun {{ $year }}</h5>
                <small class="text-secondary">Akumulasi pengeluaran gaji bersih per bulan</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Karyawan</th>
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Komisi</th>
                            <th class="text-end">Lembur</th>
                            <th class="text-end">Gaji Bersih</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $p)
                            <tr>
                                <td class="fw-semibold">{{ \Carbon\Carbon::create($year, $p->period_month, 1)->translatedFormat('F') }}</td>
                                <td>{{ $p->user?->name }}</td>
                                <td class="text-end text-secondary small">Rp {{ number_format($p->base_salary, 0, ',', '.') }}</td>
                                <td class="text-end text-success small">+Rp {{ number_format($p->total_commission, 0, ',', '.') }}</td>
                                <td class="text-end text-info small">+Rp {{ number_format($p->total_overtime, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary">Rp {{ number_format($p->net_salary, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge-pill-soft {{ $p->status->value === 'paid' ? 'badge-success-soft' : 'badge-warning-soft' }}">
                                        {{ $p->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">Belum ada slip gaji tercatat di tahun {{ $year }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

