<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user ??= auth()->user();
        abort_if(!$user, 404);

        $perPage = filter_var($request->input('per_page', 15), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        $query = $user?->notifications()->orderBy('id', 'desc');

        $query = match ($request->input('status')) {
            'read' => $query->readOnly(),
            'unread' => $query->unreadOnly(),
            'all' => $query,
            default => $query,
        };

        return response()->json(
            $query->paginate($perPage && $perPage > 1 ? $perPage : 15)
        );
    }
}
