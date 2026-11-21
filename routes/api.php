<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\EmployeeController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'active', 'record.activity'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/pos/products', [PosController::class, 'products']);
        Route::post('/pos/checkout', [PosController::class, 'checkout']);
        Route::get('/pos/receipt/{order}', [PosController::class, 'receipt']);

        Route::get('/attendance/today', [AttendanceController::class, 'today']);
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::post('/attendance/recognize', [AttendanceController::class, 'recognize']);

        Route::get('/face/profiles', [FaceController::class, 'profiles']);
        Route::post('/face/register', [FaceController::class, 'register']);
        Route::delete('/face/{user}', [FaceController::class, 'forget']);

        Route::get('/employees', [EmployeeController::class, 'index']);
    });
});
