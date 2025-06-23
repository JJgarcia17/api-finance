<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Services\Transaction\TransactionService;
use App\Services\Account\AccountBalanceService;
use App\Repositories\Transaction\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Carbon\Carbon;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;
    private $mockRepository;
    private $mockAccountBalanceService;
    private User $user;
    private Account $account;
    private Category $incomeCategory;
    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(TransactionRepository::class);
        $this->mockAccountBalanceService = Mockery::mock(AccountBalanceService::class);
        $this->service = new TransactionService($this->mockRepository, $this->mockAccountBalanceService);
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Account ' . uniqid()
        ]);
        $this->incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'name' => 'Income Category ' . uniqid()
        ]);
        $this->expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'name' => 'Expense Category ' . uniqid()
        ]);
        
        // Mock authentication for all tests
        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();
        Auth::shouldReceive('user')
            ->andReturn($this->user);
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        Auth::shouldReceive('check')
            ->andReturn(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_transaction_for_user_success()
    {
        // Arrange
        $transaction = Transaction::factory()->make([
            'id' => 1,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);

        $this->mockRepository
            ->shouldReceive('findByIdForUserOrFail')
            ->once()
            ->with(1, $this->user->id)
            ->andReturn($transaction);

        // Act
        $result = $this->service->getTransactionForUser(1, $this->user->id);

        // Assert
        $this->assertEquals($transaction, $result);
    }

    public function test_get_transaction_for_user_throws_exception_when_not_found()
    {
        // Arrange
        $this->mockRepository
            ->shouldReceive('findByIdForUserOrFail')
            ->once()
            ->with(999, $this->user->id)
            ->andThrow(new ModelNotFoundException());

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->service->getTransactionForUser(999, $this->user->id);
    }

    public function test_get_transactions_for_user_with_filters()
    {
        // Arrange
        $transactions = new \Illuminate\Database\Eloquent\Collection([
            Transaction::factory()->make(['user_id' => $this->user->id]),
            Transaction::factory()->make(['user_id' => $this->user->id])
        ]);

        $filters = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'sort_by' => 'transaction_date',
            'sort_direction' => 'desc',
            'per_page' => 10
        ];

        $this->mockRepository
            ->shouldReceive('getForUser')
            ->once()
            ->with(
                $this->user->id,
                'income',
                $this->account->id,
                $this->incomeCategory->id,
                '2024-01-01',
                '2024-12-31',
                'transaction_date',
                'desc',
                10
            )
            ->andReturn($transactions);

        // Act
        $result = $this->service->getTransactionsForUser(
            $this->user->id,
            $filters['type'],
            $filters['account_id'],
            $filters['category_id'],
            $filters['start_date'],
            $filters['end_date'],
            $filters['sort_by'],
            $filters['sort_direction'],
            $filters['per_page']
        );

        // Assert
        $this->assertEquals($transactions, $result);
    }

    public function test_create_transaction()
    {
        // Arrange
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 1000.00,
            'description' => 'Test transaction',
            'transaction_date' => '2024-01-15'
        ];

        $expectedData = array_merge($data, ['user_id' => $this->user->id]);
        $transaction = Transaction::factory()->make($expectedData);

        // Mock the transaction's load method to simulate loading the account relationship
        $mockTransaction = Mockery::mock(Transaction::class);
        $mockTransaction->shouldReceive('load')
            ->once()
            ->with('account')
            ->andReturnSelf();
        
        // Mock setAttribute calls for property assignments
        $mockTransaction->shouldReceive('setAttribute')->andReturn(null);
        $mockTransaction->shouldReceive('getAttribute')->andReturn(null);
        
        $mockTransaction->id = 1;
        $mockTransaction->account_id = $this->account->id;
        $mockTransaction->amount = 1000.00;
        $mockTransaction->type = 'income';

        // Mock DB transactions - allowing multiple calls
        DB::shouldReceive('beginTransaction')->andReturn(true);
        DB::shouldReceive('commit')->andReturn(true);
        DB::shouldReceive('rollBack')->andReturn(true);

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($mockTransaction);

        $this->mockAccountBalanceService
            ->shouldReceive('updateBalanceForTransaction')
            ->once()
            ->with($mockTransaction);

        // Act
        $result = $this->service->createTransaction($data);

        // Assert
        $this->assertEquals($mockTransaction, $result);
    }

    public function test_update_transaction()
    {
        // Arrange
        $transaction = Mockery::mock(Transaction::class);
        $transaction->shouldReceive('setAttribute')->andReturn(null);
        $transaction->shouldReceive('getAttribute')
            ->with('id')->andReturn(1);
        $transaction->shouldReceive('getAttribute')
            ->with('user_id')->andReturn($this->user->id);
        $transaction->shouldReceive('getAttribute')
            ->with('account_id')->andReturn($this->account->id);
        $transaction->shouldReceive('getAttribute')
            ->with('category_id')->andReturn($this->incomeCategory->id);
        $transaction->shouldReceive('getAttribute')
            ->with('amount')->andReturn(1000.00);
        $transaction->shouldReceive('getAttribute')
            ->with('type')->andReturn('income');

        $updateData = [
            'amount' => 1500.00,
            'description' => 'Updated description'
        ];

        // Create a replica mock for the original transaction
        $originalTransaction = Mockery::mock(Transaction::class);
        $originalTransaction->shouldReceive('load')
            ->once()
            ->with('account')
            ->andReturnSelf();
        $originalTransaction->shouldReceive('getAttribute')
            ->with('amount')->andReturn(1000.00);
        $originalTransaction->shouldReceive('getAttribute')
            ->with('type')->andReturn('income');
        $originalTransaction->shouldReceive('getAttribute')
            ->with('destination_account_id')->andReturn(null);

        $transaction->shouldReceive('replicate')
            ->once()
            ->andReturn($originalTransaction);

        // Create updated transaction mock
        $updatedTransaction = Mockery::mock(Transaction::class);
        $updatedTransaction->shouldReceive('setAttribute')->andReturn(null);
        $updatedTransaction->shouldReceive('getAttribute')
            ->with('id')->andReturn(1);
        $updatedTransaction->shouldReceive('getAttribute')
            ->with('account_id')->andReturn($this->account->id);
        $updatedTransaction->shouldReceive('getAttribute')
            ->with('destination_account_id')->andReturn(null);
        $updatedTransaction->shouldReceive('getAttribute')
            ->with('amount')->andReturn(1500.00);
        $updatedTransaction->shouldReceive('getAttribute')
            ->with('type')->andReturn('income');
        $updatedTransaction->shouldReceive('load')
            ->once()
            ->with('account')
            ->andReturnSelf();

        // Mock DB transactions - allowing multiple calls
        DB::shouldReceive('beginTransaction')->andReturn(true);
        DB::shouldReceive('commit')->andReturn(true);
        DB::shouldReceive('rollBack')->andReturn(true);

        $this->mockAccountBalanceService
            ->shouldReceive('revertBalanceForTransaction')
            ->once()
            ->with($originalTransaction);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($transaction, $updateData)
            ->andReturn($updatedTransaction);

        $this->mockAccountBalanceService
            ->shouldReceive('updateBalanceForTransaction')
            ->once()
            ->with($updatedTransaction);

        // Act
        $result = $this->service->updateTransaction($transaction, $updateData);

        // Assert
        $this->assertEquals($updatedTransaction, $result);
    }

    public function test_delete_transaction()
    {
        // Arrange
        $transaction = Mockery::mock(Transaction::class);
        $transaction->shouldReceive('setAttribute')->andReturn(null);
        $transaction->shouldReceive('getAttribute')->andReturn(null);
        $transaction->id = 1;
        $transaction->user_id = $this->user->id;
        $transaction->account_id = $this->account->id;
        $transaction->category_id = $this->incomeCategory->id;
        $transaction->amount = 1000.00;
        $transaction->type = 'income';

        $transaction->shouldReceive('load')
            ->once()
            ->with('account')
            ->andReturnSelf();

        // Mock DB transactions - allowing multiple calls
        DB::shouldReceive('beginTransaction')->andReturn(true);
        DB::shouldReceive('commit')->andReturn(true);
        DB::shouldReceive('rollBack')->andReturn(true);

        $this->mockAccountBalanceService
            ->shouldReceive('revertBalanceForDeletedTransaction')
            ->once()
            ->with($transaction);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with($transaction)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteTransaction($transaction);

        // Assert
        $this->assertTrue($result);
    }

    public function test_restore_transaction()
    {
        // Arrange
        $transaction = Mockery::mock(Transaction::class);
        $transaction->shouldReceive('setAttribute')->andReturn(null);
        $transaction->shouldReceive('getAttribute')->andReturn(null);
        $transaction->id = 1;
        $transaction->user_id = $this->user->id;
        $transaction->account_id = $this->account->id;
        $transaction->category_id = $this->incomeCategory->id;
        $transaction->amount = 1000.00;
        $transaction->type = 'income';

        $transaction->shouldReceive('load')
            ->once()
            ->with('account')
            ->andReturnSelf();

        // Mock DB transactions - allowing multiple calls
        DB::shouldReceive('beginTransaction')->andReturn(true);
        DB::shouldReceive('commit')->andReturn(true);
        DB::shouldReceive('rollBack')->andReturn(true);

        $this->mockRepository
            ->shouldReceive('restore')
            ->once()
            ->with(1, $this->user->id)
            ->andReturn($transaction);

        $this->mockAccountBalanceService
            ->shouldReceive('updateBalanceForRestoredTransaction')
            ->once()
            ->with($transaction);

        // Act
        $result = $this->service->restoreTransaction(1, $this->user->id);

        // Assert
        $this->assertEquals($transaction, $result);
    }

    public function test_get_transaction_stats()
    {
        // Arrange
        $expectedStats = [
            'total_transactions' => 10,
            'total_income' => 5000.00,
            'total_expenses' => 3000.00,
            'net_balance' => 2000.00,
            'current_month_transactions' => 5,
            'current_month_income' => 2000.00,
            'current_month_expenses' => 1000.00
        ];

        $this->mockRepository
            ->shouldReceive('getStats')
            ->once()
            ->with($this->user->id)
            ->andReturn($expectedStats);

        // Act
        $result = $this->service->getTransactionStats($this->user->id);

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    public function test_get_transactions_for_user_with_default_filters()
    {
        // Arrange
        $transactions = new \Illuminate\Database\Eloquent\Collection([]);

        $this->mockRepository
            ->shouldReceive('getForUser')
            ->once()
            ->with(
                $this->user->id,
                null, // type
                null, // account_id
                null, // category_id
                null, // start_date
                null, // end_date
                'transaction_date', // sort_by
                'desc', // sort_direction
                null // per_page
            )
            ->andReturn($transactions);

        // Act
        $result = $this->service->getTransactionsForUser(
            $this->user->id,
            null, // type
            null, // account_id
            null, // category_id
            null, // start_date
            null, // end_date
            'transaction_date', // sort_by
            'desc', // sort_direction
            null // per_page
        );

        // Assert
        $this->assertEquals($transactions, $result);
    }
}