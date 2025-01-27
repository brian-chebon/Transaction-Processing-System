<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'balance' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'USD',
            'status' => 'active',
            'last_transaction_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-31 days'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now')
        ];
    }

    /**
     * Configure the factory to generate accounts with zero balance.
     *
     * @return static
     */
    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => 0.00
        ]);
    }

    /**
     * Configure the factory to generate accounts with high balance.
     *
     * @return static
     */
    public function highBalance(): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => $this->faker->randomFloat(2, 10000, 100000)
        ]);
    }

    /**
     * Configure the factory to generate accounts with low balance.
     *
     * @return static
     */
    public function lowBalance(): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => $this->faker->randomFloat(2, 0.01, 99.99)
        ]);
    }

    /**
     * Configure the factory to generate inactive accounts.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
            'last_transaction_at' => $this->faker->dateTimeBetween('-1 year', '-6 months')
        ]);
    }

    /**
     * Configure the factory to generate suspended accounts.
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'suspended',
            'last_transaction_at' => $this->faker->dateTimeBetween('-1 month', 'now')
        ]);
    }

    /**
     * Configure the factory to use a specific user.
     *
     * @param User $user
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id
        ]);
    }

    /**
     * Configure the factory to use a specific currency.
     *
     * @param string $currency
     * @return static
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => strtoupper($currency)
        ]);
    }

    /**
     * Configure the factory to generate accounts with no recent activity.
     *
     * @return static
     */
    public function dormant(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'dormant',
            'last_transaction_at' => $this->faker->dateTimeBetween('-1 year', '-90 days')
        ]);
    }
}
