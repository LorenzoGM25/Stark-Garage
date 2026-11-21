<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly SettingService $settings,
        private readonly FaceRecognitionService $face,
        private readonly AuditLogger $audit,
    ) {
    }

    public function clockIn(User $user, array $data): Attendance
    {
        return DB::transaction(function () use ($user, $data) {
            $existing = $this->attendances->todayFor($user);

            if ($existing?->clock_in) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah melakukan clock-in hari ini.',
                ]);
            }

            $now = now();
            $shift = $user->shift ?? Shift::query()->active()->orderBy('start_time')->first();
            $distance = $this->validateGeofence($data['latitude'] ?? null, $data['longitude'] ?? null);

            [$lateMinutes, $status] = $this->resolveLateness($now, $shift);

            $attributes = [
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'date' => $now->toDateString(),
                'clock_in' => $now,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'clock_in_latitude' => $data['latitude'] ?? null,
                'clock_in_longitude' => $data['longitude'] ?? null,
                'distance_meters' => $distance,
                'photo_proof_path' => $data['photo_proof_path'] ?? null,
                'liveness_score' => $data['liveness_score'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            $attendance = $existing
                ? $this->attendances->update($existing, $attributes)
                : $this->attendances->create($attributes);

            $this->audit->log('attendance.clock_in', $attendance, "Clock-in {$user->name}", [], [
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ], $user);

            return $attendance->load(['user', 'shift']);
        });
    }

    public function clockOut(User $user, array $data = []): Attendance
    {
        return DB::transaction(function () use ($user, $data) {
            $attendance = $this->attendances->todayFor($user);

            if (! $attendance || ! $attendance->clock_in) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda belum melakukan clock-in hari ini.',
                ]);
            }

            if ($attendance->clock_out) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah melakukan clock-out hari ini.',
                ]);
            }

            $now = now();
            $distance = $this->validateGeofence($data['latitude'] ?? null, $data['longitude'] ?? null, false);

            $workMinutes = max(0, (int) $attendance->clock_in->diffInMinutes($now, false));
            $overtimeMinutes = 0;
            $earlyLeaveMinutes = 0;

            if ($attendance->shift) {
                $shiftEnd = Carbon::parse($attendance->date->toDateString().' '.$attendance->shift->end_time);

                if ($shiftEnd->lessThanOrEqualTo($attendance->clock_in)) {
                    $shiftEnd->addDay();
                }

                if ($now->greaterThan($shiftEnd)) {
                    $overtimeMinutes = (int) $shiftEnd->diffInMinutes($now);
                } elseif ($now->lessThan($shiftEnd)) {
                    $earlyLeaveMinutes = (int) $now->diffInMinutes($shiftEnd);
                }
            }

            $attendance = $this->attendances->update($attendance, [
                'clock_out' => $now,
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'clock_out_latitude' => $data['latitude'] ?? null,
                'clock_out_longitude' => $data['longitude'] ?? null,
                'distance_meters' => $distance ?? $attendance->distance_meters,
                'notes' => $data['notes'] ?? $attendance->notes,
            ]);

            $this->audit->log('attendance.clock_out', $attendance, "Clock-out {$user->name}", [], [
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ], $user);

            return $attendance->load(['user', 'shift']);
        });
    }

    /**
     * Match a face embedding and automatically clock-in or clock-out.
     *
     * @return array{action: string, attendance: Attendance, user: User, score: float}
     */
    public function recognizeAndToggle(array $embedding, array $data): array
    {
        $match = $this->face->match($embedding);

        if ($match === null) {
            throw ValidationException::withMessages([
                'embedding' => 'Wajah tidak dikenali. Pastikan pencahayaan cukup dan wajah terdaftar.',
            ]);
        }

        if (config('stark.face.require_liveness', true)) {
            $score = $data['liveness_score'] ?? null;

            if ($score === null || (float) $score < $this->settings->livenessMinScore()) {
                throw ValidationException::withMessages([
                    'liveness_score' => 'Liveness check gagal. Silakan berkedip atau gerakkan kepala.',
                ]);
            }
        }

        /** @var User $user */
        $user = $match['user'];
        $today = $this->attendances->todayFor($user);

        if (! $today?->clock_in) {
            $attendance = $this->clockIn($user, $data);
            $action = 'clock_in';
        } elseif (! $today->clock_out) {
            $attendance = $this->clockOut($user, $data);
            $action = 'clock_out';
        } else {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi hari ini sudah lengkap.',
            ]);
        }

        return [
            'action' => $action,
            'attendance' => $attendance,
            'user' => $user,
            'score' => $match['score'],
        ];
    }

    public function manualUpsert(array $data, User $actor): Attendance
    {
        $user = User::findOrFail($data['user_id']);
        $date = Carbon::parse($data['date']);
        $shift = $user->shift;
        $clockIn = ! empty($data['clock_in']) ? Carbon::parse($date->toDateString().' '.$data['clock_in']) : null;
        $clockOut = ! empty($data['clock_out']) ? Carbon::parse($date->toDateString().' '.$data['clock_out']) : null;

        if ($clockIn && $clockOut && $clockOut->lessThanOrEqualTo($clockIn)) {
            $clockOut->addDay();
        }

        $status = $data['status'] ?? null;
        $lateMinutes = (int) ($data['late_minutes'] ?? 0);
        $statusEnum = $status ? AttendanceStatus::tryFrom($status) : null;

        if ($statusEnum === null) {
            if (! $clockIn) {
                $status = AttendanceStatus::Absent->value;
                $lateMinutes = 0;
            } else {
                [$lateMinutes, $status] = $this->resolveLateness($clockIn, $shift);
            }
        } else {
            $status = $statusEnum->value;

            if ($status === AttendanceStatus::Late->value && $clockIn && $shift) {
                [$computedLate] = $this->resolveLateness($clockIn, $shift);
                $lateMinutes = (int) ($data['late_minutes'] ?? $computedLate);
            }
        }

        $workMinutes = $clockIn && $clockOut ? max(0, (int) $clockIn->diffInMinutes($clockOut, false)) : 0;
        $overtimeMinutes = 0;

        if ($clockOut && $shift) {
            $shiftEnd = Carbon::parse($date->toDateString().' '.$shift->end_time);

            if ($shiftEnd->lessThanOrEqualTo($clockIn ?? $shiftEnd)) {
                $shiftEnd->addDay();
            }

            if ($clockOut->greaterThan($shiftEnd)) {
                $overtimeMinutes = (int) $shiftEnd->diffInMinutes($clockOut);
            }
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date->toDateString()],
            [
                'shift_id' => $shift?->id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'work_minutes' => $workMinutes,
                'notes' => $data['notes'] ?? null,
            ],
        );

        $this->audit->log('attendance.manual', $attendance, "Absensi manual untuk {$user->name}", [], [
            'date' => $date->toDateString(),
            'status' => $status,
        ], $actor);

        return $attendance->load(['user', 'shift']);
    }

    public function markAbsentForDate(?Carbon $date = null): int
    {
        $date ??= today();
        $created = 0;

        $staff = User::query()->active()->staff()->with('shift')->get();

        foreach ($staff as $user) {
            $shift = $user->shift;

            if ($shift?->work_days) {
                if (! in_array($date->dayOfWeekIso, array_map('intval', $shift->work_days), true)) {
                    continue;
                }
            } elseif ($date->isSunday()) {
                continue;
            }

            $exists = Attendance::where('user_id', $user->id)->whereDate('date', $date)->exists();

            if ($exists) {
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'date' => $date->toDateString(),
                'status' => AttendanceStatus::Absent->value,
                'notes' => 'Ditandai otomatis oleh sistem.',
            ]);

            $created++;
        }

        return $created;
    }

    /** @return array{0: int, 1: string} */
    private function resolveLateness(Carbon $clockIn, ?Shift $shift): array
    {
        if (! $shift) {
            return [0, AttendanceStatus::OnTime->value];
        }

        $start = Carbon::parse($clockIn->toDateString().' '.$shift->start_time);

        if ($shift->end_time < $shift->start_time && $clockIn->format('H:i:s') < $shift->start_time) {
            $start->subDay();
        }

        $deadline = $start->copy()->addMinutes($shift->grace_minutes);

        if ($clockIn->greaterThan($deadline)) {
            return [(int) floor($deadline->diffInMinutes($clockIn)), AttendanceStatus::Late->value];
        }

        return [0, AttendanceStatus::OnTime->value];
    }

    private function validateGeofence(mixed $latitude, mixed $longitude, bool $required = true): ?int
    {
        $geofence = $this->settings->geofence();

        if (! $geofence['enabled']) {
            return null;
        }

        if ($latitude === null || $longitude === null) {
            if ($required) {
                throw ValidationException::withMessages([
                    'latitude' => 'Lokasi GPS wajib diaktifkan untuk melakukan absensi.',
                ]);
            }

            return null;
        }

        $distance = $this->distanceInMeters(
            $geofence['latitude'],
            $geofence['longitude'],
            (float) $latitude,
            (float) $longitude,
        );

        if ($distance > $geofence['radius']) {
            throw ValidationException::withMessages([
                'latitude' => "Anda berada di luar area kantor ({$distance} m dari pusat, maksimal {$geofence['radius']} m).",
            ]);
        }

        return $distance;
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
