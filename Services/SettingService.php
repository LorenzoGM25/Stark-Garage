<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings.all';

    /** @return array<string, mixed> */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->get()->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $setting->value,
            ])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->all()[$key] ?? null;

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function float(string $key, float $default = 0): float
    {
        $value = $this->all()[$key] ?? null;

        return $value === null || $value === '' ? $default : (float) $value;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->all()[$key] ?? null;

        return $value === null || $value === '' ? $default : (int) $value;
    }

    /** @return array<string, mixed> */
    public function json(string $key, array $default = []): array
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): Setting
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'group' => $group, 'type' => $type],
        );

        $this->flush();

        return $setting;
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'float',
                is_array($value) => 'json',
                default => 'string',
            };

            $this->set($key, $value, $group, $type);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function taxRate(): float
    {
        return max(0, $this->float('tax_percent', 11));
    }

    public function overtimeMultiplier(): float
    {
        return max(1, $this->float('overtime_multiplier', (float) config('stark.payroll.overtime_multiplier', 1.5)));
    }

    public function faceThreshold(): float
    {
        return $this->float('face_threshold', (float) config('stark.face.threshold', 0.82));
    }

    public function livenessMinScore(): float
    {
        return $this->float('liveness_min_score', (float) config('stark.face.liveness_min_score', 0.70));
    }

    /** @return array<string, mixed> */
    public function business(): array
    {
        return [
            'name' => $this->get('business_name', config('stark.business.name', 'STARK-GARAGE')),
            'address' => $this->get('business_address', ''),
            'phone' => $this->get('business_phone', ''),
            'email' => $this->get('business_email', ''),
            'logo_path' => $this->get('business_logo_path'),
            'receipt_footer' => $this->get('receipt_footer', 'Terima kasih atas kepercayaan Anda.'),
            'currency' => $this->get('currency', 'IDR'),
        ];
    }

    /** @return array{enabled: bool, latitude: float, longitude: float, radius: int} */
    public function geofence(): array
    {
        return [
            'enabled' => $this->bool('geofence_enabled', (bool) config('stark.attendance.geofence_enabled', false)),
            'latitude' => $this->float('geofence_latitude', 0),
            'longitude' => $this->float('geofence_longitude', 0),
            'radius' => $this->int('geofence_radius', 150),
        ];
    }

    /** @return array<int, array{min: int, max: int|null, amount: float}> */
    public function lateDeductionTiers(): array
    {
        return $this->json('late_deduction_tiers', [
            ['min' => 1, 'max' => 15, 'amount' => 10000],
            ['min' => 16, 'max' => 30, 'amount' => 25000],
            ['min' => 31, 'max' => 60, 'amount' => 50000],
            ['min' => 61, 'max' => null, 'amount' => 100000],
        ]);
    }
}
