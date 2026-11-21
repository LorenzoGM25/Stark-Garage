<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Enums\CommissionType;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['code' => 'SRV-001', 'name' => 'Ganti Oli Mesin', 'category' => 'Servis', 'price' => 85000, 'cost_price' => 65000, 'commission_rate' => 10, 'commission_type' => CommissionType::Percentage->value, 'stock' => null],
            ['code' => 'SRV-002', 'name' => 'Tune Up', 'category' => 'Servis', 'price' => 150000, 'cost_price' => 110000, 'commission_rate' => 10, 'commission_type' => CommissionType::Percentage->value, 'stock' => null],
            ['code' => 'SRV-003', 'name' => 'Ganti Ban', 'category' => 'Servis', 'price' => 25000, 'cost_price' => 0, 'commission_rate' => 5, 'commission_type' => CommissionType::Percentage->value, 'stock' => null],
            ['code' => 'PRD-001', 'name' => 'Oli Mesin 1L', 'category' => 'Suku Cadang', 'price' => 65000, 'cost_price' => 52000, 'commission_rate' => 3, 'commission_type' => CommissionType::Percentage->value, 'stock' => 40],
            ['code' => 'PRD-002', 'name' => 'Filter Oli', 'category' => 'Suku Cadang', 'price' => 25000, 'cost_price' => 18000, 'commission_rate' => 5, 'commission_type' => CommissionType::Percentage->value, 'stock' => 60],
            ['code' => 'PRD-003', 'name' => 'Busi NGK', 'category' => 'Suku Cadang', 'price' => 35000, 'cost_price' => 27000, 'commission_rate' => 5, 'commission_type' => CommissionType::Percentage->value, 'stock' => 80],
            ['code' => 'PRD-004', 'name' => 'Kampas Rem Depan', 'category' => 'Suku Cadang', 'price' => 95000, 'cost_price' => 75000, 'commission_rate' => 7, 'commission_type' => CommissionType::Percentage->value, 'stock' => 25],
            ['code' => 'PRD-005', 'name' => 'Aki Motor', 'category' => 'Suku Cadang', 'price' => 450000, 'cost_price' => 390000, 'commission_rate' => 3, 'commission_type' => CommissionType::Percentage->value, 'stock' => 12],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['code' => $product['code']], array_merge(['is_active' => true], $product));
        }
    }
}
