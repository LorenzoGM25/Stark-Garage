@extends('layouts.admin')

@section('title', 'Pengaturan Sistem Bengkel - STARK-GARAGE')
@section('page-title', 'Pengaturan Sistem Bengkel')
@section('breadcrumb')
    <li class="breadcrumb-item active">Pengaturan</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Profil Bengkel &amp; Preferensi Umum</h5>
                        <small class="text-secondary">Identitas bengkel yang tercetak pada invoice, nota, dan slip gaji</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Bengkel</label>
                                    <input type="text" name="business_name" class="form-control" value="{{ $allSettings['business_name'] ?? 'STARK-GARAGE' }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Telepon / WhatsApp</label>
                                    <input type="text" name="business_phone" class="form-control" value="{{ $allSettings['business_phone'] ?? '' }}">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email Bengkel</label>
                                    <input type="email" name="business_email" class="form-control" value="{{ $allSettings['business_email'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catatan Kaki Nota / Struk</label>
                                    <input type="text" name="receipt_footer" class="form-control" value="{{ $allSettings['receipt_footer'] ?? 'Terima kasih atas kepercayaan Anda.' }}">
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Alamat Bengkel</label>
                                <textarea name="business_address" class="form-control" rows="2">{{ $allSettings['business_address'] ?? '' }}</textarea>
                            </div>

                            <hr class="my-3" style="border-color: var(--sg-border);">

                            <h6 class="fw-bold mb-2">Kebijakan Pajak &amp; Lembur</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tarif Pajak PPN (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="tax_percent" class="form-control" value="{{ $allSettings['tax_percent'] ?? '0' }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-secondary">Dikenakan pada total tagihan sparepart &amp; jasa</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pengali Lembur (Multiplier)</label>
                                    <input type="number" step="0.1" name="overtime_multiplier" class="form-control" value="{{ $allSettings['overtime_multiplier'] ?? '1.5' }}">
                                    <small class="text-secondary">Standar 1.5x upah per jam</small>
                                </div>
                            </div>

                            <hr class="my-3" style="border-color: var(--sg-border);">

                            <h6 class="fw-bold mb-2">Absensi Mobile: Geofence &amp; Face Recognition</h6>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="geofence_enabled" id="geofenceEnabled" value="1" {{ filter_var($allSettings['geofence_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="geofenceEnabled">Wajibkan karyawan berada di area bengkel saat clock-in</label>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Latitude Bengkel</label>
                                    <input type="number" step="0.0000001" name="geofence_latitude" class="form-control" value="{{ $allSettings['geofence_latitude'] ?? '0' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Longitude Bengkel</label>
                                    <input type="number" step="0.0000001" name="geofence_longitude" class="form-control" value="{{ $allSettings['geofence_longitude'] ?? '0' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Radius (meter)</label>
                                    <input type="number" name="geofence_radius" class="form-control" value="{{ $allSettings['geofence_radius'] ?? '150' }}">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Ambang Kecocokan Wajah (0 - 1)</label>
                                    <input type="number" step="0.01" name="face_threshold" class="form-control" value="{{ $allSettings['face_threshold'] ?? '0.82' }}">
                                    <small class="text-secondary">Semakin tinggi, semakin ketat pencocokan wajah.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Selisih Minimal antar Karyawan (0 - 1)</label>
                                    <input type="number" step="0.01" name="face_margin" class="form-control" value="{{ $allSettings['face_margin'] ?? '0.05' }}">
                                    <small class="text-secondary">Jika wajah terlalu mirip dengan karyawan lain, absensi ditolak.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Skor Liveness Minimal (0 - 1)</label>
                                    <input type="number" step="0.01" name="liveness_min_score" class="form-control" value="{{ $allSettings['liveness_min_score'] ?? '0.70' }}">
                                    <small class="text-secondary">Batas minimal deteksi wajah asli (bukan foto).</small>
                                </div>
                            </div>

                            <div class="mt-4 pt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan Pengaturan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Informasi Server &amp; Environment</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="--bs-list-group-bg: transparent; --bs-list-group-border-color: var(--sg-border);">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small">Laravel Versi</span>
                            <span class="badge-pill-soft badge-neutral-soft font-monospace">{{ app()->version() }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small">Versi PHP</span>
                            <span class="badge-pill-soft badge-neutral-soft font-monospace">{{ PHP_VERSION }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small">Koneksi Database</span>
                            <span class="small fw-semibold text-capitalize">{{ config('database.default') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small">Zona Waktu</span>
                            <span class="small fw-semibold">{{ config('app.timezone') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-mark">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Keamanan &amp; Audit Log</div>
                            <small class="text-secondary">Semua aktivitas transaksi dan modifikasi diaudit secara aman.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

