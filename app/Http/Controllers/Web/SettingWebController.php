<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingWebController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(): View
    {
        $allSettings = $this->settings->all();
        return view('admin.settings.index', compact('allSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $before = $this->settings->all();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overtime_multiplier' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'geofence_enabled' => ['nullable', 'boolean'],
            'geofence_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'geofence_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'face_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'face_margin' => ['nullable', 'numeric', 'between:0,1'],
            'liveness_min_score' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $this->settings->setMany([
            'business_name' => $validated['business_name'],
            'business_phone' => $validated['business_phone'] ?? '',
            'business_email' => $validated['business_email'] ?? '',
            'business_address' => $validated['business_address'] ?? '',
            'receipt_footer' => $validated['receipt_footer'] ?? '',
        ], 'business');

        $this->settings->setMany([
            'tax_percent' => (float) ($validated['tax_percent'] ?? 0),
        ], 'pos');

        $this->settings->setMany([
            'overtime_multiplier' => (float) ($validated['overtime_multiplier'] ?? 1.5),
            'late_deduction_tiers' => $this->settings->lateDeductionTiers(),
        ], 'payroll');

        $this->settings->setMany([
            'geofence_enabled' => $request->boolean('geofence_enabled'),
            'geofence_latitude' => (float) ($validated['geofence_latitude'] ?? 0),
            'geofence_longitude' => (float) ($validated['geofence_longitude'] ?? 0),
            'geofence_radius' => (int) ($validated['geofence_radius'] ?? 150),
            'face_threshold' => (float) ($validated['face_threshold'] ?? 0.82),
            'face_margin' => (float) ($validated['face_margin'] ?? 0.05),
            'liveness_min_score' => (float) ($validated['liveness_min_score'] ?? 0.70),
        ], 'attendance');

        $after = $this->settings->all();
        $oldValues = [];
        $newValues = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ((string) $oldValue !== (string) $newValue) {
                $oldValues[$key] = $oldValue;
                $newValues[$key] = $newValue;
            }
        }

        if ($newValues !== []) {
            $this->audit->log(
                'settings.updated',
                null,
                'Pengaturan sistem diperbarui',
                $oldValues,
                $newValues,
            );
        }

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}

