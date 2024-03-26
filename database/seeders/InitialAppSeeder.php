<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class InitialAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->initialUsers();
    }

    public function initialUsers(): void
    {
        $users = [
            [
                'name' => 'Admin 1',
                'email' => 'admin@mail.com',
                'password' => Hash::make('power@123'),
                'admin' => true,
            ],
            [
                'name' => 'Customer 1',
                'email' => 'customer1@mail.com',
                'password' => Hash::make('power@123'),
                'admin' => false,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate([
                'email' => $userData['email'],
            ], Arr::except($userData, ['admin']));

            // $user->... TODO: apply roles and permissions
        }
    }

    public function generateTransactions(User $user)
    {
        $user = auth()?->user();
        $account = $user?->account ?? Account::create([
            'user_id' => $user?->id,
            'balance' => 0,
        ]);

        Transaction::factory(15)->create([
            'account_id' => $account,
        ]);
    }
}
