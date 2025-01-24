<?php

namespace App\Repositories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class AccountRepository
{
    /**
     * Get account by user ID
     *
     * @param int $userId
     * @return Account|null
     */
    public function getAccountByUserId(int $userId): ?Account
    {
        return Account::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get account with lock for update
     *
     * @param int $userId
     * @return Account|null
     */
    public function getAccountWithLock(int $userId): ?Account
    {
        return Account::where('user_id', $userId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Create a new account
     *
     * @param array $data
     * @return Account
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * Update account balance
     *
     * @param int $accountId
     * @param float $newBalance
     * @return bool
     * @throws Exception
     */
    public function updateBalance(int $accountId, float $newBalance): bool
    {
        $account = Account::lockForUpdate()->find($accountId);

        if (!$account) {
            throw new Exception('Account not found');
        }

        if (!$account->isActive()) {
            throw new Exception('Account is not active');
        }

        $account->balance = $newBalance;
        $account->last_transaction_at = now();

        return $account->save();
    }

    /**
     * Get accounts with low balance
     *
     * @param float $threshold
     * @return Collection
     */
    public function getAccountsWithLowBalance(float $threshold): Collection
    {
        return Account::where('balance', '<', $threshold)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get inactive accounts
     *
     * @param int $days
     * @return Collection
     */
    public function getInactiveAccounts(int $days = 30): Collection
    {
        return Account::where('status', 'active')
            ->where('last_transaction_at', '<', now()->subDays($days))
            ->get();
    }

    /**
     * Update account status
     *
     * @param int $accountId
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $accountId, string $status): bool
    {
        return Account::where('id', $accountId)
            ->update(['status' => $status]);
    }

    /**
     * Get account statistics
     *
     * @param int $accountId
     * @return array
     */
    public function getAccountStats(int $accountId): array
    {
        $account = Account::with(['transactions' => function ($query) {
            $query->orderBy('created_at', 'desc')
                ->limit(30); // Last 30 transactions
        }])->find($accountId);

        if (!$account) {
            throw new Exception('Account not found');
        }

        $transactions = $account->transactions;

        return [
            'current_balance' => $account->balance,
            'available_balance' => $account->getAvailableBalance(),
            'total_transactions' => $transactions->count(),
            'average_transaction_amount' => $transactions->avg('amount'),
            'largest_credit' => $transactions->where('type', 'credit')->max('amount'),
            'largest_debit' => $transactions->where('type', 'debit')->max('amount'),
            'last_transaction_date' => $account->last_transaction_at,
            'account_age_days' => $account->created_at->diffInDays(now())
        ];
    }

    /**
     * Check if account has sufficient balance
     *
     * @param int $accountId
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(int $accountId, float $amount): bool
    {
        $account = Account::find($accountId);
        return $account && $account->getAvailableBalance() >= $amount;
    }

    /**
     * Archive old transactions
     *
     * @param int $accountId
     * @param int $daysOld
     * @return int Number of archived transactions
     */
    public function archiveOldTransactions(int $accountId, int $daysOld = 365): int
    {
        return Account::find($accountId)
            ->transactions()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->update(['status' => 'archived']);
    }
}
