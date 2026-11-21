<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            if ($user->needsFaceRegistration()) {
                app(AuditLogger::class)->log(
                    'auth.login_blocked',
                    $user,
                    "Login {$user->name} ditolak karena pendaftaran wajah belum selesai",
                    [],
                    [
                        'guard' => $event->guard,
                        'reason' => 'pending_face_registration',
                        'ip' => request()->ip(),
                    ],
                    $user,
                );

                return;
            }

            $user->forceFill(['last_login_at' => now()])->save();

            app(AuditLogger::class)->log(
                'auth.login',
                $user,
                "Login {$user->name}",
                [],
                ['guard' => $event->guard, 'ip' => request()->ip()],
                $user,
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $email = $event->credentials['email'] ?? 'tidak diketahui';
            $subject = User::query()->where('email', $email)->first();

            app(AuditLogger::class)->log(
                'auth.login_failed',
                $subject,
                "Percobaan login gagal untuk {$email}",
                [],
                ['guard' => $event->guard, 'email' => $email, 'ip' => request()->ip()],
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            app(AuditLogger::class)->log(
                'auth.logout',
                $user,
                "Logout {$user->name}",
                [],
                ['guard' => $event->guard],
                $user,
            );
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            app(AuditLogger::class)->log(
                'auth.password_reset',
                $user,
                "Kata sandi {$user->name} direset",
                [],
                ['guard' => 'password-broker'],
                $user,
            );
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            app(AuditLogger::class)->log(
                'auth.lockout',
                null,
                'Akun dikunci sementara karena terlalu banyak percobaan login',
                [],
                ['ip' => $event->request->ip(), 'email' => $event->request->input('email')],
            );
        });

        Event::listen(Registered::class, function (Registered $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            app(AuditLogger::class)->log(
                'account.registered',
                $user,
                "Akun baru {$user->name} didaftarkan",
                [],
                ['email' => $user->email, 'role' => $user->role?->value],
                $user,
            );
        });
    }
}
