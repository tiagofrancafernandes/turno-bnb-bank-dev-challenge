<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;

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
                randomPrefix: true,
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
     * @param bool $randomPrefix
     *
     * @return static
     */
    public function useFakeFile(
        ?string $sourcePath = null,
        ?string $diskName = null,
        ?string $dirToSave = null,
        bool $randomPrefix = false,
    ): static {
        $fakeFile = static::getFakeFile(
            sourcePath: $sourcePath,
            diskName: $diskName,
            dirToSave: $dirToSave,
            randomPrefix: $randomPrefix,
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
     * @param bool $randomPrefix
     *
     * @return Fluent|null
     */
    public static function getFakeFile(
        ?string $sourcePath = null,
        ?string $diskName = null,
        ?string $dirToSave = null,
        bool $randomPrefix = false,
    ): ?Fluent {
        $diskName = in_array($diskName, [
            'local',
            'public',
        ]) ? $diskName : 'public';

        $storage = Storage::disk($diskName);
        $sourcePath = $sourcePath && is_file($sourcePath)
            ? $sourcePath : database_path('static-files/images/check_image.png');

        $originalName = pathinfo($sourcePath, PATHINFO_BASENAME);
        $originalExtension = pathinfo($sourcePath, PATHINFO_EXTENSION);

        $prefix = $randomPrefix ? rand(10, 99) . uniqid() . '-' : 'fake-file-';

        $dirToSave = $dirToSave ? str($dirToSave)->trim('/\\')?->toString()
            : str('fake-files/images')?->toString();

        $finalPath = str($originalName)
            ->prepend($prefix)
            ->beforeLast('.')
            ->slug()
            ->when(
                $originalExtension,
                fn ($str) => $str->append('.' . $originalExtension)
            )
            ->prepend($dirToSave . '/')
            ->toString();

        $exists = $storage->exists($finalPath);

        if (!$exists) {
            $exists = $storage->put($finalPath, file_get_contents($sourcePath));
        }

        return $exists ? new Fluent([
            'finalPath' => $finalPath,
            'originalName' => $originalName,
            'diskName' => $diskName,
        ]) : null;
    }
}
