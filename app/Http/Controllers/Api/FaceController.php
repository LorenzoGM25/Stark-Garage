<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    public function __construct(
        private readonly FaceRecognitionService $face,
        private readonly AuditLogger $audit,
    ) {
    }

    public function profiles(): JsonResponse
    {
        return response()->json([
            'profiles' => UserResource::collection($this->face->profiles()),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'embedding' => ['required', 'array'],
            'embedding.*' => ['required', 'numeric'],
            'liveness_score' => ['nullable', 'numeric'],
        ]);

        $actor = $request->user();
        $user = User::findOrFail($validated['user_id']);

        abort_if(
            $user->id !== $actor->id && ! in_array($actor->role, [Role::Owner, Role::Manager], true),
            403,
            'Anda tidak dapat mendaftarkan wajah karyawan lain.',
        );

        $wasPending = $user->needsFaceRegistration();
        $previousRegisteredAt = $user->face_registered_at?->toIso8601String();

        $user = $this->face->register($user, $validated['embedding'], $validated['liveness_score'] ?? null);

        if ($wasPending) {
            $user->forceFill(['pending_face_registration' => false])->save();
            $user = $user->refresh();
        }

        $this->audit->log(
            'face.registered',
            $user,
            $user->id === $actor->id
                ? "Wajah {$user->name} didaftarkan sendiri"
                : "Wajah {$user->name} didaftarkan oleh {$actor->name}",
            [
                'face_registered_at' => $previousRegisteredAt,
                'pending_face_registration' => $wasPending,
            ],
            [
                'face_registered_at' => $user->face_registered_at?->toIso8601String(),
                'pending_face_registration' => $user->pending_face_registration,
            ],
        );

        return response()->json([
            'message' => 'Data wajah berhasil diregistrasi.',
            'user' => UserResource::make($user),
        ]);
    }

    public function forget(Request $request, User $user): JsonResponse
    {
        abort_unless(
            in_array($request->user()->role, [Role::Owner, Role::Manager], true),
            403,
            'Hanya Owner atau Manager yang dapat menghapus data wajah.',
        );

        $registeredAt = $user->face_registered_at?->toIso8601String();

        $this->face->forget($user);

        $this->audit->log(
            'face.reset',
            $user,
            "Data wajah {$user->name} dihapus oleh {$request->user()->name}",
            ['face_registered_at' => $registeredAt],
            ['face_registered_at' => null],
        );

        return response()->json([
            'message' => 'Data wajah berhasil dihapus.',
        ]);
    }
}
