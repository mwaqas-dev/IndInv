<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'inventories.location'])->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('code', 'like', "%$s%")->orWhere('name', 'like', "%$s%")))->when($request->category, fn ($q, $c) => $q->where('category_id', $c))->when($request->status, fn ($q, $s) => $q->where('status', $s))->orderBy($request->get('sort', 'code'), $request->get('direction', 'asc'))->paginate(15)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product, 'categories' => Category::orderBy('name')->get(), 'locations' => Location::where('is_active', true)->get()]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $product = Product::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $audit->record('CREATE', 'Inventory', "Created product {$product->code}", $product, [], $product->only(['code', 'name', 'status']));

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'inventories.location']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get(), 'locations' => Location::where('is_active', true)->get()]);
    }

    public function update(Request $request, Product $product, AuditService $audit)
    {
        $old = $product->only(['code', 'name', 'category_id', 'minimum_stock', 'status']);
        $product->update($this->validated($request) + ['updated_by' => $request->user()->id]);
        $audit->record('UPDATE', 'Inventory', "Updated product {$product->code}", $product, $old, $product->only(array_keys($old)));

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product, AuditService $audit)
    {
        $audit->record('DELETE', 'Inventory', "Archived product {$product->code}", $product, $product->toArray());
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product archived.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['code' => ['required', 'max:50', 'unique:products,code,'.($request->route('product')?->id ?? 'NULL')], 'name' => ['required', 'max:255'], 'description' => ['nullable', 'string'], 'category_id' => ['nullable', 'exists:categories,id'], 'unit' => ['required', 'max:20'], 'minimum_stock' => ['required', 'numeric', 'min:0'], 'maximum_stock' => ['nullable', 'numeric', 'gte:minimum_stock'], 'status' => ['required', 'in:ACTIVE,INACTIVE']]);
    }
}
