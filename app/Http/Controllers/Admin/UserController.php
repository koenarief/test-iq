<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->value() ?: null;

        $users = User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'role', 'merchant_id', 'created_at'])
            ->with('merchant:id,name')
            ->when($search, fn ($query) => $query
                ->where(fn ($q) => $q
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);
        $role = $validated['role'] ?? User::ROLE_ADMIN;

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'merchant_id' => $role === User::ROLE_MERCHANT ? $validated['merchant_id'] : null,
        ]);
        $user->email_verified_at = ($validated['verified'] ?? true) ? now() : null;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at', 'role', 'merchant_id']),
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);
        $role = $validated['role'] ?? User::ROLE_ADMIN;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role,
            'merchant_id' => $role === User::ROLE_MERCHANT ? $validated['merchant_id'] : null,
        ]);
        $user->email_verified_at = ($validated['verified'] ?? false) ? ($user->email_verified_at ?? now()) : null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'Tidak bisa menghapus akun yang sedang digunakan sendiri.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'verified' => ['boolean'],
            'role' => ['nullable', Rule::in([User::ROLE_ADMIN, User::ROLE_MERCHANT])],
            'merchant_id' => ['nullable', 'required_if:role,'.User::ROLE_MERCHANT, 'exists:merchants,id'],
        ]);
    }
}
