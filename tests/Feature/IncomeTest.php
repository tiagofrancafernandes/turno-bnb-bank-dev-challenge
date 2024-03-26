<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Carbon;

class IncomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function getIncomeList(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        Transaction::factory()->income()->createOne([
            'account_id' => $account,
            'amount' => 1000.00,
            'success' => true,
        ]);

        Transaction::factory(5)
            ->income()
            ->create([
                'success' => true,
                'account_id' => $account,
            ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('incomes.index'));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transactions', 'array')
                ->whereType('transactions.0.amount', 'string|integer|double')
                ->whereType('transactions.0.type', 'integer')
                ->where('transactions.0.type', TransactionType::INCOME?->value)
                ->etc()
        );
    }

    /**
     * @test
     */
    public function getFilteredIncomeList(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        Transaction::factory()->income()->createOne([
            'account_id' => $account,
            'amount' => 15000.00,
            'success' => true,
        ]);

        Transaction::factory(5)
            ->income()
            ->create([
                'success' => true,
                'account_id' => $account,
            ]);

        $testDate = Carbon::create(2022, 1, 1, 14, 30, 0);
        Date::setTestNow($testDate);
        Transaction::factory(2)
            ->income()
            ->create([
                'success' => true,
                'account_id' => $account,
            ]);
        Date::setTestNow(null);

        $response = $this
            ->actingAs($user)
            ->getJson(route('incomes.index', [
                'year' => $testDate?->year,
                'month' => $testDate?->month,
                'success_only' => true,
                'limit' => 50,
                'offset' => 0,
            ]));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transactions', 'array')
                ->whereType('transactions.0.amount', 'string|integer|double')
                ->whereType('transactions.0.type', 'integer')
                ->where('transactions.0.type', TransactionType::INCOME?->value)
                ->etc()
        );
    }
}
