<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\OrderResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly OrderService $orders,
    ) {
    }

    public function products(): JsonResponse
    {
        $products = $this->products->active();

        return response()->json([
            'categories' => $this->products->categories(),
            'products' => ProductResource::collection($products),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer,card'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = $this->orders->create($request->user(), $validated);

        return response()->json([
            'message' => 'Transaksi berhasil.',
            'order' => OrderResource::make($order),
        ], 201);
    }

    public function receipt($order): OrderResource
    {
        $order = \App\Models\Order::with(['items', 'cashier', 'employee'])->findOrFail($order);

        return OrderResource::make($order);
    }
}
