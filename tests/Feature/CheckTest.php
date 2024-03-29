<?php

namespace Tests\Feature;

use App\Models\Check;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Fluent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use App\Enums\CheckStatus;

// use Illuminate\Support\Facades\Storage;

class CheckTest extends TestCase
{
    use RefreshDatabase;

    protected ?User $user = null;
    protected ?Account $account = null;

    public function setUp(): void
    {
        parent::setUp();

        // Crie um usuário de teste
        $this->user = User::factory()->createOne();
        $this->account = Account::factory()->createOne([
            'user_id' => $this->user,
            'balance' => 0,
        ]);
    }

    /**
     * @test
     */
    public function createNewCheckDeposit(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('checks.deposit'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('check_image');

        $reallyUploadTestFile = config('bnb.tests.upload_real_file', false);

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

        $response = $this->actingAs($this->user)->postJson(route('checks.deposit'), $deposit?->toArray());

        $response->assertStatus(201);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('deposit', 'array')
                ->whereType('deposit.title', 'string')
                ->whereType('deposit.checkImageUrl', 'string')
                ->whereType('deposit.amount', 'string|integer|double')
                ->whereType('success', 'boolean')
                ->whereType('deposit.account_id', 'integer')
                ->where('success', true)
                ->where('deposit.status', CheckStatus::WAITING?->value)
                ->where('deposit.title', $deposit?->title)
                ->where('deposit.amount', $deposit?->amount)
                ->where('deposit.account_id', $this->account?->id)
                ->etc()
        );

        $imageUrl = filter_var($response?->json('deposit.checkImageUrl'), FILTER_VALIDATE_URL);
        $this->assertTrue(boolval($imageUrl));

        $this->get($imageUrl)
            ->assertStatus(200)
            ->assertHeader('Content-Type', $file?->getClientMimeType());
    }

    /**
     * @test
     */
    public function listWithoutStatusFilter(): void
    {
        $count = 15;
        Check::factory($count)->account($this->account)->create();

        $response = $this->actingAs($this->user)->postJson(route('checks.index'));

        $response->assertStatus(200);

        $this->actingAs($this->user)->postJson(route('checks.index'))
            ->assertStatus(200)
            ?->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('data', 'array')
                    ->whereType('data.0.title', 'string')
                    ->whereType('data.0.amount', 'string|integer|double')
                    ->whereType('data.0.check_image_file_id', 'integer')
                    ->whereType('data.0.account_id', 'integer')
                    ->whereType('data.0.checkImageUrl', 'string')
                    ->whereType('data.0.appFile', 'array')
                    ->whereType('data.0.appFile.id', 'integer')
                    ->count('data', $count)
                    ->where('data.0.account_id', $this->account?->id)
                    ->etc()
            );
    }

    /**
     * @test
     * @dataProvider checkStatusEnumDataProvider
     */
    public function listWithStatusFilter(CheckStatus $enum, int $count, array $payload = []): void
    {
        Check::factory($count)->account($this->account)->status($enum)->create();

        $response = $this->actingAs($this->user)->postJson(route('checks.index'));

        $response->assertStatus(200);

        $this->actingAs($this->user)->postJson(route('checks.index', $payload))
            ->assertStatus(200)
            ?->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('data', 'array')
                    ->whereType('data.0.title', 'string')
                    ->whereType('data.0.amount', 'string|integer|double')
                    ->whereType('data.0.check_image_file_id', 'integer')
                    ->whereType('data.0.account_id', 'integer')
                    ->whereType('data.0.checkImageUrl', 'string')
                    ->whereType('data.0.appFile', 'array')
                    ->whereType('data.0.appFile.id', 'integer')
                    ->count('data', $count)
                    ->where('data.0.account_id', $this->account?->id)

                    ->where('data.0.status', $enum?->value)
                    ->etc()
            );
    }

    public static function checkStatusEnumDataProvider()
    {
        return [
            'status accepted and count 4' => [
                /* enum */ CheckStatus::ACCEPTED,
                /* count */ 4,
                'payload' => [
                    'status' => CheckStatus::ACCEPTED?->value,
                ],
            ],
            'status rejected and count 3' => [
                /* enum */ CheckStatus::REJECTED,
                /* count */ 3,
                'payload' => [
                    'status' => CheckStatus::REJECTED?->value,
                ],
            ],
            'status waiting and count 2' => [
                /* enum */ CheckStatus::WAITING,
                /* count */ 2,
                'payload' => [
                    'status' => CheckStatus::WAITING?->value,
                ],
            ],
            'status canceled and count 1' => [
                /* enum */ CheckStatus::CANCELED,
                /* count */ 1,
                'payload' => [
                    'status' => CheckStatus::CANCELED?->value,
                ],
            ],
        ];
    }
}
