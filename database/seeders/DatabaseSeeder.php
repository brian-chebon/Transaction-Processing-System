<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create admin account with high balance
        Account::factory()
            ->forUser($admin)
            ->highBalance()
            ->create();

        // Create regular users with accounts and transactions
        User::factory(10)->create()->each(function ($user) {
            // Create account for each user
            $account = Account::factory()
                ->forUser($user)
                ->create();

            // Create a mix of transactions for each account
            // Credit transactions
            Transaction::factory(5)
                ->forAccount($account)
                ->credit()
                ->completed()
                ->create();

            // Debit transactions
            Transaction::factory(3)
                ->forAccount($account)
                ->debit()
                ->completed()
                ->create();

            // Pending transactions
            Transaction::factory(2)
                ->forAccount($account)
                ->pending()
                ->create();
        });

        // Create some edge cases
        // Inactive user with zero balance
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        Account::factory()
            ->forUser($inactiveUser)
            ->empty()
            ->inactive()
            ->create();

        // User with suspended account
        $suspendedUser = User::factory()->create();
        Account::factory()
            ->forUser($suspendedUser)
            ->suspended()
            ->create();

        // Call TestDataSeeder
        $this->call([
            TestDataSeeder::class,
        ]);
    }
}
