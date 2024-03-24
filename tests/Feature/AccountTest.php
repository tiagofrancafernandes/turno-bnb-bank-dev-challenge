<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function testAccountTransaction(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        Transaction::factory(4)->income()->create([
            'account_id' => $account,
            'amount' => 500.00,
            'success' => true,
        ]);

        Transaction::factory(2)->expense()->create([
            'account_id' => $account,
            'amount' => 500.00,
            'success' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('account.index'));

        $response->assertStatus(200);

        $formatNumber = fn (null|float|string $value) => floatval(number_format($value, 2, '.', ''));

        $balance = $formatNumber($response->json('balance'));
        $incomeAmount = $formatNumber($response->json('incomeTransactions.amount_sum'));
        $expenseAmount = $formatNumber($response->json('expenseTransactions.amount_sum'));

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('balance', 'string')
                ->whereType('incomeTransactions.amount_sum', 'null|double|integer')
                ->whereType('expenseTransactions.amount_sum', 'null|double|integer')
                ->etc()
        );

        $this->assertEquals(($incomeAmount - $expenseAmount), $balance);
    }
}
