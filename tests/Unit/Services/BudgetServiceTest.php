<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Budget\BudgetService;
use App\Repositories\Budget\BudgetRepository;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Exception;
use Illuminate\Support\Facades\DB;

class BudgetServiceTest extends TestCase
{
    private BudgetService $service;
    private $repository;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the repository
        $this->repository = Mockery::mock(BudgetRepository::class);
        
        // Create the service with mocked repository
        $this->service = new BudgetService($this->repository);
        
        // Create a mock user with all necessary expectations
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->user->shouldReceive('setAttribute')->andReturnSelf();
        $this->user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $this->user->shouldReceive('withAccessToken')->andReturnSelf();
        $this->user->shouldReceive('getKey')->andReturn(1);
        $this->user->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $this->user->shouldReceive('getAuthPassword')->andReturn('password');
        $this->user->shouldReceive('getRememberToken')->andReturn(null);
        $this->user->shouldReceive('setRememberToken')->andReturnSelf();
        $this->user->shouldReceive('getRememberTokenName')->andReturn('remember_token');
        
        // Mock authentication
        $this->actingAs($this->user);
        
        // Mock DB facade
        DB::shouldReceive('beginTransaction')->andReturn(true);
        DB::shouldReceive('commit')->andReturn(true);
        DB::shouldReceive('rollBack')->andReturn(true);
    }

    public function test_get_for_user()
    {
        // Arrange
        $budgets = collect([new Budget()]);
        $paginator = new LengthAwarePaginator(
            $budgets,
            1,
            15,
            1,
            ['path' => request()->url()]
        );
        
        $this->repository->shouldReceive('getForUser')
            ->once()
            ->with(1, null, null, null, null, null, 15)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getForUser();

        // Assert
        $this->assertEquals($paginator, $result);
    }

    public function test_find_for_user_success()
    {
        // Arrange
        $budget = new Budget(['id' => 1, 'name' => 'Test Budget']);
        
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, 1)
            ->andReturn($budget);

        // Act
        $result = $this->service->findForUser(1);

        // Assert
        $this->assertEquals($budget, $result);
    }

    public function test_find_for_user_not_found()
    {
        // Arrange
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, 1)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Presupuesto no encontrado');
        
        $this->service->findForUser(1);
    }

    public function test_delete_success()
    {
        // Arrange
        $budget = new Budget(['id' => 1, 'name' => 'Test Budget']);
        
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, 1)
            ->andReturn($budget);
            
        $this->repository->shouldReceive('delete')
            ->once()
            ->with($budget)
            ->andReturn(true);

        // Act
        $result = $this->service->delete(1);

        // Assert
        $this->assertTrue($result);
    }

    public function test_restore_success()
    {
        // Arrange
        $budget = new Budget(['id' => 1, 'name' => 'Test Budget']);
        
        $this->repository->shouldReceive('restore')
            ->once()
            ->with(1, 1)
            ->andReturn($budget);

        // Act
        $result = $this->service->restore(1);

        // Assert
        $this->assertEquals($budget, $result);
    }

    public function test_restore_not_found()
    {
        // Arrange
        $this->repository->shouldReceive('restore')
            ->once()
            ->with(1, 1)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Presupuesto no encontrado o no se puede restaurar');
        
        $this->service->restore(1);
    }

    public function test_toggle_status()
    {
        // Arrange
        $budget = new Budget(['id' => 1, 'is_active' => true]);
        $toggledBudget = new Budget(['id' => 1, 'is_active' => false]);
        
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, 1)
            ->andReturn($budget);
            
        $this->repository->shouldReceive('update')
            ->once()
            ->with($budget, ['is_active' => false])
            ->andReturn($toggledBudget);

        // Act
        $result = $this->service->toggleStatus(1);

        // Assert
        $this->assertEquals($toggledBudget, $result);
    }

    public function test_get_active_for_user()
    {
        // Arrange
        $budgets = new Collection([new Budget(), new Budget()]);
        
        $this->repository->shouldReceive('getActiveForUser')
            ->once()
            ->with(1)
            ->andReturn($budgets);

        // Act
        $result = $this->service->getActiveForUser();

        // Assert
        $this->assertEquals($budgets, $result);
    }

    public function test_get_current_budgets()
    {
        // Arrange
        $budgets = new Collection([new Budget(), new Budget()]);
        
        $this->repository->shouldReceive('getCurrentBudgets')
            ->once()
            ->with(1)
            ->andReturn($budgets);

        // Act
        $result = $this->service->getCurrentBudgets();

        // Assert
        $this->assertEquals($budgets, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}