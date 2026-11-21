@extends('layouts.admin')

@section('title', 'Kasir / POS - STARK-GARAGE')
@section('page-title', 'Kasir / POS')
@section('breadcrumb')
    <li class="breadcrumb-item active">Kasir / POS</li>
@endsection

@push('styles')
    <style>
        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 0.75rem;
        }
        .pos-product-card {
            border: 1px solid var(--sg-border);
            border-radius: 0.85rem;
            background: var(--sg-surface);
            overflow: hidden;
            padding: 0;
            text-align: left;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
        }
        .pos-product-card:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, 0.12);
        }
        .pos-product-card:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .pos-product-image {
            width: 100%;
            height: 92px;
            object-fit: cover;
            background: var(--sg-surface-subtle);
        }
        .pos-product-placeholder {
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--sg-surface-subtle);
            color: var(--sg-primary);
            font-size: 1.8rem;
        }
        .pos-cart {
            position: sticky;
            top: 1rem;
        }
        .pos-cart-items {
            max-height: 320px;
            overflow-y: auto;
        }
        @media (max-width: 1199.98px) {
            .pos-cart {
                position: static;
            }
        }
    </style>
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-0">Pilih Produk / Jasa</h5>
                        <small class="text-secondary">Klik item untuk menambahkan ke keranjang. Tekan Enter pada pencarian untuk item pertama.</small>
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="posSearch" class="form-control" placeholder="Cari nama / kode / scan barcode..." autofocus>
                    </div>
                </div>

                <div class="px-3 pt-3">
                    <div id="categoryPills" class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-primary" data-category="">Semua</button>
                        @foreach($categories as $category)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-category="{{ $category }}">{{ $category }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="card-body">
                    <div id="productGrid" class="pos-product-grid"></div>
                    <div id="productEmpty" class="text-center py-5 text-secondary d-none">
                        <i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>
                        Tidak ada produk atau jasa yang cocok.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card pos-cart">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0">Keranjang Transaksi</h5>
                        <small class="text-secondary" id="cartCount">0 item</small>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm text-danger" id="clearCart" title="Kosongkan keranjang">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <div class="card-body">
                    <div id="cartEmpty" class="text-center py-4 text-secondary">
                        <i class="bi bi-cart3 fs-2 d-block mb-2 text-muted"></i>
                        Keranjang masih kosong.
                    </div>
                    <div id="cartItems" class="pos-cart-items d-flex flex-column gap-2"></div>

                    <hr class="my-3">

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Nama Pelanggan</label>
                            <input type="text" id="customerName" class="form-control form-control-sm" placeholder="Pelanggan Umum">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">No. HP</label>
                            <input type="text" id="customerPhone" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Plat Kendaraan</label>
                            <input type="text" id="vehiclePlate" class="form-control form-control-sm font-monospace" placeholder="B 1234 XYZ">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Mekanik</label>
                            <select id="employeeId" class="form-select form-select-sm">
                                <option value="">Tanpa mekanik</option>
                                @foreach($mechanics as $mechanic)
                                    <option value="{{ $mechanic->id }}">{{ $mechanic->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Catatan</label>
                            <input type="text" id="notes" class="form-control form-control-sm" placeholder="Catatan tambahan (opsional)">
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="background: var(--sg-surface-subtle);">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-secondary">Subtotal</span>
                        <span class="fw-semibold" id="subtotalText">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-secondary">Diskon</span>
                        <input type="number" id="discountInput" class="form-control form-control-sm text-end" style="max-width: 130px;" min="0" value="0">
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-secondary">Pajak ({{ rtrim(rtrim(number_format($taxPercent, 2, ',', '.'), '0'), ',') }}%)</span>
                        <span class="fw-semibold" id="taxText">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-2">
                        <span>Total</span>
                        <span class="text-primary" id="totalText">Rp 0</span>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Metode Bayar</label>
                            <select id="paymentMethod" class="form-select form-select-sm">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Dibayar</label>
                            <input type="number" id="paidInput" class="form-control form-control-sm text-end" min="0" value="0">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small mb-3">
                        <span class="text-secondary">Kembalian</span>
                        <span class="fw-semibold text-success" id="changeText">Rp 0</span>
                    </div>

                    <div id="posError" class="alert alert-danger py-2 px-3 small d-none"></div>

                    <button type="button" id="checkoutButton" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-cash-coin"></i> Proses Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="posSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--sg-surface); border: 1px solid var(--sg-border);">
                <div class="modal-body text-center py-4">
                    <div class="text-success mb-3"><i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i></div>
                    <h4 class="fw-bold mb-1">Transaksi Berhasil</h4>
                    <p class="text-secondary mb-3">Pembayaran sudah tercatat di sistem.</p>
                    <div class="p-3 rounded-3 mb-3" style="background: var(--sg-surface-subtle);">
                        <div class="small text-secondary">No. Order</div>
                        <div class="fw-bold fs-5" id="successOrderNumber">-</div>
                        <div class="small text-secondary mt-2">Total</div>
                        <div class="fw-bold text-primary fs-4" id="successTotal">Rp 0</div>
                        <div class="small text-secondary mt-2">Kembalian</div>
                        <div class="fw-semibold text-success" id="successChange">Rp 0</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="#" id="successReceipt" target="_blank" class="btn btn-secondary flex-grow-1">
                            <i class="bi bi-printer"></i> Cetak Nota
                        </a>
                        <button type="button" class="btn btn-primary flex-grow-1" data-bs-dismiss="modal" id="newTransaction">
                            <i class="bi bi-plus-lg"></i> Transaksi Baru
                        </button>
                    </div>
                    <a href="#" id="successDetail" class="btn btn-link btn-sm mt-2">Lihat detail transaksi</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const products = @json($products);
            const taxPercent = Number(@json($taxPercent));
            const checkoutUrl = @json(route('admin.pos.checkout'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const state = {
                cart: [],
                category: '',
                search: '',
            };

            const productGrid = document.getElementById('productGrid');
            const productEmpty = document.getElementById('productEmpty');
            const cartItems = document.getElementById('cartItems');
            const cartEmpty = document.getElementById('cartEmpty');
            const cartCount = document.getElementById('cartCount');
            const subtotalText = document.getElementById('subtotalText');
            const taxText = document.getElementById('taxText');
            const totalText = document.getElementById('totalText');
            const changeText = document.getElementById('changeText');
            const discountInput = document.getElementById('discountInput');
            const paidInput = document.getElementById('paidInput');
            const paymentMethod = document.getElementById('paymentMethod');
            const posError = document.getElementById('posError');
            const checkoutButton = document.getElementById('checkoutButton');
            const searchInput = document.getElementById('posSearch');

            const rupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(value) || 0));
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            function filteredProducts() {
                const term = state.search.toLowerCase();

                return products.filter((product) => {
                    const matchCategory = !state.category || product.category === state.category;
                    const matchTerm = !term
                        || String(product.name).toLowerCase().includes(term)
                        || String(product.code ?? '').toLowerCase().includes(term)
                        || String(product.category ?? '').toLowerCase().includes(term);

                    return matchCategory && matchTerm;
                });
            }

            function renderProducts() {
                const list = filteredProducts();
                productEmpty.classList.toggle('d-none', list.length > 0);

                productGrid.innerHTML = list.map((product) => {
                    const outOfStock = !product.is_service && Number(product.stock ?? 0) <= 0;
                    const stockLabel = product.is_service
                        ? 'Jasa'
                        : (outOfStock ? 'Habis' : 'Stok ' + product.stock);

                    const image = product.image_url
                        ? `<img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}" class="pos-product-image">`
                        : `<div class="pos-product-placeholder"><i class="bi ${product.is_service ? 'bi-wrench-adjustable' : 'bi-box-seam'}"></i></div>`;

                    return `
                        <button type="button" class="pos-product-card" data-add-product="${product.id}" ${outOfStock ? 'disabled' : ''}>
                            ${image}
                            <div class="p-2">
                                <div class="fw-semibold small text-truncate" title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</div>
                                <div class="small text-secondary font-monospace text-truncate">${escapeHtml(product.code ?? '-')}</div>
                                <div class="d-flex align-items-center justify-content-between mt-1 gap-1">
                                    <span class="fw-bold text-primary small">${rupiah(product.price)}</span>
                                    <span class="badge-pill-soft ${outOfStock ? 'badge-danger-soft' : (product.is_service ? 'badge-info-soft' : 'badge-neutral-soft')}" style="font-size: 0.65rem;">${stockLabel}</span>
                                </div>
                            </div>
                        </button>
                    `;
                }).join('');
            }

            function addToCart(productId) {
                const product = products.find((item) => item.id === productId);
                if (!product) return;

                const existing = state.cart.find((item) => item.id === productId);
                const nextQty = (existing?.qty ?? 0) + 1;

                if (!product.is_service && nextQty > Number(product.stock ?? 0)) {
                    showError(`Stok ${product.name} hanya ${product.stock ?? 0}.`);
                    return;
                }

                if (existing) {
                    existing.qty = nextQty;
                } else {
                    state.cart.push({ ...product, qty: 1 });
                }

                clearError();
                renderCart();
            }

            function changeQty(productId, delta) {
                const item = state.cart.find((entry) => entry.id === productId);
                if (!item) return;

                const nextQty = item.qty + delta;
                if (nextQty <= 0) {
                    state.cart = state.cart.filter((entry) => entry.id !== productId);
                } else if (!item.is_service && nextQty > Number(item.stock ?? 0)) {
                    showError(`Stok ${item.name} hanya ${item.stock ?? 0}.`);
                    return;
                } else {
                    item.qty = nextQty;
                }

                clearError();
                renderCart();
            }

            function renderCart() {
                cartCount.textContent = state.cart.reduce((total, item) => total + item.qty, 0) + ' item';
                cartEmpty.classList.toggle('d-none', state.cart.length > 0);

                cartItems.innerHTML = state.cart.map((item) => `
                    <div class="d-flex align-items-start gap-2 border-bottom pb-2">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold small text-truncate">${escapeHtml(item.name)}</div>
                            <small class="text-secondary">${rupiah(item.price)} / item</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2" data-qty="-1" data-id="${item.id}">−</button>
                            <span class="fw-semibold small" style="min-width: 20px; text-align: center;">${item.qty}</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2" data-qty="1" data-id="${item.id}">+</button>
                        </div>
                        <div class="text-end" style="min-width: 82px;">
                            <div class="fw-bold small">${rupiah(item.price * item.qty)}</div>
                            <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove="${item.id}">Hapus</button>
                        </div>
                    </div>
                `).join('');

                updateTotals();
            }

            function totals() {
                const subtotal = state.cart.reduce((total, item) => total + (Number(item.price) * item.qty), 0);
                const discount = Math.min(Math.max(Number(discountInput.value) || 0, 0), subtotal);
                const taxable = subtotal - discount;
                const tax = taxable * taxPercent / 100;
                const total = taxable + tax;

                return { subtotal, discount, tax, total };
            }

            function updateTotals() {
                const { subtotal, tax, total } = totals();
                subtotalText.textContent = rupiah(subtotal);
                taxText.textContent = rupiah(tax);
                totalText.textContent = rupiah(total);

                const currentPaid = Number(paidInput.value) || 0;
                if (currentPaid < total) {
                    paidInput.value = Math.round(total);
                }

                const paid = Number(paidInput.value) || 0;
                changeText.textContent = rupiah(Math.max(0, paid - total));
            }

            function showError(message) {
                posError.textContent = message;
                posError.classList.remove('d-none');
            }

            function clearError() {
                posError.classList.add('d-none');
                posError.textContent = '';
            }

            function setBusy(isBusy) {
                checkoutButton.disabled = isBusy;
                checkoutButton.innerHTML = isBusy
                    ? '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...'
                    : '<i class="bi bi-cash-coin"></i> Proses Pembayaran';
            }

            async function checkout() {
                clearError();

                if (state.cart.length === 0) {
                    showError('Keranjang masih kosong.');
                    return;
                }

                const { subtotal, discount, total } = totals();
                if (subtotal <= 0) {
                    showError('Total transaksi tidak valid.');
                    return;
                }

                const paid = Number(paidInput.value) || 0;
                if (paymentMethod.value === 'cash' && paid < total) {
                    showError('Jumlah pembayaran kurang dari total tagihan.');
                    return;
                }

                const payload = {
                    items: state.cart.map((item) => ({ product_id: item.id, quantity: item.qty })),
                    payment_method: paymentMethod.value,
                    paid_amount: paid,
                    discount: discount,
                    customer_name: document.getElementById('customerName').value || null,
                    customer_phone: document.getElementById('customerPhone').value || null,
                    vehicle_plate: document.getElementById('vehiclePlate').value || null,
                    employee_id: document.getElementById('employeeId').value || null,
                    notes: document.getElementById('notes').value || null,
                };

                setBusy(true);

                try {
                    const response = await fetch(checkoutUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        throw new Error(errors || data.message || 'Transaksi gagal diproses.');
                    }

                    document.getElementById('successOrderNumber').textContent = '#' + data.order.order_number;
                    document.getElementById('successTotal').textContent = rupiah(data.order.final_amount);
                    document.getElementById('successChange').textContent = rupiah(data.order.change_amount);
                    document.getElementById('successReceipt').href = data.receipt_url;
                    document.getElementById('successDetail').href = data.detail_url;

                    state.cart = [];
                    document.getElementById('customerName').value = '';
                    document.getElementById('customerPhone').value = '';
                    document.getElementById('vehiclePlate').value = '';
                    document.getElementById('employeeId').value = '';
                    document.getElementById('notes').value = '';
                    discountInput.value = 0;
                    paidInput.value = 0;
                    paymentMethod.value = 'cash';
                    renderCart();

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('posSuccessModal')).show();
                } catch (error) {
                    showError(error.message || 'Transaksi gagal diproses.');
                } finally {
                    setBusy(false);
                }
            }

            productGrid.addEventListener('click', (event) => {
                const button = event.target.closest('[data-add-product]');
                if (button) addToCart(Number(button.dataset.addProduct));
            });

            cartItems.addEventListener('click', (event) => {
                const qtyButton = event.target.closest('[data-qty]');
                if (qtyButton) {
                    changeQty(Number(qtyButton.dataset.id), Number(qtyButton.dataset.qty));
                    return;
                }

                const removeButton = event.target.closest('[data-remove]');
                if (removeButton) {
                    state.cart = state.cart.filter((item) => item.id !== Number(removeButton.dataset.remove));
                    renderCart();
                }
            });

            document.getElementById('categoryPills').addEventListener('click', (event) => {
                const button = event.target.closest('[data-category]');
                if (!button) return;

                state.category = button.dataset.category;
                document.querySelectorAll('#categoryPills [data-category]').forEach((pill) => {
                    pill.classList.toggle('btn-primary', pill === button);
                    pill.classList.toggle('btn-outline-secondary', pill !== button);
                });
                renderProducts();
            });

            searchInput.addEventListener('input', () => {
                state.search = searchInput.value.trim();
                renderProducts();
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const first = filteredProducts()[0];
                if (first) addToCart(first.id);
            });

            discountInput.addEventListener('input', updateTotals);
            paidInput.addEventListener('input', updateTotals);
            checkoutButton.addEventListener('click', checkout);

            document.getElementById('clearCart').addEventListener('click', () => {
                state.cart = [];
                clearError();
                renderCart();
            });

            document.getElementById('newTransaction').addEventListener('click', () => {
                searchInput.focus();
            });

            renderProducts();
            renderCart();
        });
    </script>
@endpush
