<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AccountWebController;
use App\Http\Controllers\Web\ActivityLogWebController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AttendanceWebController;
use App\Http\Controllers\Web\EmployeeWebController;
use App\Http\Controllers\Web\ExpenseWebController;
use App\Http\Controllers\Web\FaceAttendanceWebController;
use App\Http\Controllers\Web\OrderWebController;
use App\Http\Controllers\Web\PosWebController;
use App\Http\Controllers\Web\PayrollWebController;
use App\Http\Controllers\Web\ProductWebController;
use App\Http\Controllers\Web\ReportWebController;
use App\Http\Controllers\Web\SettingWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'active', 'record.activity'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Face attendance is available to every employee so they can clock in.
    Route::get('attendance/face', [FaceAttendanceWebController::class, 'index'])->name('attendance.face');
    Route::post('attendance/face/register', [FaceAttendanceWebController::class, 'register'])->name('attendance.face.register');
    Route::post('attendance/face/recognize', [FaceAttendanceWebController::class, 'recognize'])->name('attendance.face.recognize');

    Route::middleware('role:owner,manager')->group(function () {
        Route::resource('products', ProductWebController::class);
        Route::post('employees/{employee}/reset-face', [EmployeeWebController::class, 'resetFace'])->name('employees.reset-face');
        Route::resource('employees', EmployeeWebController::class);

        Route::get('attendance', [AttendanceWebController::class, 'index'])->name('attendance.index');
        Route::get('attendance/today', [AttendanceWebController::class, 'today'])->name('attendance.today');
        Route::post('attendance/manual', [AttendanceWebController::class, 'manual'])->name('attendance.manual');
        Route::post('attendance/absent', [AttendanceWebController::class, 'markAbsent'])->name('attendance.absent');

        Route::get('payroll', [PayrollWebController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [PayrollWebController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/bulk-pay', [PayrollWebController::class, 'bulkPay'])->name('payroll.bulk-pay');
        Route::get('payroll/{payroll}', [PayrollWebController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{payroll}/pay', [PayrollWebController::class, 'pay'])->name('payroll.pay');
        Route::get('payroll/{payroll}/pdf', [PayrollWebController::class, 'pdf'])->name('payroll.pdf');

        Route::get('reports/sales', [ReportWebController::class, 'sales'])->name('reports.sales');
        Route::get('reports/attendance', [ReportWebController::class, 'attendance'])->name('reports.attendance');
        Route::get('reports/payroll', [ReportWebController::class, 'payroll'])->name('reports.payroll');
        Route::get('reports/excel/sales', [ReportWebController::class, 'excelSales'])->name('reports.excel.sales');
        Route::get('reports/pdf/sales', [ReportWebController::class, 'pdfSales'])->name('reports.pdf.sales');

        Route::resource('expenses', ExpenseWebController::class)->except(['show']);

        Route::get('settings', [SettingWebController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingWebController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:owner,manager,cashier')->group(function () {
        Route::get('pos', [PosWebController::class, 'index'])->name('pos.index');
        Route::post('pos/checkout', [PosWebController::class, 'checkout'])->name('pos.checkout');

        Route::get('orders', [OrderWebController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderWebController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/void', [OrderWebController::class, 'void'])->name('orders.void');
        Route::get('orders/{order}/receipt', [OrderWebController::class, 'receipt'])->name('orders.receipt');
    });

    // Superadmin area: only the Owner can manage accounts and inspect the log.
    Route::middleware('role:owner')->group(function () {
        Route::get('accounts', [AccountWebController::class, 'index'])->name('accounts.index');
        Route::get('accounts/{user}/edit', [AccountWebController::class, 'edit'])->name('accounts.edit');
        Route::put('accounts/{user}', [AccountWebController::class, 'update'])->name('accounts.update');
        Route::post('accounts/{user}/lock', [AccountWebController::class, 'lock'])->name('accounts.lock');
        Route::post('accounts/{user}/unlock', [AccountWebController::class, 'unlock'])->name('accounts.unlock');
        Route::post('accounts/{user}/password', [AccountWebController::class, 'resetPassword'])->name('accounts.password');
        Route::post('accounts/{user}/reset-face', [AccountWebController::class, 'resetFace'])->name('accounts.reset-face');
        Route::post('accounts/{user}/force-logout', [AccountWebController::class, 'forceLogout'])->name('accounts.force-logout');

        Route::get('activity', [ActivityLogWebController::class, 'index'])->name('activity.index');
    });
});
