<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Account ' . uniqid()
        ]);
        $this->category = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Category ' . uniqid()
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_get_all_transactions()
    {
        // Arrange
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'type' => 'income'
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 500.00,
            'type' => 'expense'
        ]);
        Transaction::factory()->count(2)->create(); // Other user's transactions

        // Act
        $response = $this->getJson('/api/v1/transactions');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'amount',
                            'type',
                            'description',
                            'transaction_date',
                            'account',
                            'category',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
        
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_create_transaction()
    {
        // Arrange
        $transactionData = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1500.00,
            'type' => 'income',
            'description' => 'Salary payment',
            'transaction_date' => '2024-01-15'
        ];

        // Act
        $response = $this->postJson('/api/v1/transactions', $transactionData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'amount',
                    'type',
                    'description',
                    'transaction_date',
                    'account',
                    'category'
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1500.00,
            'type' => 'income',
            'description' => 'Salary payment'
        ]);
    }

    public function test_show_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);

        // Act
        $response = $this->getJson("/api/v1/transactions/{$transaction->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'amount',
                    'type',
                    'description',
                    'transaction_date',
                    'account',
                    'category'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount
                ]
            ]);
    }

    public function test_update_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00
        ]);

        $updateData = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1200.00,
            'description' => 'Updated description'
        ];

        // Act
        $response = $this->putJson("/api/v1/transactions/{$transaction->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $transaction->id,
                    'amount' => 1200.00,
                    'description' => 'Updated description'
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 1200.00,
            'description' => 'Updated description'
        ]);
    }

    public function test_delete_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);
    
        // Act
        $response = $this->deleteJson("/api/v1/transactions/{$transaction->id}");
    
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transacción eliminada exitosamente' // Updated to Spanish
            ]);
    
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_restore_transaction()
    {
        // Arrange
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);
        $transaction->delete();

        // Act
        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/restore");

        // Debug: Ver el contenido de la respuesta si falla
        if ($response->status() !== 200) {
            dd($response->getContent(), $response->status());
        }

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaction restored successfully'
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'deleted_at' => null
        ]);
    }

    public function test_get_transaction_stats()
    {
        
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'type' => 'income'
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 500.00,
            'type' => 'expense'
        ]);

        // Act
        $response = $this->getJson('/api/v1/transactions/stats');


        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_transactions',
                    'total_income',
                    'total_expenses',
                    'net_balance',
                    'current_month_transactions',
                    'current_month_income',
                    'current_month_expenses'
                ],
                'message',
            ]);
    }

    public function test_cannot_access_other_user_transaction()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $otherTransaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id
        ]);

        // Act
        $response = $this->getJson("/api/v1/transactions/{$otherTransaction->id}");

        // Assert
        $response->assertStatus(404);
    }

    public function test_validation_errors_on_create()
    {
        // Act
        $response = $this->postJson('/api/v1/transactions', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'account_id',
                'category_id',
                'amount',
                'type',
                'transaction_date'
            ]);
    }
}