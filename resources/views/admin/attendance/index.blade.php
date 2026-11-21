@extends('layouts.admin')

@section('title', 'Presensi & Absensi Karyawan - STARK-GARAGE')
@section('page-title', 'Presensi & Absensi')
@section('breadcrumb')
    <li class="breadcrumb-item active">Absensi</li>
@endsection

@section('content')
    <!-- Attendance KPI Cards -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Hadir Tepat Waktu</span>
                    <div class="stat-value text-success">{{ $stats['on_time'] }} <span class="fs-6 fw-normal text-secondary">Orang</span></div>
                    <span class="badge-pill-soft badge-success-soft"><i class="bi bi-clock-check"></i> Sesuai Jadwal Shift</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Terlambat Masuk</span>
                    <div class="stat-value text-warning">{{ $stats['late'] }} <span class="fs-6 fw-normal text-secondary">Orang</span></div>
                    <span class="badge-pill-soft badge-warning-soft"><i class="bi bi-exclamation-circle"></i> Kena Potongan Keterlambatan</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Izin / Cuti</span>
                    <div class="stat-value text-info">{{ $stats['leave'] }} <span class="fs-6 fw-normal text-secondary">Orang</span></div>
                    <span class="badge-pill-soft badge-info-soft"><i class="bi bi-info-circle"></i> Terkonfirmasi</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Tidak Hadir (Alpa)</span>
                    <div class="stat-value text-danger">{{ $stats['absent'] }} <span class="fs-6 fw-normal text-secondary">Orang</span></div>
                    <span class="badge-pill-soft badge-danger-soft"><i class="bi bi-x-circle"></i> Tanpa Keterangan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Log Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Catatan Presensi Harian</h5>
                <small class="text-secondary">Waktu kedatangan, jam pulang, toleransi keterlambatan, dan lembur</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form action="{{ route('admin.attendance.absent') }}" method="POST" class="m-0" onsubmit="return confirm('Tandai semua karyawan yang belum absen pada tanggal {{ $date->format('d M Y') }} sebagai Alpa?');">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-person-x"></i> Tandai Alpa
                    </button>
                </form>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
                    <i class="bi bi-pencil-square"></i> Presensi Manual
                </button>
            </div>
        </div>

        <!-- Date & Search Filter -->
        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i> Tanggal:</span>
                        <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? today()->toDateString() }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status Presensi</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama karyawan..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Tampilkan</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            {{-- Mobile: kartu presensi --}}
            <div class="d-lg-none p-3 d-flex flex-column gap-2">
                @forelse($records as $record)
                    @php
                        $pillClass = match(strtolower($record->status?->value ?? '')) {
                            'on_time' => 'badge-success-soft',
                            'late' => 'badge-warning-soft',
                            'absent' => 'badge-danger-soft',
                            'leave' => 'badge-info-soft',
                            'half_day' => 'badge-neutral-soft',
                            default => 'badge-neutral-soft'
                        };
                    @endphp
                    <div class="attendance-mobile-card border rounded-3 p-3" style="background: var(--sg-surface-subtle);">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                                <div class="avatar-circle avatar-sm">
                                    {{ strtoupper(mb_substr($record->user?->name ?? 'K', 0, 1)) }}
                                </div>
                                <div style="min-width: 0;">
                                    <div class="fw-semibold text-truncate">{{ $record->user?->name }}</div>
                                    <small class="text-secondary d-block text-truncate">
                                        {{ $record->user?->role_label }} &middot; {{ $record->shift?->name ?? 'Shift Default' }}
                                    </small>
                                </div>
                            </div>
                            <span class="badge-pill-soft {{ $pillClass }} flex-shrink-0">
                                {{ $record->status?->label() ?? 'Hadir' }}
                            </span>
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-secondary d-block">Jam Masuk</small>
                                <span class="fw-semibold font-monospace">
                                    {{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '-' }}
                                </span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Jam Keluar</small>
                                <span class="fw-semibold font-monospace">
                                    {{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '-' }}
                                </span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Terlambat</small>
                                @if($record->late_minutes > 0)
                                    <span class="fw-semibold text-danger">{{ $record->late_minutes }} menit</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Lembur</small>
                                @if($record->overtime_minutes > 0)
                                    <span class="fw-semibold text-success">{{ round($record->overtime_minutes / 60, 1) }} jam</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                            <small class="text-secondary">
                                <i class="bi bi-shield-check"></i>
                                {{ strtoupper($record->verification_method ?? 'MANUAL') }}
                            </small>
                            @if($record->notes)
                                <small class="text-secondary text-truncate ms-2" style="max-width: 55%;" title="{{ $record->notes }}">
                                    {{ $record->notes }}
                                </small>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-fingerprint fs-2 d-block mb-2 text-muted"></i>
                        Belum ada data presensi pada tanggal yang dipilih.
                    </div>
                @endforelse
            </div>

            {{-- Desktop: tabel presensi --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Shift Kerja</th>
                            <th class="text-center">Jam Masuk</th>
                            <th class="text-center">Jam Keluar</th>
                            <th class="text-center">Keterlambatan</th>
                            <th class="text-center">Lembur</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle avatar-sm">
                                            {{ strtoupper(mb_substr($record->user?->name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $record->user?->name }}</div>
                                            <small class="text-secondary">{{ $record->user?->role_label }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="small">{{ $record->shift?->name ?? 'Default' }}</span>
                                </td>
                                <td class="text-center font-monospace">
                                    {{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i:s') : '-' }}
                                </td>
                                <td class="text-center font-monospace">
                                    {{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i:s') : '-' }}
                                </td>
                                <td class="text-center">
                                    @if($record->late_minutes > 0)
                                        <span class="badge-pill-soft badge-danger-soft">{{ $record->late_minutes }} Menit</span>
                                    @else
                                        <span class="text-secondary small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($record->overtime_minutes > 0)
                                        <span class="badge-pill-soft badge-success-soft">{{ round($record->overtime_minutes / 60, 1) }} Jam</span>
                                    @else
                                        <span class="text-secondary small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $pillClass = match(strtolower($record->status?->value ?? '')) {
                                            'on_time' => 'badge-success-soft',
                                            'late' => 'badge-warning-soft',
                                            'absent' => 'badge-danger-soft',
                                            'leave' => 'badge-info-soft',
                                            'half_day' => 'badge-neutral-soft',
                                            default => 'badge-neutral-soft'
                                        };
                                    @endphp
                                    <span class="badge-pill-soft {{ $pillClass }}">
                                        {{ $record->status?->label() ?? 'Hadir' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge-pill-soft badge-neutral-soft" style="font-size: 0.7rem;">
                                        {{ strtoupper($record->verification_method ?? 'MANUAL') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    <i class="bi bi-fingerprint fs-2 d-block mb-2 text-muted"></i>
                                    Belum ada data presensi pada tanggal yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($records->hasPages())
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-secondary">Menampilkan {{ $records->firstItem() }} - {{ $records->lastItem() }} dari {{ $records->total() }} presensi</small>
                <div>{{ $records->links() }}</div>
            </div>
        @endif
    </div>

    <!-- Manual Attendance Modal -->
    <div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--sg-surface); border: 1px solid var(--sg-border);">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Input Presensi Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('admin.attendance.manual') }}" method="POST">
                    @csrf
                    <div class="modal-body d-flex flex-column gap-3">
                        <div>
                            <label class="form-label">Pilih Karyawan</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->role_label }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="{{ today()->toDateString() }}" required>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="clock_in" class="form-control" value="08:00">
                            </div>
                            <div class="col-6 col-sm-4">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="clock_out" class="form-control" value="17:00">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Status Presensi (Opsional)</label>
                            <select name="status" class="form-select">
                                <option value="">Otomatis (dari jam masuk / shift)</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Kosongkan agar status dihitung otomatis dari jam masuk dan shift.</small>
                        </div>
                        <div>
                            <label class="form-label">Catatan / Alasan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Alasan input manual..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Presensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

