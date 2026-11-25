<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly AttendanceService $service,
    ) {
    }

    public function today(Request $request): JsonResponse
    {
        $attendance = $this->attendances->todayFor($request->user());

        return response()->json([
            'attendance' => $attendance ? AttendanceResource::make($attendance->load('shift')) : null,
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo_proof_path' => ['nullable', 'string'],
            'liveness_score' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $attendance = $this->service->clockIn($request->user(), $validated);

        return response()->json([
            'message' => 'Clock-in berhasil.',
            'attendance' => AttendanceResource::make($attendance),
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $attendance = $this->service->clockOut($request->user(), $validated);

        return response()->json([
            'message' => 'Clock-out berhasil.',
            'attendance' => AttendanceResource::make($attendance),
        ]);
    }

    public function recognize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'embedding' => ['required', 'array'],
            'embedding.*' => ['required', 'numeric'],
            'liveness_score' => ['nullable', 'numeric'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo_proof_path' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $actor = $request->user();
        $expectedUser = in_array($actor->role, [\App\Enums\Role::Owner, \App\Enums\Role::Manager], true) ? null : $actor;

        try {
            $result = $this->service->recognizeAndToggle($validated['embedding'], $validated, $expectedUser);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => $result['action'] === 'clock_in' ? 'Clock-in berhasil via face recognition.' : 'Clock-out berhasil via face recognition.',
            'action' => $result['action'],
            'score' => $result['score'],
            'user' => \App\Http\Resources\UserResource::make($result['user']),
            'attendance' => AttendanceResource::make($result['attendance']->load('shift')),
        ], 201);
    }
}
