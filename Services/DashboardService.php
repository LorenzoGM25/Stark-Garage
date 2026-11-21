<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\PayrollStatus;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth()->startOfDay();
        $monthEnd = $now->copy()->endOfMonth()->endOfDay();
        $yesterdayStart = $now->copy()->subDay()->startOfDay();
        $yesterdayEnd = $now->copy()->subDay()->endOfDay();

        $todayQuery = Order::query()->completed()->whereBetween('created_at', [$todayStart, $todayEnd]);
        $todayRevenue = (float) (clone $todayQuery)->sum('final_amount');
        $todayOrders = (clone $todayQuery)->count();
        $yesterdayRevenue = (float) Order::query()->completed()->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->sum('final_amount');

        $monthRevenue = (float) Order::query()->completed()->whereBetween('created_at', [$monthStart, $monthEnd])->sum('final_amount');
        $monthOrders = (int) Order::query()->completed()->whereBetween('created_at', [$monthStart, $monthEnd])->count();

        $cost = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$monthStart, $monthEnd])
            ->sum(DB::raw('order_items.cost_price * order_items.quantity'));

        $commission = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$monthStart, $monthEnd])
            ->sum('order_items.commission_amount');

        $expenses = (float) Expense::query()->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('amount');
        $payroll = (float) Payroll::query()->period($now->month, $now->year)->sum('net_salary');

        $grossProfit = round($monthRevenue - $cost, 2);
        $netProfit = round($grossProfit - $expenses - $payroll, 2);

        $attendanceToday = Attendance::query()
            ->whereDate('date', today())
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'today_revenue' => $todayRevenue,
            'today_orders' => $todayOrders,
            'today_average' => $todayOrders > 0 ? round($todayRevenue / $todayOrders, 2) : 0.0,
            'revenue_trend' => $this->trend($todayRevenue, $yesterdayRevenue),
            'month_revenue' => $monthRevenue,
            'month_orders' => $monthOrders,
            'month_gross_profit' => $grossProfit,
            'month_net_profit' => $netProfit,
            'month_cost' => round($cost, 2),
            'month_expenses' => round($expenses, 2),
            'month_payroll' => round($payroll, 2),
            'month_commission' => round($commission, 2),
            'active_staff' => User::query()->active()->staff()->count(),
            'attendance_today' => [
                'on_time' => (int) ($attendanceToday[AttendanceStatus::OnTime->value] ?? 0),
                'late' => (int) ($attendanceToday[AttendanceStatus::Late->value] ?? 0),
                'absent' => (int) ($attendanceToday[AttendanceStatus::Absent->value] ?? 0),
                'leave' => (int) ($attendanceToday[AttendanceStatus::Leave->value] ?? 0),
            ],
            'pending_payrolls' => Payroll::query()->where('status', PayrollStatus::Draft->value)->count(),
            'low_stock' => Product::query()->active()->whereNotNull('stock')->where('stock', '<=', 5)->count(),
        ];
    }

    /** @return array{labels: array<int, string>, revenue: array<int, float>, orders: array<int, int>} */
    public function salesSeries(int $days = 14): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->completed()
            ->where('created_at', '>=', $start)
            ->select('created_at', 'final_amount')
            ->get()
            ->groupBy(fn (Order $order) => $order->created_at->toDateString());

        $labels = [];
        $revenue = [];
        $orders = [];

        foreach (CarbonPeriod::create($start, now()->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $revenue[] = round((float) ($rows->get($key)?->sum('final_amount') ?? 0), 2);
            $orders[] = (int) ($rows->get($key)?->count() ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders];
    }

    /** @return array{labels: array<int, string>, revenue: array<int, float>, orders: array<int, int>} */
    public function monthlySeries(int $months = 12): array
    {
        $start = now()->copy()->subMonths($months - 1)->startOfMonth();

        $rows = Order::query()
            ->completed()
            ->where('created_at', '>=', $start)
            ->select('created_at', 'final_amount')
            ->get()
            ->groupBy(fn (Order $order) => $order->created_at->format('Y-m'));

        $labels = [];
        $revenue = [];
        $orders = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M y');
            $revenue[] = round((float) ($rows->get($key)?->sum('final_amount') ?? 0), 2);
            $orders[] = (int) ($rows->get($key)?->count() ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders];
    }

    /** @return array<int, array{hour: string, orders: int, revenue: float}> */
    public function peakHours(): array
    {
        $rows = Order::query()
            ->completed()
            ->where('created_at', '>=', now()->copy()->subDays(30))
            ->select('created_at', 'final_amount')
            ->get();

        return collect(range(0, 23))->map(function (int $hour) use ($rows) {
            $matching = $rows->filter(fn (Order $order) => (int) $order->created_at->format('G') === $hour);

            return [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'orders' => $matching->count(),
                'revenue' => round((float) $matching->sum('final_amount'), 2),
            ];
        })->all();
    }

    /** @return array<string, float> */
    public function paymentMix(): array
    {
        return Order::query()
            ->completed()
            ->where('created_at', '>=', now()->copy()->subDays(30))
            ->select('payment_method', DB::raw('SUM(final_amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($total) => round((float) $total, 2))
            ->all();
    }

    /** @return array<int, array{name: string, quantity: int, revenue: float}> */
    public function topProducts(int $limit = 6): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->copy()->subDays(30))
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'quantity' => (int) $row->quantity,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    public function recentOrders(int $limit = 6)
    {
        return $this->orders->recent($limit);
    }

    /** @return array<int, array{user: User, attendance: ?Attendance}> */
    public function todayAttendance(): array
    {
        return User::query()
            ->active()
            ->staff()
            ->with(['attendances' => fn ($q) => $q->whereDate('date', today())])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'attendance' => $user->attendances->first(),
            ])
            ->all();
    }

    private function trend(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
