<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Account;

class TransactionController extends Controller
{
    public function incomes(Request $request)
    {
        return $this->responseByType($request, TransactionType::INCOME);
    }

    public function expenses(Request $request)
    {
        return $this->responseByType($request, TransactionType::EXPENSE);
    }

    protected function responseByType(Request $request, TransactionType $type)
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
            'period' => 'nullable|integer|in:' . implode(',', $periodList),
        ]);

        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->account ?? Account::create([ // TODO: validate if != Admin
            'user_id' => $user?->id,
            'balance' => 0,
        ]);

        $failOnly = $request->boolean('fail_only');
        $successOnly = $failOnly ? false : $request->boolean('success_only', true);

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
            'transactions' => $account?->transactions()
                    ?->where('type', $type)
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
                    // ?->whereBetween('performed_on', $filterRange)
                    ?->limit($limit)
                    ?->offset($offset)
                    ?->get(),
            'limit' => $limit,
            'offset' => $offset,
            'monthToFilter' => now()->parse($monthToFilter)?->format('Y-m'),
            'filter' => [
                'period' => $filterPeriod,
                'range' => $filterRange,
            ],
        ]);
    }

    public function newExpense(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'title' => 'required|string|min:1',
        ]);

        $user = auth()?->user();
        abort_if(!$user, 403);

        $account = $user?->account ?? Account::create([ // TODO: validate if != Admin
            'user_id' => $user?->id,
            'balance' => 0,
        ]);

        $transaction = Transaction::create(
            array_merge(
                $validated,
                [
                    'account_id' => $account?->id,
                    'type' => TransactionType::EXPENSE,
                ]
            )
        );

        $transaction = $transaction?->fresh();

        return response()->json([
            'transaction' => $transaction,
        ], $transaction?->success ? 201 : 402);
    }
}
