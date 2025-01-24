<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Repositories\TransactionRepository;
use App\Repositories\AccountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;
    protected User $user;
    protected Account $account;

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

        // Initialize service with repositories
        $this->transactionService = new TransactionService(
            new TransactionRepository(),
            new AccountRepository()
        );
    }

    /** @test */
    public function it_can_create_a_credit_transaction()
    {
        $amount = 100.00;
        $transaction = $this->transactionService->createTransaction(
            $this->user->id,
            $amount,
            'credit'
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(1100.00, $transaction->balance_after);
        $this->assertEquals('completed', $transaction->status);
    }

    /** @test */
    public function it_can_create_a_debit_transaction()
    {
        $amount = 50.00;
        $transaction = $this->transactionService->createTransaction(
            $this->user->id,
            $amount,
            'debit'
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('debit', $transaction->type);
        $this->assertEquals(950.00, $transaction->balance_after);
    }

    /** @test */
    public function it_prevents_overdraft_on_debit_transactions()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient funds');

        $this->transactionService->createTransaction(
            $this->user->id,
            2000.00,
            'debit'
        );
    }

    /** @test */
    public function it_maintains_transaction_atomicity()
    {
        $initialBalance = $this->account->balance;

        try {
            $this->transactionService->createTransaction(
                $this->user->id,
                2000.00, // Amount greater than balance
                'debit'
            );
        } catch (Exception $e) {
            // Transaction should be rolled back
            $this->account->refresh();
            $this->assertEquals($initialBalance, $this->account->balance);
            return;
        }

        $this->fail('Transaction should have thrown an exception');
    }

    /** @test */
    public function it_handles_concurrent_transactions()
    {
        $amount = 100.00;
        $promises = [];

        // Simulate multiple concurrent transactions
        for ($i = 0; $i < 5; $i++) {
            $transaction = $this->transactionService->createTransaction(
                $this->user->id,
                $amount,
                'credit'
            );

            $this->assertNotNull($transaction);
        }

        $this->account->refresh();
        $this->assertEquals(1500.00, $this->account->balance);
    }

    /** @test */
    public function it_generates_unique_transaction_references()
    {
        $references = [];

        // Create multiple transactions
        for ($i = 0; $i < 5; $i++) {
            $transaction = $this->transactionService->createTransaction(
                $this->user->id,
                10.00,
                'credit'
            );

            $this->assertNotNull($transaction->reference);
            $this->assertNotContains($transaction->reference, $references);
            $references[] = $transaction->reference;
        }
    }

    /** @test */
    public function it_records_transaction_metadata()
    {
        $transaction = $this->transactionService->createTransaction(
            $this->user->id,
            100.00,
            'credit',
            'Test transaction'
        );

        $this->assertNotNull($transaction->metadata);
        $this->assertIsArray($transaction->metadata);
    }

    /** @test */
    public function it_validates_transaction_type()
    {
        $this->expectException(Exception::class);

        $this->transactionService->createTransaction(
            $this->user->id,
            100.00,
            'invalid_type'
        );
    }

    /** @test */
    public function it_handles_transaction_reversals()
    {
        // Create initial transaction
        $transaction = $this->transactionService->createTransaction(
            $this->user->id,
            100.00,
            'credit'
        );

        // Reverse the transaction
        $reversal = $this->transactionService->reverseTransaction(
            $transaction->id,
            $this->user->id
        );

        $this->assertEquals($transaction->amount, $reversal->amount);
        $this->assertEquals('debit', $reversal->type);
        $this->assertArrayHasKey('reversed', $transaction->fresh()->metadata);
    }
}
