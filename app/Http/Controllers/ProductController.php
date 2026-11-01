<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index()
    {
        return view('admin.products.index');
    }

    public function datatable(Request $request)
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        $stock = $request->input('stock');
        $status = $request->input('status');

        $base = Product::query();
        $filtered = (clone $base)
            ->search($search !== '' ? $search : null)
            ->when($stock === 'low', fn ($q) => $q->lowStock())
            ->when($stock === 'out', fn ($q) => $q->where('stock_quantity', '<=', 0))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (int) $status));

        return DataTable::response(
            $request,
            $base,
            $filtered,
            [
                0 => 'id',
                1 => 'name',
                2 => 'barcode',
                3 => 'purchase_price',
                4 => 'selling_price',
                5 => 'stock_quantity',
                6 => 'is_active',
            ],
            function (Product $p, int $row) {
                $stockClass = $p->is_low_stock ? 'text-danger fw-semibold' : '';

                return [
                    'DT_RowIndex' => $row,
                    'name' => e($p->name),
                    'barcode' => e($p->barcode ?: '—'),
                    'buy' => money($p->purchase_price),
                    'sell' => money($p->selling_price),
                    'stock' => '<span class="'.$stockClass.'">'.e(qty_format($p->stock_quantity)).'</span>',
                    'status' => '<span class="badge bg-'.($p->is_active ? 'success' : 'secondary').'">'.($p->is_active ? 'Active' : 'Off').'</span>',
                    'actions' => '<div class="text-nowrap text-end">'
                        .'<a href="'.route('products.show', $p).'" class="btn btn-sm btn-outline-secondary me-1">View</a>'
                        .'<a href="'.route('products.edit', $p).'" class="btn btn-sm btn-outline-primary">Edit</a></div>',
                ];
            },
            'name'
        );
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $quantity = (float) $data['quantity'];
        unset($data['quantity']);

        if (empty($data['barcode'])) {
            $data['barcode'] = $this->generateBarcode();
        }

        $data['is_active'] = true;

        DB::transaction(function () use ($data, $quantity) {
            $product = Product::query()->create($data);

            if ($quantity > 0) {
                $this->inventory->createBatch([
                    'product_id' => $product->id,
                    'quantity_received' => $quantity,
                    'purchase_price' => $product->purchase_price,
                    'batch_number' => 'OPEN-'.now()->format('YmdHis'),
                    'notes' => 'Opening stock',
                ]);
            }
        });

        return redirect()->route('products.create')->with([
            'message' => 'Product saved. Add the next one.',
            'alert-type' => 'success',
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['stockBatches' => fn ($q) => $q->latest()]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $quantity = (float) $data['quantity'];
        unset($data['quantity']);

        if (empty($data['barcode'])) {
            $data['barcode'] = $product->barcode;
        }

        $data['is_active'] = $request->boolean('is_active');

        try {
            DB::transaction(function () use ($product, $data, $quantity) {
                $product->update($data);
                $this->inventory->adjust($product, $quantity, 'Changed from product form');
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return redirect()->route('products.index')->with([
            'message' => 'Product updated successfully',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Product $product)
    {
        if ($product->stock_quantity > 0) {
            return back()->with([
                'message' => 'Cannot delete product with remaining stock.',
                'alert-type' => 'error',
            ]);
        }

        $product->delete();

        return redirect()->route('products.index')->with([
            'message' => 'Product deleted successfully',
            'alert-type' => 'success',
        ]);
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'products-'.now()->format('Ymd').'.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
        Excel::import(new ProductsImport, $request->file('file'));

        return back()->with([
            'message' => 'Products imported successfully',
            'alert-type' => 'success',
        ]);
    }

    public function generateBarcodeAjax()
    {
        return response()->json(['barcode' => $this->generateBarcode()]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function generateBarcode(): string
    {
        do {
            $barcode = '2'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Product::withTrashed()->where('barcode', $barcode)->exists());

        return $barcode;
    }
}
