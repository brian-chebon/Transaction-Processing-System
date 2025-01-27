<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(['credit', 'debit']),
            'description' => $this->faker->sentence(),
            'reference' => 'TXN_' . uniqid(),
            'status' => 'completed',
            'balance_after' => 0, // This will be calculated when creating
            'metadata' => [
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'location' => $this->faker->city,
                'device_id' => $this->faker->uuid
            ]
        ];
    }

    public function forAccount(Account $account): static
    {
        return $this->state(function (array $attributes) use ($account) {
            $amount = $attributes['amount'];
            $type = $attributes['type'];
            $balanceAfter = $type === 'credit'
                ? $account->balance + $amount
                : $account->balance - $amount;

            return [
                'account_id' => $account->id,
                'balance_after' => $balanceAfter
            ];
        });
    }

    public function credit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'credit'
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'debit'
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending'
        ]);
    }
}
