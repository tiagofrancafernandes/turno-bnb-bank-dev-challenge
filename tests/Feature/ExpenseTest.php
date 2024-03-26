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

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function getExpenseList(): void
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
            ->expense()
            ->create([
                'success' => true,
                'account_id' => $account,
            ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('expense.index'));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transactions', 'array')
                ->whereType('transactions.0.amount', 'string')
                ->whereType('transactions.0.type', 'integer')
                ->where('transactions.0.type', TransactionType::EXPENSE?->value)
                ->etc()
        );
    }

    /**
     * @test
     */
    public function getFilteredExpenseList(): void
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

        $testDate = Carbon::create(2022, 1, 1, 14, 30, 0);
        Date::setTestNow($testDate);
        Transaction::factory(2)
            ->expense()
            ->create([
                'success' => true,
                'account_id' => $account,
            ]);
        Date::setTestNow(null);

        $account = $account->fresh();

        $response = $this
            ->actingAs($user)
            ->getJson(route('expense.index', [
                'year' => $testDate?->year,
                'month' => $testDate?->month,
                'success_only' => true,
                'limit' => 50,
                'offset' => 0,
            ]));

        $response->assertStatus(200);

        $firstPerformedDate = now()->parse($response->json('transactions.0.performed_on') ?? '2015-05-03');

        $this->assertEquals(
            $testDate?->format('Y-m'),
            $firstPerformedDate?->format('Y-m'),
        );

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transactions', 'array')
                ->whereType('transactions.0.amount', 'string')
                ->whereType('transactions.0.type', 'integer')
                ->where('transactions.0.type', TransactionType::EXPENSE?->value)
                ->etc()
        );
    }
}
