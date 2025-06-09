<?php

namespace Tests\Unit\Repositories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Budget\BudgetRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BudgetRepository $repository;
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BudgetRepository();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_get_for_user_returns_user_budgets()
    {
        // Create budgets for the user
        $userBudgets = Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
        
        // Create budgets for another user
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        Budget::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id
        ]);

        $result = $this->repository->getForUser($this->user->id);

        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->user_id === $this->user->id));
    }

    public function test_get_for_user_filters_by_category()
    {
        $category2 = Category::factory()->create(['user_id' => $this->user->id]);
        
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
        
        Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id
        ]);

        $result = $this->repository->getForUser($this->user->id, $this->category->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->category_id === $this->category->id));
    }

    public function test_get_for_user_filters_by_active_status()
    {
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true
        ]);
        
        Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => false
        ]);

        $result = $this->repository->getForUser($this->user->id, null, null, true);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->is_active === true));
    }

    public function test_find_for_user_returns_budget_when_found()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $result = $this->repository->findForUser($budget->id, $this->user->id);

        $this->assertNotNull($result);
        $this->assertEquals($budget->id, $result->id);
        $this->assertTrue($result->relationLoaded('category'));
        $this->assertTrue($result->relationLoaded('user'));
    }

    public function test_find_for_user_returns_null_when_not_found()
    {
        $result = $this->repository->findForUser(999, $this->user->id);

        $this->assertNull($result);
    }

    public function test_find_for_user_returns_null_for_other_user_budget()
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $budget = Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id
        ]);

        $result = $this->repository->findForUser($budget->id, $this->user->id);

        $this->assertNull($result);
    }

    public function test_create_budget()
    {
        $data = [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'is_active' => true
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Budget::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['amount'], $result->amount);
        $this->assertDatabaseHas('budgets', $data);
    }

    public function test_update_budget()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'amount' => 2000.00
        ];

        $result = $this->repository->update($budget, $updateData);

        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals(2000.00, $result->amount);
        $this->assertTrue($result->relationLoaded('category'));
        $this->assertTrue($result->relationLoaded('user'));
    }

    public function test_delete_budget()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $result = $this->repository->delete($budget);

        $this->assertTrue($result);
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    public function test_restore_budget_success()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
        $budget->delete();

        $result = $this->repository->restore($budget->id, $this->user->id);

        $this->assertNotNull($result);
        $this->assertEquals($budget->id, $result->id);
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'deleted_at' => null
        ]);
    }

    public function test_restore_budget_not_found()
    {
        $result = $this->repository->restore(999, $this->user->id);

        $this->assertNull($result);
    }

    public function test_get_active_for_user()
    {
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true
        ]);
        
        Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => false
        ]);

        $result = $this->repository->getActiveForUser($this->user->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->is_active === true));
        $this->assertTrue($result->every(fn($budget) => $budget->relationLoaded('category')));
    }

    public function test_get_budgets_by_category()
    {
        $category2 = Category::factory()->create(['user_id' => $this->user->id]);
        
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true
        ]);
        
        Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id,
            'is_active' => true
        ]);

        $result = $this->repository->getBudgetsByCategory($this->user->id, $this->category->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->category_id === $this->category->id));
        $this->assertTrue($result->every(fn($budget) => $budget->is_active === true));
    }

    public function test_get_current_budgets()
    {
        $now = Carbon::now();
        
        // Current budgets (active and within date range)
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'start_date' => $now->copy()->subDays(5),
            'end_date' => $now->copy()->addDays(5)
        ]);
        
        // Past budgets
        Budget::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'start_date' => $now->copy()->subDays(20),
            'end_date' => $now->copy()->subDays(10)
        ]);
        
        // Future budgets
        Budget::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'start_date' => $now->copy()->addDays(10),
            'end_date' => $now->copy()->addDays(20)
        ]);

        $result = $this->repository->getCurrentBudgets($this->user->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($budget) => $budget->is_active === true));
    }
}