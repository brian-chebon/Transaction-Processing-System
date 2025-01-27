<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Seed the database with test data.
     */
    public function run(): void
    {
        // Create test user with known credentials
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('test123')
        ]);

        // Create account with known balance
        $testAccount = Account::factory()
            ->forUser($testUser)
            ->create([
                'balance' => 1000.00,
                'currency' => 'USD',
                'status' => 'active'
            ]);

        // Create predictable transaction patterns
        // Large credit transaction
        Transaction::factory()->create([
            'account_id' => $testAccount->id,
            'amount' => 500.00,
            'type' => 'credit',
            'status' => 'completed',
            'description' => 'Initial deposit',
            'balance_after' => 1500.00,
            'created_at' => now()->subDays(30)
        ]);

        // Series of small debits
        $balance = 1500.00;
        for ($i = 0; $i < 5; $i++) {
            $debitAmount = 50.00;
            $balance -= $debitAmount;

            Transaction::factory()->create([
                'account_id' => $testAccount->id,
                'amount' => $debitAmount,
                'type' => 'debit',
                'status' => 'completed',
                'description' => 'Regular payment ' . ($i + 1),
                'balance_after' => $balance,
                'created_at' => now()->subDays(25 - $i)
            ]);
        }

        // Pending transactions
        Transaction::factory()->create([
            'account_id' => $testAccount->id,
            'amount' => 200.00,
            'type' => 'credit',
            'status' => 'pending',
            'description' => 'Pending deposit',
            'created_at' => now()->subHours(2)
        ]);

        Transaction::factory()->create([
            'account_id' => $testAccount->id,
            'amount' => 75.00,
            'type' => 'debit',
            'status' => 'pending',
            'description' => 'Pending payment',
            'created_at' => now()->subHour()
        ]);

        // Create test scenarios for concurrent transactions
        $concurrentUser = User::factory()->create([
            'name' => 'Concurrent Test User',
            'email' => 'concurrent@example.com',
            'password' => Hash::make('test123')
        ]);

        $concurrentAccount = Account::factory()
            ->forUser($concurrentUser)
            ->create([
                'balance' => 5000.00
            ]);

        // Create multiple transactions with same timestamp
        $timestamp = now()->subMinutes(5);
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'account_id' => $concurrentAccount->id,
                'amount' => 100.00,
                'type' => 'debit',
                'status' => 'completed',
                'description' => 'Concurrent transaction ' . ($i + 1),
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]);
        }

        // Create test case for overdraft protection
        $lowBalanceUser = User::factory()->create([
            'name' => 'Low Balance User',
            'email' => 'lowbalance@example.com',
            'password' => Hash::make('test123')
        ]);

        $lowBalanceAccount = Account::factory()
            ->forUser($lowBalanceUser)
            ->lowBalance()
            ->create([
                'balance' => 50.00
            ]);

        // Add a pending debit that would overdraft
        Transaction::factory()->create([
            'account_id' => $lowBalanceAccount->id,
            'amount' => 75.00,
            'type' => 'debit',
            'status' => 'pending',
            'description' => 'Potential overdraft transaction',
            'created_at' => now()
        ]);
    }
}
