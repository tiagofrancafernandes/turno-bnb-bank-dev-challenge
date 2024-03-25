<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TransactionType;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * Get the user that owns the Account
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the transactions for the Account
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all of income transactions for the Account
     *
     * @return HasMany
     */
    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where(
            'type',
            TransactionType::INCOME?->value,
        );
    }

    /**
     * Get all of expense transactions for the Account
     *
     * @return HasMany
     */
    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where(
            'type',
            TransactionType::EXPENSE?->value,
        );
    }

    /**
     * Alias to 'incomeTransactions()'
     * @return HasMany
     */
    public function incomes(): HasMany
    {
        return $this->incomeTransactions();
    }

    /**
     * Alias to 'expenseTransactions()'
     * @return HasMany
     */
    public function expenses(): HasMany
    {
        return $this->expenseTransactions();
    }
}
