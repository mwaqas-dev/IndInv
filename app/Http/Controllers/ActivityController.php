<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->when(! $request->user()->can('activity.view-all'), fn ($q) => $q->where('user_id', $request->user()->id))->when($request->module, fn ($q, $m) => $q->where('module', $m))->when($request->action, fn ($q, $a) => $q->where('action', $a))->when($request->search, fn ($q, $s) => $q->where('description', 'like', "%$s%"));

        return view('activity.index', ['activities' => $query->latest()->paginate(25)->withQueryString()]);
    }
}
