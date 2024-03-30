<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response|JsonResponse
    {
        $request->authenticate();

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

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            $deleted = $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => $deleted ? __('Logout completed successfully') : __('Failed to log off'),
                'deleted' => boolval($deleted),
            ], $deleted ? 200 : 422);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
