<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\SalaryType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'employee_code' => 'STG-'.fake()->unique()->numberBetween(100, 999),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Employee->value,
            'base_salary' => 2000000,
            'salary_type' => SalaryType::Monthly->value,
            'hourly_overtime_rate' => 20000,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => Role::Owner->value]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => Role::Manager->value]);
    }

    public function cashier(): static
    {
        return $this->state(fn () => ['role' => Role::Cashier->value]);
    }

    public function employee(): static
    {
        return $this->state(fn () => ['role' => Role::Employee->value]);
    }
}
