<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Fluent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

// use Illuminate\Support\Facades\Storage;

class CheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function createNewCheckDeposit(): void
    {
        $user = User::factory()->createOne();
        $account = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        $response = $this
            ->actingAs($user)->postJson(route('checks.deposit'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('check_image');

        $reallyUploadTestFile = false; // TODO: use config to get it

        $sourcePath = database_path('static-files/images/check_image.png');

        $file = $reallyUploadTestFile ? new UploadedFile(
            path: $sourcePath,
            originalName: pathinfo($sourcePath, PATHINFO_BASENAME),
            mimeType: File::mimeType($sourcePath),
            error: null,
            test: true,
        ) : UploadedFile::fake()->image($sourcePath);

        $deposit = new Fluent([
            'amount' => 5000,
            'title' => 'Salary payment',
            'check_image' => $file,
        ]);

        $response = $this
            ->actingAs($user)->postJson(route('checks.deposit'), $deposit?->toArray());

        $response->assertStatus(201);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('deposit', 'array')
                ->whereType('deposit.title', 'string')
                ->whereType('deposit.check_image_url', 'string')
                ->whereType('deposit.amount', 'string|integer|double')
                ->whereType('deposit.success', 'boolean')
                ->whereType('deposit.account', 'integer')
                ->where('deposit.success', true)
                ->where('deposit.status', 30)// TODO: enum WAITING
                ->where('deposit.title', $deposit?->title)
                ->where('deposit.amount', $deposit?->amount)
                ->where('deposit.account', $account?->id)
                ->etc()
        );

        $imageUrl = filter_var($response?->json('deposit.check_image_url'), FILTER_VALIDATE_URL);
        $this->assertTrue(boolval($imageUrl));

        $this->get($imageUrl)
            ->assertStatus(200)
            ->assertHeader('Content-Type', $file?->getClientMimeType());
    }
}
