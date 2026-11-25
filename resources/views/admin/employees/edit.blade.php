@extends('layouts.admin')

@section('title', 'Edit Karyawan ' . $employee->name . ' - STARK-GARAGE')
@section('page-title', 'Edit Karyawan / Mekanik')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Karyawan</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Ubah Data Karyawan</h5>
                    <small class="text-secondary">{{ $employee->name }} ({{ $employee->employee_code ?? 'No Code' }})</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.employees.update', $employee) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kode Karyawan</label>
                                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror" value="{{ old('employee_code', $employee->employee_code) }}" placeholder="Contoh: STG-005">
                                    @error('employee_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Alamat Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Telepon / WA</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Posisi / Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}" {{ old('role', $employee->role?->value ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                                            <option value="{{ $shift->id }}" {{ old('shift_id', $employee->shift_id) == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
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
                                            <option value="{{ $key }}" {{ old('salary_type', $employee->salary_type?->value ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                                        <input type="number" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror" value="{{ old('base_salary', $employee->base_salary) }}">
                                    </div>
                                    @error('base_salary')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tarif Lembur per Jam (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="hourly_overtime_rate" class="form-control @error('hourly_overtime_rate') is-invalid @enderror" value="{{ old('hourly_overtime_rate', $employee->hourly_overtime_rate) }}">
                                    </div>
                                    @error('hourly_overtime_rate')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi sandi baru">
                                </div>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="isActive">Karyawan Aktif Bekerja</label>
                            </div>

                            <div class="d-flex justify-content-between align-items-center gap-2 mt-4 pt-2 border-top">
                                <div>
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-eye"></i> Lihat Profil
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Batal</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="text-danger small fw-semibold">Tindakan Berbahaya</div>
                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus / menonaktifkan karyawan ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i> Hapus / Nonaktifkan Karyawan
                            </button>
                        </form>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

