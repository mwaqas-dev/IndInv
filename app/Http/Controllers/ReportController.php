<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Release;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', ['lowStock' => Product::withSum('inventories', 'quantity')->get()->filter(fn ($p) => (float) ($p->inventories_sum_quantity ?? 0) < (float) $p->minimum_stock), 'releaseStatuses' => Release::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')]);
    }

    public function inventoryCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Product', 'Location', 'Quantity', 'Reserved', 'Available']);
            Inventory::with(['product', 'location'])->cursor()->each(fn ($i) => fputcsv($out, [$i->product->code, $i->product->name, $i->location->name, $i->quantity, $i->reserved_quantity, $i->available()]));
            fclose($out);
        }, 'inventory-report-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
