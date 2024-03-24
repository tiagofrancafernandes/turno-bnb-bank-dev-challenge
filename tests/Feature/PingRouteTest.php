<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PingRouteTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testTheApplicationReturnsASuccessfulResponse(): void
    {
        $response = $this->get(route('ping', 'some-here'));

        $response->assertStatus(200);

        $response->assertJson(
            fn (AssertableJson $json) => $json->whereType('message', 'string')
                ->where('message', 'some-here')
                ->etc()
        );
    }
}
