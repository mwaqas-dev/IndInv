<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Release;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = request()->user();
        $products = Product::query();
        $releases = Release::query();
        $inventory = Inventory::query();

        return view('dashboard', ['metrics' => ['products' => (clone $products)->count(), 'stock' => (clone $inventory)->sum('quantity'), 'low_stock' => Product::withSum('inventories', 'quantity')->get()->filter(fn ($p) => (float) ($p->inventories_sum_quantity ?? 0) < (float) $p->minimum_stock)->count(), 'active_releases' => (clone $releases)->whereNotIn('status', ['CLOSED', 'REJECTED'])->count(), 'pending' => (clone $releases)->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW'])->count()], 'movements' => InventoryMovement::with(['product', 'user', 'source', 'destination'])->latest()->limit(6)->get(), 'recentReleases' => Release::with('creator')->latest()->limit(6)->get(), 'activities' => ActivityLog::with('user')->latest()->limit($user->can('activity.view') ? 10 : 5)->get()]);
    }
}
