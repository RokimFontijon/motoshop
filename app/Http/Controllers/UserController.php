<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Staff accounts. Admin only (see routes/web.php). */
class UserController extends Controller
{
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role_id' => ['required', 'exists:roles,id'],
            // A password is required for a new user; when editing, leave it empty to keep the old one.
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    public function index()
    {
        return view('users.index', [
            'users' => User::with('role')->withCount('jobOrders')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', ['user' => new User(), 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($request->validate($this->rules()));

        return redirect()->route('users.index')->with('status', 'Account created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Safety: the logged-in admin cannot remove their own admin role (they would lock themselves out).
        if ($user->is($request->user())) {
            $data['role_id'] = $user->role_id;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Job orders of this mechanic are kept (mechanic_id becomes empty).
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Account deleted.');
    }
}
