<?php

namespace App\Repositories\Eloquent;

use App\Models\Attendance;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::query()
            ->with(['user:id,name,employee_code,role', 'shift:id,name,start_time,end_time'])
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('employee_code', 'like', "%{$term}%")))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('date', $date))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date', '<=', $date))
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function todayFor(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)->whereDate('date', today())->first();
    }

    public function forUserPeriod(User $user, Carbon $start, Carbon $end): Collection
    {
        return Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();
    }

    public function create(array $attributes): Attendance
    {
        return Attendance::create($attributes);
    }

    public function update(Attendance $attendance, array $attributes): Attendance
    {
        $attendance->update($attributes);

        return $attendance->refresh();
    }
}
