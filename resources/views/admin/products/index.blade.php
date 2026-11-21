@extends('layouts.admin')

@section('title', 'Katalog Produk & Layanan - STARK-GARAGE')
@section('page-title', 'Produk & Layanan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Produk &amp; Layanan</li>
@endsection

@section('content')
    <!-- Header Summary & Quick Stats -->
    <div class="row g-3 g-xl-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Item Katalog</span>
                    <div class="stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Item sparepart &amp; jasa servis</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Item Aktif</span>
                    <div class="stat-value text-success">{{ number_format($stats['active'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-success-soft">Siap Dijual / Kasir</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Stok Kritis (&le; 5)</span>
                    <div class="stat-value text-warning">{{ number_format($stats['low_stock'], 0, ',', '.') }}</div>
                    <span class="badge-pill-soft badge-warning-soft">Perlu Restok Segera</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-label">Total Nilai Aset Stok</span>
                    <div class="stat-value text-primary">Rp {{ number_format($stats['inventory_value'], 0, ',', '.') }}</div>
                    <span class="text-secondary small">Berdasarkan HPP / Modal</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Table Card -->
    <div class="card">
        <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-0">Daftar Produk &amp; Layanan</h5>
                <small class="text-secondary">Kelola harga modal, harga jual, stok minimum, dan komisi mekanik</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Item Baru
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-3 border-bottom" style="background: var(--sg-surface-subtle);">
            <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama sparepart, kode SKU, atau barcode..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-sm-3 col-lg-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>
                <div class="col-12 col-sm-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('admin.products.index') }}" class="btn btn-ghost btn-sm" title="Reset filter"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item &amp; Kode SKU</th>
                            <th>Kategori</th>
                            <th class="text-end">Harga Beli (Modal)</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-2 border" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="stat-icon avatar-sm" style="font-size: 0.85rem;">
                                                <i class="bi {{ $product->is_service ? 'bi-wrench-adjustable' : 'bi-box-seam' }}"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $product->name }}</div>
                                            <small class="text-secondary font-monospace">{{ $product->code ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-pill-soft badge-neutral-soft">{{ $product->category ?? 'Umum' }}</span>
                                </td>
                                <td class="text-end text-secondary small">
                                    Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($product->is_service)
                                        <span class="badge-pill-soft badge-info-soft">Layanan Servis</span>
                                    @elseif($product->stock !== null)
                                        @if($product->stock <= 5)
                                            <span class="badge-pill-soft badge-warning-soft fw-bold">{{ $product->stock }} (Menipis)</span>
                                        @else
                                            <span class="badge-pill-soft badge-success-soft">{{ $product->stock }} unit</span>
                                        @endif
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($product->is_active)
                                        <span class="badge-pill-soft badge-success-soft"><i class="bi bi-check2-circle"></i> Aktif</span>
                                    @else
                                        <span class="badge-pill-soft badge-danger-soft">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-ghost btn-sm" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('admin.products.edit', $product) }}"><i class="bi bi-pencil me-2 text-warning"></i>Edit Produk</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.products.show', $product) }}"><i class="bi bi-eye me-2 text-primary"></i>Detail &amp; Riwayat</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus Item</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada produk atau layanan yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($products->hasPages())
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-secondary">Menampilkan {{ $products->firstItem() }} sampai {{ $products->lastItem() }} dari {{ $products->total() }} hasil</small>
                <div>{{ $products->links() }}</div>
            </div>
        @endif
    </div>
@endsection

