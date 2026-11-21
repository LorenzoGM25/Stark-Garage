<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseWebController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }
    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfMonth();
        $category = $request->category;
        $search = $request->search;

        $expenses = Expense::query()
            ->with('createdBy:id,name')
            ->when($search, fn ($q, $term) => $q->where('description', 'like', "%{$term}%"))
            ->when($category, fn ($q, $cat) => $q->where('category', $cat))
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Expense::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $total = (float) Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $byCategory = Expense::query()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('admin.expenses.index', compact('expenses', 'categories', 'total', 'byCategory', 'from', 'to', 'category', 'search'));
    }

    public function create(): View
    {
        return view('admin.expenses.create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $expense = Expense::create($validated + ['created_by' => $request->user()->id]);

            $this->audit->log(
                'expense.created',
                $expense,
                "Pengeluaran {$expense->description} dicatat",
                [],
                $this->snapshot($expense),
            );

            return redirect()
                ->route('admin.expenses.index')
                ->with('success', 'Pengeluaran berhasil dicatat.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan pengeluaran.');
        }
    }

    public function edit(Expense $expense): View
    {
        return view('admin.expenses.edit', [
            'expense' => $expense,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $before = $this->snapshot($expense);

            $expense->update($validated);

            $this->audit->log(
                'expense.updated',
                $expense,
                "Pengeluaran {$expense->description} diperbarui",
                $before,
                $this->snapshot($expense->fresh()),
            );

            return redirect()
                ->route('admin.expenses.index')
                ->with('success', 'Pengeluaran berhasil diperbarui.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui pengeluaran.');
        }
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        try {
            $this->audit->log(
                'expense.deleted',
                $expense,
                "Pengeluaran {$expense->description} dihapus",
                $this->snapshot($expense),
                [],
            );

            $expense->delete();

            return redirect()
                ->route('admin.expenses.index')
                ->with('success', 'Pengeluaran berhasil dihapus.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus pengeluaran.');
        }
    }

    /** @return array<int, string> */
    private function categoryOptions(): array
    {
        $defaults = ['Operasional', 'Pembelian Sparepart', 'Gaji & Bonus', 'Sewa', 'Listrik & Air', 'Marketing', 'Lainnya'];

        return collect($defaults)
            ->merge(Expense::query()->select('category')->distinct()->pluck('category'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function snapshot(Expense $expense): array
    {
        return [
            'expense_date' => $expense->expense_date?->toDateString(),
            'category' => $expense->category,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'notes' => $expense->notes,
        ];
    }
}
