<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->account;

        $yearToFilter = $request->input('year', date('Y'));
        $monthToFilter = $request->input('month', date('m'));
        $monthToFilter = "{$yearToFilter}-{$monthToFilter}-01";

        return response()->json([
            'balance' => $account?->balance,
            'transactions' =>
                $account?->transactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                    ->where('performed_on', '>=', now()->parse($monthToFilter)->startOfMonth())
                    ->where('performed_on', '<=', now()->parse($monthToFilter)->endOfMonth())
                    ->limit(100),
            'incomeTransactions' =>
                $account?->incomeTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->where('performed_on', '>=', now()->parse($monthToFilter)->startOfMonth())
                        ?->where('performed_on', '<=', now()->parse($monthToFilter)->endOfMonth())
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->first(),
            'expenseTransactions' =>
                $account?->expenseTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->where('performed_on', '>=', now()->parse($monthToFilter)->startOfMonth())
                        ?->where('performed_on', '<=', now()->parse($monthToFilter)->endOfMonth())
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->first(),
        ]);
    }
}
