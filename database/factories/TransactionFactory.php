<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['credit', 'debit']);
        $amount = $this->faker->randomFloat(2, 10, 1000);
        $account = Account::factory()->create();
        $balanceAfter = $type === 'credit'
            ? $account->balance + $amount
            : $account->balance - $amount;

        return [
            'account_id' => $account->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $this->faker->sentence(),
            'reference' => 'TXN_' . uniqid(),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'balance_after' => $balanceAfter,
            'metadata' => [
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'location' => $this->faker->city,
                'device_id' => $this->faker->uuid
            ],
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now')
        ];
    }

    /**
     * Configure the factory to generate completed transactions.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed'
        ]);
    }

    /**
     * Configure the factory to generate pending transactions.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending'
        ]);
    }

    /**
     * Configure the factory to generate credit transactions.
     *
     * @return static
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 10, 1000);
            $account = Account::find($attributes['account_id']);

            return [
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $account->balance + $amount
            ];
        });
    }

    /**
     * Configure the factory to generate debit transactions.
     *
     * @return static
     */
    public function debit(): static
    {
        return $this->state(function (array $attributes) {
            $account = Account::find($attributes['account_id']);
            $maxAmount = $account->balance * 0.9; // Ensure we don't overdraft
            $amount = $this->faker->randomFloat(2, 10, max(10, $maxAmount));

            return [
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $account->balance - $amount
            ];
        });
    }

    /**
     * Configure the factory to use a specific account.
     *
     * @param Account $account
     * @return static
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn(array $attributes) => [
            'account_id' => $account->id
        ]);
    }
}
