@extends('admin.admin_master')
@section('title','New Purchase')
@section('admin')
<div class="content">
<div class="container-fluid">
<div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="m-0">Buy Stock from Supplier</h4>
        <small class="text-muted">Select product → enter cartons → Add to list → Save</small>
    </div>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">Purchase List</a>
</div>

<form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
    @csrf

<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-4">
        <label class="form-label">Supplier <span class="text-danger">*</span></label>
        <select name="supplier_id" class="form-select" required>
            <option value="">Select supplier</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Invoice #</label>
        <input name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
    </div>
</div></div>

<div class="card mb-3 border-primary"><div class="card-body">
    <h5 class="mb-3">Add product</h5>
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Product</label>
            <select id="product_id" class="form-select">
                <option value="">Select product</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}"
                        data-name="{{ $p->name }}"
                        data-sell="{{ $p->selling_price }}">
                        {{ $p->name }} @if($p->barcode) ({{ $p->barcode }}) @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Or type name / barcode</label>
            <div class="input-group">
                <input type="text" id="scan_code" class="form-control" placeholder="Hit / barcode">
                <button type="button" class="btn btn-primary" id="btn-find">Find</button>
            </div>
        </div>
    </div>

    <div class="row g-2 mt-3">
        <div class="col-md-2">
            <label class="form-label">Cartons</label>
            <input type="number" id="cartons" class="form-control" value="10" min="1" step="1">
        </div>
        <div class="col-md-2">
            <label class="form-label">Cans / carton</label>
            <input type="number" id="per_carton" class="form-control" value="30" min="1" step="1">
        </div>
        <div class="col-md-2">
            <label class="form-label">Price / carton</label>
            <input type="number" id="carton_price" class="form-control" value="1000" min="0" step="0.01">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sell / can</label>
            <input type="number" id="sell_price" class="form-control" value="50" min="0" step="0.01">
        </div>
        <div class="col-md-2">
            <label class="form-label">Expiry</label>
            <input type="date" id="expiry" class="form-control">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-success w-100" id="btn-add">Add to list</button>
        </div>
    </div>
    <div class="mt-2 text-muted" id="calc-preview">Total qty: 300 · Cost/can: 33.33 AFN</div>
    <div class="text-danger mt-1" id="form-error"></div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h5>Purchase list</h5>
    <div class="table-responsive">
        <table class="table align-middle" id="lines-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Pack</th>
                    <th>Qty</th>
                    <th>Cost/unit</th>
                    <th>Sell</th>
                    <th>Line total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="lines-body">
                <tr id="empty-row"><td colspan="7" class="text-center text-muted">No items yet</td></tr>
            </tbody>
        </table>
    </div>
    <div class="text-end fs-5 d-none" id="total-wrap">
        Purchase total: <strong id="purchase-total">0.00 AFN</strong>
    </div>
    <div id="items-inputs"></div>
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
        <button type="submit" class="btn btn-primary btn-lg" id="btn-save">Save Purchase & Add Stock</button>
    </div>
