<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Completed = 'completed';
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Voided => 'Dibatalkan',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Voided => 'danger',
            self::Refunded => 'warning',
        };
    }
}
