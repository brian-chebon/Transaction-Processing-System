<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Create admin user
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123')
            ]);

            // Create admin account
            $adminAccount = Account::factory()
                ->forUser($admin)
                ->create([
                    'balance' => 100000.00,
                    'currency' => 'USD',
                    'status' => 'active',
                    'last_transaction_at' => now()
                ]);

            // Create regular users with accounts and transactions
            User::factory()
                ->count(10)
                ->create()
                ->each(function ($user) {
                    // Create account for user
                    $account = Account::factory()
                        ->forUser($user)
                        ->create([
                            'balance' => 1000.00,
                            'currency' => 'USD',
                            'status' => 'active',
                            'last_transaction_at' => now()
                        ]);

                    // Create some initial transactions
                    $balance = $account->balance;
                    for ($i = 0; $i < 3; $i++) {
                        Transaction::factory()
                            ->forAccount($account)
                            ->credit()
                            ->create([
                                'amount' => 100.00,
                                'description' => "Initial credit {$i}",
                                'reference' => "TXN_" . uniqid(),
                                'balance_after' => $balance + 100.00
                            ]);

                        $balance += 100.00;
                        $account->update(['balance' => $balance]);
                    }
                });

            // Run the test data seeder last
            $this->call([
                TestDataSeeder::class
            ]);
        });
    }
}
