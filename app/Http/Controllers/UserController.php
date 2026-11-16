<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::whereIn('role', ['teacher', 'parent'])->orderBy('name')->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', Rule::in(['teacher', 'parent'])],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return back()->with('status', 'Account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['teacher', 'parent'], true), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['teacher', 'parent'])],
        ]);
        $user->update($data);

        return back()->with('status', 'Account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['teacher', 'parent'], true), 404);
        $user->delete();

        return back()->with('status', 'Account deleted.');
    }
}
