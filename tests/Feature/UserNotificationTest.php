<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Testing\Fluent\AssertableJson;

class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function getUserNotificationList(): void
    {
        $user = User::factory()->createOne();
        Notification::factory(10)->create([
            'user_id' => $user,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('notifications', [
                // TODO: to create test or use dataProvider for 'status'
                // 'status' => 'unread',
                // 'status' => 'read',
            ]));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('data', 'array')
                ->whereType('data.0.id', 'integer')
                ->whereAllType([
                    'data' => 'array',
                    'data.0.title' => 'string',
                ])
                ->etc()
        );
    }

    /**
     * @test
     */
    public function getLastUserNotification(): void
    {
        $user = User::factory()->createOne();

        Notification::factory(4)->create();

        $title = 'Fake test ' . fake()->words(4, true);

        $notification = Notification::factory()->createOne([
            'user_id' => $user?->id,
            'readed' => false,
            'title' => $title,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('notifications', [
                'status' => 'unread',
                'per_page' => 5,
            ]));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('data', 'array')
                ->whereType('data.0.id', 'integer')
                ->whereAllType([
                    'data' => 'array',
                    'data.0.title' => 'string',
                ])
                ->has(
                    'data.0',
                    fn ($json) => $json->where('id', $notification?->id)
                        ->where('title', $notification?->title)
                        ->etc()
                )
                ->etc()
        );
    }
}
