<?php

namespace Tests\Feature\Admin;

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
    protected ?User $adminUser = null;
    protected ?Account $account = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->createOne();
        $this->adminUser = User::factory()->createOne([
            'email' => fake()->bothify('??????***@admin.com'),
        ]);

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
    public function failOnCreateNewCheckDepositAsAdmin(): void
    {
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

        $this->actingAs($this->adminUser)->postJson(route('checks.deposit'), $deposit?->toArray())
            ->assertStatus(403)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('message', 'string')
                    ->where('message', 'This action is unauthorized.')
                    ->etc()
            );
    }

    /**
     * @test
     */
    public function listWithoutStatusFilter(): void
    {
        $count = 15;
        Check::factory($count)->account($this->account)->create();

        $this->actingAs($this->adminUser)->postJson(route('checks.index'))
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

    /**
     * @test
     */
    public function testCheckShow(): void
    {
        $check = Check::factory()->account($this->account)->status(CheckStatus::WAITING)->createOne();

        $this->actingAs($this->user)->postJson(route('checks.show', $check?->id))
            ->assertStatus(200)
                ?->assertJson(
                    fn (AssertableJson $json) =>
                    $json->whereType('title', 'string')
                        ->whereType('amount', 'string|integer|double')
                        ->whereType('check_image_file_id', 'integer')
                        ->whereType('account_id', 'integer')
                        ->whereType('checkImageUrl', 'string')
                        ->whereType('appFile', 'array')
                        ->whereType('appFile.id', 'integer')
                        ->where('account_id', $this->account?->id)
                        ->where('title', $check?->title)
                        ->where('amount', $check?->amount)
                        ->where('status', $check?->status?->value)
                        ->etc()
                );
    }

    /**
     * @test
     */
    public function testCheckShowOfAnotherUser(): void
    {
        $user = User::factory()->createOne();
        $userAccount = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        $check = Check::factory()->account($this->account)->status(CheckStatus::WAITING)->createOne();

        $accountCheck = Check::factory()->account($this->account)->status(CheckStatus::WAITING)->createOne();
        $userAccountCheck = Check::factory()->account($userAccount)->status(CheckStatus::WAITING)->createOne();

        $this->actingAs($this->user)->postJson(route('checks.show', $accountCheck?->id))
            ->assertStatus(200);

        $this->actingAs($user)->postJson(route('checks.show', $userAccountCheck?->id))
            ->assertStatus(200);

        $this->actingAs($user)->postJson(route('checks.show', $accountCheck?->id))
            ->assertStatus(404);

        $this->actingAs($this->adminUser)->postJson(route('checks.show', $accountCheck?->id))
            ->assertStatus(200);

        $this->actingAs($this->adminUser)->postJson(route('checks.show', $userAccountCheck?->id))
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function testUpdateCheckStatus(): void
    {
        $user = User::factory()->createOne();
        $userAccount = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        $userAccountCheck = Check::factory()->account($userAccount)->status(CheckStatus::WAITING)->createOne();

        $this->postJson(route('checks.update-status', $userAccountCheck?->id))
            ->assertStatus(401);

        $this->actingAs($user)->postJson(route('checks.show', $userAccountCheck?->id))
            ->assertStatus(200);

        $this->actingAs($this->user)->postJson(route('checks.show', $userAccountCheck?->id))
            ->assertStatus(404);

        $this->actingAs($this->adminUser)->postJson(route('checks.show', $userAccountCheck?->id))
            ->assertStatus(200);

        $this->actingAs($user)->postJson(route('checks.update-status', $userAccountCheck?->id))
            ->assertStatus(403);
    }

    /**
     * @test
     * @dataProvider statusCheckerDataProvider
     */
    public function statusChecker(
        int $status,
        array $data,
        array $whereTypes,
        array $whereValues,
    ) {
        $user = User::factory()->createOne();
        $userAccount = Account::factory()->createOne([
            'user_id' => $user,
            'balance' => 0,
        ]);

        $userAccountCheck = Check::factory()->account($userAccount)->status(CheckStatus::WAITING)->createOne();

        $response = $this->actingAs($this->adminUser)->postJson(
            route('checks.update-status', $userAccountCheck?->id),
            $data
        )
            ->assertStatus($status);

        foreach ($whereTypes as $column => $type) {
            $response?->assertJson(
                fn (AssertableJson $json) => $json->whereType('message', 'string')
                    ->whereType($column, $type)
                    ->etc()
            );
        }

        foreach ($whereValues as $column => $value) {
            $response?->assertJson(
                fn (AssertableJson $json) => $json->whereType('message', 'string')
                    ->where($column, $value)
                    ->etc()
            );
        }

        // $this->actingAs($this->adminUser)->postJson(
        //     route('checks.update-status', $userAccountCheck?->id),
        //     [
        //         'status' => CheckStatus::CANCELED?->value,
        //     ]
        // )
        //     ->assertStatus(422)
        //         ?->assertJson(
        //         fn(AssertableJson $json) => $json->whereType('message', 'string')
        //             ->whereType('errors', 'array')
        //             ->where('message', 'The selected status is invalid. ')
        //             ->etc()
        //     );

        // $this->actingAs($this->adminUser)->postJson(
        //     route('checks.update-status', $userAccountCheck?->id),
        //     [
        //         'status' => CheckStatus::REJECTED?->value,
        //     ]
        // )
        //     ->assertStatus(200);
    }

    public static function statusCheckerDataProvider(): array
    {
        return [
            'no status' => [
                'status' => 422,
                'data' => [
                    //
                ],
                'whereTypes' => [],
                'whereValues' => [],
            ],
            'invalid status' => [
                'status' => 422,
                'data' => [
                    'status' => CheckStatus::CANCELED?->value,
                ],
                'whereTypes' => [
                    'errors' => 'array',
                ],
                'whereValues' => [
                    'message' => 'The selected status is invalid.',
                ],
            ],
            'valid status' => [
                'status' => 200,
                'data' => [
                    'status' => CheckStatus::REJECTED?->value,
                ],
                'whereTypes' => [
                    //
                ],
                'whereValues' => [
                    //
                ],
            ],
        ];
    }
}
