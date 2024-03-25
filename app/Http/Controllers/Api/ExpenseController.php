<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|date_format:Y',
            'month' => 'nullable|integer|date_format:m',
        ]);

        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->account?->fresh();

        $failOnly = $request->boolean('fail_only');
        $successOnly = $failOnly ? false : $request->boolean('success_only', true);

        $yearToFilter = $request->input('year', date('Y'));
        $monthToFilter = $request->input('month', date('m'));
        $monthToFilter = "{$yearToFilter}-{$monthToFilter}-01";

        $limit = $request->integer('limit', 40);
        $offset = $request->integer('offset', 0);

        return response()->json([
            'account' => $account,
            'transactions' => $account?->expenses()
                    ?->whereNotNull('performed_on')
                    ?->when(
                        $successOnly,
                        fn ($query) => $query->where('success', $successOnly)
                    )
                    ?->when(
                        $failOnly,
                        fn ($query) => $query->where('success', false)
                    )
                    ?->orderBy('performed_on', 'desc')
                    ?->orderBy('id', 'desc')
                    ?->whereBetween('performed_on', [
                        now()->parse($monthToFilter)->startOfMonth(),
                        now()->parse($monthToFilter)->endOfMonth(),
                    ])
                    ?->limit($limit)
                    ?->offset($offset)
                    ?->get(),
            'limit' => $limit,
            'offset' => $offset,
            'monthToFilter' => now()->parse($monthToFilter)?->format('Y-m'),
        ]);
    }
}
