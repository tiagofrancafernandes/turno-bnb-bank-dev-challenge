<?php

namespace App\Models;

use App\Enums\CheckStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 *
 *
 * @property int $id
 * @property string $title
 * @property string $amount
 * @property bool|null $success
 * @property CheckStatus $status
 * @property int|null $check_image_file_id
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Account $account
 * @property-read AppFile|null $appFile
 * @property-read mixed $check_image_url
 * @method static \Database\Factories\CheckFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Check newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Check newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Check query()
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereCheckImageFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereSuccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Check whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Check extends Model
{
    use HasFactory;

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'amount',
        'status',
        'check_image_file_id',
        'account_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => CheckStatus::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'checkImageUrl',
    ];

    public function scopeByStatus(Builder $query, null|int|string|CheckStatus $status): Builder
    {
        if (!$status) {
            return $query->where('account_id', 0);
        }

        $status = is_a($status, CheckStatus::class) ? $status : CheckStatus::tryFrom($status);

        if (!$status || !is_a($status, CheckStatus::class)) {
            return $query->where('account_id', 0);
        }

        return $query->where('status', $status?->value);
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()?->user();

        if ($user?->isAdmin) {
            return $query;
        }

        $account = $user?->getAccountOrCreate(0);

        return $this->scopeForAccount($query, $account);
    }

    public function scopeForAccount(Builder $query, ?Account $account = null): Builder
    {
        $user ??= auth()?->user();

        if (!$user && !$account) {
            return $query->where('account_id', 0);
        }

        if ($user?->isAdmin) {
            return $query;
        }

        $account ??= $user?->getAccountOrCreate(0);

        return $query->where('account_id', $account?->id);
    }

    /**
     * Get the account that owns the Check
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the appFile associated with the Check
     *
     * @return HasOne
     */
    public function appFile(): HasOne
    {
        return $this->hasOne(AppFile::class, 'id', 'check_image_file_id');
    }

    public function getCheckImageUrlAttribute()
    {
        return $this->appFile?->url;
    }

    public function scopeAddAppend(Builder $query, array|string $attributes)
    {
    }
}
