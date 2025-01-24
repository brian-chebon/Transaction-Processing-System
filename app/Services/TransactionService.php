<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Repositories\AccountRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionService
{
    protected $transactionRepository;
    protected $accountRepository;

    /**
     * Constructor
     *
     * @param TransactionRepository $transactionRepository
     * @param AccountRepository $accountRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        AccountRepository $accountRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->accountRepository = $accountRepository;
    }

    /**
     * Create a new transaction
     *
     * @param int $userId
     * @param float $amount
     * @param string $type
     * @param string|null $description
     * @return Transaction
     * @throws Exception
     */
    public function createTransaction(
        int $userId,
        float $amount,
        string $type,
        ?string $description = null
    ): Transaction {
        try {
            // Start database transaction
            return DB::transaction(function () use ($userId, $amount, $type, $description) {
                // Get user's account with lock
                $account = $this->accountRepository->getAccountWithLock($userId);

                if (!$account) {
                    throw new Exception('Account not found');
                }

                if (!$account->isActive()) {
                    throw new Exception('Account is not active');
                }

                // Validate transaction
                $this->validateTransaction($account, $amount, $type);

                // Process the transaction
                $transaction = $account->processTransaction($amount, $type, $description);

                // Log the transaction
                $this->logTransaction($transaction);

                return $transaction;
            });
        } catch (Exception $e) {
            Log::error('Transaction failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get user's transaction history
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserTransactions(int $userId, array $filters = [])
    {
        return $this->transactionRepository->getUserTransactions($userId, $filters);
    }

    /**
     * Reverse a transaction if possible
     *
     * @param int $transactionId
     * @param int $userId
     * @return Transaction
     * @throws Exception
     */
    public function reverseTransaction(int $transactionId, int $userId): Transaction
    {
        return DB::transaction(function () use ($transactionId, $userId) {
            $transaction = $this->transactionRepository->findById($transactionId);

            if (!$transaction || $transaction->user_id !== $userId) {
                throw new Exception('Transaction not found');
            }

            if (!$transaction->isReversible()) {
                throw new Exception('Transaction cannot be reversed');
            }

            // Create reversal transaction
            $reversalType = $transaction->type === 'credit' ? 'debit' : 'credit';

            $reversal = $this->createTransaction(
                $userId,
                $transaction->amount,
                $reversalType,
                "Reversal of transaction {$transaction->reference}"
            );

            // Update original transaction
            $transaction->update([
                'metadata->reversed' => true,
                'metadata->reversal_id' => $reversal->id
            ]);

            return $reversal;
        });
    }

    /**
     * Validate a transaction
     *
     * @param \App\Models\Account $account
     * @param float $amount
     * @param string $type
     * @throws Exception
     */
    protected function validateTransaction($account, float $amount, string $type): void
    {
        if ($type === 'debit') {
            $availableBalance = $account->getAvailableBalance();
            if ($availableBalance < $amount) {
                throw new Exception("Insufficient funds. Available balance: {$availableBalance}");
            }
        }

        // Add additional validation rules as needed
        // For example: daily limits, transaction frequency, etc.
    }

    /**
     * Log transaction details
     *
     * @param Transaction $transaction
     * @return void
     */
    protected function logTransaction(Transaction $transaction): void
    {
        Log::info('Transaction processed', [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'balance_after' => $transaction->balance_after
        ]);
    }
}
