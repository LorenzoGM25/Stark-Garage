<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityLogWebController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'role' => ['nullable', Rule::in(array_keys(Role::options()))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $from = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : null;
        $to = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : null;

        $logs = AuditLog::query()
            ->with('user:id,name,email,role,employee_code')
            ->when($filters['search'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('description', 'like', "%{$term}%")
                        ->orWhere('event', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($query) use ($term) {
                            $query->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%")
                                ->orWhere('employee_code', 'like', "%{$term}%");
                        });
                });
            })
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['role'] ?? null, function ($query, string $role) {
                $query->whereHas('user', fn ($query) => $query->where('role', $role));
            })
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $events = AuditLog::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->map(fn (string $event) => [
                'value' => $event,
                'label' => AuditLog::labelFor($event),
            ]);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $stats = [
            'today' => AuditLog::query()->where('created_at', '>=', today())->count(),
            'total' => AuditLog::query()->count(),
            'actors_today' => AuditLog::query()
                ->where('created_at', '>=', today())
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'failed_logins' => AuditLog::query()
                ->where('event', 'auth.login_failed')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return view('admin.activity.index', [
            'logs' => $logs,
            'events' => $events,
            'users' => $users,
            'roles' => Role::options(),
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }
}
