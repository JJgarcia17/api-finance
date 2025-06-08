<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Repositories\Transaction\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class TransactionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TransactionRepository $repository;
    private User $user;
    private Account $account;
    private Category $incomeCategory;
    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionRepository(new Transaction());
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_id' => $this->user->id]);
        $this->incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);
        $this->expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense'
        ]);
    }

    public function test_find_by_id_for_user_or_fail_success()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);

        // Act
        $found = $this->repository->findByIdForUserOrFail($transaction->id, $this->user->id);

        // Assert
        $this->assertEquals($transaction->id, $found->id);
        $this->assertEquals($transaction->user_id, $found->user_id);
    }

    public function test_find_by_id_for_user_or_fail_throws_exception_when_not_found()
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->findByIdForUserOrFail(999, $this->user->id);
    }

    public function test_find_by_id_for_user_or_fail_throws_exception_when_different_user()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id
        ]);

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->findByIdForUserOrFail($transaction->id, $this->user->id);
    }

    public function test_get_for_user_returns_all_transactions()
    {
        // Arrange
        Transaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);
        Transaction::factory()->count(2)->create(); // Other user's transactions

        // Act
        $transactions = $this->repository->getForUser($this->user->id);

        // Assert
        $this->assertCount(3, $transactions);
        $this->assertTrue($transactions->every(fn($transaction) => $transaction->user_id === $this->user->id));
    }

    public function test_get_for_user_with_type_filter()
    {
        // Arrange
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income'
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense'
        ]);

        // Act
        $incomeTransactions = $this->repository->getForUser($this->user->id, 'income');
        $expenseTransactions = $this->repository->getForUser($this->user->id, 'expense');

        // Assert
        $this->assertCount(1, $incomeTransactions);
        $this->assertEquals('income', $incomeTransactions->first()->type);
        $this->assertCount(1, $expenseTransactions);
        $this->assertEquals('expense', $expenseTransactions->first()->type);
    }

    public function test_get_for_user_with_account_filter()
    {
        // Arrange
        $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $otherAccount->id,
            'category_id' => $this->incomeCategory->id
        ]);

        // Act
        $transactions = $this->repository->getForUser($this->user->id, null, $this->account->id);

        // Assert
        $this->assertCount(1, $transactions);
        $this->assertEquals($this->account->id, $transactions->first()->account_id);
    }

    public function test_get_for_user_with_category_filter()
    {
        // Arrange
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id
        ]);

        // Act
        $transactions = $this->repository->getForUser($this->user->id, null, null, $this->incomeCategory->id);

        // Assert
        $this->assertCount(1, $transactions);
        $this->assertEquals($this->incomeCategory->id, $transactions->first()->category_id);
    }

    public function test_get_for_user_with_date_range_filter()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(10)->format('Y-m-d');
        $endDate = Carbon::now()->subDays(5)->format('Y-m-d');
        $withinRange = Carbon::now()->subDays(7)->format('Y-m-d');
        $outsideRange = Carbon::now()->subDays(15)->format('Y-m-d');

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'transaction_date' => $withinRange
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'transaction_date' => $outsideRange
        ]);

        // Act
        $transactions = $this->repository->getForUser(
            $this->user->id,
            null,
            null,
            null,
            $startDate,
            $endDate
        );

        // Assert
        $this->assertCount(1, $transactions);
        $this->assertEquals($withinRange, $transactions->first()->transaction_date->format('Y-m-d'));
    }

    public function test_get_for_user_with_pagination()
    {
        // Arrange
        Transaction::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);

        // Act
        $transactions = $this->repository->getForUser($this->user->id, null, null, null, null, null, 'transaction_date', 'desc', 10);

        // Assert
        $this->assertEquals(10, $transactions->perPage());
        $this->assertEquals(15, $transactions->total());
        $this->assertEquals(2, $transactions->lastPage());
    }

    public function test_create_transaction()
    {
        // Arrange
        $data = [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 1000.50,
            'description' => 'Test transaction',
            'transaction_date' => Carbon::now()->format('Y-m-d')
        ];
    
        // Act
        $transaction = $this->repository->create($data);
    
        // Assert
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($data['amount'], $transaction->amount);
        $this->assertEquals($data['description'], $transaction->description);
        
        // Modificar la aserción para incluir el formato datetime completo
        $expectedData = $data;
        $expectedData['transaction_date'] = Carbon::parse($data['transaction_date'])->format('Y-m-d H:i:s');
        $this->assertDatabaseHas('transactions', $expectedData);
    }

    public function test_update_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);
        $updateData = [
            'amount' => 2000.75,
            'description' => 'Updated description'
        ];

        // Act
        $updated = $this->repository->update($transaction, $updateData);

        // Assert
        $this->assertEquals($updateData['amount'], $updated->amount);
        $this->assertEquals($updateData['description'], $updated->description);
        $this->assertDatabaseHas('transactions', array_merge($updateData, ['id' => $transaction->id]));
    }

    public function test_delete_transaction_soft_delete()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);

        // Act
        $result = $this->repository->delete($transaction);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_restore_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id
        ]);
        $transaction->delete();

        // Act
        $restored = $this->repository->restore($transaction->id, $this->user->id);

        // Assert
        $this->assertInstanceOf(Transaction::class, $restored);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);
    }

    public function test_get_stats_returns_correct_data()
    {
        // Arrange
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Current month transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 1000,
            'transaction_date' => $currentMonth->format('Y-m-d')
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 500,
            'transaction_date' => $currentMonth->format('Y-m-d')
        ]);

        // Last month transaction (should not be included in current month stats)
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 2000,
            'transaction_date' => $lastMonth->format('Y-m-d')
        ]);

        // Act
        $stats = $this->repository->getStats($this->user->id);

        // Assert
        $this->assertEquals(3, $stats['total_transactions']);
        $this->assertEquals(3000, $stats['total_income']); // 1000 + 2000
        $this->assertEquals(500, $stats['total_expenses']);
        $this->assertEquals(2500, $stats['net_balance']); // 3000 - 500
        $this->assertEquals(2, $stats['current_month_transactions']);
        $this->assertEquals(1000, $stats['current_month_income']);
        $this->assertEquals(500, $stats['current_month_expenses']);
    }

    public function test_get_stats_with_no_transactions()
    {
        // Act
        $stats = $this->repository->getStats($this->user->id);

        // Assert
        $this->assertEquals(0, $stats['total_transactions']);
        $this->assertEquals(0, $stats['total_income']);
        $this->assertEquals(0, $stats['total_expenses']);
        $this->assertEquals(0, $stats['net_balance']);
        $this->assertEquals(0, $stats['current_month_transactions']);
        $this->assertEquals(0, $stats['current_month_income']);
        $this->assertEquals(0, $stats['current_month_expenses']);
    }
}