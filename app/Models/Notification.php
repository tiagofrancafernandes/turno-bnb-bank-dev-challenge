<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 *
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $icon
 * @property bool $readed
 * @property string|null $text
 * @property string|null $link
 * @property string|null $route
 * @property \Illuminate\Support\Collection|null $route_params
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $action_url
 * @property-read User $user
 * @method static \Database\Factories\NotificationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereReaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereRouteParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereUserId($value)
 * @property string|null $classes
 * @method static Builder|Notification ofUser(?\App\Models\User $user = null)
 * @method static Builder|Notification readOnly()
 * @method static Builder|Notification unreadOnly()
 * @method static Builder|Notification whereClasses($value)
 * @mixin \Eloquent
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'icon',
        'readed',
        'text',
        'link',
        'route',
        'route_params',
        'classes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'readed' => 'boolean',
        'route_params' => AsCollection::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'actionUrl',
    ];

    public function scopeOfUser(Builder $query, ?User $user = null) // TODO: use global scope here
    {
        $user ??= auth()->user();

        abort_if(!$user || !$user?->id, 404);

        return $query->where('user_id', $user?->id);
    }

    public function scopeReadOnly(Builder $query)
    {
        return $query->where('readed', true);
    }

    public function scopeUnreadOnly(Builder $query)
    {
        return $query->where('readed', false);
    }

    /**
     * Get the user that owns the Notification
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionUrlAttribute()
    {
        if (filter_var($this->link, FILTER_VALIDATE_URL)) {
            return $this->link;
        }

        if (!$this->route || !\Illuminate\Support\Facades\Route::has($this->route)) {
            return null;
        }

        return route($this->route, $this->route_params?->toArray());
    }
}
