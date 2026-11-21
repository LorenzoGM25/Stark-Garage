@extends('layouts.admin')

@section('title', 'Detail Order #' . $order->order_number . ' - STARK-GARAGE')
@section('page-title', 'Detail Transaksi #' . $order->order_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Transaksi</a></li>
    <li class="breadcrumb-item active">#{{ $order->order_number }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <!-- Order Items & Invoice Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0">Rincian Item Transaksi</h5>
                        <small class="text-secondary">{{ $order->items->count() }} item barang / jasa servis</small>
                    </div>
                    <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" class="btn btn-primary btn-sm">
                        <i class="bi bi-printer"></i> Cetak Struk
                    </a>
                </div>
                <div class="card-body p-0">
                    {{-- Mobile: kartu item --}}
                    <div class="d-lg-none p-3 d-flex flex-column gap-2">
                        @foreach($order->items as $item)
                            <div class="border rounded-3 p-3" style="background: var(--sg-surface-subtle);">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div style="min-width: 0;">
                                        <div class="fw-semibold">{{ $item->product_name ?? 'Item' }}</div>
                                        @if($item->product_code)
                                            <small class="text-secondary font-monospace">{{ $item->product_code }}</small>
                                        @endif
                                    </div>
                                    <span class="fw-bold text-primary flex-shrink-0">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-secondary">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                                    <small class="text-secondary">Subtotal</small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop: tabel item --}}
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item / Layanan</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->product_name ?? 'Item' }}</div>
                                            @if($item->product_code)
                                                <small class="text-secondary font-monospace">{{ $item->product_code }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center fw-medium">{{ $item->quantity }}</td>
                                        <td class="text-end text-secondary small">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot style="background: var(--sg-surface-subtle);">
                                <tr>
                                    <td colspan="3" class="text-end fw-semibold">Subtotal:</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($order->subtotal ?? $order->items->sum('subtotal'), 0, ',', '.') }}</td>
                                </tr>
                                @if($order->discount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end text-danger fw-semibold">Diskon:</td>
                                        <td class="text-end text-danger fw-bold">-Rp {{ number_format($order->discount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if($order->tax_amount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end text-secondary fw-semibold">PPN / Pajak:</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                <tr class="fs-6">
                                    <td colspan="3" class="text-end fw-bold text-primary">Total Pembayaran:</td>
                                    <td class="text-end fw-bold text-primary">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Meta & Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Informasi Pembayaran</h6>
                </div>
                @php
                    $statusClass = match(strtolower($order->status?->value ?? '')) {
                        'completed' => 'badge-success-soft',
                        'voided' => 'badge-danger-soft',
                        'refunded' => 'badge-warning-soft',
                        default => 'badge-neutral-soft'
                    };
                @endphp
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Status:</span>
                        <span class="badge-pill-soft {{ $statusClass }}">{{ $order->status?->label() ?? 'Selesai' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Metode:</span>
                        <span class="fw-semibold">{{ $order->payment_method?->label() ?? 'Tunai' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Kasir:</span>
                        <span class="fw-semibold">{{ $order->cashier?->name ?? 'Kasir Bengkel' }}</span>
                    </div>
                    @if($order->employee)
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary small">Mekanik:</span>
                            <span class="fw-semibold">{{ $order->employee->name }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Waktu Transaksi:</span>
                        <span class="small">{{ $order->created_at->translatedFormat('d F Y, H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Pelanggan:</span>
                        <span class="fw-medium">{{ $order->customer_name ?? 'Pelanggan Umum' }}</span>
                    </div>
                    @if($order->vehicle_plate)
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary small">Plat Kendaraan:</span>
                            <span class="badge-pill-soft badge-neutral-soft font-monospace">{{ $order->vehicle_plate }}</span>
                        </div>
                    @endif
                    @if($order->voided_at)
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary small">Dibatalkan:</span>
                            <span class="small text-danger">{{ $order->voided_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        @if($order->voidedBy)
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary small">Oleh:</span>
                                <span class="small">{{ $order->voidedBy->name }}</span>
                            </div>
                        @endif
                    @endif
                    @if($order->notes)
                        <div class="pt-2 border-top">
                            <span class="text-secondary small d-block">Catatan:</span>
                            <span class="small">{{ $order->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($order->status?->value === 'completed' && in_array(auth()->user()?->role, [\App\Enums\Role::Owner, \App\Enums\Role::Manager], true))
                <div class="card border-danger-subtle">
                    <div class="card-header bg-danger-subtle text-danger">
                        <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle"></i> Pembatalan (Void)</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-secondary mb-3">Hanya Manager / Owner yang dapat membatalkan transaksi ini. Stok barang akan otomatis dikembalikan ke gudang.</p>
                        <form action="{{ route('admin.orders.void', $order) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini?');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small">Alasan Pembatalan</label>
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Contoh: Kesalahan input kasir" required>
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-x-circle"></i> Batalkan Transaksi (Void)
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

