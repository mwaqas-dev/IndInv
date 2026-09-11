<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Release;
use App\Models\ReleaseComponent;
use App\Services\AuditService;
use App\Services\ReleaseWorkflowService;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request)
    {
        $releases = Release::with(['product', 'creator'])->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('number', 'like', "%$s%")->orWhere('project', 'like', "%$s%")->orWhere('version', 'like', "%$s%")))->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(15)->withQueryString();

        return view('releases.index', compact('releases'));
    }

    public function create()
    {
        return view('releases.form', ['release' => new Release, 'products' => Product::where('status', 'ACTIVE')->orderBy('code')->get()]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);
        $release = Release::create($data + ['number' => 'RLS-'.now()->format('Y').'-'.str_pad((string) ((Release::whereYear('created_at', now()->year)->count()) + 1), 4, '0', STR_PAD_LEFT), 'created_by' => $request->user()->id]);
        foreach (array_filter(preg_split('/\r?\n/', (string) $request->input('components'))) as $component) {
            ReleaseComponent::create(['release_id' => $release->id, 'name' => trim($component)]);
        } $release->history()->create(['user_id' => $request->user()->id, 'action' => 'CREATED', 'new_status' => 'DRAFT']);
        $audit->record('CREATE', 'Releases', "Created release {$release->number}", $release, [], ['status' => 'DRAFT']);

        return redirect()->route('releases.show', $release)->with('success', 'Release created as draft.');
    }

    public function show(Release $release)
    {
        $release->load(['product', 'creator', 'components', 'history.user', 'approvals.approver']);

        return view('releases.show', compact('release'));
    }

    public function edit(Release $release)
    {
        abort_unless($release->status === 'DRAFT', 422, 'Only draft releases can be edited.');

        return view('releases.form', ['release' => $release, 'products' => Product::where('status', 'ACTIVE')->orderBy('code')->get()]);
    }

    public function update(Request $request, Release $release, AuditService $audit)
    {
        abort_unless($release->status === 'DRAFT', 422, 'Only draft releases can be edited.');
        $old = $release->only(['product_id', 'project', 'version', 'release_type', 'description']);
        $release->update($this->validated($request));
        $audit->record('UPDATE', 'Releases', "Updated release {$release->number}", $release, $old, $release->only(array_keys($old)));

        return redirect()->route('releases.show', $release)->with('success', 'Release updated.');
    }

    public function transition(Request $request, Release $release, ReleaseWorkflowService $workflow)
    {
        $data = $request->validate(['status' => ['required', 'in:SUBMITTED,UNDER_REVIEW,APPROVED,REJECTED,DRAFT,RELEASED,CLOSED'], 'comments' => ['nullable', 'string', 'max:4000']]);
        $workflow->transition($release, $data['status'], $request->user(), $data['comments'] ?? null);

        return back()->with('success', 'Release workflow updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['product_id' => ['nullable', 'exists:products,id'], 'project' => ['required', 'max:255'], 'version' => ['required', 'max:100'], 'release_type' => ['required', 'max:100'], 'description' => ['required', 'string', 'max:10000']]);
    }
}
