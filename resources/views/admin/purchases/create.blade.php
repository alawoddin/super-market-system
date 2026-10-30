@extends('admin.admin_master')
@section('title','New Purchase')
@section('admin')
<div class="content" x-data="purchaseForm()">
<div class="container-fluid">
<div class="py-3">
    <h4 class="m-0">Buy Stock from Supplier</h4>
    <small class="text-muted">Scan product → enter cartons & cans → click <strong>Add to purchase list</strong> → then Save</small>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <strong>Purchase was not saved:</strong>
    <ul class="mb-0 mt-1">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="alert alert-info py-2">
    <strong>Example:</strong> 10 cartons × 30 cans · carton price 1000 AFN
    → stock = <strong>300 cans</strong> · cost/can = <strong>1000 ÷ 30 = 33.33 AFN</strong>
    · if sell price = 50 → profit/can ≈ <strong>16.67 AFN</strong>
</div>

<form method="POST" action="{{ route('purchases.store') }}" @submit="prepare">@csrf
<input type="hidden" name="items_json" x-ref="itemsJson" value="">

<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-4">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Select supplier</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Invoice #</label>
        <input name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number', $invoice) }}">
        @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
    </div>
</div></div>

<div class="card mb-3 border-primary"><div class="card-body">
    <h5 class="mb-3">1. Find product (scan barcode or type name)</h5>
    <div class="input-group input-group-lg mb-3">
        <input type="text" class="form-control" placeholder="Scan barcode / type product name then Enter"
               x-ref="scan" x-model="scanCode" @keydown.enter.prevent="scanProduct()" autofocus>
        <button type="button" class="btn btn-primary" @click="scanProduct()">Find</button>
    </div>
    <div class="text-danger mb-2" x-text="error" x-show="error"></div>

    <div class="border rounded p-3 bg-light" x-show="draft.id" x-cloak>
        <h6 class="mb-2">2. Enter carton details</h6>
        <div class="fw-semibold mb-2" x-text="draft.name"></div>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Cartons bought</label>
                <input type="number" min="0.001" step="1" class="form-control" x-model.number="draft.cartons" @input="recalcDraft()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cans / units per carton</label>
                <input type="number" min="1" step="1" class="form-control" x-model.number="draft.perCarton" @input="recalcDraft()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Price per carton (AFN)</label>
                <input type="number" min="0" step="0.01" class="form-control" x-model.number="draft.cartonPrice" @input="recalcDraft()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sell price per can (AFN)</label>
                <input type="number" min="0" step="0.01" class="form-control" x-model.number="draft.sellPrice">
            </div>
            <div class="col-md-3">
                <label class="form-label">Expiry (optional)</label>
                <input type="date" class="form-control" x-model="draft.expiry">
            </div>
            <div class="col-md-9 d-flex align-items-end">
                <div class="me-auto">
                    <div>Total stock to add: <strong x-text="draft.totalQty + ' cans/units'"></strong></div>
                    <div>Cost per can: <strong class="text-primary" x-text="draft.unitCost.toFixed(2) + ' AFN'"></strong></div>
                    <div>Line total: <strong x-text="(draft.cartons * draft.cartonPrice).toFixed(2) + ' AFN'"></strong></div>
                    <div>Est. profit/can: <strong class="text-success" x-text="Math.max(0, draft.sellPrice - draft.unitCost).toFixed(2) + ' AFN'"></strong></div>
                </div>
                <button type="button" class="btn btn-success btn-lg" @click="addDraft()">3. Add to purchase list</button>
            </div>
        </div>
        <small class="text-muted d-block mt-2">Tip: if you buy loose cans (not cartons), set cartons = total cans, and cans per carton = 1.</small>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h5>Purchase list <span class="badge bg-primary" x-text="lines.length" x-show="lines.length"></span></h5>
    <div class="alert alert-warning py-2" x-show="!lines.length">
        Nothing to save yet. Find a product, fill carton details, then click <strong>Add to purchase list</strong>.
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Pack</th>
                    <th>Total qty</th>
                    <th>Cost/unit</th>
                    <th>Sell price</th>
                    <th>Line total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <template x-for="(line, i) in lines" :key="line._key">
                <tr>
                    <td><div class="fw-semibold" x-text="line.name"></div></td>
                    <td x-text="line.cartons + ' carton × ' + line.perCarton"></td>
                    <td x-text="line.qty"></td>
                    <td x-text="Number(line.cost).toFixed(2) + ' AFN'"></td>
                    <td x-text="Number(line.sellPrice).toFixed(2) + ' AFN'"></td>
                    <td x-text="(line.cartons * line.cartonPrice).toFixed(2) + ' AFN'"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" @click="lines.splice(i,1)">Remove</button></td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
    <div class="text-end fs-5" x-show="lines.length">
        Purchase total: <strong x-text="purchaseTotal.toFixed(2) + ' AFN'"></strong>
    </div>
