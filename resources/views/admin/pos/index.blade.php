@extends('admin.admin_master')
@section('title', 'Sell - POS')
@section('admin')
<div class="content" x-data="posApp()" x-init="init()">
<div class="container-fluid">

<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Sell Products (POS)</h4>
        <small class="text-muted">Simple cash selling · AFN</small>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">Dashboard</a>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card border-primary">
            <div class="card-body">
                <label class="form-label fw-semibold">1) Find product (scan OR type name)</label>
                <div class="input-group input-group-lg mb-2">
                    <input type="text"
                           class="form-control form-control-lg border-primary"
                           style="font-size:1.4rem;"
                           placeholder="Type oil / product name, or scan barcode, then Enter"
                           x-ref="barcode"
                           x-model="query"
                           @keydown.enter.prevent="scan()"
                           autofocus>
                    <button class="btn btn-primary btn-lg px-4" @click="scan()">Add</button>
                </div>
                <div class="text-danger mb-2" x-text="error" x-show="error"></div>
                <div class="text-success mb-2" x-text="okMsg" x-show="okMsg"></div>

                <label class="form-label fw-semibold mt-2">Click product to sell (no scanner needed)</label>
                <input type="text" class="form-control mb-2" placeholder="Filter products..."
                       x-model="pickFilter">
                <div class="row g-2 mb-3" style="max-height:220px;overflow:auto;">
                    <template x-for="p in filteredQuick" :key="p.id">
                        <div class="col-6 col-md-4">
                            <button type="button"
                                    class="btn w-100 text-start border"
                                    :class="p.stock > 0 ? 'btn-light' : 'btn-outline-secondary'"
                                    :disabled="p.stock <= 0"
                                    @click="addProduct(p)">
                                <div class="fw-semibold text-truncate" x-text="p.name"></div>
                                <small class="text-muted" x-text="Number(p.selling_price).toFixed(0) + ' AFN · stock ' + p.stock"></small>
                            </button>
                        </div>
                    </template>
                    <div class="col-12 text-muted" x-show="!filteredQuick.length">
                        No products found. First add product, then add stock with Purchase.
                    </div>
                </div>

                <hr>
                <h5 class="mb-2">2) Cart</h5>
                <div class="table-responsive" style="max-height:380px;overflow:auto;">
                    <table class="table table-lg align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th width="130">Qty</th>
                                <th width="110">Total</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <template x-for="(item, index) in cart" :key="item.product_id">
                            <tr>
                                <td>
                                    <div class="fw-semibold" x-text="item.name"></div>
                                    <small class="text-muted" x-text="Number(item.unit_price).toFixed(2) + ' AFN each'"></small>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary" type="button" @click="changeQty(index,-1)">−</button>
                                        <input type="number" class="form-control text-center" x-model.number="item.quantity" @change="recalc()" min="0.001" step="1">
                                        <button class="btn btn-outline-secondary" type="button" @click="changeQty(index,1)">+</button>
                                    </div>
                                </td>
                                <td class="fw-semibold" x-text="lineTotal(item).toFixed(2)"></td>
                                <td><button class="btn btn-outline-danger" type="button" @click="remove(index)">×</button></td>
                            </tr>
                        </template>
                        <tr x-show="!cart.length">
                            <td colspan="4" class="text-center text-muted py-4">No items yet — scan a product</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">3) Take payment</h5>

                <div class="mb-3">
                    <label class="form-label">Customer (only for credit / debt)</label>
                    <select class="form-select form-select-lg" x-model="customer_id">
                        <option value="">Walk-in (cash customer)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}@if($c->phone) — {{ $c->phone }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                    <span class="fs-5">Total</span>
                    <strong class="fs-3 text-primary" x-text="grandTotal.toFixed(2) + ' AFN'"></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Payment type</label>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-lg"
                                :class="sale_type === 'cash' ? 'btn-success' : 'btn-outline-success'"
                                @click="setSaleType('cash')">
                            Cash Payment
                        </button>
                        <button type="button" class="btn btn-lg"
                                :class="sale_type === 'credit' ? 'btn-warning' : 'btn-outline-warning'"
                                @click="setSaleType('credit')">
                            Credit Sale (pay later)
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" x-text="sale_type === 'credit' ? 'Cash paid now (can be 0)' : 'Cash received from customer'"></label>
                    <input type="number" class="form-control form-control-lg" x-model.number="amount_paid" min="0" step="1">
                </div>

                <div class="d-flex justify-content-between mb-3 fs-5">
                    <span x-text="amount_paid >= grandTotal ? 'Change' : 'Still owing'"></span>
                    <strong x-text="(Math.abs(amount_paid - grandTotal)).toFixed(2) + ' AFN'"></strong>
                </div>

                <button class="btn btn-success w-100 btn-lg py-3" :disabled="busy || !cart.length" @click="checkout()">
                    <span x-show="!busy">Finish Sale & Print</span>
                    <span x-show="busy">Please wait...</span>
                </button>
                <button class="btn btn-outline-secondary w-100 mt-2" type="button" @click="clearCart()" :disabled="!cart.length">
                    Clear cart
                </button>
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        query: '',
        pickFilter: '',
        quickProducts: @json($quickProducts),
        cart: [],
        customer_id: '',
        discount: 0,
        amount_paid: 0,
        sale_type: 'cash',
        subtotal: 0,
        grandTotal: 0,
        busy: false,
        error: '',
        okMsg: '',
        get filteredQuick() {
            const q = (this.pickFilter || '').toLowerCase().trim();
            if (!q) return this.quickProducts;
            return this.quickProducts.filter(p =>
                (p.name || '').toLowerCase().includes(q) ||
                (p.category || '').toLowerCase().includes(q) ||
                (p.barcode || '').toLowerCase().includes(q)
            );
        },
        init() {
            this.$nextTick(() => this.$refs.barcode?.focus());
        },
        setSaleType(type) {
            this.sale_type = type;
            this.amount_paid = type === 'cash' ? this.grandTotal : 0;
            this.$refs.barcode?.focus();
        },
        lineTotal(item) {
            return Math.max(0, (Number(item.quantity) * Number(item.unit_price)) - (Number(item.discount) || 0));
        },
        recalc() {
            this.subtotal = this.cart.reduce((s, i) => s + this.lineTotal(i), 0);
            this.grandTotal = Math.max(0, this.subtotal - (this.discount || 0));
            if (this.sale_type === 'cash') this.amount_paid = this.grandTotal;
        },
        async scan() {
            this.error = '';
            this.okMsg = '';
            const code = this.query.trim();
            if (!code) return;
            try {
                const res = await fetch(`{{ route('pos.lookup') }}?barcode=${encodeURIComponent(code)}`);
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Product not found');
                this.addProduct(data);
                this.okMsg = 'Added: ' + data.name;
            } catch (e) {
                this.error = e.message;
            }
            this.query = '';
            this.$refs.barcode.focus();
        },
        addProduct(p) {
            if (p.stock <= 0) {
                this.error = p.name + ' has no stock. First buy stock in Purchases.';
                this.okMsg = '';
                return;
            }
            const existing = this.cart.find(i => i.product_id === p.id);
            if (existing) {
                if (existing.quantity + 1 > p.stock) { this.error = 'Not enough stock'; return; }
                existing.quantity += 1;
            } else {
                this.cart.push({
                    product_id: p.id,
                    name: p.name,
                    barcode: p.barcode,
                    quantity: 1,
                    unit_price: Number(p.selling_price),
                    discount: 0,
                    stock: p.stock,
                });
            }
            this.recalc();
            this.error = '';
            this.okMsg = 'Added: ' + p.name;
            this.$refs.barcode.focus();
        },
        changeQty(index, delta) {
            const item = this.cart[index];
            const next = Number(item.quantity) + delta;
            if (next <= 0) { this.remove(index); return; }
            if (next > item.stock) { this.error = 'Not enough stock'; return; }
            item.quantity = next;
            this.recalc();
            this.$refs.barcode.focus();
        },
        remove(index) {
            this.cart.splice(index, 1);
            this.recalc();
            this.$refs.barcode.focus();
        },
        clearCart() {
            this.cart = [];
            this.discount = 0;
            this.amount_paid = 0;
            this.sale_type = 'cash';
            this.okMsg = '';
            this.error = '';
            this.recalc();
            this.$refs.barcode.focus();
        },
        async checkout() {
            this.error = '';
            if (this.sale_type === 'credit' && !this.customer_id) {
                this.error = 'For credit sale, choose a customer.';
                return;
            }
            if (this.sale_type === 'cash' && this.amount_paid + 0.001 < this.grandTotal && !this.customer_id) {
                this.error = 'Partial cash needs a customer for remaining debt.';
                return;
            }
            this.busy = true;
            try {
                const res = await fetch('{{ route('pos.checkout') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        sale_type: this.sale_type,
                        customer_id: this.customer_id || null,
                        discount: 0,
                        amount_paid: this.amount_paid || 0,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            quantity: i.quantity,
                            unit_price: i.unit_price,
                            discount: 0,
                        })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Sale failed');
                this.clearCart();
                this.okMsg = 'Sale done: ' + data.invoice_number;
                window.open(data.print_url, '_blank');
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
                this.$refs.barcode.focus();
            }
        }
    }
}
</script>
@endpush
