<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Paid => 'Dibayar',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Paid => 'success',
        };
    }
}
