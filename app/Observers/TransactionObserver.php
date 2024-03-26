<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    /**
     * Handle the Transaction "creating" event.
     */
    public function creating(Transaction $transaction): void
    {
        $account = $transaction?->account;

        if (!$account) {
            return;
        }

        if (!$transaction?->success) {
            return;
        }

        if ($transaction?->type === TransactionType::INCOME) {
            return;
        }

        if ($transaction?->type === TransactionType::EXPENSE) {
            $transaction = static::prepareTransactionOnExpense($transaction, $account);

            return;
        }
    }

    public static function prepareTransactionOnExpense(
        Transaction $transaction,
        Account $account,
    ): Transaction {
        if ($transaction?->type !== TransactionType::EXPENSE) {
            return $transaction;
        }

        if ($account?->balance < $transaction?->amount) {
            $transaction->success = false;
            $transaction->notice = __('Insufficient balance');

            return $transaction;
        }

        $transaction->success = true;
        $transaction->notice = null;

        return $transaction;
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $account = $transaction?->account;

        if (!$account) {
            return;
        }

        if (!$transaction?->success) {
            static::notifyUser($transaction, $account);

            return;
        }

        match ($transaction?->type) {
            TransactionType::INCOME => static::processTransactionOnIncome($transaction, $account),
            TransactionType::EXPENSE => static::processTransactionOnExpense($transaction, $account),
            default => $transaction->update([
                'success' => false,
                'notice' => 'Error on process transaction',
            ]),
        };

        static::notifyUser($transaction, $account);
    }

    public static function processTransactionOnIncome(
        Transaction $transaction,
        Account $account,
    ): Transaction {
        if ($transaction?->type !== TransactionType::INCOME) {
            return $transaction;
        }

        if ($transaction?->performed_on) {
            return $transaction;
        }

        match (get_class($account?->getConnection())) {
            \Illuminate\Database\PostgresConnection::class => $account?->update([
                'balance' => DB::raw('CAST(balance AS numeric) + CAST(' . $transaction?->amount . ' AS numeric)'),
                'updated_at' => now(),
            ]),
            \Illuminate\Database\MySqlConnection::class => $account?->increment('balance', $transaction?->amount),
            default => $account?->increment('balance', $transaction?->amount),
        };

        $transaction->update([
            'success' => true,
            'performed_on' => now(),
        ]);

        return $transaction;
    }

    public static function processTransactionOnExpense(
        Transaction $transaction,
        Account $account,
    ): Transaction {
        if ($transaction?->type !== TransactionType::EXPENSE) {
            return $transaction;
        }

        if ($transaction?->performed_on) {
            return $transaction;
        }

        if ($account?->balance < $transaction?->amount) {
            $transaction->update([
                'success' => false,
                'notice' => __('Insufficient balance'),
                'performed_on' => now(),
            ]);

            return $transaction;
        }

        match (get_class($account?->getConnection())) {
            \Illuminate\Database\PostgresConnection::class => $account?->update([
                'balance' => DB::raw('CAST(balance AS numeric) - CAST(' . $transaction?->amount . ' AS numeric)'),
                'updated_at' => now(),
            ]),
            \Illuminate\Database\MySqlConnection::class => $account?->decrement('balance', $transaction?->amount),
            default => $account?->decrement('balance', $transaction?->amount),
        };

        $transaction->update([
            'success' => true,
            'performed_on' => now(),
        ]);

        return $transaction;
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        //
    }

    public static function notifyUser(
        Transaction $transaction,
        Account $account,
    ): void {
        if (!$transaction?->performed_on) {
            return;
        }

        $user = $account?->user;

        if (!$user) {
            return;
        }

        $title = match ($transaction?->type) {
            TransactionType::INCOME => $transaction?->success
            ? __('bank.finished_transaction.income.short_success')
            : __('bank.finished_transaction.income.short_error'),
            TransactionType::EXPENSE => $transaction?->success
            ? __('bank.finished_transaction.expense.short_success')
            : __('bank.finished_transaction.expense.short_error'),
        };

        $text = match ($transaction?->type) {
            TransactionType::INCOME => $transaction?->success
            ? __('bank.finished_transaction.income.success')
            : ($transaction?->notice ?: __('bank.finished_transaction.income.error')),
            TransactionType::EXPENSE => $transaction?->success
            ? __('bank.finished_transaction.expense.success')
            : ($transaction?->notice ?: __('bank.finished_transaction.expense.error')),
        };

        $notification = Notification::create([
            'user_id' => $user?->id,
            'title' => $title,
            'icon' => null,
            'readed' => false,
            'text' => $text,
            'link' => null,
            'route' => null,
            'route_params' => null,
            'classes' => implode(
                ' ',
                array_keys(
                    array_filter([
                        'text-green-100 bg-green-500 notify-success' => $transaction?->success,
                        'text-red-100 bg-red-500 notify-error' => !$transaction?->success,
                    ])
                )
            ),
        ]);

        info([
            __METHOD__,
            __FILE__ . ':' . __LINE__,
            $transaction?->toArray(),
            $user?->toArray(),
            $notification?->toArray(),
        ]);
    }
}
