<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function todayFor(User $user): ?Attendance;

    public function forUserPeriod(User $user, Carbon $start, Carbon $end): Collection;

    public function create(array $attributes): Attendance;

    public function update(Attendance $attendance, array $attributes): Attendance;
}
