<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'amount',
        'perform_date',
        'account_id',
        'success',
        'performed_on',
        'notice',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'perform_date' => 'datetime',
        'success' => 'boolean',
        'performed_on' => 'datetime',
    ];

    protected $appends = [
        'typeEnum',
        'typeLabel',
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
}
