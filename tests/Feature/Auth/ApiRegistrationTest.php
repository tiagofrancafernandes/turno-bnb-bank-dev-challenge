<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ApiRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function testNewUsersCanRegister(): void
    {
        $this->postJson(route('api.auth.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'abilities' => $abilities = ['list-any'],
        ])->assertStatus(200)
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
}
