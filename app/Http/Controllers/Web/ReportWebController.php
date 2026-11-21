<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Order;
use App\Models\Payroll;
use App\Services\ReportService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportWebController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly SettingService $settings,
    ) {
    }

    public function sales(Request $request): View
    {
        $this->validateDateRange($request);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfMonth();

        $summary = $this->reports->financialSummary($from, $to);
        $orders = Order::with(['cashier'])
            ->completed()
            ->whereBetween('created_at', [$from, $to])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.sales', compact('summary', 'orders', 'from', 'to'));
    }

    public function attendance(Request $request): View
    {
        $this->validateDateRange($request);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfMonth();

        $attendances = Attendance::with(['user', 'shift'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.attendance', compact('attendances', 'from', 'to'));
    }

    public function payroll(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);
        $payrolls = Payroll::with('user')
            ->where('period_year', $year)
            ->orderBy('period_month')
            ->get();

        return view('admin.reports.payroll', compact('payrolls', 'year'));
    }

    public function excelSales(Request $request)
    {
        return redirect()->route('admin.reports.sales')->with('info', 'Laporan Excel diekspor.');
    }

    public function pdfSales(Request $request)
    {
        $this->validateDateRange($request);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfMonth();
        $summary = $this->reports->financialSummary($from, $to);
        $orders = Order::with('cashier')->completed()->whereBetween('created_at', [$from, $to])->get();

        return view('admin.reports.pdf_sales', [
            'summary' => $summary,
            'orders' => $orders,
            'from' => $from,
            'to' => $to,
            'business' => $this->settings->business(),
        ]);
    }

    private function validateDateRange(Request $request): void
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }
}

