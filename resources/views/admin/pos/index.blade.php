@extends('admin.admin_master')
@section('title', 'POS')
@section('admin')
<div class="content" x-data="posApp()" x-init="init()">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center">
    <h4 class="m-0">Point of Sale</h4>
    <div class="text-muted">Currency: AFN · Scan barcode or search</div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-lg" placeholder="Scan barcode / search product"
                           x-ref="barcode" x-model="query" @keydown.enter.prevent="scan()" autofocus>
                    <button class="btn btn-primary" @click="scan()">Add</button>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Live search..." x-model="searchTerm" @input.debounce.300ms="liveSearch()">
                    <div class="list-group mt-2" x-show="results.length">
                        <template x-for="p in results" :key="p.id">
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between"
                                    @click="addProduct(p)">
                                <span x-text="p.name + ' (' + p.barcode + ')'"></span>
                                <span x-text="p.selling_price + ' AFN · stock ' + p.stock"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:420px;overflow:auto;">
                    <table class="table align-middle">
                        <thead><tr><th>Product</th><th width="110">Qty</th><th>Price</th><th>Disc</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                        <template x-for="(item, index) in cart" :key="item.product_id">
                            <tr>
                                <td>
                                    <div x-text="item.name"></div>
                                    <small class="text-muted" x-text="item.barcode"></small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary" @click="changeQty(index,-1)">-</button>
                                        <input type="number" class="form-control text-center" x-model.number="item.quantity" @change="recalc()" min="0.001" step="0.001">
                                        <button class="btn btn-outline-secondary" @click="changeQty(index,1)">+</button>
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm" x-model.number="item.unit_price" @change="recalc()" step="0.01"></td>
                                <td><input type="number" class="form-control form-control-sm" x-model.number="item.discount" @change="recalc()" step="0.01" min="0"></td>
                                <td x-text="lineTotal(item).toFixed(2)"></td>
                                <td><button class="btn btn-sm btn-outline-danger" @click="remove(index)">&times;</button></td>
                            </tr>
                        </template>
                        <tr x-show="!cart.length"><td colspan="6" class="text-center text-muted">Cart is empty — start scanning</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-danger small" x-text="error" x-show="error"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Customer (required for credit)</label>
                    <select class="form-select" x-model="customer_id">
                        <option value="">Walk-in customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} @if($c->phone) ({{ $c->phone }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong x-text="subtotal.toFixed(2) + ' AFN'"></strong></div>
                <div class="mb-2">
                    <label class="form-label">Overall Discount</label>
                    <input type="number" class="form-control" x-model.number="discount" @change="recalc()" min="0" step="0.01">
                </div>
                <div class="d-flex justify-content-between mb-2 fs-4"><span>Grand Total</span><strong class="text-primary" x-text="grandTotal.toFixed(2) + ' AFN'"></strong></div>
                <div class="mb-3">
                    <label class="form-label d-block">Sale Type (Cash only — AFN)</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn"
                                :class="sale_type === 'cash' ? 'btn-success' : 'btn-outline-success'"
                                @click="setSaleType('cash')">Cash Payment</button>
                        <button type="button" class="btn"
                                :class="sale_type === 'credit' ? 'btn-warning' : 'btn-outline-warning'"
                                @click="setSaleType('credit')">Credit Sale</button>
                    </div>
                    <small class="text-muted" x-show="sale_type === 'credit'">Customer required. Pay now (optional) or pay later in cash.</small>
                    <small class="text-muted" x-show="sale_type === 'cash'">Full or partial cash. Partial needs a customer for remaining debt.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label" x-text="sale_type === 'credit' ? 'Cash Paid Now (optional)' : 'Cash Received'"></label>
                    <input type="number" class="form-control form-control-lg" x-model.number="amount_paid" min="0" step="0.01">
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span x-text="amount_paid >= grandTotal ? 'Change (AFN)' : 'Remaining Debt (AFN)'"></span>
                    <strong x-text="(amount_paid >= grandTotal ? (amount_paid - grandTotal) : (grandTotal - amount_paid)).toFixed(2) + ' AFN'"></strong>
                </div>
                <button class="btn btn-success w-100 btn-lg" :disabled="busy || !cart.length" @click="checkout()">
                    <span x-show="!busy" x-text="sale_type === 'credit' ? 'Complete Credit Sale' : 'Complete Cash Sale'"></span>
                    <span x-show="busy">Processing...</span>
                </button>
                <button class="btn btn-outline-secondary w-100 mt-2" @click="clearCart()" :disabled="!cart.length">Clear Cart</button>
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
        searchTerm: '',
        results: [],
        cart: [],
        customer_id: '',
        discount: 0,
        amount_paid: 0,
        sale_type: 'cash',
        subtotal: 0,
        grandTotal: 0,
        busy: false,
        error: '',
        init() {
            this.$nextTick(() => this.$refs.barcode?.focus());
        },
        setSaleType(type) {
            this.sale_type = type;
            if (type === 'cash') {
                this.amount_paid = this.grandTotal;
            } else {
                this.amount_paid = 0;
            }
        },
        lineTotal(item) {
            return Math.max(0, (item.quantity * item.unit_price) - (item.discount || 0));
        },
        recalc() {
            this.subtotal = this.cart.reduce((s, i) => s + this.lineTotal(i), 0);
            this.grandTotal = Math.max(0, this.subtotal - (this.discount || 0));
            if (this.sale_type === 'cash') {
                this.amount_paid = this.grandTotal;
            }
        },
        async scan() {
            this.error = '';
            const code = this.query.trim();
            if (!code) return;
            try {
                const res = await fetch(`{{ route('pos.lookup') }}?barcode=${encodeURIComponent(code)}`);
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Not found');
                this.addProduct(data);
                this.query = '';
                this.$refs.barcode.focus();
            } catch (e) {
                this.error = e.message;
                this.query = '';
                this.$refs.barcode.focus();
            }
        },
        async liveSearch() {
            if (!this.searchTerm.trim()) { this.results = []; return; }
            const res = await fetch(`{{ route('pos.search') }}?q=${encodeURIComponent(this.searchTerm)}`);
            this.results = await res.json();
        },
        addProduct(p) {
            if (p.stock <= 0) { this.error = 'Out of stock'; return; }
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
            this.results = [];
            this.searchTerm = '';
            this.recalc();
            this.error = '';
            this.$refs.barcode.focus();
        },
        changeQty(index, delta) {
            const item = this.cart[index];
            const next = Number(item.quantity) + delta;
            if (next <= 0) { this.remove(index); return; }
            if (next > item.stock) { this.error = 'Not enough stock'; return; }
            item.quantity = next;
            this.recalc();
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
            this.recalc();
            this.$refs.barcode.focus();
        },
        async checkout() {
            this.error = '';
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
                        customer_id: this.customer_id || null,
                        discount: this.discount || 0,
                        amount_paid: this.amount_paid || 0,
                        payment_method: this.payment_method,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            quantity: i.quantity,
                            unit_price: i.unit_price,
                            discount: i.discount || 0,
                        })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Checkout failed');
                this.clearCart();
                toastr.success('Sale ' + data.invoice_number + ' completed');
                window.open(data.print_url, '_blank');
            } catch (e) {
                this.error = e.message;
                toastr.error(e.message);
            } finally {
                this.busy = false;
                this.$refs.barcode.focus();
            }
        }
    }
}
</script>
@endpush
