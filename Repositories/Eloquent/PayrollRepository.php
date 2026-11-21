<?php

namespace App\Repositories\Eloquent;

use App\Models\Payroll;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PayrollRepository implements PayrollRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Payroll::query()
            ->with('user:id,name,employee_code,role,avatar_path')
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('employee_code', 'like', "%{$term}%")))
            ->when($filters['month'] ?? null, fn ($q, $month) => $q->where('period_month', $month))
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->where('period_year', $year))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findOrFail(int $id): Payroll
    {
        return Payroll::with('user.shift')->findOrFail($id);
    }

    public function forPeriod(int $month, int $year): Collection
    {
        return Payroll::with('user')->period($month, $year)->get();
    }
}
