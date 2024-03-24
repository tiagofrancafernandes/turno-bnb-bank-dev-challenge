<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'type' => \Arr::random(TransactionType::cases()),
            'title' => fn (array $attr) => match ($attr['type'] ?? null) {
                TransactionType::EXPENSE => 'Product ',
                TransactionType::INCOME => 'Nominal check ',
                default => '',
            } . fake()->words(3, true),
            'amount' => rand(100, 1000) . '.' . rand(0, 99),
            'perform_date' => now()->subMinutes(rand(0, 60)),
            'account_id' => Account::inRandomOrder()?->first() ?? Account::factory(),
            'success' => fake()->boolean(90),
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
}
