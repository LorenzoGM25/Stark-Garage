<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'category', 'status']);

        $products = $this->products->paginate($filters, 12);
        $categories = $this->products->categories();

        $stats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->active()->count(),
            'low_stock' => Product::query()->active()->whereNotNull('stock')->where('stock', '<=', 5)->count(),
            'inventory_value' => (float) Product::query()
                ->whereNotNull('stock')
                ->sum(DB::raw('stock * cost_price')),
        ];

        return view('admin.products.index', compact('products', 'categories', 'stats', 'filters'));
    }

    public function create(): View
    {
        $categories = $this->products->categories();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        try {
            $product = $this->products->create($validated);

            $this->audit->log(
                'product.created',
                $product,
                "Produk {$product->name} ditambahkan",
                [],
                $this->snapshot($product),
            );

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk / layanan berhasil ditambahkan.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan produk. Silakan coba lagi.');
        }
    }

    public function show(Product $product): View
    {
        $product->loadCount('orderItems');

        $recentItems = $product->orderItems()
            ->with('order:id,order_number,created_at')
            ->latest()
            ->limit(10)
            ->get();

        $soldQuantity = (int) $product->orderItems()->sum('quantity');
        $revenue = (float) $product->orderItems()->sum('subtotal');

        return view('admin.products.show', compact('product', 'recentItems', 'soldQuantity', 'revenue'));
    }

    public function edit(Product $product): View
    {
        $categories = $this->products->categories();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatePayload($request, $product);
        $previousImage = $product->image_path;
        $before = $this->snapshot($product);

        try {
            $this->products->update($product, $validated);

            if (isset($validated['image_path']) && $previousImage && $previousImage !== $validated['image_path']) {
                Storage::disk('public')->delete($previousImage);
            }

            $this->audit->log(
                'product.updated',
                $product,
                "Produk {$product->name} diperbarui",
                $before,
                $this->snapshot($product->fresh()),
            );

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk / layanan berhasil diperbarui.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui produk. Silakan coba lagi.');
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            $this->audit->log(
                'product.updated',
                $product,
                "Produk {$product->name} dinonaktifkan karena sudah dipakai transaksi",
                ['is_active' => true],
                ['is_active' => false],
            );

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk sudah dipakai pada transaksi, jadi dinonaktifkan (bukan dihapus).');
        }

        try {
            $snapshot = $this->snapshot($product);

            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $this->audit->log(
                'product.deleted',
                $product,
                "Produk {$product->name} dihapus",
                $snapshot,
                [],
            );

            $this->products->delete($product);

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil dihapus.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus produk.');
        }
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'commission_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if (($validated['commission_type'] ?? null) === 'percentage' && (float) ($validated['commission_rate'] ?? 0) > 100) {
            throw ValidationException::withMessages([
                'commission_rate' => 'Komisi persentase tidak boleh lebih dari 100%.',
            ]);
        }

        $validated['cost_price'] = $validated['cost_price'] ?? 0;
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($validated['image']);

        return $validated;
    }

    /** @return array<string, mixed> */
    private function snapshot(Product $product): array
    {
        return [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            'cost_price' => (float) $product->cost_price,
            'commission_rate' => (float) $product->commission_rate,
            'commission_type' => $product->commission_type,
            'stock' => $product->stock,
            'is_active' => $product->is_active,
        ];
    }
}
