<?php

namespace App\Http\Controllers\Web;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\PayrollService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollWebController extends Controller
{
    public function __construct(
        private readonly PayrollService $payroll,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $payrolls = Payroll::with(['user', 'generator'])
            ->period($month, $year)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_payrolls' => Payroll::period($month, $year)->count(),
            'total_amount' => (float) Payroll::period($month, $year)->sum('net_salary'),
            'paid_count' => Payroll::period($month, $year)->where('status', PayrollStatus::Paid)->count(),
            'draft_count' => Payroll::period($month, $year)->where('status', PayrollStatus::Draft)->count(),
        ];

        return view('admin.payroll.index', compact('payrolls', 'month', 'year', 'stats'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $this->payroll->generate($month, $year, $request->user());

        return redirect()->route('admin.payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "Slip gaji untuk periode {$month}/{$year} berhasil dibuat / dihitung ulang.");
    }

    public function bulkPay(Request $request): RedirectResponse
    {
        $ids = (array) $request->input('payroll_ids', []);
        if (!empty($ids)) {
            Payroll::whereIn('id', $ids)->where('status', '!=', PayrollStatus::Paid)->update([
                'status' => PayrollStatus::Paid,
                'paid_at' => now(),
            ]);
        }

        return back()->with('success', 'Semua gaji yang dipilih berhasil ditandai lunas.');
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load(['user.shift', 'generator']);
        return view('admin.payroll.show', compact('payroll'));
    }

    public function pay(Payroll $payroll): RedirectResponse
    {
        $payroll->update([
            'status' => PayrollStatus::Paid,
            'paid_at' => now(),
        ]);

        return back()->with('success', "Gaji {$payroll->user?->name} berhasil ditandai telah dibayarkan.");
    }

    public function pdf(Payroll $payroll)
    {
        $payroll->load(['user.shift', 'generator']);
        return view('admin.payroll.pdf', [
            'payroll' => $payroll,
            'business' => $this->settings->business(),
        ]);
    }
}

