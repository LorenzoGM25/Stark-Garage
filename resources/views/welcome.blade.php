<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>STARK-GARAGE &bull; Sistem Manajemen Bengkel Profesional</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background: radial-gradient(circle at 50% 0%, #1e293b 0%, #090e1a 100%); color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column;">

    <!-- Navbar -->
    <header class="py-4 border-bottom" style="border-color: rgba(255, 255, 255, 0.08); backdrop-filter: blur(12px);">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-mark">
                    <i class="bi bi-shield-shaded"></i>
                </span>
                <div>
                    <span class="fw-bold fs-5 tracking-tight d-block lh-1">STARK-GARAGE</span>
                    <small class="text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">AUTOMOTIVE ERP PLATFORM</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="live-indicator d-none d-sm-inline-flex">
                    <span class="dot"></span>
                    <span>Sistem Siap Operasi</span>
                </span>

                @auth
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-speedometer2"></i> Masuk Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk Akun
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="badge-pill-soft badge-info-soft mb-3 px-3 py-2">
                        <i class="bi bi-cpu"></i> Next-Gen Workshop Operating System
                    </span>
                    <h1 class="display-4 fw-extrabold mb-3 tracking-tight" style="line-height: 1.15;">
                        Solusi Cerdas Manajemen <br class="d-none d-lg-block">
                        <span style="background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Bengkel &amp; Servis Modern
                        </span>
                    </h1>
                    <p class="lead text-secondary mb-4" style="font-size: 1.1rem; max-width: 580px;">
                        Tingkatkan efisiensi bengkel Anda mulai dari POS kasir pintar, manajemen inventaris sparepart, presensi biometrik wajah mekanik, hingga laporan laba-rugi otomatis.
                    </p>

                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3 flex-wrap">
                        @auth
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg px-4">
                                Buka Dashboard Bengkel <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4">
                                Masuk ke Portal Admin <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        @endauth
                    </div>

                    <!-- Highlight pills -->
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-4 mt-5 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.08);">
                        <div>
                            <div class="h5 fw-bold mb-0">100%</div>
                            <small class="text-secondary">Akurasi Transaksi</small>
                        </div>
                        <div class="vr" style="background-color: rgba(255, 255, 255, 0.15);"></div>
                        <div>
                            <div class="h5 fw-bold mb-0">&lt; 1 Detik</div>
                            <small class="text-secondary">Face Recognition</small>
                        </div>
                        <div class="vr" style="background-color: rgba(255, 255, 255, 0.15);"></div>
                        <div>
                            <div class="h5 fw-bold mb-0">Realtime</div>
                            <small class="text-secondary">Laba Bersih &amp; Pajak</small>
                        </div>
                    </div>
                </div>

                <!-- Interactive Preview Hero Card -->
                <div class="col-lg-5">
                    <div class="card p-4" style="background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(16px); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3" style="border-color: rgba(255, 255, 255, 0.08);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-danger d-inline-block" style="width: 10px; height: 10px;"></span>
                                <span class="rounded-circle bg-warning d-inline-block" style="width: 10px; height: 10px;"></span>
                                <span class="rounded-circle bg-success d-inline-block" style="width: 10px; height: 10px;"></span>
                            </div>
                            <span class="badge-pill-soft badge-neutral-soft" style="font-size: 0.72rem;">STARK-POS ENGINE</span>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            <div class="p-3 rounded-3" style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.05);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-secondary">Status Servis Pit 01</span>
                                    <span class="badge-pill-soft badge-success-soft">Sedang Pengerjaan</span>
                                </div>
                                <div class="fw-semibold">Ganti Oli Motul 10W-40 + Tune Up Injeksi</div>
                                <div class="small text-info mt-1"><i class="bi bi-person-fill"></i> Mekanik: Budi Santoso</div>
                            </div>

                            <div class="p-3 rounded-3" style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.05);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-secondary">Estimasi Tagihan</span>
                                    <span class="badge-pill-soft badge-info-soft">QRIS / Tunai</span>
                                </div>
                                <div class="h4 fw-bold mb-0 text-success">Rp 385.000</div>
                                <small class="text-secondary">Termasuk PPN &amp; Diskon Promo</small>
                            </div>

                            <a href="{{ route('login') }}" class="btn btn-primary w-100 py-2 mt-2">
                                <i class="bi bi-lock-fill me-1"></i> Masuk untuk Akses Penuh
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 border-top text-center" style="border-color: rgba(255, 255, 255, 0.08);">
        <div class="container text-secondary small">
            &copy; {{ date('Y') }} STARK-GARAGE Enterprise. Dikembangkan untuk efisiensi bengkel tingkat tinggi.
        </div>
    </footer>

</body>
</html>
