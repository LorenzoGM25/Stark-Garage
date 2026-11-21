<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_cashier_can_open_pos_page(): void
    {
        $cashier = User::factory()->cashier()->create();

        Product::create([
            'code' => 'OLI-001',
            'name' => 'Oli Mesin 1L',
            'category' => 'Pelumas',
            'price' => 50000,
            'cost_price' => 35000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($cashier)
            ->get(route('admin.pos.index'))
            ->assertOk()
            ->assertSee('Keranjang Transaksi')
            ->assertSee('Oli Mesin 1L');
    }

    public function test_cashier_can_checkout_and_create_order(): void
    {
        $cashier = User::factory()->cashier()->create();

        $product = Product::create([
            'code' => 'OLI-001',
            'name' => 'Oli Mesin 1L',
            'category' => 'Pelumas',
            'price' => 50000,
            'cost_price' => 35000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('admin.pos.checkout'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 300000,
            'customer_name' => 'Pelanggan Uji',
            'vehicle_plate' => 'B 1234 XYZ',
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.final_amount', 111000)
            ->assertJsonPath('order.change_amount', 189000);

        $this->assertDatabaseHas('orders', [
            'cashier_id' => $cashier->id,
            'customer_name' => 'Pelanggan Uji',
            'vehicle_plate' => 'B 1234 XYZ',
        ]);

        $order = Order::query()->firstOrFail();
        $this->assertSame(1, $order->items()->count());

        // Stok berkurang sesuai jumlah yang dibeli.
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_employee_cannot_access_pos(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->get(route('admin.pos.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->postJson(route('admin.pos.checkout'), [
                'items' => [['product_id' => 1, 'quantity' => 1]],
                'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }

    public function test_cash_payment_below_total_is_rejected(): void
    {
        $cashier = User::factory()->cashier()->create();

        $product = Product::create([
            'code' => 'JSA-001',
            'name' => 'Jasa Servis',
            'category' => 'Jasa',
            'price' => 100000,
            'stock' => null,
            'is_active' => true,
        ]);

        $this->actingAs($cashier)
            ->postJson(route('admin.pos.checkout'), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'paid_amount' => 50000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('paid_amount');
    }
}
