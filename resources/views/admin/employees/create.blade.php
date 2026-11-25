@extends('layouts.admin')

@section('title', 'Tambah Karyawan Baru - STARK-GARAGE')
@section('page-title', 'Tambah Anggota Tim / Mekanik')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Karyawan</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Formulir Pendaftaran Karyawan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.employees.store') }}" method="POST">
                        @csrf
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kode Karyawan</label>
                                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror" placeholder="Otomatis (contoh: STG-005)" value="{{ old('employee_code') }}">
                                    <div class="form-text">Biarkan kosong untuk membuat kode otomatis berurutan.</div>
                                    @error('employee_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nama Karyawan" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Alamat Email (Untuk Login) <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="nama@stark.id" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Telepon / WA</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="0812-xxxx-xxxx" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Peran / Posisi Kerja <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shift Kerja</label>
                                    <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                                        <option value="">Default (08:00 - 17:00)</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                                        @endforeach
                                    </select>
                                    @error('shift_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Skema Pembayaran Gaji <span class="text-danger">*</span></label>
                                    <select name="salary_type" class="form-select @error('salary_type') is-invalid @enderror" required>
                                        @foreach($salaryTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('salary_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('salary_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gaji Pokok (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror" placeholder="0" value="{{ old('base_salary', 0) }}">
                                    </div>
                                    @error('base_salary')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tarif Lembur per Jam (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="hourly_overtime_rate" class="form-control @error('hourly_overtime_rate') is-invalid @enderror" placeholder="0" value="{{ old('hourly_overtime_rate', 0) }}">
                                    </div>
                                    @error('hourly_overtime_rate')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kata Sandi Akun <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi kata sandi" required>
                                </div>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                                <label class="form-check-label small" for="isActive">Karyawan Aktif Bekerja</label>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-person-check-fill"></i> Daftarkan Karyawan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

