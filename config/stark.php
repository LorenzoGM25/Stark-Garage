<?php

return [
    'business' => [
        'name' => env('BUSINESS_NAME', 'STARK-GARAGE'),
        'currency' => 'IDR',
        'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    ],

    'face' => [
        'threshold' => (float) env('FACE_MATCH_THRESHOLD', 0.82),
        'margin' => (float) env('FACE_MATCH_MARGIN', 0.05),
        'min_embedding_length' => (int) env('FACE_MIN_EMBEDDING_LENGTH', 64),
        'max_embedding_length' => (int) env('FACE_MAX_EMBEDDING_LENGTH', 512),
        'require_liveness' => (bool) env('FACE_REQUIRE_LIVENESS', true),
        'liveness_min_score' => (float) env('FACE_LIVENESS_MIN_SCORE', 0.70),
    ],

    'attendance' => [
        'geofence_enabled' => (bool) env('ATTENDANCE_GEOFENCE_ENABLED', false),
    ],

    'payroll' => [
        'working_hours_per_day' => 8,
        'overtime_multiplier' => 1.5,
    ],
];
