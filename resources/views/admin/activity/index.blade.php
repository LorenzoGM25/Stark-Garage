@extends('layouts.admin')

@section('title', 'Log Aktivitas - STARK-GARAGE')
@section('page-title', 'Log Aktivitas Karyawan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Log Aktivitas</li>
@endsection

@section('content')
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Aktivitas Hari Ini</span>
                    <div class="stat-value text-primary">{{ number_format($stats['today'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Sejak 00:00 hari ini</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Tercatat</span>
                    <div class="stat-value text-dark">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Seluruh riwayat audit</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Karyawan Aktif Hari Ini</span>
                    <div class="stat-value text-success">{{ number_format($stats['actors_today'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Pengguna yang melakukan aksi</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Login Gagal (7 Hari)</span>
                    <div class="stat-value text-danger">{{ number_format($stats['failed_logins'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Percobaan login tidak berhasil</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Superadmin &rsaquo; Log Aktivitas</h5>
                <small class="text-secondary">Jejak setiap perubahan data dan aktivitas login karyawan</small>
            </div>
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people-fill"></i> Kelola Akun
            </a>
        </div>

        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.activity.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Nama, email, deskripsi..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Jenis Aktivitas</label>
                    <select name="event" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($events as $event)
                            <option value="{{ $event['value'] }}" {{ ($filters['event'] ?? '') === $event['value'] ? 'selected' : '' }}>{{ $event['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Pengguna</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Jabatan</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['role'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'event', 'user_id', 'role', 'date_from', 'date_to']))
                        <a href="{{ route('admin.activity.index') }}" class="btn btn-ghost btn-sm" title="Reset"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 10rem;">Waktu</th>
                            <th>Pelaku</th>
                            <th>Aktivitas</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div class="small fw-medium">{{ $log->created_at?->format('d M Y') }}</div>
                                    <small class="text-secondary">{{ $log->created_at?->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div class="fw-semibold small">{{ $log->user->name }}</div>
                                        <small class="text-secondary">{{ $log->user->role_label ?? '-' }}</small>
                                    @else
                                        <span class="text-secondary small">Tidak dikenal / Sistem</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-pill-soft badge-{{ $log->event_tone }}-soft">{{ $log->event_label }}</span>
                                </td>
                                <td>
                                    <span class="small">{{ $log->description ?? $log->event_label }}</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-icon btn-ghost btn-sm text-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#logDetail{{ $log->id }}"
                                        aria-expanded="false" aria-controls="logDetail{{ $log->id }}" title="Lihat Detail">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="logDetail{{ $log->id }}">
                                <td colspan="5" class="bg-light">
                                    <div class="row g-3 p-2">
                                        <div class="col-md-3">
                                            <div class="text-secondary small mb-1">IP Address</div>
                                            <div class="fw-medium small">{{ $log->ip_address ?? '-' }}</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-secondary small mb-1">Objek</div>
                                            <div class="fw-medium small">
                                                {{ $log->subject_type ? class_basename($log->subject_type) : '-' }}
                                                {{ $log->subject_id ? '#'.$log->subject_id : '' }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-secondary small mb-1">User Agent</div>
                                            <div class="fw-medium small text-break">{{ $log->user_agent ?? '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-secondary small mb-1">Nilai Sebelum</div>
                                            <pre class="bg-white border rounded p-2 small mb-0" style="max-height: 12rem; overflow: auto;">{{ $log->old_values ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-secondary small mb-1">Nilai Sesudah</div>
                                            <pre class="bg-white border rounded p-2 small mb-0" style="max-height: 12rem; overflow: auto;">{{ $log->new_values ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-secondary">
                                    <i class="bi bi-clock-history fs-2 d-block mb-2 text-muted"></i>
                                    Belum ada aktivitas yang sesuai dengan kriteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($logs->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">Menampilkan {{ $logs->firstItem() }} sampai {{ $logs->lastItem() }} dari {{ $logs->total() }} aktivitas</small>
                <div>{{ $logs->links() }}</div>
            </div>
        @endif
    </div>
@endsection
