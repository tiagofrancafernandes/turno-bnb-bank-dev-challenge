<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Helpers\Formatter;

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

        Transaction::factory()->income()->createOne([
            'account_id' => $account,
            'amount' => 5000,
            'success' => true,
        ]);

        Transaction::factory(2)->expense()->create([
            'account_id' => $account,
            'amount' => 500,
            'success' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('account.index'));

        $response->assertStatus(200);

        $account = $account?->fresh();

        $responseBalance = Formatter::floatFormat($response->json('account.balance'));

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('account.balance', 'string')
                ->whereType('incomeAmount', 'null|double|integer')
                ->whereType('expenseAmount', 'null|double|integer')
                ->etc()
        );

        $this->assertEquals($account?->balance, $responseBalance);
    }
}
