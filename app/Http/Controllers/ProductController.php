<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Models\Category;
use App\Models\Product;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index()
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.index', compact('categories'));
    }

    public function datatable(Request $request)
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        $categoryId = $request->input('category_id');
        $stock = $request->input('stock');
        $status = $request->input('status');

        $base = Product::query()->with('category');
        $filtered = (clone $base)
            ->search($search !== '' ? $search : null)
            ->when($categoryId, fn ($q, $id) => $q->where('category_id', $id))
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
                3 => 'category_id',
                4 => 'purchase_price',
                5 => 'selling_price',
                6 => 'stock_quantity',
                7 => 'is_active',
            ],
            function (Product $p, int $row) {
                $stockClass = $p->is_low_stock ? 'text-danger fw-semibold' : '';

                return [
                    'DT_RowIndex' => $row,
                    'name' => e($p->name).'<div class="small text-muted">'.e($p->unit).'</div>',
                    'barcode' => e($p->barcode ?: '—'),
                    'category' => e($p->category?->name ?? '—'),
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
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $units = config('supermarket.units');

        return view('admin.products.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if (empty($data['barcode'])) {
            $data['barcode'] = $this->generateBarcode();
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['track_expiry'] = $request->boolean('track_expiry', true);

        Product::query()->create($data);

        return redirect()->route('products.index')->with([
            'message' => 'Product created successfully',
            'alert-type' => 'success',
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'stockBatches' => fn ($q) => $q->latest()]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $units = config('supermarket.units');

        return view('admin.products.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['track_expiry'] = $request->boolean('track_expiry', true);

        $product->update($data);

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
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', Rule::in(array_keys(config('supermarket.units')))],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
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
