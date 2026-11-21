<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(User $cashier, array $data): Order
    {
        $items = $data['items'] ?? [];

        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages(['items' => 'Keranjang tidak boleh kosong.']);
        }

        return DB::transaction(function () use ($cashier, $data, $items) {
            $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
            $products = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'Salah satu produk tidak ditemukan.']);
            }

            $lineItems = [];
            $subtotal = 0.0;

            foreach ($items as $index => $item) {
                /** @var Product|null $product */
                $product = $products->get((int) ($item['product_id'] ?? 0));
                $quantity = (int) ($item['quantity'] ?? 0);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'Produk tidak tersedia atau sudah tidak aktif.',
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Jumlah minimal 1.',
                    ]);
                }

                if ($product->stock !== null && $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Stok {$product->name} tidak mencukupi (tersisa {$product->stock}).",
                    ]);
                }

                $gross = round((float) $product->price * $quantity, 2);
                $itemDiscount = min(max(0, round((float) ($item['discount'] ?? 0), 2)), $gross);
                $lineSubtotal = round($gross - $itemDiscount, 2);

                $commission = $product->commission_type === CommissionType::Fixed
                    ? round((float) $product->commission_rate * $quantity, 2)
                    : round($lineSubtotal * (float) $product->commission_rate / 100, 2);

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => $quantity,
                    'price' => (float) $product->price,
                    'cost_price' => (float) $product->cost_price,
                    'discount' => $itemDiscount,
                    'commission_amount' => $commission,
                    'subtotal' => $lineSubtotal,
                ];

                $subtotal = round($subtotal + $lineSubtotal, 2);
            }

            $orderDiscount = min(max(0, round((float) ($data['discount'] ?? 0), 2)), $subtotal);
            $taxable = round($subtotal - $orderDiscount, 2);
            $taxRate = $this->settings->taxRate();
            $taxAmount = round($taxable * $taxRate / 100, 2);
            $finalAmount = round($taxable + $taxAmount, 2);

            $paymentMethod = PaymentMethod::tryFrom($data['payment_method'] ?? '') ?? PaymentMethod::Cash;
            $paidAmount = isset($data['paid_amount']) ? round((float) $data['paid_amount'], 2) : $finalAmount;

            if ($paymentMethod === PaymentMethod::Cash && $paidAmount < $finalAmount) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Jumlah pembayaran kurang dari total tagihan.',
                ]);
            }

            $paidAmount = max($paidAmount, $finalAmount);
            $changeAmount = round(max(0, $paidAmount - $finalAmount), 2);

            $employeeId = $data['employee_id'] ?? null;
            $employeeId = $employeeId && User::whereKey($employeeId)->exists() ? (int) $employeeId : null;

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'cashier_id' => $cashier->id,
                'employee_id' => $employeeId,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $orderDiscount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod->value,
                'status' => OrderStatus::Completed->value,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->createMany($lineItems);

            foreach ($lineItems as $line) {
                if ($line['product_id'] === null) {
                    continue;
                }

                $product = $products->get($line['product_id']);

                if ($product && $product->stock !== null) {
                    $product->decrement('stock', $line['quantity']);
                }
            }

            $this->audit->log('order.completed', $order, "Transaksi {$order->order_number}", [], [
                'total' => $finalAmount,
                'items' => count($lineItems),
                'payment_method' => $paymentMethod->value,
            ], $cashier);

            return $order->load(['items', 'cashier', 'employee']);
        });
    }

    public function void(Order $order, User $actor, ?string $reason = null): Order
    {
        if ($order->status !== OrderStatus::Completed) {
            throw ValidationException::withMessages([
                'order' => 'Hanya transaksi selesai yang dapat dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $reason) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if ($item->product_id && $item->product) {
                    if ($item->product->stock !== null) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Voided->value,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'notes' => trim(($order->notes ? $order->notes.' | ' : '').'Void: '.($reason ?? 'Tanpa alasan')),
            ]);

            $this->audit->log('order.voided', $order, "Void transaksi {$order->order_number}", [
                'status' => OrderStatus::Completed->value,
            ], [
                'status' => OrderStatus::Voided->value,
                'reason' => $reason,
            ], $actor);

            return $order->refresh()->load(['items', 'cashier', 'employee', 'voidedBy']);
        });
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'STG-'.now()->format('ymd').'-';
        $sequence = Order::whereDate('created_at', today())->lockForUpdate()->count();

        do {
            $sequence++;
            $number = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
