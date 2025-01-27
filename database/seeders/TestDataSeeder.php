<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Create test user
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('test123')
            ]);

            // Create main account
            $testAccount = Account::factory()
                ->forUser($testUser)
                ->create([
                    'balance' => 1000.00,
                    'currency' => 'USD',
                    'status' => 'active'
                ]);

            // Create a large credit transaction
            Transaction::factory()
                ->forAccount($testAccount)
                ->credit()
                ->create([
                    'amount' => 500.00,
                    'description' => 'Initial deposit',
                    'balance_after' => 1500.00,
                    'created_at' => now()->subDays(30)
                ]);

            // Create concurrent test user
            $concurrentUser = User::factory()->create([
                'name' => 'Concurrent Test User',
                'email' => 'concurrent@example.com',
                'password' => Hash::make('test123')
            ]);

            $concurrentAccount = Account::factory()
                ->forUser($concurrentUser)
                ->create([
                    'balance' => 5000.00,
                    'status' => 'active'
                ]);

            // Create low balance test user
            $lowBalanceUser = User::factory()->create([
                'name' => 'Low Balance User',
                'email' => 'lowbalance@example.com',
                'password' => Hash::make('test123')
            ]);

            $lowBalanceAccount = Account::factory()
                ->forUser($lowBalanceUser)
                ->create([
                    'balance' => 50.00,
                    'status' => 'active'
                ]);

            // Add transactions for the main test account
            $balance = 1500.00;
            for ($i = 0; $i < 5; $i++) {
                $debitAmount = 50.00;
                $balance -= $debitAmount;

                Transaction::factory()
                    ->forAccount($testAccount)
                    ->debit()
                    ->create([
                        'amount' => $debitAmount,
                        'description' => 'Regular payment ' . ($i + 1),
                        'balance_after' => $balance,
                        'created_at' => now()->subDays(25 - $i)
                    ]);
            }

            // Add pending transactions
            Transaction::factory()
                ->forAccount($testAccount)
                ->credit()
                ->pending()
                ->create([
                    'amount' => 200.00,
                    'description' => 'Pending deposit',
                    'created_at' => now()->subHours(2)
                ]);

            // Add concurrent transactions
            $timestamp = now()->subMinutes(5);
            for ($i = 0; $i < 3; $i++) {
                Transaction::factory()
                    ->forAccount($concurrentAccount)
                    ->debit()
                    ->create([
                        'amount' => 100.00,
                        'description' => 'Concurrent transaction ' . ($i + 1),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp
                    ]);
            }

            // Add potential overdraft transaction
            Transaction::factory()
                ->forAccount($lowBalanceAccount)
                ->debit()
                ->pending()
                ->create([
                    'amount' => 75.00,
                    'description' => 'Potential overdraft transaction',
                    'created_at' => now()
                ]);
        });
    }
}
