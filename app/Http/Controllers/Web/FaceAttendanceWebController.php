<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FaceAttendanceWebController extends Controller
{
    public function __construct(
        private readonly FaceRecognitionService $face,
        private readonly AttendanceService $attendance,
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $request->user();
        $canManage = $this->canManageFaces($actor);
        $onboardingRequested = $request->boolean('onboarding') && $canManage && $request->filled('user_id');
        $targetId = (int) ($request->input('user_id') ?: $actor->id);

        $target = User::findOrFail($targetId);
        abort_if($target->id !== $actor->id && ! $canManage, 403, 'Anda tidak dapat mendaftarkan wajah karyawan lain.');

        $onboarding = $onboardingRequested && $target->needsFaceRegistration();

        $targets = $canManage
            ? User::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'role', 'face_registered_at'])
            : collect([$actor]);

        return view('admin.attendance.face', [
            'actor' => $actor,
            'target' => $target,
            'targets' => $targets,
            'canManage' => $canManage,
            'onboarding' => $onboarding,
            'initialMode' => $onboarding || $request->input('mode') === 'register' ? 'register' : 'attendance',
            'redirectAfterRegister' => $onboarding ? route('admin.employees.show', $target) : null,
            'today' => $this->attendances->todayFor($actor),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'embedding' => ['required', 'array'],
            'embedding.*' => ['required', 'numeric'],
            'liveness_score' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $actor = $request->user();
        $target = User::findOrFail($validated['user_id'] ?? $actor->id);

        abort_if(
            $target->id !== $actor->id && ! $this->canManageFaces($actor),
            403,
            'Anda tidak dapat mendaftarkan wajah karyawan lain.',
        );

        $onboarding = $request->boolean('onboarding') && $this->canManageFaces($actor);
        $wasPending = $target->needsFaceRegistration();
        $previousRegisteredAt = $target->face_registered_at?->toIso8601String();

        $target = $this->face->register(
            $target,
            $validated['embedding'],
            isset($validated['liveness_score']) ? (float) $validated['liveness_score'] : null,
        );

        if ($wasPending) {
            $target->forceFill(['pending_face_registration' => false])->save();
            $target = $target->refresh();
        }

        $this->audit->log(
            'face.registered',
            $target,
            $target->id === $actor->id
                ? "Wajah {$target->name} didaftarkan sendiri"
                : "Wajah {$target->name} didaftarkan oleh {$actor->name}",
            [
                'face_registered_at' => $previousRegisteredAt,
                'pending_face_registration' => $wasPending,
            ],
            [
                'face_registered_at' => $target->face_registered_at?->toIso8601String(),
                'pending_face_registration' => $target->pending_face_registration,
            ],
        );

        return response()->json([
            'message' => "Wajah {$target->name} berhasil didaftarkan.",
            'redirect' => $onboarding ? route('admin.employees.show', $target) : null,
            'user' => [
                'id' => $target->id,
                'name' => $target->name,
                'role_label' => $target->role_label,
                'has_face' => $target->hasFace(),
                'pending_face_registration' => $target->pending_face_registration,
                'face_registered_at' => $target->face_registered_at?->toIso8601String(),
            ],
        ]);
    }

    public function recognize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'embedding' => ['required', 'array'],
            'embedding.*' => ['required', 'numeric'],
            'liveness_score' => ['nullable', 'numeric', 'between:0,1'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo_proof_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->attendance->recognizeAndToggle($validated['embedding'], $validated, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $attendance = $result['attendance']->load('shift');

        return response()->json([
            'message' => $result['action'] === 'clock_in'
                ? 'Clock-in berhasil melalui face recognition.'
                : 'Clock-out berhasil melalui face recognition.',
            'action' => $result['action'],
            'score' => $result['score'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'role_label' => $result['user']->role_label,
            ],
            'attendance' => [
                'date' => $attendance->date?->toDateString(),
                'clock_in' => $attendance->clock_in?->format('H:i'),
                'clock_out' => $attendance->clock_out?->format('H:i'),
                'status_label' => $attendance->status?->label(),
                'late_minutes' => $attendance->late_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
                'shift_name' => $attendance->shift?->name,
            ],
        ]);
    }

    private function canManageFaces(?User $user): bool
    {
        return in_array($user?->role, [Role::Owner, Role::Manager], true);
    }
}
