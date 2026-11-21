<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'STARK-GARAGE'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="auth-body">
    <main class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="brand-mark"><i class="bi bi-wrench-adjustable"></i></span>
                                <div class="text-start">
                                    <div class="fw-bold text-body-emphasis">STARK-GARAGE</div>
                                    <small class="text-body-secondary">POS • Attendance • Payroll</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="card border-0 shadow-lg auth-card">
                        <div class="card-body p-4 p-lg-5">
                            @yield('content')
                        </div>
                    </div>
                    <p class="text-center text-body-secondary small mt-4 mb-0">&copy; {{ now()->year }} STARK-GARAGE. All rights reserved.</p>
                </div>
            </div>
        </div>
    </main>

    @stack('scripts')
</body>

</html>
