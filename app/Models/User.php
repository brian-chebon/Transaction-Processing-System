<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the account associated with the user.
     *
     * @return HasOne
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Get all transactions for the user.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user has sufficient balance for a debit transaction
     *
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->account && $this->account->balance >= $amount;
    }

    /**
     * Get user's current balance
     *
     * @return float|null
     */
    public function getCurrentBalance(): ?float
    {
        return $this->account ? $this->account->balance : null;
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Create an account for the user when they register
        static::created(function ($user) {
            $user->account()->create([
                'balance' => 0.00,
                'currency' => 'USD'
            ]);
        });
    }

    /**
     * Get user's transaction history with pagination
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTransactionHistory(int $perPage = 15)
    {
        return $this->transactions()
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user's recent transactions
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentTransactions(int $limit = 5)
    {
        return $this->transactions()
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
