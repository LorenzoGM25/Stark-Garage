<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::Owner, Role::Manager], true)) {
            return view('admin.dashboard-staff', [
                'user' => $user,
                'today' => $user->attendances()
                    ->with('shift:id,name,start_time,end_time')
                    ->whereDate('date', today())
                    ->first(),
                'recentAttendances' => $user->attendances()
                    ->with('shift:id,name,start_time,end_time')
                    ->latest('date')
                    ->limit(7)
                    ->get(),
            ]);
        }

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
