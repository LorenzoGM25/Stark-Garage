@extends('layouts.admin')

@section('title', 'Laporan Rekapitulasi Presensi - STARK-GARAGE')
@section('page-title', 'Laporan Rekapitulasi Presensi')
@section('breadcrumb')
    <li class="breadcrumb-item active">Laporan Presensi</li>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.attendance') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Dari:</span>
                        <input type="date" name="date_from" class="form-control" value="{{ $from->toDateString() }}">
                    </div>
                </div>
                <div class="col-12 col-sm-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Sampai:</span>
                        <input type="date" name="date_to" class="form-control" value="{{ $to->toDateString() }}">
                    </div>
                </div>
                <div class="col-12 col-sm-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h5 class="fw-bold mb-0">Riwayat Presensi Seluruh Staf</h5>
                <small class="text-secondary">Periode {{ $from->translatedFormat('d M Y') }} s/d {{ $to->translatedFormat('d M Y') }}</small>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Mobile: kartu rekap presensi --}}
            <div class="d-lg-none p-3 d-flex flex-column gap-2">
                @forelse($attendances as $att)
                    @php
                        $pillClass = match(strtolower($att->status?->value ?? '')) {
                            'on_time' => 'badge-success-soft',
                            'late' => 'badge-warning-soft',
                            'absent' => 'badge-danger-soft',
                            'leave' => 'badge-info-soft',
                            default => 'badge-neutral-soft'
                        };
                    @endphp
                    <div class="border rounded-3 p-3" style="background: var(--sg-surface-subtle);">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ $att->user?->name }}</div>
                                <small class="text-secondary">
                                    {{ $att->user?->role_label }} &middot; {{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}
                                </small>
                            </div>
                            <span class="badge-pill-soft {{ $pillClass }} flex-shrink-0">{{ $att->status?->label() ?? 'Hadir' }}</span>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-secondary d-block">Masuk</small>
                                <span class="font-monospace fw-semibold">{{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-' }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Pulang</small>
                                <span class="font-monospace fw-semibold">{{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-' }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Shift</small>
                                <span class="small">{{ $att->shift?->name ?? 'Default' }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-secondary d-block">Terlambat</small>
                                @if($att->late_minutes > 0)
                                    <span class="fw-semibold text-danger">{{ $att->late_minutes }} menit</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-secondary">Belum ada catatan presensi pada rentang tanggal ini.</div>
                @endforelse
            </div>

            {{-- Desktop: tabel rekap presensi --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Karyawan</th>
                            <th>Shift</th>
                            <th class="text-center">Jam Masuk</th>
                            <th class="text-center">Jam Pulang</th>
                            <th class="text-center">Keterlambatan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            @php
                                $pillClass = match(strtolower($att->status?->value ?? '')) {
                                    'on_time' => 'badge-success-soft',
                                    'late' => 'badge-warning-soft',
                                    'absent' => 'badge-danger-soft',
                                    'leave' => 'badge-info-soft',
                                    default => 'badge-neutral-soft'
                                };
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $att->user?->name }}</span>
                                    <small class="d-block text-secondary">{{ $att->user?->role_label }}</small>
                                </td>
                                <td><span class="small">{{ $att->shift?->name ?? 'Default' }}</span></td>
                                <td class="text-center">{{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-' }}</td>
                                <td class="text-center">{{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-' }}</td>
                                <td class="text-center">
                                    @if($att->late_minutes > 0)
                                        <span class="badge-pill-soft badge-danger-soft">{{ $att->late_minutes }} Menit</span>
                                    @else
                                        <span class="text-secondary small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge-pill-soft {{ $pillClass }}">{{ $att->status?->label() ?? 'Hadir' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">Belum ada catatan presensi pada rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($attendances->hasPages())
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-secondary">Menampilkan {{ $attendances->firstItem() }} - {{ $attendances->lastItem() }}</small>
                <div>{{ $attendances->links() }}</div>
            </div>
        @endif
    </div>
@endsection

