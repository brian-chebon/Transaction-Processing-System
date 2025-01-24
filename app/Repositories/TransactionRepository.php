<?php

namespace App\Services;

use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Cache;
use Exception;

class BalanceService
{
    protected $accountRepository;
    protected $transactionRepository;

    // Cache TTL in seconds (5 minutes)
    protected const CACHE_TTL = 300;

    /**
     * Constructor
     *
     * @param AccountRepository $accountRepository
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        AccountRepository $accountRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Get current balance for a user
     *
     * @param int $userId
     * @return float
     * @throws Exception
     */
    public function getCurrentBalance(int $userId): float
    {
        return Cache::remember(
            "user_balance_{$userId}",
            self::CACHE_TTL,
            function () use ($userId) {
                $account = $this->accountRepository->getAccountByUserId($userId);

                if (!$account) {
                    throw new Exception('Account not found');
                }

                return $account->balance;
            }
        );
    }

    /**
     * Get detailed balance information
     *
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function getBalanceDetails(int $userId): array
    {
        $account = $this->accountRepository->getAccountByUserId($userId);

        if (!$account) {
            throw new Exception('Account not found');
        }

        $pendingTransactions = $this->transactionRepository->getPendingTransactions($userId);
        $pendingCredits = $pendingTransactions->where('type', 'credit')->sum('amount');
        $pendingDebits = $pendingTransactions->where('type', 'debit')->sum('amount');

        return [
            'current_balance' => $account->balance,
            'available_balance' => $account->getAvailableBalance(),
            'pending_credits' => $pendingCredits,
            'pending_debits' => $pendingDebits,
            'currency' => $account->currency,
            'last_updated' => $account->updated_at,
            'account_status' => $account->status,
            'recent_transactions' => $this->getRecentTransactions($userId)
        ];
    }

    /**
     * Get balance history for a date range
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getBalanceHistory(int $userId, string $startDate, string $endDate): array
    {
        return $this->transactionRepository->getBalanceHistory($userId, $startDate, $endDate);
    }

    /**
     * Get recent transactions
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    protected function getRecentTransactions(int $userId, int $limit = 5)
    {
        return $this->transactionRepository->getRecentTransactions($userId, $limit);
    }

    /**
     * Invalidate balance cache for a user
     *
     * @param int $userId
     * @return void
     */
    public function invalidateBalanceCache(int $userId): void
    {
        Cache::forget("user_balance_{$userId}");
    }

    /**
     * Calculate aggregate balance metrics
     *
     * @param int $userId
     * @param string $period
     * @return array
     */
    public function getBalanceMetrics(int $userId, string $period = 'month'): array
    {
        $transactions = $this->transactionRepository->getTransactionsByPeriod($userId, $period);

        return [
            'total_credits' => $transactions->where('type', 'credit')->sum('amount'),
            'total_debits' => $transactions->where('type', 'debit')->sum('amount'),
            'average_balance' => $transactions->avg('balance_after'),
            'max_balance' => $transactions->max('balance_after'),
            'min_balance' => $transactions->min('balance_after'),
            'transaction_count' => $transactions->count(),
            'period' => $period
        ];
    }
}
