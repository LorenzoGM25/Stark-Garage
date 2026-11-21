@extends('layouts.admin')

@section('title', 'Kelola Akun - STARK-GARAGE')
@section('page-title', 'Kelola Akun & Akses')
@section('breadcrumb')
    <li class="breadcrumb-item active">Kelola Akun</li>
@endsection

@section('content')
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Akun</span>
                    <div class="stat-value text-primary">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Seluruh pengguna sistem</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Akun Aktif</span>
                    <div class="stat-value text-success">{{ number_format($stats['active'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-success-soft">Dapat login</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Akun Terkunci</span>
                    <div class="stat-value text-danger">{{ number_format($stats['locked'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Tidak dapat login</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Owner Aktif</span>
                    <div class="stat-value text-dark">{{ number_format($stats['owners'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Pemegang akses superadmin</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Superadmin &rsaquo; Kelola Akun</h5>
                <small class="text-secondary">Kunci/buka akun, ubah email, reset kata sandi, hapus data wajah, dan putuskan sesi login</small>
            </div>
            <a href="{{ route('admin.activity.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-clock-history"></i> Log Aktivitas
            </a>
        </div>

        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.accounts.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, email, kode, atau telepon..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua Jabatan</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['role'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="locked" {{ ($filters['status'] ?? '') === 'locked' ? 'selected' : '' }}>Terkunci</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('admin.accounts.index') }}" class="btn btn-ghost btn-sm"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Jabatan</th>
                            <th class="text-center">Wajah</th>
                            <th>Login Terakhir</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            @php($isSelf = $account->id === auth()->id())
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle avatar-sm">
                                            {{ strtoupper(mb_substr($account->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $account->name }}
                                                @if($isSelf)
                                                    <span class="badge-pill-soft badge-info-soft ms-1">Anda</span>
                                                @endif
                                            </div>
                                            <small class="text-secondary">{{ $account->email }}</small>
                                            <div class="text-secondary" style="font-size: .72rem;">{{ $account->employee_code ?? 'Tanpa kode' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $account->role?->badge() ?? 'secondary' }}">{{ $account->role_label }}</span>
                                </td>
                                <td class="text-center">
                                    @if($account->needsFaceRegistration())
                                        <span class="badge-pill-soft badge-warning-soft"><i class="bi bi-exclamation-circle-fill"></i> Wajib</span>
                                    @elseif($account->hasFace())
                                        <span class="badge-pill-soft badge-success-soft"><i class="bi bi-check-circle-fill"></i> Ada</span>
                                    @else
                                        <span class="badge-pill-soft badge-neutral-soft text-muted"><i class="bi bi-dash-circle"></i> Belum</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        {{ $account->last_login_at?->format('d M Y H:i') ?? 'Belum pernah' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    @if($account->is_active)
                                        <span class="badge-pill-soft badge-success-soft">Aktif</span>
                                    @else
                                        <span class="badge-pill-soft badge-danger-soft">Terkunci</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn-icon btn-ghost btn-sm text-primary" title="Kelola Akun">
                                            <i class="bi bi-sliders"></i>
                                        </a>

                                        @if($account->is_active)
                                            <form method="POST" action="{{ route('admin.accounts.lock', $account) }}" class="m-0" onsubmit="return confirm({{ Illuminate\Support\Js::from('Kunci akun '.$account->name.'? Sesi aktifnya akan langsung diputus.') }});">
                                                @csrf
                                                <button type="submit" class="btn btn-icon btn-ghost btn-sm text-danger" title="Kunci Akun" {{ $isSelf ? 'disabled' : '' }}>
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.accounts.unlock', $account) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-icon btn-ghost btn-sm text-success" title="Buka Akun">
                                                    <i class="bi bi-unlock-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.accounts.force-logout', $account) }}" class="m-0" onsubmit="return confirm({{ Illuminate\Support\Js::from('Putuskan semua sesi login '.$account->name.'?') }});">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-ghost btn-sm text-warning" title="Paksa Keluar" {{ $isSelf ? 'disabled' : '' }}>
                                                <i class="bi bi-box-arrow-right"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="bi bi-person-x fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada akun yang sesuai dengan kriteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($accounts->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">Menampilkan {{ $accounts->firstItem() }} sampai {{ $accounts->lastItem() }} dari {{ $accounts->total() }} akun</small>
                <div>{{ $accounts->links() }}</div>
            </div>
        @endif
    </div>
@endsection
