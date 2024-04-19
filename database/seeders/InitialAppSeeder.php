<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Admin 1',
                'email' => 'admin@mail.com',
                'password' => Hash::make('power@123'),
            ],
            [
                'name' => 'Customer 1',
                'email' => 'customer1@mail.com',
                'password' => Hash::make('power@123'),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate([
                'email' => $userData['email'],
            ], $userData);

            if ($user?->isAdmin()) {
                continue;
            }

            static::generateTransactions($user);
        }
    }

    public static function generateTransactions(?User $user = null): void
    {
        $user ??= auth()?->user();

        if (!$user || !$user?->isAdmin()) {
            return;
        }

        $account = $user?->getAccountOrCreate(0);

        if (!$account) {
            return;
        }

        Transaction::factory(15)->create([
            'account_id' => $account,
        ]);
    }
}
