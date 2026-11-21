<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'clock_in' => $this->clock_in?->toIso8601String(),
            'clock_out' => $this->clock_out?->toIso8601String(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'work_minutes' => $this->work_minutes,
            'worked_hours' => $this->worked_hours,
            'distance_meters' => $this->distance_meters,
            'liveness_score' => $this->liveness_score,
            'notes' => $this->notes,
            'is_open' => $this->is_open,
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),
            'shift' => $this->whenLoaded('shift', fn () => [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
                'start_time' => $this->shift->start_time,
                'end_time' => $this->shift->end_time,
                'grace_minutes' => $this->shift->grace_minutes,
            ]),
        ];
    }
}
