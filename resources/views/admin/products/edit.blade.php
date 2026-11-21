@extends('layouts.admin')

@section('title', 'Edit Produk ' . $product->name . ' - STARK-GARAGE')
@section('page-title', 'Edit Produk & Layanan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Ubah Data Item</h5>
                        <small class="text-secondary">{{ $product->name }} ({{ $product->code ?? 'No Code' }})</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nama Produk / Layanan</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kode Produk</label>
                                    <input type="text" name="code" class="form-control font-monospace" value="{{ old('code', $product->code) }}" required>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kategori</label>
                                    <input type="text" name="category" list="categoryOptions" class="form-control" value="{{ old('category', $product->category) }}" required>
                                    <datalist id="categoryOptions">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stok Barang (Kosongkan bila Jasa Servis)</label>
                                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Harga Modal (HPP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Harga Jual Konsumen</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipe Komisi Mekanik</label>
                                    <select name="commission_type" class="form-select" required>
                                        <option value="fixed" {{ old('commission_type', $product->commission_type?->value ?? '') === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                                        <option value="percentage" {{ old('commission_type', $product->commission_type?->value ?? '') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Besaran Komisi Mekanik</label>
                                    <input type="number" step="0.01" name="commission_rate" class="form-control" value="{{ old('commission_rate', $product->commission_rate) }}">
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Foto Produk</label>
                                @if($product->image_url)
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-3 border" style="width: 72px; height: 72px; object-fit: cover;">
                                        <small class="text-secondary">Foto saat ini. Unggah file baru untuk mengganti.</small>
                                    </div>
                                @endif
                                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small class="text-secondary">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                            </div>

                            <div>
                                <label class="form-label">Deskripsi / Catatan Tambahan (Opsional)</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
                            </div>

                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="isActive">Item Aktif</label>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Perbarui Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
