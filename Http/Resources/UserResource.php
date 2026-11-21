<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'employee_code' => $this->employee_code,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'role_label' => $this->role_label,
            'avatar_url' => $this->avatar_url,
            'initials' => $this->initials,
            'base_salary' => $this->base_salary,
            'salary_type' => $this->salary_type?->value,
            'is_active' => $this->is_active,
            'has_face' => $this->hasFace(),
            'face_registered_at' => $this->face_registered_at?->toIso8601String(),
            'shift' => $this->whenLoaded('shift', fn () => [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
                'start_time' => $this->shift->start_time,
                'end_time' => $this->shift->end_time,
                'grace_minutes' => $this->shift->grace_minutes,
            ]),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
