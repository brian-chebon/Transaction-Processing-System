<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Auth;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected string $baseUrl = '/api/v1/balance';

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and account
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000.00,
            'status' => 'active'
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function user_can_view_current_balance()
    {
        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'balance',
                    'currency',
                    'timestamp'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'balance' => 1000.00,
                    'currency' => 'USD'
                ]
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_balance()
    {
        Auth::logout();
        Sanctum::actingAs(new User()); // Create an unauthenticated session

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_view_detailed_balance_information()
    {
        // Create some transactions
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'credit',
            'status' => 'pending'
        ]);

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 50.00,
            'type' => 'debit',
            'status' => 'pending'
        ]);

        $response = $this->getJson($this->baseUrl . '/details');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_balance',
                    'available_balance',
                    'pending_credits',
                    'pending_debits',
                    'currency',
                    'last_updated',
                    'account_status',
                    'recent_transactions'
                ]
            ]);
    }

    /** @test */
    public function balance_reflects_completed_transactions()
    {
        // Create a completed credit transaction
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'credit',
            'status' => 'completed'
        ]);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'balance' => 1100.00
                ]
            ]);
    }

    /** @test */
    public function pending_transactions_affect_available_balance()
    {
        // Create a pending debit transaction
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'debit',
            'status' => 'pending'
        ]);

        $response = $this->getJson($this->baseUrl . '/details');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'current_balance' => 1000.00,
                    'available_balance' => 900.00
                ]
            ]);
    }

    /** @test */
    public function can_retrieve_balance_history()
    {
        // Create historical transactions
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'credit',
            'status' => 'completed',
            'created_at' => now()->subDays(2)
        ]);

        $response = $this->getJson($this->baseUrl . '/history?' . http_build_query([
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString()
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'date',
                        'balance',
                        'transaction_id'
                    ]
                ]
            ]);
    }

    /** @test */
    public function validates_date_range_parameters()
    {
        $response = $this->getJson($this->baseUrl . '/history?' . http_build_query([
            'date_from' => now()->addDays(1)->toDateString(),
            'date_to' => now()->toDateString()
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_from']);
    }

    /** @test */
    public function handles_inactive_account()
    {
        $this->account->update(['status' => 'inactive']);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Account not found'
            ]);
    }

    /** @test */
    public function returns_correct_currency_format()
    {
        $response = $this->getJson($this->baseUrl);

        $balance = $response->json('data.balance');

        // Verify balance is returned with exactly 2 decimal places
        $this->assertEquals(
            number_format($balance, 2, '.', ''),
            sprintf('%.2f', $balance)
        );
    }
}
