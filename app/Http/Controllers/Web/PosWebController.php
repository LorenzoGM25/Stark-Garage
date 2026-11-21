<?php

namespace App\Http\Controllers\Web;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosWebController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePos($request);

        $products = Product::query()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'category' => $product->category ?? 'Umum',
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'is_service' => $product->is_service,
                'image_url' => $product->image_url,
            ])
            ->values();

        $mechanics = User::query()
            ->active()
            ->where('role', Role::Employee->value)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pos.index', [
            'products' => $products,
            'categories' => $products->pluck('category')->unique()->sort()->values(),
            'paymentMethods' => PaymentMethod::cases(),
            'mechanics' => $mechanics,
            'taxPercent' => $this->settings->taxRate(),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $this->authorizePos($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orders->create($request->user(), $validated);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Transaksi gagal diproses. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'message' => 'Transaksi berhasil.',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'final_amount' => (float) $order->final_amount,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
                'payment_method_label' => $order->payment_method?->label(),
                'items_count' => $order->items->count(),
            ],
            'receipt_url' => route('admin.orders.receipt', $order),
            'detail_url' => route('admin.orders.show', $order),
        ], 201);
    }

    private function authorizePos(Request $request): void
    {
        abort_unless(
            in_array($request->user()?->role, [Role::Owner, Role::Manager, Role::Cashier], true),
            403,
            'Hanya kasir/resepsionis, manager, atau owner yang dapat mengakses halaman POS.',
        );
    }
}
