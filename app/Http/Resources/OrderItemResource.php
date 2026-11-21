<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'discount' => (float) $this->discount,
            'commission_amount' => (float) $this->commission_amount,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}
