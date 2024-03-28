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
        $adminUser = User::factory()->createOne();

        $userOneFakeFile = AppFile::factory()->createOne([
            'user_id' => $userOne,
            'public' => false,
        ]);

        $this->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(404);
        $this->actingAs($userOne)->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(200);
        $this->actingAs($userTwo)->get(route('app_file.show', $userOneFakeFile?->id))->assertStatus(404);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $userOneFakeFile?->id,
            'isAdmin' => true, // TODO: change to use policy
        ]))->assertStatus(200); // ADMIN

        $noUserPrivateFakeFile = AppFile::factory()->createOne([
            'user_id' => null,
            'public' => false,
        ]);

        $this->assertTrue(AppFile::where('id', $noUserPrivateFakeFile?->id)->exists());

        $this->get(route('app_file.show', $noUserPrivateFakeFile?->id))->assertStatus(404);
        $this->actingAs($userOne)->get(route('app_file.show', $noUserPrivateFakeFile?->id))->assertStatus(404);
        $this->actingAs($userTwo)->get(route('app_file.show', $noUserPrivateFakeFile?->id))->assertStatus(404);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $noUserPrivateFakeFile?->id,
            'isAdmin' => true, // TODO: change to use policy
        ]))->assertStatus(200); // ADMIN

        $noUserPublicFakeFile = AppFile::factory()->createOne([
            'user_id' => null,
            'public' => true,
        ]);

        $this->assertTrue(AppFile::where('id', $noUserPublicFakeFile?->id)->exists());

        $this->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($userOne)->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($userTwo)->get(route('app_file.show', $noUserPublicFakeFile?->id))->assertStatus(200);
        $this->actingAs($adminUser)->get(route('app_file.show', [
            'appFile' => $noUserPublicFakeFile?->id,
            'isAdmin' => true, // TODO: change to use policy
        ]))->assertStatus(200); // ADMIN
    }
}
