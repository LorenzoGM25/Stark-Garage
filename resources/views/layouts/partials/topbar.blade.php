<header class="app-topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-icon btn-ghost d-lg-none" data-sidebar-toggle aria-label="Buka navigasi">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="page-heading">
            <h1 class="h5 mb-0">@yield('page-title', 'Dashboard')</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Beranda</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
    </div>

    <!-- Quick Search, Status, and Controls -->
    <div class="d-flex align-items-center gap-3">
        <div class="quick-search-box d-none d-md-block">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="globalQuickSearch" class="form-control form-control-sm" placeholder="Cari transaksi, sparepart...">
            <span class="kbd-hint">Ctrl+K</span>
        </div>

        <div class="d-none d-sm-inline-flex">
            <span class="live-indicator">
                <span class="dot"></span>
                <span>Bengkel Aktif</span>
            </span>
        </div>

        <button class="btn btn-icon btn-ghost" id="themeToggle" data-theme-toggle title="Ganti Tema (Gelap/Terang)" aria-label="Ganti tema">
            <i class="bi bi-moon-stars-fill"></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-ghost d-flex align-items-center gap-2 px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-circle avatar-sm">
                    {{ strtoupper(mb_substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                </div>
                <div class="d-none d-lg-block text-start">
                    <div class="small fw-semibold lh-1">{{ auth()->user()?->name ?? 'Administrator' }}</div>
                    <small class="text-secondary" style="font-size: 0.72rem;">{{ auth()->user()?->email ?? 'admin@stark.id' }}</small>
                </div>
                <i class="bi bi-chevron-down small text-secondary d-none d-lg-inline ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg py-2">
                <li class="px-3 py-2 border-bottom mb-1">
                    <div class="fw-semibold text-truncate">{{ auth()->user()?->name ?? 'Administrator' }}</div>
                    <div class="small text-secondary text-truncate">{{ auth()->user()?->email ?? 'admin@stark.id' }}</div>
                </li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle me-2 text-primary"></i>Profil Saya</a></li>
                @if(in_array(auth()->user()?->role?->value, ['owner', 'manager'], true))
                    <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}"><i class="bi bi-sliders me-2 text-info"></i>Pengaturan Sistem</a></li>
                @endif
                @if(auth()->user()?->role?->value === 'owner')
                    <li><a class="dropdown-item" href="{{ route('admin.accounts.index') }}"><i class="bi bi-shield-lock me-2 text-warning"></i>Kelola Akun</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.activity.index') }}"><i class="bi bi-clock-history me-2 text-secondary"></i>Log Aktivitas</a></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
