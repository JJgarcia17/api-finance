<?php

namespace Tests\Unit\Repositories;

use App\Models\Account;
use App\Models\User;
use App\Repositories\Account\AccountRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AccountRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AccountRepository();
        $this->user = User::factory()->create();
    }

    public function test_get_all_for_user_returns_user_accounts()
    {
        // Create accounts for the user with unique names
        $userAccounts = Account::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'name' => fn() => 'User Account ' . uniqid()
        ]);
        
        // Create accounts for another user
        $otherUser = User::factory()->create();
        Account::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'name' => fn() => 'Other Account ' . uniqid()
        ]);

        $result = $this->repository->getAllForUser($this->user->id);

        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($account) => $account->user_id === $this->user->id));
    }

    public function test_get_active_for_user_returns_only_active_accounts()
    {
        // Create active accounts with unique names
        Account::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'name' => fn() => 'Active Account ' . uniqid()
        ]);
        
        // Create inactive accounts with unique names
        Account::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'is_active' => false,
            'name' => fn() => 'Inactive Account ' . uniqid()
        ]);

        $result = $this->repository->getActiveForUser($this->user->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($account) => $account->is_active === true));
    }

    public function test_find_for_user_returns_account_when_belongs_to_user()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);

        $result = $this->repository->findForUser($account->id, $this->user->id);

        $this->assertNotNull($result);
        $this->assertEquals($account->id, $result->id);
    }

    public function test_find_for_user_throws_exception_when_not_belongs_to_user()
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findForUser($account->id, $this->user->id);
    }

    public function test_create_creates_new_account()
    {
        $accountData = [
            'name' => 'Test Account',
            'type' => 'bank', // Usar tipo válido
            'currency' => 'USD',
            'initial_balance' => 1000.00, // Usar columna correcta
            'current_balance' => 1000.00, // Usar columna correcta
            'color' => '#FF0000',
            'description' => 'Test description',
            'user_id' => $this->user->id
        ];

        $result = $this->repository->create($accountData);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($accountData['name'], $result->name);
        $this->assertEquals($accountData['user_id'], $result->user_id);
        $this->assertDatabaseHas('accounts', $accountData);
    }

    public function test_update_updates_existing_account()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);
        $updateData = [
            'name' => 'Updated Account',
            'current_balance' => 2000.00 // Usar columna correcta
        ];

        $result = $this->repository->update($account, $updateData);

        $this->assertInstanceOf(Account::class, $result); // Retorna el modelo, no booleano
        $this->assertEquals($updateData['name'], $result->name);
        $this->assertEquals($updateData['current_balance'], $result->current_balance);
    }

    public function test_delete_soft_deletes_account()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);

        $result = $this->repository->delete($account);

        $this->assertTrue($result);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_restore_restores_soft_deleted_account()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);
        $account->delete(); // Soft delete

        $result = $this->repository->restore($account);

        $this->assertTrue($result);
        $account->refresh();
        $this->assertNull($account->deleted_at);
    }

    public function test_force_delete_permanently_deletes_account()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);
        $accountId = $account->id;

        $result = $this->repository->forceDelete($account);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
    }

    public function test_name_exists_for_user_returns_true_when_name_exists()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Existing Account'
        ]);

        $result = $this->repository->nameExistsForUser('Existing Account', $this->user->id);

        $this->assertTrue($result);
    }

    public function test_name_exists_for_user_returns_false_when_name_does_not_exist()
    {
        $result = $this->repository->nameExistsForUser('Non-existing Account', $this->user->id);

        $this->assertFalse($result);
    }

    public function test_name_exists_for_user_excludes_specified_account()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Account'
        ]);

        $result = $this->repository->nameExistsForUser('Test Account', $this->user->id, $account->id);

        $this->assertFalse($result);
    }

    public function test_get_stats_for_user_returns_correct_statistics()
    {
        // Create accounts with different types and balances with unique names
        Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'bank',
            'name' => 'Bank Account ' . uniqid(),
            'current_balance' => 1000.00,
            'is_active' => true,
            'include_in_total' => true  // Asegurar que se incluya en el total
        ]);
        
        Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'savings',
            'name' => 'Savings Account ' . uniqid(),
            'current_balance' => 2000.00,
            'is_active' => true,
            'include_in_total' => true  // Asegurar que se incluya en el total
        ]);
        
        Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'cash',
            'name' => 'Cash Account ' . uniqid(),
            'current_balance' => 500.00,
            'is_active' => false,
            'include_in_total' => true  // Asegurar que se incluya en el total
        ]);
    
        $result = $this->repository->getStatsForUser($this->user->id);
    
        $this->assertEquals(3, $result['total_accounts']);
        $this->assertEquals(2, $result['active_accounts']);
        $this->assertEquals(3500.00, $result['total_balance']);
    }
}