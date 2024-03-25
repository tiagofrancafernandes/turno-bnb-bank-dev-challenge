<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|date_format:Y',
            'month' => 'nullable|integer|date_format:m',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:0',
        ]);

        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->account;

        $yearToFilter = $request->input('year', date('Y'));
        $monthToFilter = $request->input('month', date('m'));
        $monthToFilter = "{$yearToFilter}-{$monthToFilter}-01";

        $limit = $request->integer('limit', 40);
        $offset = $request->integer('offset', 0);

        return response()->json([
            'balance' => $account?->balance,
            'transactions' =>
                $account?->transactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', [
                            now()->parse($monthToFilter)->startOfMonth(),
                            now()->parse($monthToFilter)->endOfMonth(),
                        ])
                    ->limit($limit)
                    ->offset($offset)
                    ->get(),
            'incomeTransactions' =>
                $account?->incomeTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', [
                            now()->parse($monthToFilter)->startOfMonth(),
                            now()->parse($monthToFilter)->endOfMonth(),
                        ])
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->first(),
            'expenseTransactions' =>
                $account?->expenseTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', [
                            now()->parse($monthToFilter)->startOfMonth(),
                            now()->parse($monthToFilter)->endOfMonth(),
                        ])
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->first(),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
}
