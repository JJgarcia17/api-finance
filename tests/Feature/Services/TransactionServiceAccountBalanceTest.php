<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Transaction\TransactionService;
use App\Services\Account\AccountBalanceService;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\Account\AccountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class TransactionServiceAccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;
    private User $user;
    private Account $account;
    private Category $incomeCategory;
    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => 1000.00,
            'current_balance' => 1000.00
        ]);
        $this->incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);
        $this->expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense'
        ]);

        // Set up the service with dependencies
        $transactionRepository = new TransactionRepository(new Transaction());
        $accountRepository = new AccountRepository(new Account());
        $accountBalanceService = new AccountBalanceService($accountRepository);
        $this->transactionService = new TransactionService($transactionRepository, $accountBalanceService);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    public function test_creating_income_transaction_increases_account_balance()
    {
        // Arrange
        $initialBalance = $this->account->current_balance;
        $transactionAmount = 500.00;

        $transactionData = [
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => $transactionAmount,
            'description' => 'Test income',
            'transaction_date' => now()->format('Y-m-d')
        ];

        // Act
        $transaction = $this->transactionService->createTransaction($transactionData);

        // Assert
        $this->account->refresh();
        $expectedBalance = $initialBalance + $transactionAmount;
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($transactionAmount, $transaction->amount);
    }

    public function test_creating_expense_transaction_decreases_account_balance()
    {
        // Arrange
        $initialBalance = $this->account->current_balance;
        $transactionAmount = 300.00;

        $transactionData = [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => $transactionAmount,
            'description' => 'Test expense',
            'transaction_date' => now()->format('Y-m-d')
        ];

        // Act
        $transaction = $this->transactionService->createTransaction($transactionData);

        // Assert
        $this->account->refresh();
        $expectedBalance = $initialBalance - $transactionAmount;
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($transactionAmount, $transaction->amount);
    }

    public function test_updating_transaction_adjusts_account_balance_correctly()
    {
        // Arrange - Create initial transaction
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 200.00
        ]);

        // Manually update account balance to simulate the transaction being processed
        $this->account->update(['current_balance' => 1200.00]); // 1000 + 200

        $updateData = [
            'amount' => 500.00, // Changed from 200 to 500
            'type' => 'income'
        ];

        // Act
        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $updateData);

        // Assert
        $this->account->refresh();
        $expectedBalance = 1500.00; // 1000 (initial) + 500 (new amount)
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertEquals(500.00, $updatedTransaction->amount);
    }

    public function test_updating_transaction_type_adjusts_account_balance_correctly()
    {
        // Arrange - Create initial income transaction
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 300.00
        ]);

        // Manually update account balance to simulate the transaction being processed
        $this->account->update(['current_balance' => 1300.00]); // 1000 + 300

        $updateData = [
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 300.00
        ];

        // Act
        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $updateData);

        // Assert
        $this->account->refresh();
        $expectedBalance = 700.00; // 1000 (initial) - 300 (now expense)
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertEquals('expense', $updatedTransaction->type);
    }

    public function test_deleting_transaction_reverts_account_balance()
    {
        // Arrange - Create transaction
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 400.00
        ]);

        // Manually update account balance to simulate the transaction being processed
        $this->account->update(['current_balance' => 1400.00]); // 1000 + 400

        // Act
        $result = $this->transactionService->deleteTransaction($transaction);

        // Assert
        $this->assertTrue($result);
        $this->account->refresh();
        $expectedBalance = 1000.00; // Back to initial balance
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_restoring_transaction_reapplies_account_balance()
    {
        // Arrange - Create and delete transaction
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 350.00
        ]);

        $transaction->delete();

        // Act
        $restoredTransaction = $this->transactionService->restoreTransaction($transaction->id, $this->user->id);

        // Assert
        $this->account->refresh();
        $expectedBalance = 1350.00; // 1000 + 350
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
        $this->assertNotNull($restoredTransaction);
        $this->assertNull($restoredTransaction->deleted_at);
    }

    public function test_multiple_transactions_cumulative_balance_effect()
    {
        // Arrange
        $initialBalance = $this->account->current_balance;

        // Act - Create multiple transactions
        $this->transactionService->createTransaction([
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Income 1',
            'transaction_date' => now()->format('Y-m-d')
        ]);

        $this->transactionService->createTransaction([
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 50.00,
            'description' => 'Expense 1',
            'transaction_date' => now()->format('Y-m-d')
        ]);

        $this->transactionService->createTransaction([
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 200.00,
            'description' => 'Income 2',
            'transaction_date' => now()->format('Y-m-d')
        ]);

        // Assert
        $this->account->refresh();
        $expectedBalance = $initialBalance + 100.00 - 50.00 + 200.00; // 1250.00
        
        $this->assertEquals($expectedBalance, $this->account->current_balance);
    }
}
