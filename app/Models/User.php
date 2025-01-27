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

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->account && $this->account->balance >= $amount;
    }

    public function getCurrentBalance(): ?float
    {
        return $this->account ? $this->account->balance : null;
    }

    public function getTransactionHistory(int $perPage = 15)
    {
        return $this->transactions()
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getRecentTransactions(int $limit = 5)
    {
        return $this->transactions()
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Optional: Add a method to create default account if needed
    public function createDefaultAccount(): Account
    {
        if ($this->account()->exists()) {
            throw new \Exception('User already has an account');
        }

        return $this->account()->create([
            'balance' => 0.00,
            'currency' => 'USD',
            'status' => 'active'
        ]);
    }
}
