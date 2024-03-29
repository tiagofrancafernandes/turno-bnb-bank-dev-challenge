<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Fluent;
use App\Models\AppFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppFile>
 */
class AppFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => static::getFakeFile(
                sourcePath: database_path('static-files/images/check_image.png'),
                diskName: 'public',
            )?->finalPath,
            'original_name' => 'check_image.png',
            'disk' => 'public',
            'user_id' => fake()->boolean(10) ? (User::inRandomOrder()?->first() ?: User::factory()) : null,
            'public' => fn (array $attr) => ($attr['user_id'] ?? null) ? fake()->boolean(90) : false,
            'expires_in' => fake()->boolean(10) ? now()->addDays(rand(1, 50)) : null,
        ];
    }

    /**
     * useFakeFile function
     *
     * @param string|null $sourcePath
     * @param string|null $diskName
     * @param string|null $dirToSave
     * @param string|null $prefix
     *
     * @return static
     */
    public function useFakeFile(
        ?string $sourcePath = null,
        ?string $diskName = null,
        ?string $dirToSave = null,
        ?string $prefix = null,
    ): static {
        $fakeFile = static::getFakeFile(
            sourcePath: $sourcePath,
            diskName: $diskName,
            dirToSave: $dirToSave,
            prefix: $prefix,
        );

        return $this->state(fn (array $attributes) => [
            'path' => $fakeFile?->finalPath,
            'original_name' => $fakeFile?->originalName,
            'disk' => $fakeFile?->diskName,
        ]);
    }

    /**
     * getFakeFile function
     *
     * @param string|null $sourcePath
     * @param string|null $diskName
     * @param string|null $dirToSave
     * @param ?string $prefix
     *
     * @return Fluent|null
     */
    public static function getFakeFile(
        ?string $sourcePath = null,
        ?string $diskName = null,
        ?string $dirToSave = null,
        ?string $prefix = null,
    ): ?Fluent {
        $dirToSave = $dirToSave ? str($dirToSave)->trim('/\\')?->toString() : str('fake-files/images');

        $sourcePath ??= database_path('static-files/images/check_image.png');

        return AppFile::prepareFile(
            sourcePath: $sourcePath,
            diskName: $diskName,
            dirToSave: $dirToSave,
            prefix: $prefix ?? 'fake-file-',
            originalName: pathinfo($sourcePath, PATHINFO_BASENAME),
        );
    }
}
