@extends('layouts.admin')

@section('title', 'Tambah Produk / Layanan - STARK-GARAGE')
@section('page-title', 'Tambah Produk & Layanan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produk</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h5 class="fw-bold mb-0">Informasi Item Katalog Baru</h5>
                        <small class="text-secondary">Isi data sparepart atau tipe layanan servis bengkel</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nama Produk / Layanan</label>
                                    <input type="text" name="name" class="form-control" placeholder="Contoh: Oli Mesin Motul 10W-40 1L" value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kode Produk / Barcode</label>
                                    <input type="text" name="code" class="form-control font-monospace" placeholder="PRD-10293" value="{{ old('code') }}" required>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kategori</label>
                                    <input type="text" name="category" list="categoryOptions" class="form-control" placeholder="Oli, Ban, Sparepart, Servis..." value="{{ old('category') }}" required>
                                    <datalist id="categoryOptions">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stok Barang (Kosongkan bila Jasa Servis)</label>
                                    <input type="number" name="stock" class="form-control" placeholder="Jumlah unit (atau kosongkan untuk servis)" value="{{ old('stock') }}">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Harga Modal (Beli / HPP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="cost_price" class="form-control" placeholder="0" value="{{ old('cost_price', 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Harga Jual Konsumen</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price" class="form-control" placeholder="0" value="{{ old('price') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipe Komisi Mekanik</label>
                                    <select name="commission_type" class="form-select" required>
                                        <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                                        <option value="percentage" {{ old('commission_type') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Besaran Komisi Mekanik</label>
                                    <input type="number" step="0.01" name="commission_rate" class="form-control" placeholder="0" value="{{ old('commission_rate', 0) }}">
                                    <small class="text-secondary">Nominal Rupiah atau angka persen (misal: 10)</small>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Foto Produk (Opsional)</label>
                                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small class="text-secondary">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                            </div>

                            <div>
                                <label class="form-label">Deskripsi / Catatan Tambahan (Opsional)</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Spesifikasi atau petunjuk pemakaian...">{{ old('description') }}</textarea>
                            </div>

                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                                <label class="form-check-label small" for="isActive">Item Aktif (Dapat Dijual di Kasir)</label>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
