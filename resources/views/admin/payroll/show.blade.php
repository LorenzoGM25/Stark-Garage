@extends('layouts.admin')

@section('title', 'Slip Gaji - ' . ($payroll->user?->name ?? 'Karyawan') . ' - STARK-GARAGE')
@section('page-title', 'Slip Gaji Karyawan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.payroll.index') }}">Penggajian</a></li>
    <li class="breadcrumb-item active">{{ $payroll->user?->name }}</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-mark">
                            <i class="bi bi-shield-shaded"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0">STARK-GARAGE</h4>
                            <small class="text-secondary">SLIP GAJI KARYAWAN &bull; PERIODE {{ $payroll->period_label }}</small>
                        </div>
                    </div>
                    <div>
                        @if($payroll->status->value === 'paid')
                            <span class="badge-pill-soft badge-success-soft fs-6 px-3 py-1"><i class="bi bi-check-circle-fill"></i> LUNAS</span>
                        @else
                            <span class="badge-pill-soft badge-warning-soft fs-6 px-3 py-1">DRAFT</span>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <small class="text-secondary d-block">Nama Karyawan:</small>
                        <span class="fw-bold fs-6">{{ $payroll->user?->name }}</span>
                        <div class="text-secondary small">{{ $payroll->user?->role_label }}</div>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-secondary d-block">Rekap Kehadiran:</small>
                        <span class="fw-semibold">{{ $payroll->present_days }} Hadir | {{ $payroll->late_days }} Telat | {{ $payroll->absent_days }} Alpa</span>
                        <div class="text-secondary small">Lembur: {{ $payroll->overtime_hours }} Jam</div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead style="background: var(--sg-surface-subtle);">
                            <tr>
                                <th>Deskripsi Penghasilan &amp; Potongan</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Gaji Pokok / Upah Harian</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Komisi Pengerjaan Jasa Servis Mekanik</td>
                                <td class="text-end text-success fw-semibold">+Rp {{ number_format($payroll->total_commission, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Uang Lembur (Overtime Pay)</td>
                                <td class="text-end text-info fw-semibold">+Rp {{ number_format($payroll->total_overtime, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Potongan Kehadiran (Terlambat &amp; Alpa)</td>
                                <td class="text-end text-danger fw-semibold">-Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot style="background: var(--sg-surface-subtle);">
                            <tr class="fs-5">
                                <td class="fw-bold text-primary">Gaji Bersih Diterima (Take Home Pay):</td>
                                <td class="text-end fw-bold text-primary">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('admin.payroll.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                    <div class="d-flex gap-2">
                        @if($payroll->status->value !== 'paid')
                            <form action="{{ route('admin.payroll.pay', $payroll) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check2"></i> Tandai Sudah Dibayar
                                </button>
                            </form>
                        @endif
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer"></i> Cetak Slip Gaji
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

