<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response|JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if (!$request->expectsJson()) {
            $request?->session()->regenerate();

            return response()->noContent();
        }

        $user = auth()?->user();

        $abilities = collect($request->input('abilities'))
            ->filter(fn ($item) => is_string($item) && !is_numeric($item) && trim(($item)))
            ->map(fn ($item) => trim($item))
            ->toArray() ?: ['*'];

        $token = $user?->createToken('api_login', $abilities);

        return response()->json([
            'accessToken' => [
                'token' => $token?->plainTextToken,
                'id' => $token?->accessToken?->id,
                'expires_at' => $token?->accessToken?->expires_at,
                'abilities' => $token?->accessToken?->abilities,
            ]
        ]);
    }
}
