<?php

namespace App\Http\Controllers\Web;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Enums\SalaryType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeWebController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'role', 'status']);
        $filters['staff_only'] = true;

        $employees = $this->users->paginate($filters, 12);
        $shifts = Shift::query()->active()->orderBy('name')->get();
        $roles = Role::options();

        $stats = [
            'total' => User::query()->staff()->count(),
            'active' => User::query()->active()->staff()->count(),
            'inactive' => User::query()->staff()->where('is_active', false)->count(),
            'payroll_cost' => (float) Payroll::query()
                ->where('period_month', now()->month)
                ->where('period_year', now()->year)
                ->sum('net_salary'),
        ];

        return view('admin.employees.index', compact('employees', 'shifts', 'roles', 'stats', 'filters'));
    }

    public function create(): View
    {
        return view('admin.employees.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:30', 'unique:users,employee_code'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_type' => ['required', Rule::in(array_keys(SalaryType::options()))],
            'hourly_overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->user()->role !== Role::Owner
            && in_array($validated['role'], [Role::Owner->value, Role::Manager->value], true)) {
            throw ValidationException::withMessages([
                'role' => 'Manager tidak dapat membuat akun Owner atau Manager.',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['pending_face_registration'] = true;
        $validated['password'] = Hash::make($validated['password']);
        $validated['base_salary'] = $validated['base_salary'] ?? 0;
        $validated['hourly_overtime_rate'] = $validated['hourly_overtime_rate'] ?? 0;

        try {
            $user = DB::transaction(function () use ($validated) {
                $user = User::create($validated);
                $user->syncRoles([$user->role->value]);

                return $user;
            });

            $this->audit->log(
                'employee.created',
                $user,
                "Karyawan {$user->name} ditambahkan",
                [],
                $this->snapshot($user),
            );

            return redirect()
                ->route('admin.attendance.face', [
                    'user_id' => $user->id,
                    'mode' => 'register',
                    'onboarding' => 1,
                ])
                ->with('success', 'Data karyawan berhasil ditambahkan. Lanjutkan pendaftaran wajah wajib.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menambahkan karyawan. Silakan coba lagi.');
        }
    }

    public function show(Request $request, User $employee): View
    {
        $this->ensureCanView($request, $employee);

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $attendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $summary = [
            'present' => Attendance::query()->where('user_id', $employee->id)->whereBetween('date', [$monthStart, $monthEnd])
                ->whereIn('status', [AttendanceStatus::OnTime->value, AttendanceStatus::Late->value, AttendanceStatus::HalfDay->value])
                ->count(),
            'late' => Attendance::query()->where('user_id', $employee->id)->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', AttendanceStatus::Late->value)
                ->count(),
            'absent' => Attendance::query()->where('user_id', $employee->id)->whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', AttendanceStatus::Absent->value)
                ->count(),
            'overtime_hours' => round((int) Attendance::query()->where('user_id', $employee->id)->whereBetween('date', [$monthStart, $monthEnd])->sum('overtime_minutes') / 60, 2),
        ];

        $payrolls = Payroll::query()
            ->where('user_id', $employee->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(6)
            ->get();

        $orders = Order::query()
            ->where('employee_id', $employee->id)
            ->orWhere(function ($q) use ($employee) {
                $q->whereNull('employee_id')->where('cashier_id', $employee->id);
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.employees.show', compact('employee', 'attendances', 'summary', 'payrolls', 'orders'));
    }

    public function edit(Request $request, User $employee): View
    {
        $this->ensureCanManage($request, $employee);

        return view('admin.employees.edit', ['employee' => $employee] + $this->formData());
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->ensureCanManage($request, $employee);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('users', 'employee_code')->ignore($employee->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_type' => ['required', Rule::in(array_keys(SalaryType::options()))],
            'hourly_overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->user()->role !== Role::Owner
            && in_array($validated['role'], [Role::Owner->value, Role::Manager->value], true)) {
            throw ValidationException::withMessages([
                'role' => 'Manager tidak dapat memberikan jabatan Owner atau Manager.',
            ]);
        }

        if ($employee->id === $request->user()->id && $validated['role'] !== Role::Owner->value) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan jabatan akun Owner Anda sendiri.',
            ]);
        }

        $before = $this->snapshot($employee);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['base_salary'] = $validated['base_salary'] ?? 0;
        $validated['hourly_overtime_rate'] = $validated['hourly_overtime_rate'] ?? 0;

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        try {
            DB::transaction(function () use ($employee, $validated) {
                $employee->update($validated);
                $employee->syncRoles([$employee->role->value]);
            });

            $this->audit->log(
                'employee.updated',
                $employee,
                "Data karyawan {$employee->name} diperbarui",
                $before,
                $this->snapshot($employee->fresh()),
            );

            return redirect()
                ->route('admin.employees.index')
                ->with('success', 'Data karyawan berhasil diperbarui.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui data karyawan.');
        }
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        $this->ensureCanManage($request, $employee);

        if ($employee->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($employee->role === Role::Owner && $this->activeOwnerCount($employee->id) === 0) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya Owner yang aktif.');
        }

        $hasHistory = $employee->ordersAsCashier()->exists()
            || $employee->ordersAsEmployee()->exists()
            || $employee->attendances()->exists()
            || $employee->payrolls()->exists();

        try {
            if ($hasHistory) {
                $employee->update(['is_active' => false]);

                $this->audit->log(
                    'employee.deactivated',
                    $employee,
                    "Karyawan {$employee->name} dinonaktifkan karena memiliki riwayat",
                    ['is_active' => true],
                    ['is_active' => false],
                );

                return redirect()
                    ->route('admin.employees.index')
                    ->with('success', 'Karyawan memiliki riwayat transaksi/absensi, jadi dinonaktifkan (bukan dihapus).');
            }

            $snapshot = $this->snapshot($employee);

            $this->audit->log(
                'employee.deleted',
                $employee,
                "Karyawan {$employee->name} dihapus",
                $snapshot,
                [],
            );

            $employee->delete();

            return redirect()
                ->route('admin.employees.index')
                ->with('success', 'Data karyawan berhasil dihapus.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus data karyawan.');
        }
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $roles = Role::options();

        if (auth()->user()?->role !== Role::Owner) {
            unset($roles[Role::Owner->value], $roles[Role::Manager->value]);
        }

        return [
            'shifts' => Shift::query()->active()->orderBy('name')->get(),
            'roles' => $roles,
            'salaryTypes' => SalaryType::options(),
        ];
    }

    private function ensureCanManage(Request $request, User $target): void
    {
        $actor = $request->user();

        if ($actor?->role === Role::Owner) {
            return;
        }

        abort_if(
            in_array($target->role, [Role::Owner, Role::Manager], true),
            403,
            'Manager tidak dapat mengubah akun Owner atau Manager lain.',
        );
    }

    private function ensureCanView(Request $request, User $target): void
    {
        $actor = $request->user();

        if ($actor?->role === Role::Owner || $target->id === $actor?->id) {
            return;
        }

        abort_if(
            in_array($target->role, [Role::Owner, Role::Manager], true),
            403,
            'Manager tidak dapat melihat profil Owner atau Manager lain.',
        );
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
            'base_salary' => (float) $user->base_salary,
            'salary_type' => $user->salary_type?->value,
            'is_active' => $user->is_active,
            'pending_face_registration' => $user->pending_face_registration,
        ];
    }
}
