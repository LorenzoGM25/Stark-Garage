<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'business_name', 'value' => 'STARK-GARAGE', 'group' => 'business', 'type' => 'string'],
            ['key' => 'business_address', 'value' => '', 'group' => 'business', 'type' => 'string'],
            ['key' => 'business_phone', 'value' => '', 'group' => 'business', 'type' => 'string'],
            ['key' => 'business_email', 'value' => '', 'group' => 'business', 'type' => 'string'],
            ['key' => 'business_logo_path', 'value' => null, 'group' => 'business', 'type' => 'string'],
            ['key' => 'receipt_footer', 'value' => 'Terima kasih atas kepercayaan Anda.', 'group' => 'business', 'type' => 'string'],
            ['key' => 'currency', 'value' => 'IDR', 'group' => 'business', 'type' => 'string'],
            ['key' => 'tax_percent', 'value' => '11', 'group' => 'pos', 'type' => 'integer'],
            ['key' => 'overtime_multiplier', 'value' => '1.5', 'group' => 'payroll', 'type' => 'float'],
            ['key' => 'face_threshold', 'value' => '0.82', 'group' => 'attendance', 'type' => 'float'],
            ['key' => 'face_margin', 'value' => '0.05', 'group' => 'attendance', 'type' => 'float'],
            ['key' => 'liveness_min_score', 'value' => '0.70', 'group' => 'attendance', 'type' => 'float'],
            ['key' => 'geofence_enabled', 'value' => '0', 'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'geofence_latitude', 'value' => '0', 'group' => 'attendance', 'type' => 'float'],
            ['key' => 'geofence_longitude', 'value' => '0', 'group' => 'attendance', 'type' => 'float'],
            ['key' => 'geofence_radius', 'value' => '150', 'group' => 'attendance', 'type' => 'integer'],
            ['key' => 'late_deduction_tiers', 'value' => json_encode([
                ['min' => 1, 'max' => 15, 'amount' => 10000],
                ['min' => 16, 'max' => 30, 'amount' => 25000],
                ['min' => 31, 'max' => 60, 'amount' => 50000],
                ['min' => 61, 'max' => null, 'amount' => 100000],
            ], JSON_UNESCAPED_UNICODE), 'group' => 'payroll', 'type' => 'json'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
