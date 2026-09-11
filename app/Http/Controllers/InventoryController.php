<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $movements = InventoryMovement::with(['product', 'user', 'source', 'destination'])->when($request->product, fn ($q, $id) => $q->where('product_id', $id))->when($request->type, fn ($q, $t) => $q->where('type', $t))->latest()->paginate(20)->withQueryString();

        return view('inventory.index', ['movements' => $movements, 'products' => Product::orderBy('code')->get(), 'locations' => Location::where('is_active', true)->get()]);
    }

    public function create()
    {
        return view('inventory.transaction', ['products' => Product::where('status', 'ACTIVE')->orderBy('code')->get(), 'locations' => Location::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id'], 'type' => ['required', 'in:STOCK_IN,STOCK_OUT,TRANSFER,ADJUSTMENT'], 'source_location_id' => ['nullable', 'exists:locations,id'], 'destination_location_id' => ['nullable', 'exists:locations,id'], 'quantity' => ['required', 'numeric', 'not_in:0'], 'reason' => ['required', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:4000']]);
        $product = Product::findOrFail($data['product_id']);
        match ($data['type']) {
            'STOCK_IN' => $inventory->stockIn($product, $data['destination_location_id'], $data['quantity'], $data['reason'], $data['reference'] ?? null, $data['notes'] ?? null, $request->user()), 'STOCK_OUT' => $inventory->stockOut($product, $data['source_location_id'], $data['quantity'], $data['reason'], $data['reference'] ?? null, $data['notes'] ?? null, $request->user()), 'TRANSFER' => $inventory->transfer($product, $data['source_location_id'], $data['destination_location_id'], $data['quantity'], $data['reason'], $data['reference'] ?? null, $data['notes'] ?? null, $request->user()), 'ADJUSTMENT' => $inventory->adjust($product, $data['destination_location_id'] ?? $data['source_location_id'], $data['quantity'], $data['reason'], $data['reference'] ?? null, $data['notes'] ?? null, $request->user())
        };

        return redirect()->route('inventory.index')->with('success', 'Inventory transaction recorded.');
    }

    public function history(Product $product, Request $request)
    {
        $movements = $product->movements()->with(['user', 'source', 'destination'])->when($request->type, fn ($q, $t) => $q->where('type',$t))->latest()->paginate(25)->withQueryString();

        return view('inventory.history',compact('product','movements'));
    }
}
