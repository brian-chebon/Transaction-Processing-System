<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'balance' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'USD',
            'status' => 'active',
            'last_transaction_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-31 days'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now')
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id
        ]);
    }

    public function withStatus(string $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status
        ]);
    }
}
