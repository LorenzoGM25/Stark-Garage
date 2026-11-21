@extends('layouts.admin')

@section('title', 'Dashboard - STARK-GARAGE')
@section('page-title', 'Dashboard Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    @php($isCashier = $user->role?->value === 'cashier')

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">Halo, {{ $user->name }}! 👋</h4>
            <p class="text-secondary small mb-0">
                {{ $user->role_label }} &middot; {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.attendance.face') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-bounding-box"></i> Absensi Wajah
            </a>
            @if($isCashier)
                <a href="{{ route('admin.pos.index') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-cart-check-fill"></i> Buka Kasir
                </a>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-receipt"></i> Riwayat Transaksi
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 g-xl-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Presensi Hari Ini</h5>
                    <small class="text-secondary">{{ now()->translatedFormat('d F Y') }}</small>
                </div>
                <div class="card-body">
                    @if($today)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge-pill-soft badge-{{ $today->status?->badge() ?? 'secondary' }}-soft">
                                {{ $today->status?->label() ?? 'Tercatat' }}
                            </span>
                            @if($today->is_open)
                                <span class="badge-pill-soft badge-info-soft">Sedang Bekerja</span>
                            @elseif($today->clock_out)
                                <span class="badge-pill-soft badge-success-soft">Selesai</span>
                            @endif
                        </div>

                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="text-secondary small mb-1">Clock-in</div>
                                <div class="fw-bold fs-5">{{ $today->clock_in?->format('H:i') ?? '-' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-secondary small mb-1">Clock-out</div>
                                <div class="fw-bold fs-5">{{ $today->clock_out?->format('H:i') ?? '-' }}</div>
                            </div>
                        </div>

                        <hr>

                        <dl class="row small mb-0">
                            <dt class="col-6 text-secondary fw-normal">Shift</dt>
                            <dd class="col-6 text-end">{{ $today->shift?->name ?? 'Default' }}</dd>
                            <dt class="col-6 text-secondary fw-normal">Terlambat</dt>
                            <dd class="col-6 text-end">{{ $today->late_minutes }} menit</dd>
                            <dt class="col-6 text-secondary fw-normal">Lembur</dt>
                            <dd class="col-6 text-end">{{ $today->overtime_minutes }} menit</dd>
                            <dt class="col-6 text-secondary fw-normal">Metode</dt>
                            <dd class="col-6 text-end">{{ $today->verification_method }}</dd>
                        </dl>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock fs-1 text-muted d-block mb-2"></i>
                            <p class="text-secondary mb-3">Anda belum melakukan absensi hari ini.</p>
                            <a href="{{ route('admin.attendance.face') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-camera"></i> Absen Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Riwayat Presensi Terakhir</h5>
                    <small class="text-secondary">7 catatan absensi terakhir Anda</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Shift</th>
                                    <th class="text-center">Masuk</th>
                                    <th class="text-center">Pulang</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAttendances as $attendance)
                                    <tr>
                                        <td class="small fw-medium">{{ $attendance->date?->format('d M Y') }}</td>
                                        <td class="small text-secondary">{{ $attendance->shift?->name ?? 'Default' }}</td>
                                        <td class="text-center small">{{ $attendance->clock_in?->format('H:i') ?? '-' }}</td>
                                        <td class="text-center small">{{ $attendance->clock_out?->format('H:i') ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge-pill-soft badge-{{ $attendance->status?->badge() ?? 'secondary' }}-soft">
                                                {{ $attendance->status?->label() ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-secondary">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2 text-muted"></i>
                                            Belum ada riwayat presensi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-secondary">Data pribadi Anda hanya dapat dilihat oleh Anda dan Owner/Manager.</small>
                    <a href="{{ route('profile.edit') }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-person-gear"></i> Profil Saya
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
