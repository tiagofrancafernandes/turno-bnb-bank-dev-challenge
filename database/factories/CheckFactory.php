<?php

namespace Database\Factories;

use App\Enums\CheckStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Account;
use App\Models\AppFile;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Check>
 */
class CheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => Arr::random([
                'Check Deposit',
                'Nominal Check Deposit',
                'Digital Check Deposit',
            ]),
            'amount' => rand(100, 15000) . '.00',
            'status' => Arr::random(CheckStatus::cases()),
            'check_image_file_id' => AppFile::factory(),
            'account_id' => Account::factory(),
        ];
    }

    public function status(?CheckStatus $status = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status ?? Arr::random(CheckStatus::cases()),
        ]);
    }

    public function account(?Account $account = null): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account ?? Account::factory(),
        ]);
    }
}
