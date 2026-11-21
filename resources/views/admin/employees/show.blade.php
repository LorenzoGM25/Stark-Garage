@extends('layouts.admin')

@section('title', 'Profil Karyawan ' . $employee->name . ' - STARK-GARAGE')
@section('page-title', 'Profil & Performa Karyawan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Karyawan</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card text-center p-4">
                <div class="avatar-circle mx-auto mb-3" style="width: 4.5rem; height: 4.5rem; font-size: 1.75rem;">
                    {{ strtoupper(mb_substr($employee->name, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $employee->name }}</h5>
                <span class="badge-pill-soft badge-info-soft mx-auto mb-3">{{ $employee->role_label }}</span>
                <div class="text-secondary small">{{ $employee->email }}</div>
                <div class="text-secondary small">{{ $employee->phone ?? '-' }}</div>

                <hr class="my-3" style="border-color: var(--sg-border);">

                <div class="d-flex justify-content-between text-start mb-2">
                    <span class="text-secondary small">Status:</span>
                    <span class="badge-pill-soft {{ $employee->is_active ? 'badge-success-soft' : 'badge-danger-soft' }}">
                        {{ $employee->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between text-start mb-2">
                    <span class="text-secondary small">Shift:</span>
                    <span class="small fw-semibold">{{ $employee->shift?->name ?? 'Default' }}</span>
                </div>
                <div class="d-flex justify-content-between text-start mb-2">
                    <span class="text-secondary small">Skema Gaji:</span>
                    <span class="small fw-semibold">{{ $employee->salary_type?->label() ?? 'Bulanan' }}</span>
                </div>
                <div class="d-flex justify-content-between text-start mb-3">
                    <span class="text-secondary small">Gaji Pokok:</span>
                    <span class="small fw-bold text-primary">Rp {{ number_format($employee->base_salary, 0, ',', '.') }}</span>
                </div>

                <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary btn-sm w-100">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Biometrik Wajah &amp; Presensi</h5>
                </div>
                <div class="card-body">
                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-between" style="background: var(--sg-surface-subtle);">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-person-bounding-box fs-2 text-primary"></i>
                            <div>
                                <div class="fw-semibold">Status Pendaftaran Face Recognition</div>
                                <small class="text-secondary">Digunakan untuk absensi mesin POS / tablet bengkel secara instan</small>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-2">
                            @if($employee->needsFaceRegistration())
                                <span class="badge-pill-soft badge-warning-soft"><i class="bi bi-exclamation-circle-fill"></i> Wajib Daftar</span>
                            @elseif($employee->hasFace())
                                <span class="badge-pill-soft badge-success-soft"><i class="bi bi-check-circle-fill"></i> Sudah Terdaftar</span>
                            @else
                                <span class="badge-pill-soft badge-warning-soft">Belum Didaftarkan</span>
                            @endif

                            @if(in_array(auth()->user()?->role?->value, ['owner', 'manager'], true))
                                <a href="{{ route('admin.attendance.face', $employee->needsFaceRegistration()
                                    ? ['user_id' => $employee->id, 'mode' => 'register', 'onboarding' => 1]
                                    : ['user_id' => $employee->id, 'mode' => 'register']) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person-bounding-box"></i>
                                    {{ $employee->needsFaceRegistration() ? 'Lanjutkan Pendaftaran Wajah' : ($employee->hasFace() ? 'Daftarkan Ulang Wajah' : 'Daftarkan Wajah') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($employee->needsFaceRegistration())
                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="bi bi-shield-lock-fill"></i>
                            Akun ini belum dapat login. Selesaikan pendaftaran wajah untuk membuka akses masuk.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

