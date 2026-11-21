<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'cashier_id',
        'employee_id',
        'customer_name',
        'customer_phone',
        'vehicle_plate',
        'subtotal',
        'discount',
        'tax_rate',
        'tax_amount',
        'final_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'notes',
        'voided_at',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'voided_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Completed->value);
    }

    public function scopeBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function getIsVoidedAttribute(): bool
    {
        return $this->status !== OrderStatus::Completed;
    }

    public function getItemsCountAttribute(): int
    {
        if (array_key_exists('items_count', $this->attributes)) {
            return (int) $this->attributes['items_count'];
        }

        return $this->relationLoaded('items')
            ? $this->items->count()
            : $this->items()->count();
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->items->sum(fn (OrderItem $item) => (float) $item->cost_price * $item->quantity);
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) $this->subtotal - $this->total_cost;
    }
}
