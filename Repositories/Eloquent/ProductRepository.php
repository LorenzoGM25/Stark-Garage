<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->search($filters['search'] ?? null)
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('is_active', (bool) $filters['status']))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function active(): Collection
    {
        return Product::query()->active()->orderBy('category')->orderBy('name')->get();
    }

    public function categories(): Collection
    {
        return Product::query()->select('category')->distinct()->orderBy('category')->pluck('category');
    }

    public function create(array $attributes): Product
    {
        return Product::create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->refresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}
