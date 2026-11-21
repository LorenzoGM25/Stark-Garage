<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'period_month',
        'period_year',
        'base_salary',
        'total_commission',
        'total_overtime',
        'total_deduction',
        'net_salary',
        'present_days',
        'late_days',
        'absent_days',
        'overtime_hours',
        'breakdown',
        'status',
        'paid_at',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'base_salary' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'total_overtime' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'present_days' => 'integer',
            'late_days' => 'integer',
            'absent_days' => 'integer',
            'overtime_hours' => 'decimal:2',
            'breakdown' => 'array',
            'status' => PayrollStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopePeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::create($this->period_year, $this->period_month, 1)->translatedFormat('F Y');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === PayrollStatus::Paid;
    }
}
