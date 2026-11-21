@extends('layouts.admin')

@section('title', 'Edit Biaya Operasional - STARK-GARAGE')
@section('page-title', 'Edit Pengeluaran')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.expenses.index') }}">Pengeluaran</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Edit Catatan Pengeluaran Kas</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenses.update', $expense) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="d-flex flex-column gap-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Transaksi</label>
                                    <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', \Carbon\Carbon::parse($expense->expense_date)->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kategori Biaya</label>
                                    <input type="text" name="category" list="expenseCategories" class="form-control" value="{{ old('category', $expense->category) }}" required>
                                    <datalist id="expenseCategories">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Keterangan Pengeluaran</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description', $expense->description) }}" required>
                            </div>

                            <div>
                                <label class="form-label">Nominal Biaya (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Catatan Tambahan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Nomor nota toko atau rincian tambahan...">{{ old('notes', $expense->notes) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Perbarui Pengeluaran</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

