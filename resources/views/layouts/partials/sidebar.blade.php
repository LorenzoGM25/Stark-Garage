<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
            <span class="brand-mark">
                <i class="bi bi-shield-shaded"></i>
            </span>
            <div class="brand-text">
                <div class="brand-title">STARK-GARAGE</div>
                <small class="brand-subtitle">Automotive ERP System</small>
            </div>
        </a>
        <button class="btn btn-icon btn-ghost d-lg-none text-white" data-sidebar-toggle aria-label="Tutup Menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav flex-grow-1">
        @php($navRole = auth()->user()?->role?->value)
        @php($isOwner = $navRole === 'owner')
        @php($isManagerial = in_array($navRole, ['owner', 'manager'], true))

        <div class="nav-section">
            <div class="nav-section-title">Menu Utama</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <div class="d-flex align-items-center gap-2">
                    <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                    <span class="nav-text">Dashboard</span>
                </div>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Operasional Bengkel</div>
            @if(in_array($navRole, ['owner', 'manager', 'cashier'], true))
                <a class="nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}" href="{{ route('admin.pos.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-cart-check-fill"></i></span>
                        <span class="nav-text">Kasir / POS</span>
                    </div>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-receipt-cutoff"></i></span>
                        <span class="nav-text">Riwayat Transaksi</span>
                    </div>
                </a>
            @endif
            @if($isManagerial)
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-box-seam-fill"></i></span>
                        <span class="nav-text">Produk &amp; Sparepart</span>
                    </div>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}" href="{{ route('admin.expenses.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-cash-stack"></i></span>
                        <span class="nav-text">Biaya Operasional</span>
                    </div>
                </a>
            @endif
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Sumber Daya Manusia</div>
            @if($isManagerial)
                <a class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
                        <span class="nav-text">Data Karyawan</span>
                    </div>
                </a>
            @endif
            <a class="nav-link {{ request()->routeIs('admin.attendance.face*') ? 'active' : '' }}" href="{{ route('admin.attendance.face') }}">
                <div class="d-flex align-items-center gap-2">
                    <span class="nav-icon"><i class="bi bi-person-bounding-box"></i></span>
                    <span class="nav-text">Absensi Wajah</span>
                </div>
            </a>
            @if($isManagerial)
                <a class="nav-link {{ request()->routeIs('admin.attendance.index', 'admin.attendance.today', 'admin.attendance.manual', 'admin.attendance.absent') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-person-check-fill"></i></span>
                        <span class="nav-text">Presensi &amp; Absensi</span>
                    </div>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}" href="{{ route('admin.payroll.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-wallet2"></i></span>
                        <span class="nav-text">Penggajian (Payroll)</span>
                    </div>
                </a>
            @endif
        </div>

        @if($isManagerial)
            <div class="nav-section">
                <div class="nav-section-title">Laporan Keuangan</div>
                <a class="nav-link {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}" href="{{ route('admin.reports.sales') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span>
                        <span class="nav-text">Laporan Penjualan</span>
                    </div>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.reports.attendance') ? 'active' : '' }}" href="{{ route('admin.reports.attendance') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-calendar2-week"></i></span>
                        <span class="nav-text">Laporan Kehadiran</span>
                    </div>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Konfigurasi</div>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-sliders2"></i></span>
                        <span class="nav-text">Pengaturan Sistem</span>
                    </div>
                </a>
            </div>
        @endif

        @if($isOwner)
            <div class="nav-section">
                <div class="nav-section-title">Superadmin</div>
                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}" href="{{ route('admin.accounts.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-shield-lock-fill"></i></span>
                        <span class="nav-text">Kelola Akun</span>
                    </div>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}" href="{{ route('admin.activity.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="nav-icon"><i class="bi bi-clock-history"></i></span>
                        <span class="nav-text">Log Aktivitas</span>
                    </div>
                </a>
            </div>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="avatar-circle">
                {{ strtoupper(mb_substr(auth()->user()?->name ?? 'Admin', 0, 2)) }}
            </div>
            <div class="user-meta">
                <div class="user-name">{{ auth()->user()?->name ?? 'Admin Garage' }}</div>
                <small class="user-role">{{ auth()->user()?->role_label ?? 'Administrator' }}</small>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-icon btn-ghost text-white-50 hover-text-white" title="Keluar" aria-label="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-toggle></div>
