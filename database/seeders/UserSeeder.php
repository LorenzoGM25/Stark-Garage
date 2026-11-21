<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Shift;
use App\Enums\Role;
use App\Enums\SalaryType;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $shift = Shift::where('name', 'Shift Pagi')->first();

        $owner = User::updateOrCreate(['email' => 'william@starkgarage.com'], [
            'name' => 'Stark Owner',
            'employee_code' => 'STG-001',
            'phone' => '081234567890',
            'password' => Hash::make('password'),
            'role' => Role::Owner->value,
            'shift_id' => $shift?->id,
            'base_salary' => 0,
            'salary_type' => SalaryType::Monthly->value,
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        $manager = User::updateOrCreate(['email' => 'manager@stark-garage.local'], [
            'name' => 'Stark Manager',
            'employee_code' => 'STG-002',
            'phone' => '081234567891',
            'password' => Hash::make('password'),
            'role' => Role::Manager->value,
            'shift_id' => $shift?->id,
            'base_salary' => 4000000,
            'salary_type' => SalaryType::Monthly->value,
            'is_active' => true,
        ]);
        $manager->assignRole('manager');

        $cashier = User::updateOrCreate(['email' => 'kasir@stark-garage.local'], [
            'name' => 'Kasir Pertama',
            'employee_code' => 'STG-003',
            'phone' => '081234567892',
            'password' => Hash::make('password'),
            'role' => Role::Cashier->value,
            'shift_id' => $shift?->id,
            'base_salary' => 2500000,
            'salary_type' => SalaryType::Monthly->value,
            'is_active' => true,
        ]);
        $cashier->assignRole('cashier');

        $employee = User::updateOrCreate(['email' => 'karyawan@stark-garage.local'], [
            'name' => 'Mekanik Stark',
            'employee_code' => 'STG-004',
            'phone' => '081234567893',
            'password' => Hash::make('password'),
            'role' => Role::Employee->value,
            'shift_id' => $shift?->id,
            'base_salary' => 2000000,
            'salary_type' => SalaryType::Monthly->value,
            'hourly_overtime_rate' => 25000,
            'is_active' => true,
        ]);
        $employee->assignRole('employee');
    }
}
