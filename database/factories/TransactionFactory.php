<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => Arr::random(TransactionType::cases()),
            'title' => fn (array $attr) => TransactionFactory::fakeTitle($attr['type'] ?? null),
            'amount' => rand(100, 1000) . '.' . rand(0, 99),
            'account_id' => Account::inRandomOrder()?->first() ?? Account::factory(),
            'success' => fake()->boolean(90),
            'performed_on' => null,
        ];
    }

    /**
     * Force 'type' to TransactionType::EXPENSE
     */
    public function expense(?float $amount = null): static
    {
        return $this->state(
            fn (array $attributes) => array_filter([
                'type' => TransactionType::EXPENSE,
                'amount' => $amount,
            ])
        );
    }

    /**
     * Force 'type' to TransactionType::INCOME
     */
    public function income(?float $amount = null): static
    {
        return $this->state(
            fn (array $attributes) => array_filter([
                'type' => TransactionType::INCOME,
                'amount' => $amount,
            ])
        );
    }

    public static function fakeTitle(?TransactionType $type = null): string
    {
        $type ??= Arr::random(TransactionType::cases());

        return Arr::random(static::fakeTitleList()[$type?->name] ?? []);
    }

    public static function fakeTitleList(): array
    {
        return [
            'INCOME' => [
                'Check deposit',
                'Check payment',
                'Bank deposit',
                'Deposit slip',
                'Cheque clearance',
                'Check processing',
                'Check receipt',
                'Check receipt confirmation',
                'Bank transaction',
                'Check verification',
            ],
            'EXPENSE' => [
                'Supermarket',
                'Electronics store',
                'Gas station',
                'Pharmacy',
                'Restaurant',
                'Coffee shop',
                'Gym membership',
                'Movie theater',
                'Hair salon',
                'Car wash',
                'Furniture store',
                'Hardware store',
                'Jewelry store',
                'Online shopping',
                'Pet store',
                'Travel expenses',
                'Utility bills',
                'Clothing store',
                'Shoe store',
                'Sporting goods store',
            ],
        ];
    }
}
