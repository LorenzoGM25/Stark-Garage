<x-guest-layout>
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3 brand-mark mx-auto" style="width: 3.25rem; height: 3.25rem; font-size: 1.6rem;">
            <i class="bi bi-shield-shaded"></i>
        </div>
        <h2 class="h4 fw-bold mb-1 tracking-tight">Masuk ke STARK-GARAGE</h2>
        <p class="text-secondary small mb-0">Sistem Informasi &amp; Manajemen Operasional Bengkel</p>
    </div>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="d-flex flex-column gap-3">
        @csrf

        <div>
            <label for="email" class="form-label small fw-semibold">Alamat Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@stark.id" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label for="password" class="form-label small fw-semibold mb-0">Kata Sandi</label>
                @if (Route::has('password.request'))
                    <a class="small text-decoration-none text-primary" href="{{ route('password.request') }}">
                        Lupa sandi?
                    </a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label for="remember_me" class="form-check-label small text-secondary">Ingat saya</label>
            </div>
        </div>

        <div class="d-grid mt-2">
            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right"></i> Masuk Sekarang
            </button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="text-center mt-4 pt-3 border-top small text-secondary" style="border-color: var(--sg-border);">
            Belum memiliki akun terdaftar?
            <a class="text-decoration-none fw-semibold text-primary" href="{{ route('register') }}">Registrasi akun</a>
        </div>
    @endif
</x-guest-layout>
