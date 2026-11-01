@extends('admin.admin_master')
@section('title', 'Sell - POS')
@section('admin')
<div class="content" x-data="posApp()" x-init="init()">
<div class="container-fluid">

<div class="pos-head">
    <div>
        <div class="small text-uppercase text-muted mb-1" style="letter-spacing:.1em">Checkout desk</div>
        <h1 class="mk-display">Sell</h1>
        <small class="text-muted">Scan, tap a product, or type the name · AFN</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-light">Dashboard</a>
        <button type="button" class="btn btn-outline-secondary" @click="clearCart()" :disabled="!cart.length">Clear cart</button>
    </div>
</div>

<div class="pos-shell">
    <div class="card pos-panel">
        <div class="card-body">
            <div class="pos-scan">
                <input type="text"
                       placeholder="Scan barcode or type product name, then Enter"
                       x-ref="barcode"
                       x-model="query"
                       @keydown.enter.prevent="scan()"
                       autofocus>
                <button type="button" class="btn btn-primary btn-add" @click="scan()">Add</button>
            </div>

            <div class="pos-msg">
                <span class="text-danger" x-text="error" x-show="error"></span>
                <span class="text-success" x-text="okMsg" x-show="okMsg && !error"></span>
            </div>

            <div class="pos-section-label">
                <strong>Quick pick</strong>
                <input type="text" class="form-control form-control-sm pos-filter" style="max-width:220px"
                       placeholder="Filter…" x-model="pickFilter">
            </div>

            <div class="pos-grid">
                <template x-for="p in filteredQuick" :key="p.id">
                    <button type="button" class="pos-product" :disabled="p.stock <= 0" @click="addProduct(p)">
                        <div class="name" x-text="p.name"></div>
                        <div class="meta">
                            <span class="price" x-text="Number(p.selling_price).toFixed(0) + ' AFN'"></span>
                            <span x-text="'Stock ' + p.stock"></span>
                        </div>
                    </button>
                </template>
                <div class="text-muted" style="grid-column:1/-1" x-show="!filteredQuick.length">
                    No products found. Add a product, then purchase stock.
                </div>
            </div>

            <div class="pos-section-label">
                <strong>Cart <span class="badge bg-success" x-text="cart.length" x-show="cart.length"></span></strong>
            </div>

            <div class="pos-cart-wrap">
                <div class="pos-cart-scroll table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:140px">Qty</th>
                                <th style="width:100px" class="text-end">Line</th>
                                <th style="width:44px"></th>
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
                                    <div class="pos-qty">
                                        <button type="button" @click="changeQty(index,-1)">−</button>
                                        <input type="number" x-model.number="item.quantity" @change="recalc()" min="0.001" step="1">
                                        <button type="button" @click="changeQty(index,1)">+</button>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold" x-text="lineTotal(item).toFixed(2)"></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(index)">×</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!cart.length">
                            <td colspan="4" class="text-center text-muted py-5">Cart is empty — scan or tap a product</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card pos-panel pos-pay">
        <div class="card-body">
            <div class="pos-total-box">
                <div class="lbl">Amount due</div>
                <div class="amt" x-text="grandTotal.toFixed(2) + ' AFN'"></div>
                <div class="small mt-1" style="opacity:.8" x-text="cart.length + ' item(s) in cart'"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Cash received</label>
                <input type="number" class="form-control form-control-lg" x-model.number="amount_paid" min="0" step="1">
            </div>

            <div class="pos-due-row">
                <span x-text="amount_paid >= grandTotal ? 'Change' : 'Not enough cash'"></span>
                <strong x-text="(Math.abs(amount_paid - grandTotal)).toFixed(2) + ' AFN'"></strong>
            </div>

            <button class="btn pos-finish w-100" :disabled="busy || !cart.length" @click="checkout()">
                <span x-show="!busy">Finish sale & print</span>
                <span x-show="busy">Processing…</span>
            </button>
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
        discount: 0,
        amount_paid: 0,
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
                (p.barcode || '').toLowerCase().includes(q)
            );
        },
        init() {
            this.$nextTick(() => this.$refs.barcode?.focus());
        },
        lineTotal(item) {
            return Math.max(0, (Number(item.quantity) * Number(item.unit_price)) - (Number(item.discount) || 0));
        },
        recalc() {
            this.subtotal = this.cart.reduce((s, i) => s + this.lineTotal(i), 0);
            this.grandTotal = Math.max(0, this.subtotal - (this.discount || 0));
            this.amount_paid = this.grandTotal;
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
                this.error = p.name + ' has no stock. Add quantity in Products.';
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
            this.okMsg = '';
            this.error = '';
            this.recalc();
            this.$refs.barcode.focus();
        },
        async checkout() {
            this.error = '';
            if (this.amount_paid + 0.001 < this.grandTotal) {
                this.error = 'Cash received is less than the amount due.';
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
