@extends('layouts.admin')

@section('title', 'Kelola Akun ' . $account->name . ' - STARK-GARAGE')
@section('page-title', 'Kelola Akun')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.accounts.index') }}">Kelola Akun</a></li>
    <li class="breadcrumb-item active">{{ $account->name }}</li>
@endsection

@section('content')
    @php($isSelf = $account->id === auth()->id())

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Data Akun</h5>
                    <small class="text-secondary">Ubah identitas, email, dan jabatan akun</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.accounts.update', $account) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Karyawan</label>
                                <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $account->employee_code) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alamat Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $account->email) }}" required>
                                <small class="text-secondary">Mengubah email akan mereset status verifikasi email.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon / WA</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $account->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan / Role</label>
                                <select name="role" class="form-select" required>
                                    @foreach($roles as $key => $label)
                                        <option value="{{ $key }}" {{ old('role', $account->role?->value ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($isSelf)
                                    <small class="text-warning">Anda tidak dapat menurunkan jabatan Owner akun sendiri.</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Akun</label>
                                <div class="form-control bg-light d-flex align-items-center justify-content-between" style="cursor: default;">
                                    <span>{{ $account->is_active ? 'Aktif' : 'Terkunci' }}</span>
                                    <span class="badge-pill-soft {{ $account->is_active ? 'badge-success-soft' : 'badge-danger-soft' }}">
                                        {{ $account->is_active ? 'Dapat login' : 'Tidak dapat login' }}
                                    </span>
                                </div>
                                <small class="text-secondary">Gunakan tombol kunci/buka di halaman daftar akun.</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('admin.accounts.index') }}" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Reset Kata Sandi</h5>
                    <small class="text-secondary">Tetapkan kata sandi baru tanpa perlu kata sandi lama</small>
                </div>
                <div class="card-body">
                    @if($errors->passwordReset->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->passwordReset->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.accounts.password', $account) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kata Sandi Baru</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi kata sandi" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-secondary">
                                @if($isSelf)
                                    Kata sandi akun Anda sendiri akan diubah.
                                @else
                                    Sesi login {{ $account->name }} akan diputus setelah reset.
                                @endif
                            </small>
                            <button type="submit" class="btn btn-warning" onclick="return confirm({{ Illuminate\Support\Js::from('Reset kata sandi '.$account->name.'?') }});">
                                <i class="bi bi-key-fill"></i> Reset Kata Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Ringkasan Akun</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar-circle" style="width: 3.5rem; height: 3.5rem; font-size: 1.3rem;">
                            {{ strtoupper(mb_substr($account->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $account->name }}</div>
                            <small class="text-secondary">{{ $account->email }}</small>
                        </div>
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-5 text-secondary fw-normal">Jabatan</dt>
                        <dd class="col-7">{{ $account->role_label }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Kode</dt>
                        <dd class="col-7">{{ $account->employee_code ?? '-' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Login terakhir</dt>
                        <dd class="col-7">{{ $account->last_login_at?->format('d M Y H:i') ?? 'Belum pernah' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Wajah</dt>
                        <dd class="col-7">
                            @if($account->needsFaceRegistration())
                                <span class="badge-pill-soft badge-warning-soft">Wajib daftar</span>
                                <div class="text-secondary mt-1" style="font-size: .72rem;">
                                    Login diblokir sampai wajah didaftarkan.
                                </div>
                            @elseif($account->hasFace())
                                <span class="badge-pill-soft badge-success-soft">Terdaftar</span>
                                <div class="text-secondary mt-1" style="font-size: .72rem;">
                                    {{ $account->face_registered_at?->format('d M Y H:i') }}
                                </div>
                            @else
                                <span class="badge-pill-soft badge-neutral-soft text-muted">Belum ada</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Keamanan &amp; Akses</h5>
                    <small class="text-secondary">Tindakan langsung terhadap akun</small>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-semibold small">Kunci / Buka Akun</div>
                            <small class="text-secondary">Akun terkunci tidak dapat login dan sesinya diputus.</small>
                        </div>
                        @if($account->is_active)
                            <form method="POST" action="{{ route('admin.accounts.lock', $account) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from('Kunci akun '.$account->name.'?') }});">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" {{ $isSelf ? 'disabled' : '' }}>
                                    <i class="bi bi-lock-fill"></i> Kunci
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.accounts.unlock', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-unlock-fill"></i> Buka
                                </button>
                            </form>
                        @endif
                    </div>

                    <hr class="my-0">

                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-semibold small">Hapus Data Wajah</div>
                            <small class="text-secondary">Karyawan harus mendaftarkan wajah ulang.</small>
                        </div>
                        <form method="POST" action="{{ route('admin.accounts.reset-face', $account) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from('Hapus data wajah '.$account->name.'?') }});">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-sm" {{ $account->hasFace() ? '' : 'disabled' }}>
                                <i class="bi bi-person-bounding-box"></i> Reset Wajah
                            </button>
                        </form>
                    </div>

                    <hr class="my-0">

                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-semibold small">Paksa Keluar</div>
                            <small class="text-secondary">Putuskan semua sesi web dan token API akun ini.</small>
                        </div>
                        <form method="POST" action="{{ route('admin.accounts.force-logout', $account) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from('Putuskan semua sesi '.$account->name.'?') }});">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $isSelf ? 'disabled' : '' }}>
                                <i class="bi bi-box-arrow-right"></i> Paksa Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
