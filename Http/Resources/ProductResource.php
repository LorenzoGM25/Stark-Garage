<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'commission_rate' => (float) $this->commission_rate,
            'commission_type' => $this->commission_type?->value,
            'commission_amount' => $this->getCommissionForAttribute(),
            'stock' => $this->stock,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'is_service' => $this->is_service,
        ];
    }
}
