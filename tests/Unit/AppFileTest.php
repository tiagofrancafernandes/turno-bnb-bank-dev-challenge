<?php

namespace Tests\Unit;

use App\Models\AppFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

// use PHPUnit\Framework\TestCase;
// use Illuminate\Foundation\Testing\WithFaker;

class AppFileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function testAppFileVisibility(): void
    {
        $userOne = User::factory()->createOne();
        $userTwo = User::factory()->createOne();

        $userOneFakeFile = AppFile::factory()->createOne([
            'user_id' => $userOne,
            'public' => false,
        ]);

        $this->assertNotNull(AppFile::forUser($userOne)->where('id', $userOneFakeFile?->id)->first());
        $this->assertNull(AppFile::forUser($userTwo)->where('id', $userOneFakeFile?->id)->first());

        $noUserPrivateFakeFile = AppFile::factory()->createOne([
            'user_id' => null,
            'public' => false,
        ]);

        $this->assertTrue(AppFile::where('id', $noUserPrivateFakeFile?->id)->exists());
        $this->assertFalse(AppFile::forUser()->where('id', $noUserPrivateFakeFile?->id)->exists());
        $this->assertFalse(AppFile::forUser($userOne)->where('id', $noUserPrivateFakeFile?->id)->exists());
        $this->assertFalse(AppFile::forUser($userTwo)->where('id', $noUserPrivateFakeFile?->id)->exists());

        $noUserPublicFakeFile = AppFile::factory()->createOne([
            'user_id' => null,
            'public' => true,
        ]);

        $this->assertTrue(AppFile::where('id', $noUserPublicFakeFile?->id)->exists());
        $this->assertTrue(AppFile::forUser()->where('id', $noUserPublicFakeFile?->id)->exists());
        $this->assertTrue(AppFile::forUser($userOne)->where('id', $noUserPublicFakeFile?->id)->exists());
        $this->assertTrue(AppFile::forUser($userTwo)->where('id', $noUserPublicFakeFile?->id)->exists());
    }
}
