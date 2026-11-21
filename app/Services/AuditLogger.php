<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    private const REQUEST_FLAG = '_audit_log_written';

    public function log(
        string $event,
        Model|string|null $subject = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
    ): void {
        try {
            $actor ??= Auth::user();
            $subjectType = $subject instanceof Model ? $subject::class : $subject;
            $subjectId = $subject instanceof Model ? $subject->getKey() : null;

            AuditLog::create([
                'user_id' => $actor?->getKey(),
                'event' => $event,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'ip_address' => $this->requestIp(),
                'user_agent' => $this->userAgent(),
                'created_at' => now(),
            ]);

            $this->markLogged();
        } catch (Throwable $e) {
            Log::warning('Failed to write audit log', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Whether an explicit audit entry was already written during this request.
     * Used by the generic request logger to avoid duplicate entries.
     */
    public function alreadyLogged(): bool
    {
        try {
            return (bool) request()->attributes->get(self::REQUEST_FLAG, false);
        } catch (Throwable) {
            return false;
        }
    }

    public function markLogged(): void
    {
        try {
            request()->attributes->set(self::REQUEST_FLAG, true);
        } catch (Throwable) {
            // No active request (console command); nothing to flag.
        }
    }

    private function requestIp(): ?string
    {
        try {
            return request()->ip();
        } catch (Throwable) {
            return null;
        }
    }

    private function userAgent(): ?string
    {
        try {
            return substr((string) request()->userAgent(), 0, 1000) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
