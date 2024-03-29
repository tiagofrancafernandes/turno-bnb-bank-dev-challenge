<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Fluent;

/**
 *
 *
 * @property int $id
 * @property string $path
 * @property string|null $original_name
 * @property string $disk
 * @property int|null $user_id
 * @property bool $public
 * @property \Illuminate\Support\Carbon|null $expires_in
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $url
 * @property-read User|null $user
 * @method static \Database\Factories\AppFileFactory factory($count = null, $state = [])
 * @method static Builder|AppFile forUser(?\App\Models\User $user = null)
 * @method static Builder|AppFile newModelQuery()
 * @method static Builder|AppFile newQuery()
 * @method static Builder|AppFile query()
 * @method static Builder|AppFile whereCreatedAt($value)
 * @method static Builder|AppFile whereDisk($value)
 * @method static Builder|AppFile whereExpiresIn($value)
 * @method static Builder|AppFile whereId($value)
 * @method static Builder|AppFile whereOriginalName($value)
 * @method static Builder|AppFile wherePath($value)
 * @method static Builder|AppFile wherePublic($value)
 * @method static Builder|AppFile whereUpdatedAt($value)
 * @method static Builder|AppFile whereUserId($value)
 * @mixin \Eloquent
 */
class AppFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'path',
        'original_name',
        'disk',
        'user_id',
        'public',
        'expires_in',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
        'expires_in' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url',
    ];

    /**
     * Get the user that owns the AppFile
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()?->user();

        return $query
            ->when(
                $user,
                fn (Builder $q) => $q
                    ->where('user_id', $user?->id)
                    ->orWhere(
                        fn ($q2) => $q2
                            ->whereNull('user_id')
                            ->where('public', true)
                    )
            )
            ->when(
                !$user,
                fn (Builder $q) => $q
                    ->whereNull('user_id')
                    ->where('public', true)
            );
    }

    public function getStorage(): ?FilesystemAdapter
    {
        if (!$this?->path) {
            return null;
        }

        return Storage::disk($this?->disk ?: config('filesystems.default'));
    }

    public function getStoragePath(): ?string
    {
        $storage = $this->getStorage();

        if (!$this->path || !$storage || !$storage?->exists($this->path)) {
            return null;
        }

        return $this->path ? $this->getStorage()?->path($this->path) : null;
    }

    public function fileExists(): bool
    {
        return $this->path && $this->getStorage()?->exists($this->path);
    }

    public function url(): ?string
    {
        return $this?->id ? route('app_file.show', $this?->id) : null;
    }

    public function getUrlAttribute()
    {
        return $this->url();
    }

    /**
     * prepareFile function
     *
     * @param string|null $sourcePath
     * @param string|null $diskName
     * @param string|null $dirToSave
     * @param ?string $prefix
     * @param ?string $originalName
     *
     * @return Fluent|null
     */
    public static function prepareFile(
        ?string $sourcePath = null,
        ?string $diskName = null,
        ?string $dirToSave = null,
        ?string $prefix = null,
        ?string $originalName = null,
    ): ?Fluent {
        if (!$sourcePath || !is_file($sourcePath)) {
            return null;
        }

        $diskName = in_array($diskName, [
            'local',
            'public',
        ]) ? $diskName : 'public';

        $storage = Storage::disk($diskName);

        $originalName = pathinfo($originalName ?? $sourcePath, PATHINFO_BASENAME);
        $originalExtension = pathinfo($originalName ?? $sourcePath, PATHINFO_EXTENSION);

        $prefix = ($prefix ?: '') . rand(10, 99) . uniqid() . '-';

        $dirToSave = $dirToSave ? str($dirToSave)->trim('/\\')?->toString() : '';

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
            'storage' => $storage,
        ]) : null;
    }
}
