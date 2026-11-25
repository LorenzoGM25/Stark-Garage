<?php

namespace App\Http\Controllers\Web;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceWebController extends Controller
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly AttendanceService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = $request->filled('date') ? Carbon::parse($request->date) : today();
        $filters = $request->only(['search', 'status']);
        $filters['date'] = $date->toDateString();

        $records = $this->attendances->paginate($filters, 15);

        $statusCounts = Attendance::query()
            ->whereDate('date', $date)
            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $staff = User::query()->active()->orderBy('name')->get();

        $stats = [
            'on_time' => (int) ($statusCounts[AttendanceStatus::OnTime->value] ?? 0),
            'late' => (int) ($statusCounts[AttendanceStatus::Late->value] ?? 0),
            'absent' => (int) ($statusCounts[AttendanceStatus::Absent->value] ?? 0),
            'leave' => (int) ($statusCounts[AttendanceStatus::Leave->value] ?? 0),
            'staff_total' => $staff->count(),
        ];

        $statuses = AttendanceStatus::cases();

        return view('admin.attendance.index', compact('records', 'staff', 'date', 'filters', 'stats', 'statuses'));
    }

    public function today(): JsonResponse
    {
        $records = Attendance::query()
            ->with(['user:id,name,employee_code,role', 'shift:id,name'])
            ->whereDate('date', today())
            ->orderByDesc('clock_in')
            ->get();

        return response()->json([
            'date' => today()->toDateString(),
            'total' => $records->count(),
            'data' => $records,
        ]);
    }

    public function manual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(array_column(AttendanceStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($validated['clock_in']) && ! empty($validated['clock_out']) && $validated['clock_out'] <= $validated['clock_in']) {
            return back()->withInput()->withErrors([
                'clock_out' => 'Jam pulang harus lebih besar dari jam masuk.',
            ]);
        }

        try {
            $attendance = $this->service->manualUpsert($validated, $request->user());

            return back()->with('success', "Absensi {$attendance->user->name} pada {$attendance->date->format('d M Y')} berhasil disimpan.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan absensi manual.');
        }
    }

    public function markAbsent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $created = $this->service->markAbsentForDate(Carbon::parse($validated['date']));

            return back()->with(
                'success',
                $created > 0
                    ? "{$created} karyawan berhasil ditandai tidak hadir."
                    : 'Semua karyawan sudah memiliki data absensi pada tanggal tersebut.',
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menandai absensi.');
        }
    }
}
