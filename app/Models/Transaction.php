<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\Formatter;

/**
 *
 *
 * @property int $id
 * @property string $title
 * @property TransactionType $type
 * @property-read float $amount
 * @property int $account_id
 * @property bool|null $success
 * @property \Illuminate\Support\Carbon|null $performed_on
 * @property string|null $notice
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Account $account
 * @property-read mixed $type_enum
 * @property-read mixed $type_label
 * @method static Builder|Transaction expensesOnly()
 * @method static \Database\Factories\TransactionFactory factory($count = null, $state = [])
 * @method static Builder|Transaction incomesOnly()
 * @method static Builder|Transaction newModelQuery()
 * @method static Builder|Transaction newQuery()
 * @method static Builder|Transaction query()
 * @method static Builder|Transaction whereAccountId($value)
 * @method static Builder|Transaction whereAmount($value)
 * @method static Builder|Transaction whereCreatedAt($value)
 * @method static Builder|Transaction whereId($value)
 * @method static Builder|Transaction whereNotice($value)
 * @method static Builder|Transaction wherePerformedOn($value)
 * @method static Builder|Transaction whereSuccess($value)
 * @method static Builder|Transaction whereTitle($value)
 * @method static Builder|Transaction whereType($value)
 * @method static Builder|Transaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'type',
        'amount',
        'account_id',
        'success',
        'performed_on',
        'notice',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionType::class,
        'success' => 'boolean',
        'performed_on' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'typeEnum',
        'typeLabel',
    ];

    protected $dates = [
        'performed_on',
    ];

    /**
     * Get the account that owns the Transaction
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeIncomesOnly(Builder $query): Builder
    {
        return $query->where(
            'type',
            TransactionType::INCOME?->value,
        );
    }

    public function scopeExpensesOnly(Builder $query): Builder
    {
        return $query->where(
            'type',
            TransactionType::EXPENSE?->value,
        );
    }

    public function getTypeEnumAttribute()
    {
        return $this->type;
    }

    public function getTypeLabelAttribute()
    {
        return $this->type?->label(true);
    }

    /**
     * Get the user's first name.
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Formatter::floatFormat($value),
        );
    }
}
