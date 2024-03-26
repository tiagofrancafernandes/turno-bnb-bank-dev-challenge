<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use Illuminate\Support\Fluent;
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
            'amount' => 15000,
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
            ->getJson(route('expenses.index'));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transactions', 'array')
                ->whereType('transactions.0.amount', 'string|integer|double')
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
            'amount' => 15000,
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
            ->getJson(route('expenses.index', [
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
                ->whereType('transactions.0.amount', 'string|integer|double')
                ->whereType('transactions.0.type', 'integer')
                ->where('transactions.0.type', TransactionType::EXPENSE?->value)
                ->etc()
        );
    }

    /**
     * @test
     */
    public function validateExpenseCreation(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 35000,
        ]);

        $transaction =  new Fluent([
            'amount' => 15000,
            'title' => 'Super Notebook',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('expenses.create'), $transaction?->toArray());

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transaction', 'array')
                ->whereType('transaction.title', 'string')
                ->whereType('transaction.amount', 'string|integer|double')
                ->whereType('transaction.type', 'integer')
                ->whereType('transaction.success', 'boolean')
                ->where('transaction.type', TransactionType::EXPENSE?->value)
                ->where('transaction.success', true)
                ->where('transaction.title', $transaction?->title)
                ->where('transaction.amount', $transaction?->amount)
                ->etc()
        );
    }

    /**
     * @test
     */
    public function validateExpenseCreationWithInsufficientBalance(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 5000,
        ]);

        $transaction =  new Fluent([
            'amount' => 15000,
            'title' => 'Super Notebook',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('expenses.create'), $transaction?->toArray());

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('transaction', 'array')
                ->whereType('transaction.title', 'string')
                ->whereType('transaction.amount', 'string|integer|double')
                ->whereType('transaction.type', 'integer')
                ->whereType('transaction.success', 'boolean')
                ->where('transaction.type', TransactionType::EXPENSE?->value)
                ->where('transaction.success', false)
                ->where('transaction.notice', __('Insufficient balance'))
                ->where('transaction.title', $transaction?->title)
                ->where('transaction.amount', $transaction?->amount)
                ->etc()
        );
    }
}
