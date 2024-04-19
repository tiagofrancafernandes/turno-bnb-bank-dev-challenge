<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Formatter;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $periodList = [
            365, // 1a
            180, // 6m
            150, // 5m
            120, // 4m
            90, // 3m
            60, // 2m
            30,
            15,
            7,
        ];

        $request->validate([
            'year' => 'nullable|integer|date_format:Y',
            'month' => 'nullable|integer|date_format:m',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:0',
            'period' => 'nullable|integer|in:' . implode(',', $periodList),
        ]);

        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->getAccountOrCreate(0);

        $filterPeriod = $request->input('period', 30);
        $monthToFilter = now()
            ->setYear($request->input('year', date('Y')))
            ->setMonth($request->input('month', date('m')));

        $filterRange = [
            now()->parse($monthToFilter)->subDays($filterPeriod),
            now()->parse($monthToFilter),
        ];

        $limit = $request->integer('limit', 40);
        $offset = $request->integer('offset', 0);

        return response()->json([
            'account' => $account,
            'transactions' =>
                $account?->transactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', $filterRange)
                    ->limit($limit)
                    ->offset($offset)
                    ->get(),
            'incomeAmount' => Formatter::floatFormat(
                $account?->incomeTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', $filterRange)
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->groupBy('id')
                        ?->first()
                        ?->amount_sum
            ),
            'expenseAmount' => Formatter::floatFormat(
                $account?->expenseTransactions()
                        ?->whereNotNull('performed_on')
                        ?->orderBy('id', 'desc')
                        ?->whereBetween('performed_on', $filterRange)
                        ?->select([DB::raw('sum(amount) as amount_sum')])
                        ?->groupBy('id')
                        ?->first()
                        ?->amount_sum
            ),
            'limit' => $limit,
            'offset' => $offset,
            'filter' => [
                'period' => $filterPeriod,
                'range' => $filterRange,
            ],
        ]);
    }
}
