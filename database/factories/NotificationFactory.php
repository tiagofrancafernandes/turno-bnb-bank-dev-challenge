<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()?->first() ?? User::factory(),
            'title' => fake()->words(3, true),
            'icon' => null,
            'readed' => fake()->boolean(5),
            'text' => fake()->paragraph(),
            'link' => Arr::random([null, url('fake-item')]),
            'route' => fn (array $attr) => ($attr['link'] ?? null) ? null : Arr::random([null, 'ping']),
            'route_params' => fn (array $attr) => ($attr['route'] ?? null) === 'ping' ? [
                'message' => fake()->words(3, true),
            ] : [],
        ];
    }

    /**
     * Force to use User::factory()
     */
    public function useFactory(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }
}
