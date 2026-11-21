@extends('layouts.admin')

@section('title', 'Biaya Operasional Bengkel - STARK-GARAGE')
@section('page-title', 'Biaya Operasional Bengkel')
@section('breadcrumb')
    <li class="breadcrumb-item active">Pengeluaran</li>
@endsection

@section('content')
    <!-- Expenses Summary Card -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Pengeluaran Periode</span>
                    <div class="stat-value text-danger">Rp {{ number_format($total, 0, ',', '.') }}</div>
                    <span class="text-secondary small">{{ $from->translatedFormat('d M') }} - {{ $to->translatedFormat('d M Y') }}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Jumlah Catatan Transaksi</span>
                    <div class="stat-value">{{ $expenses->total() }} <span class="fs-6 fw-normal text-secondary">Pengeluaran</span></div>
                    <span class="badge-pill-soft badge-neutral-soft">Biaya Operasional Terdata</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Kategori Pengeluaran Terbesar</span>
                    <div class="stat-value text-warning fs-5 fw-bold">
                        {{ $byCategory->first()?->category ?? 'Belum ada data' }}
                    </div>
                    <span class="text-secondary small">
                        Rp {{ number_format($byCategory->first()?->total ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Table Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Rincian Pengeluaran Operasional</h5>
                <small class="text-secondary">Peralatan bengkel, listrik, sewa, konsumsi, dan utilitas pendukung</small>
            </div>
            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Catat Pengeluaran Baru
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Keterangan..." value="{{ $search }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1"><i class="bi bi-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Keterangan Pengeluaran</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-end">Jumlah Biaya</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td class="fw-medium">
                                    {{ \Carbon\Carbon::parse($expense->expense_date)->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    <span class="badge-pill-soft badge-neutral-soft">{{ $expense->category }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $expense->description }}</div>
                                    @if($expense->notes)
                                        <small class="text-secondary">{{ $expense->notes }}</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary">{{ $expense->createdBy?->name ?? 'Admin Bengkel' }}</small>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-icon btn-ghost btn-sm text-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Hapus catatan pengeluaran ini?');" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-ghost btn-sm text-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="bi bi-wallet fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada catatan pengeluaran pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($expenses->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">Menampilkan {{ $expenses->firstItem() }} - {{ $expenses->lastItem() }} pengeluaran</small>
                <div>{{ $expenses->links() }}</div>
            </div>
        @endif
    </div>
@endsection

