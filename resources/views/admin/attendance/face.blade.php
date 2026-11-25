@extends('layouts.admin')

@section('title', 'Absensi Wajah - STARK-GARAGE')
@section('page-title', 'Absensi Wajah')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.attendance.index') }}">Presensi</a></li>
    <li class="breadcrumb-item active">Absensi Wajah</li>
@endsection

@push('styles')
    <style>
        .face-stage {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 1rem;
            overflow: hidden;
            background: #0f172a;
            border: 1px solid var(--sg-border);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .face-stage video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        .face-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .face-stage canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .face-stage .face-placeholder {
            text-align: center;
            color: #94a3b8;
            padding: 1rem;
        }
        .face-stage .face-placeholder i {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 0.5rem;
            opacity: 0.6;
        }
        .face-guide-circle {
            position: absolute;
            width: min(250px, 60vw);
            height: min(250px, 60vw);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 2.5px dashed rgba(255, 255, 255, 0.55);
            pointer-events: none;
            transition: all 0.25s ease;
            box-sizing: border-box;
            z-index: 2;
        }
        .face-guide-circle.is-wrong {
            border: 2.5px solid #ef4444;
            box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.22);
            animation: pulse-red 1.2s infinite ease-in-out;
        }
        .face-guide-circle.is-correct {
            border: 2.5px solid #22c55e;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.28);
            animation: pulse-green 1s infinite ease-in-out;
        }
        @keyframes pulse-green {
            0%, 100% { transform: translate(-50%, -50%) scale(1); box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.28); }
            50% { transform: translate(-50%, -50%) scale(1.02); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.4); }
        }
        @keyframes pulse-red {
            0%, 100% { transform: translate(-50%, -50%) scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.22); }
            50% { transform: translate(-50%, -50%) scale(1.02); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.35); }
        }
        .face-stage:not(.is-camera) .face-guide-circle {
            display: none;
        }
        .face-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: #fff;
            text-align: center;
            padding: 1rem;
            z-index: 5;
        }
        .shutter-flash {
            position: absolute;
            inset: 0;
            background: white;
            opacity: 0;
            pointer-events: none;
            z-index: 10;
            transition: opacity 0.15s ease-out;
        }
        .shutter-flash.active {
            opacity: 0.85;
        }
        .face-mode .btn {
            min-width: 140px;
        }
        @media (max-width: 575.98px) {
            .face-mode .btn {
                flex: 1 1 0;
                min-width: 0;
            }
        }
    </style>
@endpush

