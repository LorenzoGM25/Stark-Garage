<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderWebController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderService $service,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $filters = $request->only(['search', 'status', 'payment_method', 'date_from', 'date_to']);

        $orders = $this->orders->paginate($filters, 15);

        $todayQuery = Order::query()->completed()->whereDate('created_at', today());
        $monthQuery = Order::query()->completed()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

        $stats = [
            'today_count' => (clone $todayQuery)->count(),
            'today_revenue' => (float) (clone $todayQuery)->sum('final_amount'),
            'month_count' => (clone $monthQuery)->count(),
            'month_revenue' => (float) (clone $monthQuery)->sum('final_amount'),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'cashier', 'employee', 'voidedBy']);

        return view('admin.orders.show', compact('order'));
    }

    public function void(Request $request, Order $order): RedirectResponse
    {
        $actor = $request->user();

        abort_unless(
            in_array($actor->role, [Role::Owner, Role::Manager], true),
            403,
            'Hanya Owner atau Manager yang dapat membatalkan transaksi.',
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->service->void($order, $actor, $validated['reason']);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membatalkan transaksi.');
        }
    }

    public function receipt(Order $order): View
    {
        $order->load(['items', 'cashier', 'employee']);

        return view('admin.orders.receipt', [
            'order' => $order,
            'business' => $this->settings->business(),
        ]);
    }
}
