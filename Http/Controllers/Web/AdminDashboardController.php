<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        return view('admin.dashboard', [
            'summary' => $this->dashboard->summary(),
            'salesSeries' => $this->dashboard->salesSeries(14),
            'monthlySeries' => $this->dashboard->monthlySeries(12),
            'peakHours' => $this->dashboard->peakHours(),
            'paymentMix' => $this->dashboard->paymentMix(),
            'topProducts' => $this->dashboard->topProducts(6),
            'recentOrders' => $this->dashboard->recentOrders(6),
            'todayAttendance' => $this->dashboard->todayAttendance(),
        ]);
    }
}
