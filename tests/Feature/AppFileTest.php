<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AppFile;
use App\Models\User;

// use PHPUnit\Framework\TestCase;
// use Illuminate\Foundation\Testing\WithFaker;

class AppFileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function testAppFileShow(): void
    {
        $userOne = User::factory()->createOne();
        $userTwo = User::factory()->createOne();
        $adminUser = User::factory()->createOne([
            'email' => fake()->bothify('??????***@admin.com'),
        ]);

        $userOneFakeFile = AppFile::factory()->createOne([
            'user_id' => $userOne,
            'public' => false,
        ]);

        $this->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(404);
        $this->actingAs($userOne)->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(200);
        $this->actingAs($userTwo)->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(404);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $userOneFakeFile?->id,
        ]))->assertStatus(200); // ADMIN

        $privateFakeWithoutOwnerFile = AppFile::factory()->createOne([
            'user_id' => null,
            'public' => false,
        ]);

        $this->assertTrue(AppFile::where('id', $privateFakeWithoutOwnerFile?->id)->exists());

        $this->actingAs($userOne)->get(route('app_file.show', $privateFakeWithoutOwnerFile?->id))->assertStatus(404);
        $this->actingAs($userOne)->get(route('app_file.show', $privateFakeWithoutOwnerFile?->id))->assertStatus(404);
        $this->actingAs($userTwo)->get(route('app_file.show', $privateFakeWithoutOwnerFile?->id))->assertStatus(404);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $privateFakeWithoutOwnerFile?->id,
        ]))
        ->assertStatus(200); // ADMIN

        $noUserPublicFakeFile = AppFile::factory()
            ->useFakeFile(
                sourcePath: database_path('static-files/images/check_image.png'),
                diskName: 'public',
            )
            ->createOne([
                'user_id' => null,
                'public' => true,
            ]);

        $this->assertTrue(AppFile::where('id', $noUserPublicFakeFile?->id)->exists());

        $this->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($userOne)->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($userTwo)->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $noUserPublicFakeFile?->id,
        ]))->assertStatus(200); // ADMIN

        $this->get(route('app_file.show', $noUserPublicFakeFile?->id))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }
}