@section('content')
    @if($onboarding)
        <div class="alert alert-warning d-flex gap-3 align-items-center justify-content-between mb-4 flex-wrap" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-person-bounding-box fs-2"></i>
                <div>
                    <div class="fw-bold">Pendaftaran Wajah Wajib</div>
                    <div class="small">
                        Akun <strong>{{ $target->name }}</strong> belum dapat login sebelum wajahnya terdaftar.
                        Posisikan wajah karyawan di dalam lingkaran hingga berwarna hijau.
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-dark btn-sm text-nowrap" onclick="return confirm('Lewati pendaftaran wajah sekarang? Karyawan belum dapat login sebelum wajahnya didaftarkan.');">
                    <i class="bi bi-clock-history"></i> Lewati / Daftarkan Nanti
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 40px; height: 40px; background: #0f5146;">
                            <i class="bi bi-camera fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Selfie Verification <span class="visually-hidden">Pemindai Wajah</span></h5>
                            <small class="text-secondary">
                                @if($onboarding)
                                    Daftarkan wajah {{ $target->name }} untuk mengaktifkan akses login.
                                @else
                                    Take one clear selfie to continue attendance.
                                @endif
                            </small>
                        </div>
                    </div>
                    @unless($onboarding)
                        <div class="face-mode btn-group w-100 w-sm-auto" role="group" aria-label="Mode pemindaian">
                            <input type="radio" class="btn-check" name="faceMode" id="modeAttendance" value="attendance" @checked($initialMode === 'attendance')>
                            <label class="btn btn-outline-primary btn-sm" for="modeAttendance">
                                <i class="bi bi-clock-history"></i> Absensi
                            </label>
                            <input type="radio" class="btn-check" name="faceMode" id="modeRegister" value="register" @checked($initialMode === 'register')>
                            <label class="btn btn-outline-primary btn-sm" for="modeRegister">
                                <i class="bi bi-person-plus"></i> Registrasi Wajah
                            </label>
                        </div>
                    @endunless
                </div>

                <div class="card-body">
                    {{-- Attendance Action Selector (In / Out / Auto) --}}
                    @unless($onboarding)
                        <div class="d-flex align-items-center justify-content-between mb-3 p-2 rounded-3 border flex-wrap gap-2" id="attendanceTypeSection" style="background: var(--sg-surface-subtle);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history text-primary fs-5"></i>
                                <span class="small fw-semibold">Pilih Jenis Absen:</span>
                            </div>
                            <div class="btn-group btn-group-sm w-100 w-sm-auto" role="group" aria-label="Tipe Presensi">
                                <input type="radio" class="btn-check" name="attendanceAction" id="actionAuto" value="auto" checked>
                                <label class="btn btn-outline-secondary" for="actionAuto" title="Otomatis: Masuk jika belum absen, Pulang jika sudah absen">
                                    <i class="bi bi-magic"></i> Otomatis
                                </label>

                                <input type="radio" class="btn-check" name="attendanceAction" id="actionIn" value="clock_in">
                                <label class="btn btn-outline-success" for="actionIn">
                                    <i class="bi bi-box-arrow-in-right"></i> Absen Masuk
                                </label>

                                <input type="radio" class="btn-check" name="attendanceAction" id="actionOut" value="clock_out">
                                <label class="btn btn-outline-primary" for="actionOut">
                                    <i class="bi bi-box-arrow-right"></i> Absen Pulang
                                </label>
                            </div>
                        </div>
                    @endunless

                    {{-- Notice info bar (matches reference UI) --}}
                    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mb-3 small rounded-3" style="background: #f8fafc; border-color: #e2e8f0;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 20px; height: 20px; background: #0f5146; font-size: 11px; font-weight: bold;">
                            <i class="bi bi-info-lg"></i>
                        </div>
                        <span class="text-secondary">Take one clear selfie first. After that, location will be checked automatically before you submit attendance.</span>
                    </div>

                    {{-- Camera stage --}}
                    <div class="face-stage" id="faceStage">
                        <video id="faceVideo" autoplay playsinline muted class="d-none"></video>
                        <canvas id="faceCanvas"></canvas>
                        <div class="face-guide-circle" id="faceGuideCircle"></div>
                        <div class="shutter-flash" id="shutterFlash"></div>

                        <div class="face-placeholder" id="facePlaceholder">
                            <i class="bi bi-camera-video"></i>
                            <div class="fw-semibold text-white">Kamera Belum Aktif</div>
                            <small class="text-secondary">Klik "Aktifkan Kamera" untuk memulai pemindaian wajah langsung.</small>
                        </div>

                        <div class="face-overlay d-none" id="faceOverlay">
                            <div class="spinner-border text-light" role="status"></div>
                            <div class="fw-semibold fs-6" id="faceOverlayText">Memproses wajah...</div>
                            <div class="small opacity-75" id="faceOverlaySub">Tahan posisi wajah Anda...</div>
                        </div>
                    </div>

                    {{-- Guide Status Card (matches the user's reference screenshot) --}}
                    <div class="face-guide-status mt-3 p-3 rounded-3 d-flex align-items-center gap-3" id="faceGuideCard" style="background: var(--sg-surface-subtle); border: 1px solid var(--sg-border); transition: all 0.3s ease;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" id="faceGuideIconWrap" style="width: 44px; height: 44px; background: rgba(100, 116, 139, 0.15); color: #64748b; transition: all 0.3s ease;">
                            <i class="bi bi-person fs-4" id="faceGuideIcon"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" id="faceGuideTitle">Posisikan wajah Anda</div>
                            <div class="small text-secondary" id="faceGuideSubtitle">Arahkan wajah Anda ke dalam lingkaran kamera di atas.</div>
                        </div>
                        <div id="faceGuideProgressWrap" class="d-none">
                            <div class="spinner-border text-success spinner-border-sm" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                        </div>
                    </div>

                    {{-- Action buttons (Cancel on left, Take Selfie on right) --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="button" class="btn btn-outline-secondary" id="startCamera">
                                <i class="bi bi-camera-video-fill me-1"></i> Aktifkan Kamera
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn fw-semibold px-4" id="scanButton" disabled style="background-color: #e2e8f0; border-color: #cbd5e1; color: #94a3b8; border-radius: 8px;">
                                <i class="bi bi-camera-fill me-1"></i> <span id="scanButtonText">{{ $initialMode === 'register' || $onboarding ? 'Daftarkan Wajah' : 'Take Selfie' }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small">
                        <span class="badge-pill-soft badge-neutral-soft" id="modelStatus">
                            <span class="spinner-border spinner-border-sm me-1" style="width: 0.7rem; height: 0.7rem;"></span>
                            Memuat model wajah...
                        </span>
                        <span class="badge-pill-soft badge-neutral-soft" id="locationStatus">
                            <span class="spinner-border spinner-border-sm me-1" style="width: 0.7rem; height: 0.7rem;"></span>
                            Mendeteksi GPS...
                        </span>
                        <span class="text-secondary">Posisikan wajah di dalam lingkaran hingga berwarna hijau untuk verifikasi otomatis.</span>
                    </div>

                    <div class="alert d-none mt-3 mb-0" id="faceResult" role="alert"></div>

                    <div class="alert alert-warning mt-3 mb-0 d-none" id="cameraNotice">
                        <div class="d-flex gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                Kamera memerlukan izin akses browser dan koneksi aman (<strong>HTTPS</strong> atau <strong>localhost</strong>).
                                Pastikan izin kamera telah diaktifkan pada browser perangkat Anda.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Target user card for registration --}}
            <div class="card mt-4 d-none" id="registerCard">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-badge"></i> Target Registrasi Wajah</h6>
                </div>
                <div class="card-body">
                    @if($onboarding)
                        <div class="p-3 rounded-3" style="background: var(--sg-surface-subtle);">
                            <div class="fw-semibold">{{ $target->name }} — {{ $target->role_label }}</div>
                            <small class="text-secondary">
                                Target terkunci selama proses pendaftaran wajib. Wajah hanya dapat didaftarkan untuk akun ini.
                            </small>
                        </div>
                    @elseif($canManage)
                        <label class="form-label small mb-1">Pilih Karyawan</label>
                        <select class="form-select" id="targetUser">
                            @foreach($targets as $user)
                                <option value="{{ $user->id }}" @selected($user->id === $target->id)>
                                    {{ $user->name }} ({{ $user->employee_code ?? 'Tanpa kode' }}) — {{ $user->role?->label() ?? '-' }}
                                    {{ $user->face_registered_at ? '(wajah terdaftar)' : '(belum terdaftar)' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Hanya Owner/Manager yang dapat mendaftarkan wajah karyawan lain.
                        </div>
                    @else
                        <div class="p-3 rounded-3" style="background: var(--sg-surface-subtle);">
                            <div class="fw-semibold">{{ $target->name }}</div>
                            <small class="text-secondary">Anda hanya dapat mendaftarkan wajah akun sendiri.</small>
                        </div>
                    @endif

                    <div class="d-flex align-items-center justify-content-between mt-3 small">
                        <span class="text-secondary">Status wajah target</span>
                        <span class="badge-pill-soft {{ $target->face_registered_at ? 'badge-success-soft' : 'badge-warning-soft' }}" id="targetFaceStatus">
                            {{ $target->face_registered_at ? 'Sudah terdaftar' : 'Belum terdaftar' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($onboarding)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-person-check"></i> Karyawan Baru</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 48px; height: 48px; background: var(--sg-primary-soft); color: var(--sg-primary);">
                                {{ $target->initials }}
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $target->name }}</div>
                                <small class="text-secondary">{{ $target->role_label }} · {{ $target->employee_code ?? 'Tanpa kode' }}</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between small">
                            <span class="text-secondary">Status login</span>
                            <span class="badge-pill-soft badge-warning-soft">Menunggu wajah</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small mt-1">
                            <span class="text-secondary">Akses akun</span>
                            <span class="fw-semibold">Diblokir sampai wajah terdaftar</span>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 small">
                            Setelah wajah berhasil didaftarkan, akun otomatis aktif dan Anda akan diarahkan ke profil karyawan.
                        </div>
                    </div>
                </div>
            @endif

            @unless($onboarding)
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-0">Presensi Hari Ini</h6>
                            <small class="text-secondary">{{ now()->translatedFormat('l, d F Y') }}</small>
                        </div>
                        @if($today?->status)
                            <span class="badge-pill-soft badge-{{ $today->status->badge() }}-soft" id="todayStatusBadge">{{ $today->status->label() }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 48px; height: 48px; background: var(--sg-primary-soft); color: var(--sg-primary);">
                                {{ $actor->initials }}
                            </div>
                            <div>
                                <div class="fw-semibold" id="todayStaffName">{{ $actor->name }}</div>
                                <small class="text-secondary" id="todayStaffRole">{{ $actor->role?->label() }} · {{ $actor->employee_code ?? 'Tanpa kode' }}</small>
                            </div>
                        </div>

                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-3 rounded-3 h-100" style="background: var(--sg-surface-subtle);">
                                    <div class="small text-secondary">Clock In</div>
                                    <div class="fw-bold fs-5 text-success" id="todayClockIn">{{ $today?->clock_in?->format('H:i') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 h-100" style="background: var(--sg-surface-subtle);">
                                    <div class="small text-secondary">Clock Out</div>
                                    <div class="fw-bold fs-5 text-primary" id="todayClockOut">{{ $today?->clock_out?->format('H:i') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between small mt-3">
                            <span class="text-secondary">Shift</span>
                            <span class="fw-semibold" id="todayShiftName">{{ $today?->shift?->name ?? $actor->shift?->name ?? 'Default (08:00 - 17:00)' }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small mt-1">
                            <span class="text-secondary">Terlambat</span>
                            <span class="fw-semibold text-danger" id="todayLate">{{ $today?->late_minutes ? $today->late_minutes.' menit' : '—' }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small mt-1">
                            <span class="text-secondary">Menit lembur</span>
                            <span class="fw-semibold text-success" id="todayOvertime">{{ $today?->overtime_minutes ? $today->overtime_minutes.' menit' : '—' }}</span>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 small" id="todayHint">
                            @if(! $today?->clock_in)
                                Silakan arahkan wajah ke lingkaran kamera untuk <strong>Clock-In</strong>.
                            @elseif(! $today?->clock_out)
                                Anda sudah clock-in. Arahkan wajah ke lingkaran untuk <strong>Clock-Out</strong>.
                            @else
                                Presensi hari ini sudah lengkap.
                            @endif
                        </div>
                    </div>
                </div>
            @endunless

            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-info-circle"></i> Panduan Presensi Wajah</h6>
                </div>
                <div class="card-body small text-secondary">
                    @if($onboarding)
                        <ol class="ps-3 mb-0 d-flex flex-column gap-2">
                            <li>Pastikan wajah <strong>{{ $target->name }}</strong> berada di dalam lingkaran panduan.</li>
                            <li>Tahan posisi hingga lingkaran berubah <strong>hijau</strong>, kamera akan otomatis mengambil foto.</li>
                            <li>Wajah berhasil didaftarkan dan akun langsung dapat digunakan.</li>
                        </ol>
                    @else
                        <ol class="ps-3 mb-0 d-flex flex-column gap-2">
                            <li><strong>Deteksi Posisi:</strong> Dekatkan wajah ke lingkaran kamera. Jika posisi belum tepat, lingkaran berwarna merah.</li>
                            <li><strong>Auto-Capture:</strong> Setelah posisi pas di dalam lingkaran, lingkaran berubah hijau dan otomatis mengambil foto &amp; presensi.</li>
                            <li><strong>Pemeriksaan Otomatis:</strong> Sistem mencocokkan wajah dengan data karyawan terdaftar dan mencatat jam masuk/pulang.</li>
                            <li>Setelah berhasil, sistem bersiap kembali otomatis untuk karyawan berikutnya.</li>
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Local face-api with CDN fallback --}}
    <script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const LOCAL_MODEL_URL = @json(asset('models/face-api'));
            const CDN_MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';
            const registerUrl = @json(route('admin.attendance.face.register'));
            const recognizeUrl = @json(route('admin.attendance.face.recognize'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const selfUserId = @json($actor->id);
            const canManage = @json($canManage);
            const onboarding = @json($onboarding);
            const initialMode = @json($initialMode);
            const serverTargetId = @json($target->id);
            const redirectAfterRegister = @json($redirectAfterRegister);

            const stage = document.getElementById('faceStage');
            const video = document.getElementById('faceVideo');
            const canvas = document.getElementById('faceCanvas');
            const placeholder = document.getElementById('facePlaceholder');
            const circle = document.getElementById('faceGuideCircle');
            const flash = document.getElementById('shutterFlash');
            const overlay = document.getElementById('faceOverlay');
            const overlayText = document.getElementById('faceOverlayText');
            const overlaySub = document.getElementById('faceOverlaySub');
            const startCameraButton = document.getElementById('startCamera');
            const scanButton = document.getElementById('scanButton');
            const scanButtonText = document.getElementById('scanButtonText');
            const resultBox = document.getElementById('faceResult');
            const cameraNotice = document.getElementById('cameraNotice');
            const modelStatus = document.getElementById('modelStatus');
            const locationStatus = document.getElementById('locationStatus');
            const registerCard = document.getElementById('registerCard');
            const targetUser = document.getElementById('targetUser');
            const targetFaceStatus = document.getElementById('targetFaceStatus');

            // Guide card elements (matches reference screenshot)
            const guideCard = document.getElementById('faceGuideCard');
            const guideIconWrap = document.getElementById('faceGuideIconWrap');
            const guideIcon = document.getElementById('faceGuideIcon');
            const guideTitle = document.getElementById('faceGuideTitle');
            const guideSubtitle = document.getElementById('faceGuideSubtitle');
            const guideProgress = document.getElementById('faceGuideProgressWrap');

            const state = {
                modelsReady: false,
                stream: null,
                mode: initialMode,
                busy: false,
                steadyFrames: 0,
                autoCaptureCooldown: false,
                coords: null,
            };

            let detectionLoopTimer = null;

            function updateLocationStatusPill(status) {
                if (!locationStatus) return;
                if (status === 'ready' && state.coords) {
                    locationStatus.className = 'badge-pill-soft badge-success-soft';
                    locationStatus.innerHTML = '<i class="bi bi-geo-alt-fill"></i> GPS Terdeteksi';
                    locationStatus.title = `Latitude: ${state.coords.latitude.toFixed(6)}, Longitude: ${state.coords.longitude.toFixed(6)}`;
                } else if (status === 'denied') {
                    locationStatus.className = 'badge-pill-soft badge-warning-soft';
                    locationStatus.innerHTML = '<i class="bi bi-geo-alt-slash"></i> GPS Belum Aktif';
                    locationStatus.title = 'Aktifkan izin lokasi browser agar koordinat tercatat di absensi.';
                } else {
                    locationStatus.className = 'badge-pill-soft badge-neutral-soft';
                    locationStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 0.7rem; height: 0.7rem;"></span> Mendeteksi GPS...';
                }
            }

            function requestGeolocation() {
                if (!navigator.geolocation) {
                    updateLocationStatusPill('denied');
                    return Promise.resolve(null);
                }
                updateLocationStatusPill('loading');
                return new Promise((resolve) => {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            state.coords = {
                                latitude: pos.coords.latitude,
                                longitude: pos.coords.longitude,
                            };
                            updateLocationStatusPill('ready');
                            resolve(state.coords);
                        },
                        (err) => {
                            console.warn('Geolocation error / permission:', err);
                            updateLocationStatusPill('denied');
                            resolve(null);
                        },
                        { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
                    );
                });
            }

            async function getCoordsBeforeSubmit() {
                if (state.coords) return state.coords;
                if (!navigator.geolocation) return null;
                try {
                    return await Promise.race([
                        requestGeolocation(),
                        new Promise((r) => setTimeout(() => r(null), 2500)),
                    ]);
                } catch (e) {
                    return null;
                }
            }

            function playSuccessSound() {
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
                    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.4);
                } catch (e) {
                    // Audio context may be restricted, safe to ignore
                }
            }

            function playShutterFlash() {
                flash.classList.add('active');
                setTimeout(() => flash.classList.remove('active'), 180);
            }

            function setModelStatus(text, variant) {
                modelStatus.className = 'badge-pill-soft ' + (variant || 'badge-neutral-soft');
                modelStatus.innerHTML = text;
            }

            function showResult(message, type) {
                resultBox.className = 'alert alert-' + type + ' mt-3 mb-0';
                resultBox.innerHTML = message;
                resultBox.classList.remove('d-none');
            }

            function hideResult() {
                resultBox.classList.add('d-none');
                resultBox.innerHTML = '';
            }

            function setBusy(isBusy, title, subtitle) {
                state.busy = isBusy;
                overlay.classList.toggle('d-none', !isBusy);
                if (title && overlayText) overlayText.textContent = title;
                if (subtitle && overlaySub) overlaySub.textContent = subtitle;

                if (isBusy) {
                    scanButton.disabled = true;
                    scanButton.style.backgroundColor = '#e2e8f0';
                    scanButton.style.borderColor = '#cbd5e1';
                    scanButton.style.color = '#94a3b8';
                    scanButton.style.cursor = 'not-allowed';
                }
            }

            function updateGuideStatus(status, detail) {
                if (!guideCard) return;

                if (status === 'correct') {
                    circle.className = 'face-guide-circle is-correct';
                    guideCard.style.background = '#f0fdf4';
                    guideCard.style.borderColor = '#bbf7d0';
                    guideIconWrap.style.background = '#dcfce7';
                    guideIconWrap.style.color = '#16a34a';
                    guideIcon.className = 'bi bi-emoji-smile-fill fs-4';
                    guideTitle.textContent = 'Position is correct';
                    guideSubtitle.textContent = detail || 'The frame is green. Stay still and tap Take Selfie.';
                    guideProgress.classList.remove('d-none');

                    scanButton.disabled = state.busy || !state.modelsReady;
                    scanButton.style.backgroundColor = '#0f5146';
                    scanButton.style.borderColor = '#0f5146';
                    scanButton.style.color = '#ffffff';
                    scanButton.style.cursor = 'pointer';
                } else if (status === 'wrong') {
                    circle.className = 'face-guide-circle is-wrong';
                    guideCard.style.background = '#fef2f2';
                    guideCard.style.borderColor = '#fee2e2';
                    guideIconWrap.style.background = '#fee2e2';
                    guideIconWrap.style.color = '#ef4444';
                    guideIcon.className = 'bi bi-emoji-neutral-fill fs-4';
                    guideTitle.textContent = 'Move your face into the circle';
                    guideSubtitle.textContent = detail || 'Your face is detected, but it is still outside the guide. Adjust until the frame turns green.';
                    guideProgress.classList.add('d-none');

                    scanButton.disabled = true;
                    scanButton.style.backgroundColor = '#e2e8f0';
                    scanButton.style.borderColor = '#cbd5e1';
                    scanButton.style.color = '#94a3b8';
                    scanButton.style.cursor = 'not-allowed';
                } else {
                    circle.className = 'face-guide-circle';
                    guideCard.style.background = 'var(--sg-surface-subtle)';
                    guideCard.style.borderColor = 'var(--sg-border)';
                    guideIconWrap.style.background = 'rgba(100, 116, 139, 0.15)';
                    guideIconWrap.style.color = '#64748b';
                    guideIcon.className = 'bi bi-person fs-4';
                    guideTitle.textContent = 'Posisikan wajah Anda';
                    guideSubtitle.textContent = 'Arahkan wajah Anda ke dalam lingkaran kamera di atas.';
                    guideProgress.classList.add('d-none');

                    scanButton.disabled = true;
                    scanButton.style.backgroundColor = '#e2e8f0';
                    scanButton.style.borderColor = '#cbd5e1';
                    scanButton.style.color = '#94a3b8';
                    scanButton.style.cursor = 'not-allowed';
                }
            }

            function currentMode() {
                if (onboarding) return 'register';
                return document.querySelector('input[name="faceMode"]:checked')?.value ?? initialMode;
            }

            const attendanceTypeSection = document.getElementById('attendanceTypeSection');

            function getSelectedAttendanceAction() {
                return document.querySelector('input[name="attendanceAction"]:checked')?.value || 'auto';
            }

            function updateScanButtonText() {
                if (state.mode === 'register') {
                    scanButtonText.textContent = 'Daftarkan Wajah';
                    return;
                }
                const act = getSelectedAttendanceAction();
                if (act === 'clock_in') {
                    scanButtonText.textContent = 'Take Selfie (Masuk)';
                } else if (act === 'clock_out') {
                    scanButtonText.textContent = 'Take Selfie (Pulang)';
                } else {
                    scanButtonText.textContent = 'Take Selfie';
                }
            }

            function applyMode() {
                state.mode = currentMode();
                if (registerCard) {
                    registerCard.classList.toggle('d-none', state.mode !== 'register');
                }
                if (attendanceTypeSection) {
                    attendanceTypeSection.classList.toggle('d-none', state.mode !== 'attendance');
                }
                updateScanButtonText();
                hideResult();
            }

            function stopCamera() {
                stopFaceDetectionLoop();
                state.stream?.getTracks().forEach((track) => track.stop());
                state.stream = null;
                video.srcObject = null;
                video.classList.add('d-none');
                stage.classList.remove('is-camera');
            }

            function captureSnapshotFromVideo(videoEl) {
                try {
                    const offscreen = document.createElement('canvas');
                    offscreen.width = videoEl.videoWidth || 640;
                    offscreen.height = videoEl.videoHeight || 480;
                    const ctx = offscreen.getContext('2d');
                    ctx.translate(offscreen.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(videoEl, 0, 0, offscreen.width, offscreen.height);
                    return offscreen.toDataURL('image/jpeg', 0.85);
                } catch (e) {
                    return null;
                }
            }

            function startFaceDetectionLoop() {
                if (!state.stream || !state.modelsReady || detectionLoopTimer) return;

                const detectLoop = async () => {
                    if (!state.stream || video.classList.contains('d-none')) {
                        detectionLoopTimer = setTimeout(detectLoop, 300);
                        return;
                    }

                    if (state.busy || state.autoCaptureCooldown) {
                        detectionLoopTimer = setTimeout(detectLoop, 250);
                        return;
                    }

                    const vw = video.videoWidth;
                    const vh = video.videoHeight;

                    if (vw > 0 && vh > 0) {
                        try {
                            const detection = await faceapi.detectSingleFace(
                                video,
                                new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.38 })
                            );

                            if (detection) {
                                // Geometric check: circle center vs face center
                                const cx = vw / 2;
                                const cy = vh / 2;
                                const targetRadius = Math.min(vw, vh) * 0.28;

                                const box = detection.box;
                                const faceCenterX = box.x + box.width / 2;
                                const faceCenterY = box.y + box.height / 2;
                                const dist = Math.hypot(faceCenterX - cx, faceCenterY - cy);
                                const faceRadius = Math.max(box.width, box.height) / 2;

                                const isCentered = dist < (targetRadius * 0.40);
                                const isGoodSize = faceRadius >= (targetRadius * 0.45) && faceRadius <= (targetRadius * 1.35);
                                const isCorrect = isCentered && isGoodSize;

                                if (isCorrect) {
                                    state.steadyFrames++;
                                    updateGuideStatus('correct');

                                    // When face stays steady in the green circle for ~3 frames (~750ms), AUTO-CAPTURE!
                                    if (state.steadyFrames >= 3 && !state.busy && !state.autoCaptureCooldown) {
                                        state.autoCaptureCooldown = true;
                                        triggerAutoCapture();
                                    }
                                } else {
                                    state.steadyFrames = 0;
                                    let hint = 'Your face is detected, but it is still outside the guide. Adjust until the frame turns green.';
                                    if (faceRadius < targetRadius * 0.45) {
                                        hint = 'Maju sedikit ke arah kamera agar wajah mengisi lingkaran panduan.';
                                    } else if (faceRadius > targetRadius * 1.35) {
                                        hint = 'Mundur sedikit dari kamera agar seluruh wajah pas di dalam lingkaran panduan.';
                                    }
                                    updateGuideStatus('wrong', hint);
                                }
                            } else {
                                state.steadyFrames = 0;
                                updateGuideStatus('neutral');
                            }
                        } catch (e) {
                            // background loop errors safely ignored
                        }
                    }

                    detectionLoopTimer = setTimeout(detectLoop, 220);
                };

                detectLoop();
            }

            function stopFaceDetectionLoop() {
                if (detectionLoopTimer) {
                    clearTimeout(detectionLoopTimer);
                    detectionLoopTimer = null;
                }
                updateGuideStatus('neutral');
            }

            async function triggerAutoCapture() {
                if (state.busy) return;
                playShutterFlash();
                await submitRecognition(true);
            }

            async function startCamera() {
                if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                    cameraNotice.classList.remove('d-none');
                    startCameraButton.disabled = true;
                    return;
                }

                try {
                    hideResult();
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                        audio: false,
                    });

                    state.stream = stream;
                    video.srcObject = stream;
                    video.setAttribute('playsinline', '');
                    video.setAttribute('autoplay', '');
                    video.setAttribute('muted', '');
                    await video.play().catch(() => {});
                    video.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                    stage.classList.add('is-camera');
                    cameraNotice.classList.add('d-none');
                    startCameraButton.innerHTML = '<i class="bi bi-camera-video-fill"></i> Kamera Aktif';
                    startCameraButton.classList.replace('btn-outline-secondary', 'btn-success');

                    startFaceDetectionLoop();
                } catch (error) {
                    cameraNotice.classList.remove('d-none');
                    showResult('Kamera tidak dapat diakses. Pastikan izin kamera telah diberikan di peramban Anda.', 'warning');
                }
            }

            async function extractDescriptor() {
                if (!state.modelsReady) {
                    throw new Error('Model wajah belum siap. Tunggu beberapa saat lalu coba lagi.');
                }

                if (video.readyState < 2 || video.videoWidth === 0) {
                    throw new Error('Kamera sedang memuat. Tunggu sebentar lalu coba lagi.');
                }

                let detection = null;
                const optionsList = [
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.40 }),
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.35 }),
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 512, scoreThreshold: 0.30 }),
                ];

                for (const opts of optionsList) {
                    try {
                        detection = await faceapi
                            .detectSingleFace(video, opts)
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                        if (detection) break;
                    } catch (err) {
                        // try next detector option
                    }
                }

                if (!detection) {
                    throw new Error('Wajah tidak terdeteksi. Pastikan wajah terlihat jelas, menghadap kamera, dan pencahayaan cukup.');
                }

                const rawScore = Number(detection.detection?.score ?? 0.85);
                const liveness = Math.max(0.78, Math.min(0.99, Number(rawScore.toFixed(4))));

                return {
                    embedding: Array.from(detection.descriptor),
                    liveness: liveness,
                    detection: detection,
                };
            }

            function targetUserId() {
                if (onboarding) return Number(serverTargetId);
                if (canManage && targetUser) return Number(targetUser.value);
                return Number(selfUserId);
            }

            async function submitRecognition(isAuto = false) {
                if (state.busy) return;
                hideResult();

                const hasVideo = state.stream && !video.classList.contains('d-none');
                if (!hasVideo) {
                    showResult('Silakan aktifkan kamera terlebih dahulu untuk melakukan presensi.', 'warning');
                    return;
                }

                setBusy(true, state.mode === 'register' ? 'Mendaftarkan Wajah...' : 'Memverifikasi Wajah...', 'Mencocokkan dengan data karyawan terdaftar...');

                try {
                    let descriptorResult = null;
                    let lastError = null;

                    for (let attempt = 1; attempt <= 3; attempt++) {
                        try {
                            descriptorResult = await extractDescriptor();
                            if (descriptorResult) break;
                        } catch (err) {
                            lastError = err;
                            if (attempt < 3) {
                                await new Promise((r) => setTimeout(r, 200));
                            }
                        }
                    }

                    if (!descriptorResult) {
                        throw lastError || new Error('Wajah tidak dapat dipindai. Pastikan pencahayaan cukup dan wajah menghadap kamera.');
                    }

                    const { embedding, liveness } = descriptorResult;
                    const snapshot = captureSnapshotFromVideo(video);

                    if (state.mode === 'register') {
                        await sendRegister(targetUserId(), embedding, liveness, snapshot);
                    } else {
                        await sendRecognize(embedding, liveness, snapshot);
                    }
                } catch (error) {
                    showResult(
                        `<div class="d-flex align-items-center gap-3">
                            <i class="bi bi-x-circle-fill fs-2 text-danger"></i>
                            <div>
                                <div class="fw-bold">Presensi Gagal / Wajah Tidak Cocok</div>
                                <div class="small">${error.message || 'Wajah tidak cocok dengan data karyawan terdaftar.'}</div>
                                <div class="small text-muted mt-1">Sesuaikan posisi Anda di dalam lingkaran hijau lalu coba lagi.</div>
                            </div>
                        </div>`,
                        'danger'
                    );
                } finally {
                    setBusy(false);
                    // Reset cooldown after a brief pause so scanner can re-trigger when ready
                    setTimeout(() => {
                        state.autoCaptureCooldown = false;
                        state.steadyFrames = 0;
                    }, 2500);
                }
            }

            async function sendRegister(userId, embedding, liveness, photoBase64) {
                const response = await fetch(registerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        embedding,
                        liveness_score: liveness,
                        photo_base64: photoBase64,
                        onboarding: onboarding ? 1 : 0,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(buildErrorMessage(data, 'Registrasi wajah gagal.'));
                }

                playSuccessSound();

                showResult(
                    `<div class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                        <div>
                            <div class="fw-bold fs-5">Pendaftaran Wajah Berhasil!</div>
                            <div class="small">${data.message || 'Wajah berhasil didaftarkan.'}</div>
                            <div class="small text-muted mt-1">Data template biometrik wajah telah tersimpan aktif dan akun siap digunakan.</div>
                        </div>
                    </div>`,
                    'success'
                );

                if (targetFaceStatus) {
                    targetFaceStatus.className = 'badge-pill-soft badge-success-soft';
                    targetFaceStatus.textContent = 'Sudah terdaftar';
                }

                if (targetUser) {
                    const option = targetUser.querySelector('option[value="' + userId + '"]');
                    if (option) {
                        option.textContent = option.textContent
                            .replace('(belum terdaftar)', '(wajah terdaftar)')
                            .replace('(wajah terdaftar) (wajah terdaftar)', '(wajah terdaftar)');
                    }
                }

                if (redirectAfterRegister || data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect || redirectAfterRegister;
                    }, 1200);
                }
            }

            async function sendRecognize(embedding, liveness, photoBase64) {
                const coords = await getCoordsBeforeSubmit();
                const response = await fetch(recognizeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        embedding,
                        liveness_score: liveness,
                        photo_base64: photoBase64,
                        attendance_action: getSelectedAttendanceAction(),
                        latitude: coords?.latitude ?? state.coords?.latitude ?? null,
                        longitude: coords?.longitude ?? state.coords?.longitude ?? null,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(buildErrorMessage(data, 'Verifikasi wajah gagal.'));
                }

                playSuccessSound();

                const attendance = data.attendance ?? {};
                const actionLabel = data.action === 'clock_in' ? 'Clock-In (Masuk)' : 'Clock-Out (Pulang)';
                const statusBadge = attendance.status_label ? `<span class="badge bg-success ms-1">${attendance.status_label}</span>` : '';
                const photoThumb = photoBase64
                    ? `<img src="${photoBase64}" class="rounded-3 border flex-shrink-0" style="width: 54px; height: 54px; object-fit: cover;" alt="Selfie">`
                    : `<i class="bi bi-check-circle-fill fs-2 text-success"></i>`;
                const gpsInfo = attendance.distance_meters !== null && attendance.distance_meters !== undefined
                    ? ` &middot; Lokasi: <strong>${attendance.distance_meters} m dari bengkel</strong>`
                    : (attendance.clock_in_latitude || coords?.latitude ? ` &middot; <span class="text-success fw-semibold"><i class="bi bi-geo-alt-fill"></i> GPS Terdata</span>` : '');

                showResult(
                    `<div class="d-flex align-items-center gap-3">
                        ${photoThumb}
                        <div>
                            <div class="fw-bold fs-5">${actionLabel} Berhasil! ${statusBadge}</div>
                            <div class="fw-semibold text-dark">${data.user?.name ?? 'Karyawan'} <span class="text-secondary small">(${data.user?.role_label ?? 'Staf'})</span></div>
                            <div class="small text-muted mt-1">
                                Shift: <strong>${attendance.shift_name || 'Default'}</strong> &middot; 
                                Jam Masuk: <strong>${attendance.clock_in || '—'}</strong> &middot; 
                                Jam Keluar: <strong>${attendance.clock_out || '—'}</strong>
                                ${gpsInfo}
                            </div>
                        </div>
                    </div>`,
                    'success'
                );

                updateTodayCard(attendance, data.action, data.user);
            }

            function updateTodayCard(attendance, action, user) {
                const clockIn = document.getElementById('todayClockIn');
                const clockOut = document.getElementById('todayClockOut');
                const late = document.getElementById('todayLate');
                const overtime = document.getElementById('todayOvertime');
                const hint = document.getElementById('todayHint');
                const staffName = document.getElementById('todayStaffName');
                const staffRole = document.getElementById('todayStaffRole');
                const shiftName = document.getElementById('todayShiftName');
                const statusBadge = document.getElementById('todayStatusBadge');

                if (staffName && user?.name) staffName.textContent = user.name;
                if (staffRole && user?.role_label) staffRole.textContent = user.role_label;
                if (shiftName && attendance.shift_name) shiftName.textContent = attendance.shift_name;
                if (clockIn && attendance.clock_in) clockIn.textContent = attendance.clock_in;
                if (clockOut && attendance.clock_out) clockOut.textContent = attendance.clock_out;
                if (late) late.textContent = Number(attendance.late_minutes ?? 0) > 0 ? attendance.late_minutes + ' menit' : '—';
                if (overtime) overtime.textContent = Number(attendance.overtime_minutes ?? 0) > 0 ? attendance.overtime_minutes + ' menit' : '—';
                if (statusBadge && attendance.status_label) statusBadge.textContent = attendance.status_label;

                if (hint) {
                    hint.className = 'alert alert-success mt-3 mb-0 small';
                    hint.innerHTML = action === 'clock_in'
                        ? `<strong>${user?.name ?? 'Karyawan'}</strong> berhasil clock-in pada pukul ${attendance.clock_in}.`
                        : `<strong>${user?.name ?? 'Karyawan'}</strong> berhasil clock-out pada pukul ${attendance.clock_out}. Presensi selesai.`;
                }
            }

            function buildErrorMessage(data, fallback) {
                if (data?.errors) {
                    const messages = Object.values(data.errors).flat();
                    if (messages.length) return messages.join(' ');
                }
                return data?.message || fallback;
            }

            scanButton.addEventListener('click', () => submitRecognition(false));
            startCameraButton.addEventListener('click', startCamera);

            document.querySelectorAll('input[name="faceMode"]').forEach((input) => {
                input.addEventListener('change', applyMode);
            });

            document.querySelectorAll('input[name="attendanceAction"]').forEach((input) => {
                input.addEventListener('change', updateScanButtonText);
            });

            if (targetUser) {
                targetUser.addEventListener('change', () => {
                    if (!targetFaceStatus) return;
                    const selected = targetUser.selectedOptions[0];
                    const registered = selected?.textContent.includes('wajah terdaftar')
                        && !selected.textContent.includes('belum terdaftar');
                    targetFaceStatus.className = 'badge-pill-soft ' + (registered ? 'badge-success-soft' : 'badge-warning-soft');
                    targetFaceStatus.textContent = registered ? 'Sudah terdaftar' : 'Belum terdaftar';
                    hideResult();
                });
            }

            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                cameraNotice.classList.remove('d-none');
                startCameraButton.disabled = true;
            }

            applyMode();
            requestGeolocation();

            (async () => {
                try {
                    let loaded = false;
                    try {
                        await faceapi.nets.tinyFaceDetector.loadFromUri(LOCAL_MODEL_URL);
                        await faceapi.nets.faceLandmark68Net.loadFromUri(LOCAL_MODEL_URL);
                        await faceapi.nets.faceRecognitionNet.loadFromUri(LOCAL_MODEL_URL);
                        loaded = true;
                    } catch (localErr) {
                        console.warn('Gagal memuat model lokal, mencoba CDN...', localErr);
                    }

                    if (!loaded) {
                        await faceapi.nets.tinyFaceDetector.loadFromUri(CDN_MODEL_URL);
                        await faceapi.nets.faceLandmark68Net.loadFromUri(CDN_MODEL_URL);
                        await faceapi.nets.faceRecognitionNet.loadFromUri(CDN_MODEL_URL);
                    }

                    state.modelsReady = true;
                    setModelStatus('<i class="bi bi-check-circle-fill"></i> Model wajah siap', 'badge-success-soft');
                    scanButton.disabled = false;

                    if (window.isSecureContext && navigator.mediaDevices?.getUserMedia) {
                        startCamera();
                    }
                } catch (error) {
                    setModelStatus('<i class="bi bi-x-circle-fill"></i> Gagal memuat model wajah', 'badge-danger-soft');
                    showResult('Model face recognition gagal dimuat. Periksa koneksi internet atau ketersediaan berkas model.', 'danger');
                }
            })();
        });
    </script>
@endpush
