<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AccountWebController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(Role::options()))],
            'status' => ['nullable', Rule::in(['active', 'locked'])],
        ]);

        $accounts = User::query()
            ->when($filters['search'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('employee_code', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'locked', fn ($query) => $query->where('is_active', false))
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 WHEN role = 'manager' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'locked' => User::query()->where('is_active', false)->count(),
            'owners' => User::query()
                ->where('role', Role::Owner->value)
                ->where('is_active', true)
                ->count(),
        ];

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'roles' => Role::options(),
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.accounts.edit', [
            'account' => $user,
            'roles' => Role::options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('users', 'employee_code')->ignore($user->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
        ]);

        $actor = $request->user();
        $newRole = Role::from($validated['role']);

        if ($user->id === $actor->id && $newRole !== Role::Owner) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan jabatan akun Owner Anda sendiri.',
            ]);
        }

        if ($user->role === Role::Owner && $newRole !== Role::Owner && $this->activeOwnerCount($user->id) === 0) {
            throw ValidationException::withMessages([
                'role' => 'Harus ada minimal satu akun Owner yang aktif.',
            ]);
        }

        $before = $this->snapshot($user);

        $user->update([
            'name' => $validated['name'],
            'employee_code' => $validated['employee_code'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $newRole,
        ]);

        $this->syncSpatieRole($user);

        if ($before['email'] !== $user->email) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        $this->audit->log(
            'account.updated',
            $user,
            "Akun {$user->name} diperbarui oleh {$actor->name}",
            $before,
            $this->snapshot($user),
        );

        return redirect()
            ->route('admin.accounts.edit', $user)
            ->with('success', 'Data akun berhasil diperbarui.');
    }

    public function lock(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($user->id === $actor->id) {
            return back()->with('error', 'Anda tidak dapat mengunci akun sendiri.');
        }

        if ($user->role === Role::Owner && $this->activeOwnerCount($user->id) === 0) {
            return back()->with('error', 'Tidak dapat mengunci satu-satunya Owner yang aktif.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Akun ini sudah terkunci.');
        }

        $user->update(['is_active' => false]);
        $this->terminateSessions($user);

        $this->audit->log(
            'account.locked',
            $user,
            "Akun {$user->name} dikunci oleh {$actor->name}",
            ['is_active' => true],
            ['is_active' => false],
        );

        return back()->with('success', "Akun {$user->name} berhasil dikunci dan sesi aktifnya diputus.");
    }

    public function unlock(Request $request, User $user): RedirectResponse
    {
        if ($user->is_active) {
            return back()->with('error', 'Akun ini sudah aktif.');
        }

        $user->update(['is_active' => true]);

        $this->audit->log(
            'account.unlocked',
            $user,
            "Akun {$user->name} dibuka oleh {$request->user()->name}",
            ['is_active' => false],
            ['is_active' => true],
        );

        return back()->with('success', "Akun {$user->name} berhasil dibuka.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordReset', [
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        if ($user->id !== $request->user()->id) {
            $this->terminateSessions($user);
        }

        $this->audit->log(
            'account.password_reset',
            $user,
            "Kata sandi akun {$user->name} direset oleh {$request->user()->name}",
            [],
            ['password_changed' => true, 'sessions_terminated' => $user->id !== $request->user()->id],
        );

        return back()->with('success', "Kata sandi {$user->name} berhasil direset.");
    }

    public function resetFace(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasFace()) {
            return back()->with('error', 'Akun ini belum memiliki data wajah.');
        }

        $previousRegisteredAt = $user->face_registered_at?->toIso8601String();

        $user->forceFill([
            'face_embedding' => null,
            'face_registered_at' => null,
        ])->save();

        $this->audit->log(
            'account.face_reset',
            $user,
            "Data wajah {$user->name} dihapus oleh {$request->user()->name}",
            ['face_registered_at' => $previousRegisteredAt],
            ['face_registered_at' => null],
        );

        return back()->with('success', "Data wajah {$user->name} berhasil dihapus. Karyawan perlu mendaftar ulang.");
    }

    public function forceLogout(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Untuk keluar dari akun sendiri, gunakan tombol Keluar.');
        }

        $this->terminateSessions($user);

        $this->audit->log(
            'account.force_logout',
            $user,
            "Sesi {$user->name} diputus paksa oleh {$request->user()->name}",
            [],
            ['sessions_terminated' => true],
        );

        return back()->with('success', "Semua sesi {$user->name} berhasil diputus.");
    }

    private function terminateSessions(User $user): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        PersonalAccessToken::query()
            ->where('tokenable_type', $user::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();
    }

    private function activeOwnerCount(?int $exceptId = null): int
    {
        return User::query()
            ->where('role', Role::Owner->value)
            ->where('is_active', true)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->count();
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'employee_code' => $user->employee_code,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->value,
            'is_active' => $user->is_active,
        ];
    }

    private function syncSpatieRole(User $user): void
    {
        try {
            if (\Spatie\Permission\Models\Role::query()->where('name', $user->role->value)->exists()) {
                $user->syncRoles([$user->role->value]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
