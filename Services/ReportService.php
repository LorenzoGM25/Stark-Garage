<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** @return array<string, float|int> */
    public function financialSummary(Carbon $start, Carbon $end): array
    {
        $orders = Order::query()->completed()->whereBetween('created_at', [$start, $end]);

        $revenue = (float) (clone $orders)->sum('final_amount');
        $transactions = (int) (clone $orders)->count();
        $discount = (float) (clone $orders)->sum('discount');
        $tax = (float) (clone $orders)->sum('tax_amount');

        $items = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereBetween('orders.created_at', [$start, $end]);

        $cost = (float) (clone $items)->sum(DB::raw('order_items.cost_price * order_items.quantity'));
        $commission = (float) (clone $items)->sum('order_items.commission_amount');
        $expenses = (float) Expense::query()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');

        $grossProfit = round($revenue - $cost, 2);

        return [
            'revenue' => round($revenue, 2),
            'transactions' => $transactions,
            'average_ticket' => $transactions > 0 ? round($revenue / $transactions, 2) : 0.0,
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'cost' => round($cost, 2),
            'commission' => round($commission, 2),
            'gross_profit' => $grossProfit,
            'expenses' => round($expenses, 2),
            'net_profit' => round($grossProfit - $expenses, 2),
        ];
    }

    /** @return Collection<int, object> */
    public function salesByDay(Carbon $start, Carbon $end): Collection
    {
        return Order::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as transactions, SUM(final_amount) as revenue, SUM(discount) as discount, SUM(tax_amount) as tax')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /** @return Collection<int, object> */
    public function paymentBreakdown(Carbon $start, Carbon $end): Collection
    {
        return Order::query()
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->select('payment_method', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(final_amount) as revenue'))
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();
    }

    /** @return Collection<int, object> */
    public function topProducts(Carbon $start, Carbon $end, int $limit = 20): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
                DB::raw('SUM(order_items.commission_amount) as commission'),
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, object> */
    public function attendanceSummary(Carbon $start, Carbon $end): Collection
    {
        return Attendance::query()
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->whereBetween('attendances.date', [$start->toDateString(), $end->toDateString()])
            ->select(
                'users.id',
                'users.name',
                'users.employee_code',
                DB::raw("SUM(CASE WHEN attendances.status IN ('on_time','late','half_day') THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_days"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN attendances.status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
                DB::raw('SUM(attendances.late_minutes) as late_minutes'),
                DB::raw('SUM(attendances.overtime_minutes) as overtime_minutes'),
            )
            ->groupBy('users.id', 'users.name', 'users.employee_code')
            ->orderBy('users.name')
            ->get();
    }

    /** @return Collection<int, object> */
    public function payrollSummary(int $month, int $year): Collection
    {
        return Payroll::query()
            ->join('users', 'users.id', '=', 'payrolls.user_id')
            ->where('payrolls.period_month', $month)
            ->where('payrolls.period_year', $year)
            ->select(
                'payrolls.*',
                'users.name',
                'users.employee_code',
            )
            ->orderBy('users.name')
            ->get();
    }
}
