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
            background: var(--sg-surface-subtle);
            border: 1px solid var(--sg-border);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .face-stage video,
        .face-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .face-stage .face-placeholder {
            text-align: center;
            color: var(--sg-text-muted);
            padding: 1rem;
        }
        .face-stage .face-placeholder i {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 0.5rem;
            opacity: 0.45;
        }
        .face-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: #fff;
            text-align: center;
            padding: 1rem;
        }
        .face-frame {
            position: absolute;
            inset: 12% 24%;
            border: 2px dashed rgba(255, 255, 255, 0.75);
            border-radius: 45% 45% 40% 40%;
            pointer-events: none;
        }
        .face-stage.is-camera .face-frame {
            display: block;
        }
        .face-stage:not(.is-camera) .face-frame {
            display: none;
        }
        .face-mode .btn {
            min-width: 150px;
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
        <div class="alert alert-warning d-flex gap-3 align-items-start mb-4" role="alert">
            <i class="bi bi-person-bounding-box fs-3"></i>
            <div>
                <div class="fw-bold">Pendaftaran Wajah Wajib</div>
                <div class="small">
                    Akun <strong>{{ $target->name }}</strong> belum dapat login sebelum wajahnya terdaftar.
                    Selesaikan pendaftaran wajah sekarang. Halaman ini terkunci pada karyawan tersebut.
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-0">Pemindai Wajah</h5>
                        <small class="text-secondary">
                            @if($onboarding)
                                Daftarkan wajah {{ $target->name }} untuk mengaktifkan akses login.
                            @else
                                Gunakan kamera atau unggah foto wajah yang jelas.
                            @endif
                        </small>
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
                    <div class="face-stage" id="faceStage">
                        <video id="faceVideo" autoplay playsinline muted class="d-none"></video>
                        <img id="facePreview" class="d-none" alt="Pratinjau foto wajah">
                        <div class="face-placeholder" id="facePlaceholder">
                            <i class="bi bi-camera-video"></i>
                            <div class="fw-semibold">Kamera belum aktif</div>
                            <small>Klik "Aktifkan Kamera", atau gunakan tombol unggah foto di bawah.</small>
                        </div>
                        <div class="face-frame"></div>
                        <div class="face-overlay d-none" id="faceOverlay">
                            <div class="spinner-border" role="status"></div>
                            <div class="fw-semibold" id="faceOverlayText">Memproses wajah...</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" id="startCamera">
                            <i class="bi bi-camera-video-fill"></i> Aktifkan Kamera
                        </button>
                        <button type="button" class="btn btn-primary" id="scanButton" disabled>
                            <i class="bi bi-upc-scan"></i> <span id="scanButtonText">Scan &amp; Absen</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="captureButton" disabled>
                            <i class="bi bi-camera-fill"></i> Ambil Foto HP
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="uploadButton">
                            <i class="bi bi-image"></i> Unggah Foto
                        </button>
                    </div>

                    <input type="file" id="photoInput" accept="image/*" class="d-none">
                    <input type="file" id="cameraInput" accept="image/*" capture="user" class="d-none">

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small">
                        <span class="badge-pill-soft badge-neutral-soft" id="modelStatus">
                            <span class="spinner-border spinner-border-sm me-1" style="width: 0.7rem; height: 0.7rem;"></span>
                            Memuat model wajah...
                        </span>
                        <span class="text-secondary">Wajah harus terlihat penuh, tidak tertutup masker, dan pencahayaan cukup.</span>
                    </div>

                    <div class="alert d-none mt-3 mb-0" id="faceResult" role="alert"></div>

                    <div class="alert alert-warning mt-3 mb-0 d-none" id="cameraNotice">
                        <div class="d-flex gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                Kamera browser hanya aktif pada <strong>HTTPS</strong> atau <strong>localhost</strong>.
                                Karena halaman ini diakses lewat alamat LAN (HTTP), gunakan tombol
                                <strong>Ambil Foto HP</strong> atau <strong>Unggah Foto</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                    {{ $user->name }} — {{ $user->role?->label() ?? '-' }}
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
                            Setelah wajah berhasil didaftarkan, akun otomatis dapat login dan Anda akan diarahkan ke profil karyawan.
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
                        <span class="badge-pill-soft badge-{{ $today->status->badge() }}-soft">{{ $today->status->label() }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                             style="width: 48px; height: 48px; background: var(--sg-primary-soft); color: var(--sg-primary);">
                            {{ $actor->initials }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $actor->name }}</div>
                            <small class="text-secondary">{{ $actor->role?->label() }} · {{ $actor->employee_code ?? 'Tanpa kode' }}</small>
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-3 rounded-3 h-100" style="background: var(--sg-surface-subtle);">
                                <div class="small text-secondary">Clock In</div>
                                <div class="fw-bold fs-5" id="todayClockIn">{{ $today?->clock_in?->format('H:i') ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 h-100" style="background: var(--sg-surface-subtle);">
                                <div class="small text-secondary">Clock Out</div>
                                <div class="fw-bold fs-5" id="todayClockOut">{{ $today?->clock_out?->format('H:i') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between small mt-3">
                        <span class="text-secondary">Shift</span>
                        <span class="fw-semibold">{{ $today?->shift?->name ?? $actor->shift?->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between small mt-1">
                        <span class="text-secondary">Terlambat</span>
                        <span class="fw-semibold" id="todayLate">{{ $today?->late_minutes ? $today->late_minutes.' menit' : '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between small mt-1">
                        <span class="text-secondary">Menit lembur</span>
                        <span class="fw-semibold" id="todayOvertime">{{ $today?->overtime_minutes ? $today->overtime_minutes.' menit' : '—' }}</span>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small" id="todayHint">
                        @if(! $today?->clock_in)
                            Silakan pindai wajah untuk <strong>clock-in</strong>.
                        @elseif(! $today?->clock_out)
                            Anda sudah clock-in. Pindai wajah lagi untuk <strong>clock-out</strong>.
                        @else
                            Presensi hari ini sudah lengkap.
                        @endif
                    </div>
                </div>
            </div>
            @endunless

            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-info-circle"></i> Panduan</h6>
                </div>
                <div class="card-body small text-secondary">
                    @if($onboarding)
                        <ol class="ps-3 mb-0 d-flex flex-column gap-2">
                            <li>Pastikan wajah <strong>{{ $target->name }}</strong> terlihat penuh dan pencahayaan cukup.</li>
                            <li>Gunakan kamera, ambil foto HP, atau unggah foto wajah yang jelas.</li>
                            <li>Klik <strong>Daftarkan Wajah</strong> dan tunggu proses selesai.</li>
                            <li>Akun langsung dapat login setelah pendaftaran berhasil.</li>
                        </ol>
                    @else
                        <ol class="ps-3 mb-0 d-flex flex-column gap-2">
                            <li>Registrasikan wajah terlebih dahulu melalui mode <strong>Registrasi Wajah</strong>.</li>
                            <li>Untuk absensi, pilih mode <strong>Absensi</strong> lalu pindai wajah.</li>
                            <li>Sistem otomatis menentukan clock-in atau clock-out sesuai data hari ini.</li>
                            <li>Jika wajah tidak dikenali, ulangi dengan pencahayaan lebih baik atau daftarkan ulang wajah.</li>
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';
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
            const preview = document.getElementById('facePreview');
            const placeholder = document.getElementById('facePlaceholder');
            const overlay = document.getElementById('faceOverlay');
            const overlayText = document.getElementById('faceOverlayText');
            const startCameraButton = document.getElementById('startCamera');
            const scanButton = document.getElementById('scanButton');
            const scanButtonText = document.getElementById('scanButtonText');
            const captureButton = document.getElementById('captureButton');
            const uploadButton = document.getElementById('uploadButton');
            const photoInput = document.getElementById('photoInput');
            const cameraInput = document.getElementById('cameraInput');
            const resultBox = document.getElementById('faceResult');
            const cameraNotice = document.getElementById('cameraNotice');
            const modelStatus = document.getElementById('modelStatus');
            const registerCard = document.getElementById('registerCard');
            const targetUser = document.getElementById('targetUser');
            const targetFaceStatus = document.getElementById('targetFaceStatus');

            const state = {
                modelsReady: false,
                stream: null,
                mode: initialMode,
                busy: false,
            };

            const detectorOptions = new faceapi.TinyFaceDetectorOptions({
                inputSize: 416,
                scoreThreshold: 0.45,
            });

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

            function setBusy(isBusy, text) {
                state.busy = isBusy;
                overlay.classList.toggle('d-none', !isBusy);
                if (text) overlayText.textContent = text;
                scanButton.disabled = isBusy || !state.modelsReady;
                captureButton.disabled = isBusy || !state.modelsReady;
            }

            function currentMode() {
                if (onboarding) return 'register';
                return document.querySelector('input[name="faceMode"]:checked')?.value ?? initialMode;
            }

            function applyMode() {
                state.mode = currentMode();
                registerCard.classList.toggle('d-none', state.mode !== 'register');
                scanButtonText.textContent = state.mode === 'register' ? 'Daftarkan Wajah' : 'Scan & Absen';
                hideResult();
            }

            function stopCamera() {
                state.stream?.getTracks().forEach((track) => track.stop());
                state.stream = null;
                video.srcObject = null;
                video.classList.add('d-none');
            }

            function usePreviewImage(source) {
                preview.src = typeof source === 'string' ? source : source.src;
                preview.classList.remove('d-none');
                placeholder.classList.add('d-none');
                video.classList.add('d-none');
                stage.classList.remove('is-camera');
            }

            function restoreCameraView() {
                if (!state.stream) return;
                preview.classList.add('d-none');
                placeholder.classList.add('d-none');
                video.classList.remove('d-none');
                stage.classList.add('is-camera');
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
                    await video.play().catch(() => {});
                    video.classList.remove('d-none');
                    preview.classList.add('d-none');
                    placeholder.classList.add('d-none');
                    stage.classList.add('is-camera');
                    cameraNotice.classList.add('d-none');
                    startCameraButton.innerHTML = '<i class="bi bi-camera-video-fill"></i> Kamera Aktif';
                } catch (error) {
                    cameraNotice.classList.remove('d-none');
                    showResult('Kamera tidak dapat diakses. Gunakan tombol <strong>Ambil Foto HP</strong> atau <strong>Unggah Foto</strong>.', 'warning');
                }
            }

            function loadImageFromFile(file) {
                return new Promise((resolve, reject) => {
                    const objectUrl = URL.createObjectURL(file);
                    const image = new Image();
                    image.onload = () => resolve(image);
                    image.onerror = () => {
                        URL.revokeObjectURL(objectUrl);
                        reject(new Error('Foto tidak dapat dibaca.'));
                    };
                    image.src = objectUrl;
                });
            }

            async function extractDescriptor(source) {
                if (!state.modelsReady) {
                    throw new Error('Model wajah belum siap. Tunggu beberapa saat lalu coba lagi.');
                }

                const detection = await faceapi
                    .detectSingleFace(source, detectorOptions)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    throw new Error('Wajah tidak terdeteksi. Pastikan wajah terlihat penuh dan pencahayaan cukup.');
                }

                const score = Number(detection.detection.score ?? 0);
                if (score < 0.5) {
                    throw new Error('Kualitas deteksi wajah terlalu rendah. Coba dekatkan wajah ke kamera.');
                }

                return {
                    embedding: Array.from(detection.descriptor),
                    liveness: Number(score.toFixed(4)),
                };
            }

            function targetUserId() {
                if (onboarding) return Number(serverTargetId);
                if (canManage && targetUser) return Number(targetUser.value);
                return Number(selfUserId);
            }

            async function submitRecognition() {
                if (state.busy) return;
                hideResult();

                const source = state.stream && !video.classList.contains('d-none') ? video : null;

                if (!source) {
                    const file = await pickFile(cameraInput);
                    if (!file) return;
                    await processImageFile(file);
                    return;
                }

                setBusy(true, state.mode === 'register' ? 'Mendaftarkan wajah...' : 'Memverifikasi wajah...');

                try {
                    const { embedding, liveness } = await extractDescriptor(source);

                    if (state.mode === 'register') {
                        await sendRegister(targetUserId(), embedding, liveness);
                    } else {
                        await sendRecognize(embedding, liveness);
                    }
                } catch (error) {
                    showResult(error.message || 'Terjadi kesalahan saat memproses wajah.', 'danger');
                } finally {
                    setBusy(false);
                }
            }

            async function processImageFile(file) {
                if (!file) return;
                hideResult();

                const image = await loadImageFromFile(file);
                usePreviewImage(image);

                setBusy(true, state.mode === 'register' ? 'Mendaftarkan wajah...' : 'Memverifikasi wajah...');

                try {
                    const { embedding, liveness } = await extractDescriptor(image);

                    if (state.mode === 'register') {
                        await sendRegister(targetUserId(), embedding, liveness);
                    } else {
                        await sendRecognize(embedding, liveness);
                    }
                } catch (error) {
                    showResult(error.message || 'Terjadi kesalahan saat memproses wajah.', 'danger');
                } finally {
                    setBusy(false);
                }
            }

            async function sendRegister(userId, embedding, liveness) {
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
                        onboarding: onboarding ? 1 : 0,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(buildErrorMessage(data, 'Registrasi wajah gagal.'));
                }

                showResult(
                    '<i class="bi bi-check-circle-fill"></i> ' + (data.message || 'Wajah berhasil didaftarkan.') +
                    '<div class="small mt-1">Skor liveness: ' + liveness.toFixed(2) + '</div>',
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

            async function sendRecognize(embedding, liveness) {
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
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(buildErrorMessage(data, 'Verifikasi wajah gagal.'));
                }

                const attendance = data.attendance ?? {};
                const actionLabel = data.action === 'clock_in' ? 'Clock-in' : 'Clock-out';

                showResult(
                    '<i class="bi bi-check-circle-fill"></i> <strong>' + actionLabel + ' berhasil</strong>' +
                    '<div class="mt-1">' + (data.user?.name ?? 'Karyawan') + ' · kemiripan ' + Number(data.score ?? 0).toFixed(2) + '</div>' +
                    '<div class="small mt-1">Masuk: ' + (attendance.clock_in || '—') + ' · Keluar: ' + (attendance.clock_out || '—') + '</div>',
                    'success'
                );

                updateTodayCard(attendance, data.action);
            }

            function updateTodayCard(attendance, action) {
                const clockIn = document.getElementById('todayClockIn');
                const clockOut = document.getElementById('todayClockOut');
                const late = document.getElementById('todayLate');
                const overtime = document.getElementById('todayOvertime');
                const hint = document.getElementById('todayHint');

                if (clockIn && attendance.clock_in) clockIn.textContent = attendance.clock_in;
                if (clockOut && attendance.clock_out) clockOut.textContent = attendance.clock_out;
                if (late) late.textContent = Number(attendance.late_minutes ?? 0) > 0 ? attendance.late_minutes + ' menit' : '—';
                if (overtime) overtime.textContent = Number(attendance.overtime_minutes ?? 0) > 0 ? attendance.overtime_minutes + ' menit' : '—';

                if (hint) {
                    hint.innerHTML = action === 'clock_in'
                        ? 'Anda sudah clock-in. Pindai wajah lagi untuk <strong>clock-out</strong>.'
                        : 'Presensi hari ini sudah lengkap.';
                }
            }

            function buildErrorMessage(data, fallback) {
                if (data?.errors) {
                    const messages = Object.values(data.errors).flat();
                    if (messages.length) return messages.join(' ');
                }
                return data?.message || fallback;
            }

            function pickFile(input) {
                return new Promise((resolve) => {
                    input.value = '';
                    input.onchange = () => resolve(input.files?.[0] ?? null);
                    input.click();
                });
            }

            scanButton.addEventListener('click', submitRecognition);
            startCameraButton.addEventListener('click', startCamera);

            captureButton.addEventListener('click', async () => {
                const file = await pickFile(cameraInput);
                if (file) processImageFile(file);
            });

            uploadButton.addEventListener('click', async () => {
                const file = await pickFile(photoInput);
                if (file) processImageFile(file);
            });

            document.querySelectorAll('input[name="faceMode"]').forEach((input) => {
                input.addEventListener('change', applyMode);
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

            (async () => {
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                    state.modelsReady = true;
                    setModelStatus('<i class="bi bi-check-circle-fill"></i> Model wajah siap', 'badge-success-soft');
                    scanButton.disabled = false;
                    captureButton.disabled = false;

                    if (window.isSecureContext && navigator.mediaDevices?.getUserMedia) {
                        startCamera();
                    }
                } catch (error) {
                    setModelStatus('<i class="bi bi-x-circle-fill"></i> Gagal memuat model wajah', 'badge-danger-soft');
                    showResult('Model face recognition gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.', 'danger');
                }
            })();
        });
    </script>
@endpush
