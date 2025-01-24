<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Exception;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status',
        'last_transaction_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'last_transaction_at' => 'datetime',
    ];

    /**
     * Get the user that owns the account.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the account.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Process a new transaction with locking to prevent race conditions
     *
     * @param float $amount
     * @param string $type
     * @param string|null $description
     * @return Transaction
     * @throws Exception
     */
    public function processTransaction(float $amount, string $type, ?string $description = null): Transaction
    {
        // Lock the account for update to prevent race conditions
        return DB::transaction(function () use ($amount, $type, $description) {
            // Reload the account with a lock
            $account = self::lockForUpdate()->find($this->id);

            if ($type === 'debit') {
                if ($account->balance < $amount) {
                    throw new Exception('Insufficient funds');
                }
                $newBalance = $account->balance - $amount;
            } else {
                $newBalance = $account->balance + $amount;
            }

            // Update account balance
            $account->balance = $newBalance;
            $account->last_transaction_at = now();
            $account->save();

            // Create transaction record
            return $account->transactions()->create([
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'balance_after' => $newBalance,
                'status' => 'completed'
            ]);
        });
    }

    /**
     * Get pending transactions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingTransactions()
    {
        return $this->transactions()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate available balance (current balance minus pending debits)
     *
     * @return float
     */
    public function getAvailableBalance(): float
    {
        $pendingDebits = $this->transactions()
            ->where('status', 'pending')
            ->where('type', 'debit')
            ->sum('amount');

        return (float) ($this->balance - $pendingDebits);
    }

    /**
     * Check if account is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            $account->status = $account->status ?? 'active';
            $account->currency = $account->currency ?? 'USD';
        });
    }
}
