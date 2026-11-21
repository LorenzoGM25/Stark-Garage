<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case Absent = 'absent';
    case Leave = 'leave';
    case HalfDay = 'half_day';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'Tepat Waktu',
            self::Late => 'Terlambat',
            self::Absent => 'Tidak Hadir',
            self::Leave => 'Izin / Cuti',
            self::HalfDay => 'Setengah Hari',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::OnTime => 'success',
            self::Late => 'warning',
            self::Absent => 'danger',
            self::Leave => 'info',
            self::HalfDay => 'secondary',
        };
    }
}
