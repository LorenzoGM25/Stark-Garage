@extends('layouts.admin')

@section('title', 'Detail Produk ' . $product->name . ' - STARK-GARAGE')
@section('page-title', 'Detail Produk & Layanan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">{{ $product->name }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Informasi Produk</h5>
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-3 border w-100" style="max-height: 220px; object-fit: cover;">
                    @endif
                    <div>
                        <span class="text-secondary small d-block">Nama Item:</span>
                        <h4 class="fw-bold mb-0">{{ $product->name }}</h4>
                    </div>
                    @if($product->description)
                        <div class="border-top pt-2">
                            <span class="text-secondary small d-block">Deskripsi:</span>
                            <p class="small mb-0">{{ $product->description }}</p>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Kode Produk:</span>
                        <span class="font-monospace fw-semibold">{{ $product->code ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Kategori:</span>
                        <span class="badge-pill-soft badge-neutral-soft">{{ $product->category }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Jenis:</span>
                        <span class="fw-semibold">{{ $product->is_service ? 'Jasa Servis' : 'Sparepart Fisik' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Harga Modal:</span>
                        <span class="fw-semibold text-secondary">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Harga Jual:</span>
                        <span class="fw-bold fs-5 text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="text-secondary small">Status:</span>
                        <span class="badge-pill-soft {{ $product->is_active ? 'badge-success-soft' : 'badge-danger-soft' }}">
                            {{ $product->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Status Inventaris &amp; Margin</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3" style="background: var(--sg-surface-subtle);">
                                <span class="text-secondary small d-block">Stok Tersedia</span>
                                <div class="h3 fw-bold mb-0 {{ ($product->stock ?? 0) <= 5 ? 'text-warning' : 'text-success' }}">
                                    {{ $product->is_service ? 'Tidak Terbatas' : ($product->stock ?? 0) . ' Unit' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3" style="background: var(--sg-surface-subtle);">
                                <span class="text-secondary small d-block">Estimasi Margin Keuntungan</span>
                                @php
                                    $margin = ($product->cost_price > 0 && $product->price > 0) ? (($product->price - $product->cost_price) / $product->price) * 100 : 100;
                                @endphp
                                <div class="h3 fw-bold mb-0 text-primary">{{ round($margin, 1) }}%</div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Katalog
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

