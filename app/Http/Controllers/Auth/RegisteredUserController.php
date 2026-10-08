<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public self-registration. This has always been the only unauthenticated
 * route that can create a User, so it now explicitly pins the created
 * account to the candidate role regardless of input — previously it relied
 * on User's default `role` attribute (admin), which meant anyone visiting
 * /register could create an admin account.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'positions' => Position::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'position_id' => ['nullable', 'exists:positions,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
            'position_id' => $validated['position_id'] ?? null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('candidate.dashboard', absolute: false));
    }
}
