@extends('layouts.admin')

@section('title', 'Manajemen Karyawan & Mekanik - STARK-GARAGE')
@section('page-title', 'Karyawan & Mekanik')
@section('breadcrumb')
    <li class="breadcrumb-item active">Karyawan</li>
@endsection

@section('content')
    <!-- Stats Row -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Anggota Tim</span>
                    <div class="stat-value text-primary">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Mekanik, kasir, admin</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Karyawan Aktif</span>
                    <div class="stat-value text-success">{{ number_format($stats['active'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-success-soft">Status Aktif Bekerja</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Non-Aktif / Cuti</span>
                    <div class="stat-value text-warning">{{ number_format($stats['inactive'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Tidak aktif di sistem</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Estimasi Payroll Bulan Ini</span>
                    <div class="stat-value text-primary">Rp {{ number_format($stats['payroll_cost'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Akumulasi gaji &amp; komisi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Table Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Daftar Tim Bengkel &amp; Penugasan Shift</h5>
                <small class="text-secondary">Kelola peran, jam kerja shift, jenis gaji (bulanan/harian), dan biometrik wajah</small>
            </div>
            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus-fill"></i> Tambah Karyawan
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.employees.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama karyawan, email, atau nomor telepon..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua Jabatan</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-ghost btn-sm"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jabatan / Role</th>
                            <th>Shift Kerja</th>
                            <th>Skema Gaji</th>
                            <th class="text-center">Biometrik Wajah</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle avatar-sm">
                                            {{ strtoupper(mb_substr($employee->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $employee->name }}</div>
                                            <small class="text-secondary">{{ $employee->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-pill-soft badge-info-soft">{{ $employee->role?->label() ?? 'Staf' }}</span>
                                </td>
                                <td>
                                    <span class="small fw-medium">{{ $employee->shift?->name ?? 'Default (08:00 - 17:00)' }}</span>
                                </td>
                                <td>
                                    <div class="small fw-semibold">Rp {{ number_format($employee->base_salary ?? 0, 0, ',', '.') }}</div>
                                    <small class="text-secondary">{{ $employee->salary_type?->label() ?? 'Bulanan' }}</small>
                                </td>
                                <td class="text-center">
                                    @if($employee->needsFaceRegistration())
                                        <span class="badge-pill-soft badge-warning-soft"><i class="bi bi-exclamation-circle-fill"></i> Wajib Daftar</span>
                                    @elseif($employee->hasFace())
                                        <span class="badge-pill-soft badge-success-soft"><i class="bi bi-check-circle-fill"></i> Terdaftar</span>
                                    @else
                                        <span class="badge-pill-soft badge-neutral-soft text-muted"><i class="bi bi-dash-circle"></i> Belum Ada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($employee->is_active)
                                        <span class="badge-pill-soft badge-success-soft">Aktif</span>
                                    @else
                                        <span class="badge-pill-soft badge-danger-soft">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        @if($employee->needsFaceRegistration())
                                            <a href="{{ route('admin.attendance.face', ['user_id' => $employee->id, 'mode' => 'register', 'onboarding' => 1]) }}" class="btn btn-icon btn-ghost btn-sm text-warning" title="Lanjutkan Pendaftaran Wajah">
                                                <i class="bi bi-person-bounding-box"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-icon btn-ghost btn-sm text-warning" title="Edit Karyawan">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-icon btn-ghost btn-sm text-primary" title="Lihat Profil">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada data karyawan yang sesuai dengan kriteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($employees->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">Menampilkan {{ $employees->firstItem() }} sampai {{ $employees->lastItem() }} karyawan</small>
                <div>{{ $employees->links() }}</div>
            </div>
        @endif
    </div>
@endsection

