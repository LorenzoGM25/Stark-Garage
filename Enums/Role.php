<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Cashier => 'Kasir',
            self::Employee => 'Karyawan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Owner => 'dark',
            self::Manager => 'primary',
            self::Cashier => 'info',
            self::Employee => 'secondary',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
