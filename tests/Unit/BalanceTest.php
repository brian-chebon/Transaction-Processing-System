<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Exception;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    protected BalanceService $balanceService;
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
        $this->balanceService = new BalanceService(
            new AccountRepository(),
            new TransactionRepository()
        );
    }

    /** @test */
    public function it_can_get_current_balance()
    {
        $balance = $this->balanceService->getCurrentBalance($this->user->id);

        $this->assertEquals(1000.00, $balance);
    }

    /** @test */
    public function it_caches_balance_queries()
    {
        // First call should cache the result
        $balance1 = $this->balanceService->getCurrentBalance($this->user->id);

        // Modify balance directly in database
        $this->account->update(['balance' => 2000.00]);

        // Second call should return cached value
        $balance2 = $this->balanceService->getCurrentBalance($this->user->id);

        $this->assertEquals($balance1, $balance2);
        $this->assertEquals(1000.00, $balance2);
    }

    /** @test */
    public function it_invalidates_balance_cache()
    {
        // Get initial balance (cached)
        $balance1 = $this->balanceService->getCurrentBalance($this->user->id);

        // Modify balance and invalidate cache
        $this->account->update(['balance' => 2000.00]);
        $this->balanceService->invalidateBalanceCache($this->user->id);

        // Get new balance
        $balance2 = $this->balanceService->getCurrentBalance($this->user->id);

        $this->assertNotEquals($balance1, $balance2);
        $this->assertEquals(2000.00, $balance2);
    }

    /** @test */
    public function it_provides_detailed_balance_information()
    {
        // Create some pending transactions
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

        $details = $this->balanceService->getBalanceDetails($this->user->id);

        $this->assertArrayHasKey('current_balance', $details);
        $this->assertArrayHasKey('available_balance', $details);
        $this->assertArrayHasKey('pending_credits', $details);
        $this->assertArrayHasKey('pending_debits', $details);
        $this->assertEquals(100.00, $details['pending_credits']);
        $this->assertEquals(50.00, $details['pending_debits']);
    }

    /** @test */
    public function it_handles_balance_history()
    {
        // Create some historical transactions
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'credit',
            'status' => 'completed',
            'created_at' => now()->subDays(2)
        ]);

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 50.00,
            'type' => 'debit',
            'status' => 'completed',
            'created_at' => now()->subDay()
        ]);

        $history = $this->balanceService->getBalanceHistory(
            $this->user->id,
            now()->subDays(3)->toDateString(),
            now()->toDateString()
        );

        $this->assertIsArray($history);
        $this->assertCount(2, $history);
    }

    /** @test */
    public function it_calculates_balance_metrics()
    {
        // Create test transactions
        Transaction::factory()->count(5)->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'type' => 'credit',
            'status' => 'completed'
        ]);

        Transaction::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'amount' => 50.00,
            'type' => 'debit',
            'status' => 'completed'
        ]);

        $metrics = $this->balanceService->getBalanceMetrics($this->user->id);

        $this->assertArrayHasKey('total_credits', $metrics);
        $this->assertArrayHasKey('total_debits', $metrics);
        $this->assertArrayHasKey('average_balance', $metrics);
        $this->assertEquals(500.00, $metrics['total_credits']);
        $this->assertEquals(150.00, $metrics['total_debits']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_account()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Account not found');

        $this->balanceService->getCurrentBalance(999);
    }

    /** @test */
    public function it_handles_zero_balance()
    {
        $this->account->update(['balance' => 0.00]);

        $balance = $this->balanceService->getCurrentBalance($this->user->id);

        $this->assertEquals(0.00, $balance);
        $this->assertIsFloat($balance);
    }
}
