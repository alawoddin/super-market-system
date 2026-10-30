<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index');
    }

    public function datatable(Request $request)
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        $status = $request->input('status');

        $base = Category::query()->withCount('products');
        $filtered = (clone $base)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (int) $status));

        return DataTable::response(
            $request,
            $base,
            $filtered,
            [0 => 'id', 1 => 'name', 2 => 'products_count', 3 => 'is_active'],
            function (Category $c, int $row) {
                return [
                    'DT_RowIndex' => $row,
                    'name' => e($c->name).($c->description ? '<div class="text-muted small">'.e(Str::limit($c->description, 60)).'</div>' : ''),
                    'products' => (int) $c->products_count,
                    'status' => '<span class="badge bg-'.($c->is_active ? 'success' : 'secondary').'">'.($c->is_active ? 'Active' : 'Inactive').'</span>',
                    'actions' => '<div class="text-nowrap text-end">'
                        .'<a href="'.route('categories.edit', $c).'" class="btn btn-sm btn-outline-primary me-1">Edit</a>'
                        .'<form action="'.route('categories.destroy', $c).'" method="POST" class="d-inline" onsubmit="return confirm(\'Delete category?\')">'
                        .csrf_field().method_field('DELETE')
                        .'<button class="btn btn-sm btn-outline-danger">Delete</button></form></div>',
                ];
            },
            'name'
        );
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with([
            'message' => 'Category created successfully',
            'alert-type' => 'success',
        ]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with([
            'message' => 'Category updated successfully',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with([
                'message' => 'Cannot delete category with products. Reassign products first.',
                'alert-type' => 'error',
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with([
            'message' => 'Category deleted successfully',
            'alert-type' => 'success',
        ]);
    }
}
