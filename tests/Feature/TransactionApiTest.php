<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected string $baseUrl = '/api/v1/transactions';

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
    public function user_can_create_credit_transaction()
    {
        $response = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'credit',
            'description' => 'Test credit transaction'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'amount',
                    'type',
                    'description',
                    'balance_after',
                    'created_at'
                ]
            ]);

        $this->account->refresh();
        $this->assertEquals(1100.00, $this->account->balance);
    }

    /** @test */
    public function user_can_create_debit_transaction()
    {
        $response = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'debit',
            'description' => 'Test debit transaction'
        ]);

        $response->assertStatus(201);
        $this->account->refresh();
        $this->assertEquals(900.00, $this->account->balance);
    }

    /** @test */
    public function user_cannot_debit_more_than_balance()
    {
        $response = $this->postJson($this->baseUrl, [
            'amount' => 2000.00,
            'type' => 'debit',
            'description' => 'Test overdraft prevention'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Transaction failed'
            ]);

        $this->account->refresh();
        $this->assertEquals(1000.00, $this->account->balance);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'type']);
    }

    /** @test */
    public function validates_transaction_type()
    {
        $response = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_transaction()
    {
        auth()->logout();

        $response = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'credit'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_view_transaction_history()
    {
        // Create some test transactions
        Transaction::factory()->count(5)->create([
            'account_id' => $this->account->id
        ]);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'amount',
                            'type',
                            'status',
                            'created_at'
                        ]
                    ],
                    'current_page',
                    'total'
                ]
            ]);
    }

    /** @test */
    public function user_can_filter_transactions()
    {
        // Create test transactions
        Transaction::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'type' => 'credit'
        ]);

        Transaction::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'type' => 'debit'
        ]);

        $response = $this->getJson($this->baseUrl . '?type=credit');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data.data')));
    }

    /** @test */
    public function user_can_search_transactions_by_date_range()
    {
        // Create transactions with different dates
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'created_at' => now()->subDays(5)
        ]);

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'created_at' => now()
        ]);

        $response = $this->getJson($this->baseUrl . '?' . http_build_query([
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString()
        ]));

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.data')));
    }

    /** @test */
    public function handles_transaction_idempotency()
    {
        $reference = 'TEST_' . uniqid();

        // First request
        $response1 = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'credit',
            'reference' => $reference
        ]);

        // Second request with same reference
        $response2 = $this->postJson($this->baseUrl, [
            'amount' => 100.00,
            'type' => 'credit',
            'reference' => $reference
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(422);

        $this->account->refresh();
        $this->assertEquals(1100.00, $this->account->balance);
    }
}
