<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_paginated_accounts()
    {
        // Create accounts for the user
        Account::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'name' => fn() => 'Account ' . uniqid()
        ]);
        
        // Create accounts for another user
        $otherUser = User::factory()->create();
        Account::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'name' => fn() => 'Other Account ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'accounts' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'currency',
                            'current_balance',
                            'is_active'
                        ]
                    ],
                    // Remove 'summary' from required structure since it's conditional
                ],
                'message',
                'status'
            ])
            ->assertJsonCount(3, 'data.accounts')
            // Optionally check if summary exists when accounts are present
            ->assertJsonPath('data.summary.total_accounts', 3);
    }

    public function test_show_returns_account_when_belongs_to_user()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Account ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $account->id,
                    'name' => $account->name
                ],
                'message' => 'Cuenta obtenida exitosamente',
                'status' => 200
            ]);
    }

    public function test_show_returns_404_when_account_not_belongs_to_user()
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Account ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(404);
    }

    public function test_store_creates_new_account()
    {
        $accountData = [
            'name' => 'New Account',
            'type' => 'bank',
            'currency' => 'USD',
            'initial_balance' => 1000.00,
            'color' => '#FF0000',
            'icon' => 'bank',
            'description' => 'Test account'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounts', $accountData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => $accountData['name'],
                    'type' => $accountData['type']
                ],
                'message' => 'Cuenta creada exitosamente',
                'status' => 201
            ]);

        $this->assertDatabaseHas('accounts', [
            'name' => $accountData['name'],
            'user_id' => $this->user->id
        ]);
    }

    public function test_update_updates_existing_account()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Account ' . uniqid()
        ]);

        $updateData = [
            'name' => 'Updated Account',
            'current_balance' => 2000.00
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/accounts/{$account->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $account->id,
                    'name' => $updateData['name']
                ],
                'message' => 'Cuenta actualizada exitosamente',
                'status' => 200
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => $updateData['name']
        ]);
    }

    public function test_destroy_deletes_account()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Account to Delete ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente',
                'status' => 200
            ]);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_get_active_returns_only_active_accounts()
    {
        // Create active accounts
        Account::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'name' => fn() => 'Active Account ' . uniqid()
        ]);
        
        // Create inactive account
        Account::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
            'name' => 'Inactive Account ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.accounts');
        
        // Verify all returned accounts are active
        $accounts = $response->json('data.accounts');
        foreach ($accounts as $account) {
            $this->assertTrue($account['is_active']);
        }
    }

    public function test_get_stats_returns_user_statistics()
    {
        // Create accounts with different balances
        Account::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 1000.00,
            'is_active' => true,
            'include_in_total' => true,
            'name' => 'Stats Account 1 ' . uniqid()
        ]);
        
        Account::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 2000.00,
            'is_active' => true,
            'include_in_total' => true,
            'name' => 'Stats Account 2 ' . uniqid()
        ]);
        
        Account::factory()->create([
            'user_id' => $this->user->id,
            'current_balance' => 500.00,
            'is_active' => false,
            'include_in_total' => true,
            'name' => 'Stats Account 3 ' . uniqid()
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_accounts' => 3,
                    'active_accounts' => 2,
                    'total_balance' => 3500.00
                ],
                'message' => 'Estadísticas obtenidas exitosamente',
                'status' => 200
            ]);
    }

    public function test_unauthorized_access_returns_401()
    {
        $response = $this->getJson('/api/v1/accounts');
        $response->assertStatus(401);
    }
}