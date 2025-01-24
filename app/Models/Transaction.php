<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'amount',
        'type',
        'description',
        'reference',
        'status',
        'balance_after',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'pending'
    ];

    /**
     * Get the account that owns the transaction.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user that owns the transaction through the account.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include credit transactions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope a query to only include debit transactions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope a query to only include completed transactions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Complete the transaction
     *
     * @return bool
     */
    public function complete(): bool
    {
        return $this->update(['status' => 'completed']);
    }

    /**
     * Mark transaction as failed
     *
     * @param string|null $reason
     * @return bool
     */
    public function fail(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'metadata->failure_reason' => $reason
        ]);
    }

    /**
     * Check if transaction is reversible
     *
     * @return bool
     */
    public function isReversible(): bool
    {
        return $this->status === 'completed'
            && $this->created_at->diffInHours(now()) < 24;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate reference if not provided
        static::creating(function ($transaction) {
            if (!$transaction->reference) {
                $transaction->reference = uniqid('TXN_', true);
            }
        });
    }
}
