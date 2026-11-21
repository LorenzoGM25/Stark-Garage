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
        $result = $this->identify($embedding);

        if ($result['status'] !== 'matched' || ! $result['user'] instanceof User) {
            return null;
        }

        return [
            'user' => $result['user'],
            'score' => round($result['score'], 4),
            'threshold' => $result['threshold'],
        ];
    }

    /**
     * Identify a face and report why a scan was rejected, so similar employees
     * cannot silently clock in for one another.
     *
     * @return array{
     *     status: 'matched'|'below_threshold'|'ambiguous'|'wrong_user'|'no_profiles',
     *     user: User|null,
     *     best_user: User|null,
     *     score: float,
     *     runner_up_score: float|null,
     *     margin: float|null,
     *     threshold: float,
     *     required_margin: float
     * }
     */
    public function identify(array $embedding, ?int $expectedUserId = null): array
    {
        $embedding = $this->validateEmbedding($embedding);
        $threshold = $this->settings->faceThreshold();
        $requiredMargin = $this->settings->faceMargin();

        $scores = [];

        foreach ($this->users->faceProfiles() as $user) {
            $profile = $user->face_embedding;

            if (! is_array($profile) || $profile === []) {
                continue;
            }

            $scores[] = [
                'user' => $user,
                'score' => $this->cosineSimilarity($embedding, $profile),
            ];
        }

        if ($scores === []) {
            return [
                'status' => 'no_profiles',
                'user' => null,
                'best_user' => null,
                'score' => 0.0,
                'runner_up_score' => null,
                'margin' => null,
                'threshold' => $threshold,
                'required_margin' => $requiredMargin,
            ];
        }

        usort($scores, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $best = $scores[0];
        $runnerUpScore = $scores[1]['score'] ?? null;
        $margin = $runnerUpScore === null ? null : $best['score'] - $runnerUpScore;

        $result = [
            'status' => 'matched',
            'user' => $best['user'],
            'best_user' => $best['user'],
            'score' => $best['score'],
            'runner_up_score' => $runnerUpScore,
            'margin' => $margin,
            'threshold' => $threshold,
            'required_margin' => $requiredMargin,
        ];

        if ($best['score'] < $threshold) {
            return ['status' => 'below_threshold', 'user' => null] + $result;
        }

        if ($expectedUserId !== null && $best['user']->id !== $expectedUserId) {
            return ['status' => 'wrong_user', 'user' => null] + $result;
        }

        if ($margin !== null && $margin < $requiredMargin) {
            return ['status' => 'ambiguous', 'user' => null] + $result;
        }

        return $result;
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
