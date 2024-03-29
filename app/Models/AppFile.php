<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

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
}
