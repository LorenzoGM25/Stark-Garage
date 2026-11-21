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
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Telepon / WA</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Alamat Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Posisi / Role</label>
                                    <select name="role" class="form-select" required>
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}" {{ old('role', $employee->role?->value ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Shift Kerja</label>
                                    <select name="shift_id" class="form-select">
                                        <option value="">Default (08:00 - 17:00)</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ old('shift_id', $employee->shift_id) == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Skema Pembayaran Gaji</label>
                                    <select name="salary_type" class="form-select" required>
                                        @foreach($salaryTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('salary_type', $employee->salary_type?->value ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gaji Pokok (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="base_salary" class="form-control" value="{{ old('base_salary', $employee->base_salary) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tarif Lembur per Jam (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="hourly_overtime_rate" class="form-control" value="{{ old('hourly_overtime_rate', $employee->hourly_overtime_rate) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                                    <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
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

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

