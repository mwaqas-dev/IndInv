<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('is_active', $request->status))->latest()->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User, 'roles' => Role::orderBy('label')->get()]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request, true);
        $user = null;
        DB::transaction(function () use ($data, &$user) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'is_active' => $data['is_active'] ?? true]);
            $user->roles()->sync($data['roles']);
        });
        $audit->record('CREATE', 'Users', "Created user {$user->email}", $user, [], ['roles' => $data['roles']]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'roles' => Role::orderBy('label')->get()]);
    }

    public function update(Request $request, User $user, AuditService $audit)
    {
        $data = $this->validated($request, false);
        $old = ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active, 'roles' => $user->roles->pluck('id')->all()];
        DB::transaction(function () use ($data, $user) {
            $user->update(collect($data)->only(['name', 'email', 'is_active'])->all());
            if (! empty($data['password'])) {
                $user->update(['password' => $data['password']]);
            } $user->roles()->sync($data['roles']);
        });
        $audit->record('UPDATE', 'Users', "Updated user {$user->email}", $user, $old, ['roles' => $data['roles'], 'is_active' => $user->is_active]);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function toggle(User $user, AuditService $audit)
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => ! $user->is_active]);
        $audit->record($user->is_active ? 'ACTIVATE' : 'DEACTIVATE', 'Users', ($user->is_active ? 'Activated ' : 'Deactivated ').$user->email, $user, [], ['is_active' => $user->is_active]);

        return back()->with('success', 'User status updated.');
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = ['name' => ['required', 'max:255'], 'email' => ['required', 'email', 'unique:users,email,'.($request->route('user')?->id ?? 'NULL')], 'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['exists:roles,id'], 'is_active' => ['boolean']];
        $rules['password'] = $creating ? ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()] : ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()];

        return $request->validate($rules);
    }
}