</div></div>
</form>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
    const lines = [];
    const body = document.getElementById('lines-body');
    const emptyRow = document.getElementById('empty-row');
    const inputs = document.getElementById('items-inputs');
    const err = document.getElementById('form-error');
    const productSelect = document.getElementById('product_id');
    const totalWrap = document.getElementById('total-wrap');
    const purchaseTotal = document.getElementById('purchase-total');

    function money(n) { return Number(n).toFixed(2) + ' AFN'; }

    function recalcPreview() {
        const cartons = Number(document.getElementById('cartons').value) || 0;
        const per = Number(document.getElementById('per_carton').value) || 0;
        const price = Number(document.getElementById('carton_price').value) || 0;
        const qty = cartons * per;
        const cost = per > 0 ? (price / per) : 0;
        document.getElementById('calc-preview').textContent =
            'Total qty: ' + qty + ' · Cost/can: ' + cost.toFixed(2) + ' AFN · Line: ' + (cartons * price).toFixed(2) + ' AFN';
    }

    ['cartons', 'per_carton', 'carton_price'].forEach(id => {
        document.getElementById(id).addEventListener('input', recalcPreview);
    });
    recalcPreview();

    productSelect.addEventListener('change', function () {
        const opt = productSelect.options[productSelect.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('sell_price').value = opt.dataset.sell || 50;
        }
    });

    document.getElementById('btn-find').addEventListener('click', findProduct);
    document.getElementById('scan_code').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); findProduct(); }
    });

    async function findProduct() {
        err.textContent = '';
        const code = document.getElementById('scan_code').value.trim();
        if (!code) { err.textContent = 'Type a product name or barcode'; return; }
        try {
            const res = await fetch(@json(route('purchases.lookup')) + '?barcode=' + encodeURIComponent(code));
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Not found');
            productSelect.value = String(data.id);
            document.getElementById('sell_price').value = data.selling_price || 50;
            document.getElementById('scan_code').value = '';
        } catch (e) {
            err.textContent = e.message;
        }
    }

    function render() {
        body.querySelectorAll('tr.line-row').forEach(r => r.remove());
        inputs.innerHTML = '';
        emptyRow.style.display = lines.length ? 'none' : '';
        totalWrap.classList.toggle('d-none', !lines.length);

        let total = 0;
        lines.forEach((line, i) => {
            total += line.cartons * line.cartonPrice;
            const tr = document.createElement('tr');
            tr.className = 'line-row';
            tr.innerHTML =
                '<td>' + line.name + '</td>' +
                '<td>' + line.cartons + ' carton × ' + line.perCarton + '</td>' +
                '<td>' + line.qty + '</td>' +
                '<td>' + money(line.cost) + '</td>' +
                '<td>' + money(line.sellPrice) + '</td>' +
                '<td>' + money(line.cartons * line.cartonPrice) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger" data-i="' + i + '">Remove</button></td>';
            body.appendChild(tr);

            const fields = {
                product_id: line.id,
                quantity: line.qty,
                unit_cost: line.cost,
                selling_price: line.sellPrice,
                cartons: line.cartons,
                units_per_carton: line.perCarton,
                carton_price: line.cartonPrice,
                discount: 0,
                expiration_date: line.expiry || ''
            };
            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[' + i + '][' + key + ']';
                input.value = fields[key];
                inputs.appendChild(input);
            });
        });

        purchaseTotal.textContent = money(total);
        body.querySelectorAll('button[data-i]').forEach(btn => {
            btn.addEventListener('click', () => {
                lines.splice(Number(btn.dataset.i), 1);
                render();
            });
        });
    }

    document.getElementById('btn-add').addEventListener('click', function () {
        err.textContent = '';
        const opt = productSelect.options[productSelect.selectedIndex];
        if (!opt || !opt.value) {
            err.textContent = 'Select a product first';
            return;
        }
        const cartons = Number(document.getElementById('cartons').value) || 0;
        const perCarton = Number(document.getElementById('per_carton').value) || 0;
        const cartonPrice = Number(document.getElementById('carton_price').value) || 0;
        const sellPrice = Number(document.getElementById('sell_price').value) || 0;
        if (cartons <= 0 || perCarton <= 0) {
            err.textContent = 'Enter cartons and cans per carton';
            return;
        }
        lines.push({
            id: Number(opt.value),
            name: opt.dataset.name || opt.textContent.trim(),
            cartons: cartons,
            perCarton: perCarton,
            cartonPrice: cartonPrice,
            qty: cartons * perCarton,
            cost: Number((cartonPrice / perCarton).toFixed(4)),
            sellPrice: sellPrice,
            expiry: document.getElementById('expiry').value || ''
        });
        render();
    });

    document.getElementById('purchase-form').addEventListener('submit', function (e) {
        if (!lines.length) {
            e.preventDefault();
            err.textContent = 'Add at least one product to the purchase list before saving';
        }
    });
})();
</script>
@endpush
