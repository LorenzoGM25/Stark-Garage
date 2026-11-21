<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FaceRecognitionService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * Match a face embedding against every registered employee profile.
     *
     * @return array{user: User, score: float, threshold: float}|null
     */
    public function match(array $embedding): ?array
    {
        $embedding = $this->validateEmbedding($embedding);
        $threshold = $this->settings->faceThreshold();
        $best = null;

        foreach ($this->users->faceProfiles() as $user) {
            $profile = $user->face_embedding;

            if (! is_array($profile) || $profile === []) {
                continue;
            }

            $score = $this->cosineSimilarity($embedding, $profile);

            if ($best === null || $score > $best['score']) {
                $best = ['user' => $user, 'score' => $score];
            }
        }

        if ($best === null || $best['score'] < $threshold) {
            return null;
        }

        return [
            'user' => $best['user'],
            'score' => round($best['score'], 4),
            'threshold' => $threshold,
        ];
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $normA += $x * $x;
            $normB += $y * $y;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    public function validateEmbedding(array $embedding): array
    {
        $min = (int) config('stark.face.min_embedding_length', 64);
        $max = (int) config('stark.face.max_embedding_length', 512);

        if (count($embedding) < $min || count($embedding) > $max) {
            throw ValidationException::withMessages([
                'embedding' => "Vektor wajah harus berisi antara {$min} dan {$max} nilai.",
            ]);
        }

        foreach ($embedding as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                throw ValidationException::withMessages([
                    'embedding' => 'Vektor wajah mengandung nilai yang tidak valid.',
                ]);
            }
        }

        return array_values(array_map(fn ($value) => (float) $value, $embedding));
    }

    /**
     * Register (or replace) an employee's face embedding.
     */
    public function register(User $user, array $embedding, ?float $livenessScore = null): User
    {
        $embedding = $this->validateEmbedding($embedding);

        if ($livenessScore !== null && $livenessScore < $this->settings->livenessMinScore()) {
            throw ValidationException::withMessages([
                'liveness_score' => 'Pemeriksaan liveness gagal. Silakan ulangi pemindaian wajah.',
            ]);
        }

        $user->forceFill([
            'face_embedding' => $embedding,
            'face_registered_at' => now(),
        ])->save();

        return $user->refresh();
    }

    public function forget(User $user): void
    {
        $user->forceFill([
            'face_embedding' => null,
            'face_registered_at' => null,
        ])->save();
    }

    /** @return Collection<int, User> */
    public function profiles(): Collection
    {
        return $this->users->faceProfiles();
    }
}
