<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Arr;
// use Laravel\Sanctum\Sanctum;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function testUsersCanAuthenticate(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'abilities' => $abilities = ['abc-list', 'abc-create']
        ]);

        $response
            ->assertStatus(200)
                ?->assertJson(
                    fn (AssertableJson $json) =>
                    $json->whereType('accessToken', 'array')
                        ->whereType('accessToken.token', 'string')
                        ->whereType('accessToken.id', 'integer')
                        ->whereType('accessToken.expires_at', 'null|string')
                        ->whereType('accessToken.abilities', 'array')
                        ->where('accessToken.abilities', $abilities)
                        ->etc()
                );
    }

    public function testUsersCanNotAuthenticateWithInvalidPassword(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('email')
            ->assertJsonValidationErrors('email')
                ?->assertJson(
                    fn (AssertableJson $json) =>
                    $json
                        ->where('message', __('auth.failed'))
                        ->where('errors.email.0', __('auth.failed'))
                        ->etc()
                );
    }

    public function testUsersCanLogout()
    {
        $accessToken = User::factory()
            ->createOne()
            ?->createToken('logout_test_token')
            ?->plainTextToken;

        $this->assertNotEmpty($accessToken);

        $this->postJson(route('api.auth.logout'))
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->postJson(
            route('api.auth.logout'),
            [],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'message' => __('Logout completed successfully'),
                'deleted' => true,
            ]);

        $this->postJson(
            route('api.auth.logout'),
            [],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ]
        )
            ->assertStatus(422)
            ->assertJson([
                'message' => __('Failed to log off'),
                'deleted' => false,
            ]);
    }
}
