<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::updateOrCreate(['name' => 'Shift Pagi'], [
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5, 6],
            'late_deduction_tiers' => null,
            'is_active' => true,
        ]);

        Shift::updateOrCreate(['name' => 'Shift Sore'], [
            'start_time' => '13:00:00',
            'end_time' => '21:00:00',
            'grace_minutes' => 10,
            'work_days' => [1, 2, 3, 4, 5, 6],
            'late_deduction_tiers' => null,
            'is_active' => true,
        ]);
    }
}
