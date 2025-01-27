<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use Carbon\Carbon;

class TransactionRepository
{
    /**
     * Get user transactions with pagination
     *
     * @param int $userId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserTransactions(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get pending transactions
     *
     * @param int $userId
     * @return Collection
     */
    public function getPendingTransactions(int $userId): Collection
    {
        return Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('status', 'pending')
            ->get();
    }

    /**
     * Get recent transactions
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecentTransactions(int $userId, int $limit = 5): Collection
    {
        return Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find transaction by ID
     *
     * @param int $id
     * @return Transaction|null
     */
    public function findById(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    /**
     * Get balance history
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getBalanceHistory(int $userId, string $startDate, string $endDate): array
    {
        return Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'date' => $transaction->created_at->toDateString(),
                    'balance' => $transaction->balance_after,
                    'transaction_id' => $transaction->id
                ];
            })->toArray();
    }

    /**
     * Get transactions by period
     *
     * @param int $userId
     * @param string $period
     * @return Collection
     */
    public function getTransactionsByPeriod(int $userId, string $period = 'month'): Collection
    {
        $startDate = match ($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };

        return Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
