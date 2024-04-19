<?php

namespace App\Policies;

use App\Models\Check;
use App\Models\User;
use App\Enums\CheckStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CheckPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Check $check): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        return boolval(static::getCheck($user, $check));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this?->deposit($user);
    }

    /**
     * Determine whether the user can deposit models.
     */
    public function deposit(User $user): bool
    {
        return !$user?->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Check $check): bool
    {
        if ($user?->isAdmin()) {
            return false;
        }

        return boolval(static::getCheck($user, $check));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Check $check): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        return boolval(static::getCheck($user, $check));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Check $check): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Check $check): bool
    {
        return false;
    }

    public function approveDeposit(User $user): bool
    {
        return boolval($user?->isAdmin());
    }

    /**
     * updateStatus function
     *
     * @param User $user
     * @param Check $check
     *
     * @return boolean
     */
    public function updateStatus(User $user, Check $check, int|CheckStatus $status): bool
    {
        if (
            !$user ||
            !$check
            || !in_array($check?->status, [
                CheckStatus::CREATED,
                CheckStatus::WAITING,
            ])
        ) {
            return false;
        }

        $status = is_a($status, CheckStatus::class) ? $status : CheckStatus::tryFrom($status);

        if (!$status) {
            return false;
        }

        if (
            in_array($status, [
                CheckStatus::REJECTED,
                CheckStatus::ACCEPTED,
            ]) && $user?->isAdmin()
        ) {
            return true;
        }

        $check = $check
                ?->with('account.user')
                ?->whereHas(
                    'account.user',
                    fn (Builder $query) => $query?->whereId($user?->id)
                )?->whereId($check?->id)
                ?->first();

        return boolval($check);
    }

    protected static function getCheck(
        ?User $user,
        ?Check $check,
        null|int|CheckStatus $status = null,
    ): ?Check {
        if (
            !$user || !$user?->id
            || !$check || !$check?->id
        ) {
            return null;
        }

        $checkQuery = $check?->with('account.user')?->whereId($check?->id);

        if (!$user?->isAdmin()) {
            $checkQuery = $checkQuery?->whereHas(
                'account.user',
                fn (Builder $query) => $query?->whereId($user?->id)
            );
        }

        if ($status) {
            $status = is_a($status, CheckStatus::class) ? $status : CheckStatus::tryFrom($status);
        }

        if (
            $status && in_array($status, [
                CheckStatus::REJECTED,
                CheckStatus::ACCEPTED,
            ]) && !$user?->isAdmin()
        ) {
            return null;
        }

        return $checkQuery?->first();
    }
}
