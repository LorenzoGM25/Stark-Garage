<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\SalaryType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name',
        'employee_code',
        'email',
        'phone',
        'password',
        'avatar_path',
        'role',
        'shift_id',
        'base_salary',
        'salary_type',
        'hourly_overtime_rate',
        'is_active',
        'last_login_at',
        'pending_face_registration',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'salary_type' => SalaryType::class,
            'face_embedding' => 'array',
            'face_registered_at' => 'datetime',
            'base_salary' => 'decimal:2',
            'hourly_overtime_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'pending_face_registration' => 'boolean',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function ordersAsCashier(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function ordersAsEmployee(): HasMany
    {
        return $this->hasMany(Order::class, 'employee_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', [Role::Manager->value, Role::Employee->value, Role::Cashier->value]);
    }

    public static function generateEmployeeCode(): string
    {
        $maxNum = 0;
        $codes = static::query()->whereNotNull('employee_code')->pluck('employee_code');
        foreach ($codes as $code) {
            if (preg_match('/STG-(\d+)/i', $code, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return sprintf('STG-%03d', $maxNum + 1);
    }

    public function hasFace(): bool
    {
        return is_array($this->face_embedding) && count($this->face_embedding) > 0;
    }

    public function needsFaceRegistration(): bool
    {
        return (bool) $this->pending_face_registration;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? '-';
    }
}
