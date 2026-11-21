@extends('layouts.admin')

@section('title', 'Catat Pengeluaran Baru - STARK-GARAGE')
@section('page-title', 'Catat Biaya Operasional')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.expenses.index') }}">Pengeluaran</a></li>
    <li class="breadcrumb-item active">Catat Baru</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Formulir Pengeluaran Kas Bengkel</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenses.store') }}" method="POST">
                        @csrf
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Transaksi</label>
                                    <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', today()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kategori Biaya</label>
                                    <input type="text" name="category" list="expenseCategories" class="form-control" placeholder="Peralatan, Listrik, Sewa, Konsumsi..." value="{{ old('category') }}" required>
                                    <datalist id="expenseCategories">
                                        <option value="Peralatan Bengkel"></option>
                                        <option value="Listrik & Air"></option>
                                        <option value="Konsumsi Tim"></option>
                                        <option value="Sewa Tempat"></option>
                                        <option value="Lain-lain"></option>
                                    </datalist>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Keterangan Pengeluaran</label>
                                <input type="text" name="description" class="form-control" placeholder="Contoh: Pembelian Oli Kompresor & Kunci Pas 12mm" value="{{ old('description') }}" required>
                            </div>

                            <div>
                                <label class="form-label">Nominal Biaya (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="amount" class="form-control" placeholder="0" value="{{ old('amount') }}" required>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Catatan Tambahan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Nomor nota toko atau rincian tambahan...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pengeluaran</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