</div></div>

<div class="card"><div class="card-body row g-3">
    <div class="col-md-3">
        <label class="form-label">Discount (AFN)</label>
        <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Cash paid now (AFN)</label>
        <input type="number" step="0.01" name="amount_paid" class="form-control" value="{{ old('amount_paid', 0) }}">
    </div>
    <input type="hidden" name="payment_method" value="cash">
    <div class="col-md-3">
        <label class="form-label">Payment</label>
        <input class="form-control" value="Cash (AFN)" disabled>
    </div>
    <div class="col-md-3">
        <label class="form-label">Notes</label>
        <input name="notes" class="form-control" value="{{ old('notes') }}">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary btn-lg" :disabled="!lines.length">
            4. Save Purchase & Add Stock
        </button>
        <span class="text-muted ms-2" x-show="!lines.length">Add items to the list first</span>
    </div>
</div></div>
</form>
</div></div>
@endsection

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
<script>
function purchaseForm() {
    return {
        scanCode: '',
        error: '',
        lines: [],
        draft: { id: null, name: '', cartons: 10, perCarton: 30, cartonPrice: 1000, sellPrice: 50, unitCost: 0, totalQty: 0, expiry: '' },
        get purchaseTotal() {
            return this.lines.reduce((s, l) => s + (Number(l.cartons) * Number(l.cartonPrice)), 0);
        },
        recalcDraft() {
            const cartons = Number(this.draft.cartons) || 0;
            const per = Number(this.draft.perCarton) || 0;
            const price = Number(this.draft.cartonPrice) || 0;
            this.draft.totalQty = cartons * per;
            this.draft.unitCost = per > 0 ? (price / per) : 0;
        },
        async scanProduct() {
            this.error = '';
            const code = this.scanCode.trim();
            if (!code) return;
            try {
                const res = await fetch(`{{ route('purchases.lookup') }}?barcode=${encodeURIComponent(code)}`);
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Not found');
                this.draft.id = data.id;
                this.draft.name = data.name;
                this.draft.sellPrice = Number(data.selling_price) || 50;
                this.recalcDraft();
                this.scanCode = '';
            } catch (e) {
                this.error = e.message;
                this.draft.id = null;
            }
        },
        addDraft() {
            this.recalcDraft();
            if (!this.draft.id) { this.error = 'Scan a product first'; return; }
            if (this.draft.cartons <= 0 || this.draft.perCarton <= 0 || this.draft.cartonPrice < 0) {
                this.error = 'Enter cartons, cans per carton, and carton price';
                return;
            }
            this.lines.push({
                _key: Date.now() + '-' + Math.random(),
                id: this.draft.id,
                name: this.draft.name,
                cartons: Number(this.draft.cartons),
                perCarton: Number(this.draft.perCarton),
                cartonPrice: Number(this.draft.cartonPrice),
                qty: Number(this.draft.totalQty),
                cost: Number(this.draft.unitCost.toFixed(4)),
                sellPrice: Number(this.draft.sellPrice) || 0,
                expiry: this.draft.expiry || '',
            });
            this.draft = { id: null, name: '', cartons: 10, perCarton: 30, cartonPrice: 1000, sellPrice: 50, unitCost: 0, totalQty: 0, expiry: '' };
            this.error = '';
            this.$refs.scan?.focus();
        },
        prepare(e) {
            if (!this.lines.length) {
                e.preventDefault();
                this.error = 'Add at least one product to the purchase list (click “Add to purchase list”)';
                alert(this.error);
                return;
            }
            const payload = this.lines.map(line => ({
                product_id: line.id,
                quantity: line.qty,
                unit_cost: line.cost,
                selling_price: line.sellPrice,
                cartons: line.cartons,
                units_per_carton: line.perCarton,
                carton_price: line.cartonPrice,
                discount: 0,
                expiration_date: line.expiry || null,
            }));
            this.$refs.itemsJson.value = JSON.stringify(payload);
        }
    }
}
</script>
@endpush
