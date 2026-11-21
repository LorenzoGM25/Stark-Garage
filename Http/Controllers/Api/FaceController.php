<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    public function __construct(private readonly FaceRecognitionService $face)
    {
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

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $user = $this->face->register($user, $validated['embedding'], $validated['liveness_score'] ?? null);

        return response()->json([
            'message' => 'Data wajah berhasil diregistrasi.',
            'user' => UserResource::make($user),
        ]);
    }

    public function forget(\App\Models\User $user): JsonResponse
    {
        $this->face->forget($user);

        return response()->json([
            'message' => 'Data wajah berhasil dihapus.',
        ]);
    }
}
