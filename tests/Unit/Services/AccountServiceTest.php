<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use App\Repositories\Account\AccountRepository;
use App\Services\Account\AccountService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Mockery;

class AccountServiceTest extends TestCase
{
    private AccountService $service;
    private $mockRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(AccountRepository::class);
        $this->service = new AccountService($this->mockRepository);
        $this->user = User::factory()->make(['id' => 1]);
        
        // Mock authentication - agregar el método check()
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

    public function test_get_accounts_for_user_returns_user_accounts()
    {
        // Crear una Collection de Eloquent en lugar de Support Collection
        $accounts = new Collection([
            Account::factory()->make(['user_id' => $this->user->id]),
            Account::factory()->make(['user_id' => $this->user->id])
        ]);

        $this->mockRepository->shouldReceive('getAllForUser')
            ->once()
            ->with(
                $this->user->id,
                null, // type
                null, // isActive
                null, // includeInTotal
                'name', // sortBy
                'asc', // sortDirection
                null // perPage
            )
            ->andReturn($accounts);

        $result = $this->service->getAccountsForUser($this->user->id);

        $this->assertEquals($accounts, $result);
    }

    public function test_get_active_accounts_for_user_returns_only_active_accounts()
    {
        // Usar Collection de Eloquent
        $activeAccounts = new Collection([
            Account::factory()->make([
                'user_id' => $this->user->id,
                'is_active' => true
            ])
        ]);

        $this->mockRepository->shouldReceive('getActiveForUser')
            ->once()
            ->with($this->user->id)
            ->andReturn($activeAccounts);

        $result = $this->service->getActiveAccountsForUser($this->user->id);

        $this->assertEquals($activeAccounts, $result);
    }

    public function test_get_account_for_user_returns_account_when_found()
    {
        $account = Account::factory()->make(['user_id' => $this->user->id]);
        $accountId = 1;

        $this->mockRepository->shouldReceive('findForUser')
            ->once()
            ->with($accountId, $this->user->id)
            ->andReturn($account);

        $result = $this->service->getAccountForUser($accountId, $this->user->id);

        $this->assertEquals($account, $result);
    }

    public function test_get_account_for_user_throws_exception_when_not_found()
    {
        $accountId = 999;

        $this->mockRepository->shouldReceive('findForUser')
            ->once()
            ->with($accountId, $this->user->id)
            ->andThrow(new ModelNotFoundException());

        $this->expectException(ModelNotFoundException::class);

        $this->service->getAccountForUser($accountId, $this->user->id);
    }

    public function test_create_account_creates_account_with_correct_data()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.00,
            'color' => '#FF5733',
            'icon' => 'bank'
        ];

        $expectedAccount = Account::factory()->make(array_merge($data, [
            'user_id' => $this->user->id,
            'current_balance' => 1000.00,
            'is_active' => true,
            'include_in_total' => true
        ]));

        $this->mockRepository->shouldReceive('nameExistsForUser')
            ->once()
            ->with($data['name'], $this->user->id)
            ->andReturn(false);

        $this->mockRepository->shouldReceive('create')
            ->once()
            ->andReturn($expectedAccount);

        $result = $this->service->createAccount($data);

        $this->assertEquals($expectedAccount, $result);
    }

    public function test_update_account_updates_account_successfully()
    {
        $updateData = [
            'name' => 'Updated Account Name',
            'description' => 'Updated description'
        ];

        $account = Account::factory()->make(['user_id' => $this->user->id]);
        $updatedAccount = Account::factory()->make(array_merge(
            $account->toArray(),
            $updateData
        ));

        $this->mockRepository->shouldReceive('nameExistsForUser')
            ->once()
            ->with($updateData['name'], $account->user_id, $account->id)
            ->andReturn(false);

        $this->mockRepository->shouldReceive('update')
            ->once()
            ->with($account, $updateData)
            ->andReturn($updatedAccount);

        $result = $this->service->updateAccount($account, $updateData);

        $this->assertEquals($updatedAccount, $result);
    }

    public function test_delete_account_soft_deletes_account()
    {
        $account = Mockery::mock(Account::class);
        
        // Mock the getAttribute method to return the user_id when accessed
        $account->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn($this->user->id);
        
        // Create a proper mock for the HasMany relationship
        $transactionsMock = Mockery::mock('Illuminate\Database\Eloquent\Relations\HasMany');
        $transactionsMock->shouldReceive('exists')
            ->once()
            ->andReturn(false);
        
        $account->shouldReceive('transactions')
            ->once()
            ->andReturn($transactionsMock);
    
        $this->mockRepository->shouldReceive('delete')
            ->once()
            ->with($account)
            ->andReturn(true);
    
        $result = $this->service->deleteAccount($account);
    
        $this->assertTrue($result);
    }

    public function test_restore_account_restores_soft_deleted_account()
    {
        $account = Account::factory()->make(['user_id' => $this->user->id]);

        $this->mockRepository->shouldReceive('restore')
            ->once()
            ->with($account)
            ->andReturn(true);

        $result = $this->service->restoreAccount($account);

        $this->assertTrue($result);
    }

    public function test_get_account_stats_returns_correct_statistics()
    {
        $expectedStats = [
            'total_accounts' => 3,
            'active_accounts' => 2,
            'total_balance' => 1500.00
        ];

        $this->mockRepository->shouldReceive('getStatsForUser')
            ->once()
            ->with($this->user->id)
            ->andReturn($expectedStats);

        $result = $this->service->getAccountStats($this->user->id);

        $this->assertEquals($expectedStats, $result);
    }

    public function test_toggle_account_status_toggles_status_successfully()
    {
        $account = Account::factory()->make([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);
        
        $toggledAccount = Account::factory()->make([
            'user_id' => $this->user->id,
            'is_active' => false
        ]);

        $this->mockRepository->shouldReceive('toggleStatus')
            ->once()
            ->with($account)
            ->andReturn($toggledAccount);

        $result = $this->service->toggleAccountStatus($account);

        $this->assertEquals($toggledAccount, $result);
    }

    public function test_update_account_balance_updates_balance_successfully()
    {
        $account = Account::factory()->make([
            'user_id' => $this->user->id,
            'current_balance' => 1000.00
        ]);
        
        $newBalance = 1500.00;
        $updatedAccount = Account::factory()->make([
            'user_id' => $this->user->id,
            'current_balance' => $newBalance
        ]);

        $this->mockRepository->shouldReceive('updateBalance')
            ->once()
            ->with($account, $newBalance)
            ->andReturn($updatedAccount);

        $result = $this->service->updateAccountBalance($account, $newBalance);

        $this->assertEquals($updatedAccount, $result);
    }
}