<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\OrderStatus;
use App\Enums\PayrollStatus;
use App\Enums\SalaryType;
use App\Models\Attendance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Generate or refresh draft payrolls for the given period.
     *
     * @return Collection<int, Payroll>
     */
    public function generate(int $month, int $year, ?User $generator = null, ?int $onlyUserId = null): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $users = User::query()
            ->active()
            ->staff()
            ->with('shift')
            ->when($onlyUserId, fn ($q) => $q->whereKey($onlyUserId))
            ->get();

        DB::transaction(function () use ($users, $month, $year, $start, $end, $generator) {
            foreach ($users as $user) {
                $existing = Payroll::query()
                    ->where('user_id', $user->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->first();

                if ($existing?->status === PayrollStatus::Paid) {
                    continue;
                }

                $data = $this->calculate($user, $start, $end);
                $data['generated_by'] = $generator?->id;

                Payroll::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'period_month' => $month,
                        'period_year' => $year,
                    ],
                    $data,
                );
            }
        });

        if ($generator) {
            $this->audit->log('payroll.generated', null, "Payroll {$month}/{$year} digenerate", [], [
                'month' => $month,
                'year' => $year,
                'employees' => $users->count(),
            ], $generator);
        }

        return Payroll::query()
            ->with('user')
            ->period($month, $year)
            ->orderBy('id')
            ->get();
    }

    /** @return array<string, mixed> */
    public function calculate(User $user, Carbon $start, Carbon $end): array
    {
        $attendances = Attendance::query()
            ->with('shift')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $scheduledDays = $this->scheduledDays($start, $end, $user);
        $presentDays = $attendances->filter(fn (Attendance $a) => in_array($a->status, [
            AttendanceStatus::OnTime,
            AttendanceStatus::Late,
            AttendanceStatus::HalfDay,
        ], true))->count();
        $lateAttendances = $attendances->filter(fn (Attendance $a) => $a->status === AttendanceStatus::Late);
        $leaveDays = $attendances->filter(fn (Attendance $a) => $a->status === AttendanceStatus::Leave)->count();
        $absentDays = max(0, $scheduledDays - $presentDays - $leaveDays);

        $baseSalary = $this->baseSalaryFor($user, $presentDays);
        $commission = $this->commissionFor($user, $start, $end, $commissionOrderCount);
        $overtimeMinutes = (int) $attendances->sum('overtime_minutes');
        $overtimeHours = round($overtimeMinutes / 60, 2);

        $lateDeductions = $this->lateDeductions($lateAttendances);
        $lateTotal = round(array_sum(array_column($lateDeductions, 'amount')), 2);
        $absenceDeduction = $this->absenceDeduction($user, $scheduledDays, $absentDays);
        $totalDeduction = round($lateTotal + $absenceDeduction, 2);

        $overtimeRate = $this->hourlyOvertimeRate($user, $scheduledDays);
        $overtimePay = round($overtimeHours * $overtimeRate * $this->settings->overtimeMultiplier(), 2);
        $netSalary = max(0, round($baseSalary + $commission + $overtimePay - $totalDeduction, 2));

        return [
            'base_salary' => round($baseSalary, 2),
            'total_commission' => $commission,
            'total_overtime' => $overtimePay,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'present_days' => $presentDays,
            'late_days' => $lateAttendances->count(),
            'absent_days' => $absentDays,
            'overtime_hours' => $overtimeHours,
            'breakdown' => [
                'scheduled_days' => $scheduledDays,
                'leave_days' => $leaveDays,
                'commission_orders' => $commissionOrderCount,
                'late_deductions' => $lateDeductions,
                'absence_deduction' => $absenceDeduction,
                'overtime_rate' => round($overtimeRate, 2),
                'overtime_multiplier' => $this->settings->overtimeMultiplier(),
            ],
            'status' => PayrollStatus::Draft->value,
        ];
    }

    public function markPaid(Payroll $payroll, User $actor): Payroll
    {
        if ($payroll->status === PayrollStatus::Paid) {
            return $payroll;
        }

        $payroll->update([
            'status' => PayrollStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $this->audit->log('payroll.paid', $payroll, "Payroll {$payroll->user->name} dibayar", [
            'status' => PayrollStatus::Draft->value,
        ], [
            'status' => PayrollStatus::Paid->value,
            'net_salary' => $payroll->net_salary,
        ], $actor);

        return $payroll->refresh()->load('user');
    }

    /** @param array<int, int> $ids */
    public function bulkMarkPaid(array $ids, User $actor): int
    {
        $count = 0;

        Payroll::query()
            ->with('user')
            ->whereIn('id', $ids)
            ->where('status', PayrollStatus::Draft->value)
            ->get()
            ->each(function (Payroll $payroll) use ($actor, &$count) {
                $this->markPaid($payroll, $actor);
                $count++;
            });

        return $count;
    }

    private function baseSalaryFor(User $user, int $presentDays): float
    {
        $rate = (float) $user->base_salary;

        return match ($user->salary_type) {
            SalaryType::Daily => round($rate * $presentDays, 2),
            SalaryType::Weekly => round($rate * (int) ceil($presentDays / 6), 2),
            default => round($rate, 2),
        };
    }

    private function commissionFor(User $user, Carbon $start, Carbon $end, ?int &$orderCount = null): float
    {
        $scope = function ($query) use ($user, $start, $end) {
            $query->where('status', OrderStatus::Completed->value)
                ->whereBetween('created_at', [$start, $end])
                ->where(function ($q) use ($user) {
                    $q->where('employee_id', $user->id)
                        ->orWhere(function ($q) use ($user) {
                            $q->whereNull('employee_id')->where('cashier_id', $user->id);
                        });
                });
        };

        $orderCount = Order::query()->where($scope)->count();

        return round((float) OrderItem::query()->whereHas('order', $scope)->sum('commission_amount'), 2);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Attendance> $lateAttendances
     * @return array<int, array{date: string, minutes: int, amount: float}>
     */
    private function lateDeductions($lateAttendances): array
    {
        $defaultTiers = $this->settings->lateDeductionTiers();
        $deductions = [];

        foreach ($lateAttendances as $attendance) {
            $tiers = $attendance->shift?->late_deduction_tiers ?: $defaultTiers;
            $amount = 0.0;

            if (is_array($tiers)) {
                usort($tiers, fn ($a, $b) => ((int) ($a['min'] ?? 0)) <=> ((int) ($b['min'] ?? 0)));

                foreach ($tiers as $tier) {
                    $min = (int) ($tier['min'] ?? 0);
                    $max = $tier['max'] ?? null;

                    if ($attendance->late_minutes >= $min && ($max === null || $attendance->late_minutes <= (int) $max)) {
                        $amount = (float) ($tier['amount'] ?? 0);
                        break;
                    }
                }
            }

            $deductions[] = [
                'date' => $attendance->date->toDateString(),
                'minutes' => (int) $attendance->late_minutes,
                'amount' => round($amount, 2),
            ];
        }

        return $deductions;
    }

    private function absenceDeduction(User $user, int $scheduledDays, int $absentDays): float
    {
        if ($absentDays <= 0) {
            return 0.0;
        }

        $base = (float) $user->base_salary;

        return match ($user->salary_type) {
            SalaryType::Monthly => $scheduledDays > 0 ? round($base / $scheduledDays * $absentDays, 2) : 0.0,
            SalaryType::Weekly => round($base / 6 * $absentDays, 2),
            default => 0.0,
        };
    }

    private function hourlyOvertimeRate(User $user, int $scheduledDays): float
    {
        if ((float) $user->hourly_overtime_rate > 0) {
            return (float) $user->hourly_overtime_rate;
        }

        $base = (float) $user->base_salary;
        $hoursPerDay = max(1, (int) config('stark.payroll.working_hours_per_day', 8));

        return match ($user->salary_type) {
            SalaryType::Monthly => $scheduledDays > 0 ? $base / ($scheduledDays * $hoursPerDay) : 0.0,
            SalaryType::Weekly => $base / (6 * $hoursPerDay),
            default => $base / $hoursPerDay,
        };
    }

    private function scheduledDays(Carbon $start, Carbon $end, User $user): int
    {
        $workDays = $user->shift?->work_days;
        $workDays = is_array($workDays) ? array_map('intval', $workDays) : null;
        $count = 0;

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $day) {
            if ($workDays !== null) {
                if (in_array($day->dayOfWeekIso, $workDays, true)) {
                    $count++;
                }
            } elseif (! $day->isSunday()) {
                $count++;
            }
        }

        return $count;
    }
}
