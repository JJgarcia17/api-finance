<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Category\CategoryService;
use App\Repositories\Category\CategoryRepository;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Exception; 

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $service;
    private CategoryRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(CategoryRepository::class);
        $this->service = new CategoryService($this->repository);
        $this->user = User::factory()->create();
    }

    public function test_get_categories_for_user()
    {

        $categories = collect([Category::factory()->make()]);
        $filters = ['type' => 'income'];

        $paginator = new LengthAwarePaginator(
            $categories,
            $categories->count(),
            15,
            1,
            ['path' => request()->url()]
        );
        
        $this->repository->shouldReceive('getAllForUser')
            ->once()
            ->with($this->user->id, $filters, 15)
            ->andReturn($paginator);

        $result = $this->service->getCategoriesForUser($this->user->id, $filters, 15);

        $this->assertEquals($paginator, $result);
    }

    public function test_get_category_for_user()
    {
     
        $category = Category::factory()->make();
        
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, $this->user->id)
            ->andReturn($category);

        $result = $this->service->getCategoryForUser(1, $this->user->id);

        $this->assertEquals($category, $result);
    }

    public function test_create_category_success()
    {
       
        $data = [
            'name' => 'Test Category',
            'type' => 'income',
            'color' => '#FF5733'
        ];
        $expectedData = array_merge($data, [
            'user_id' => $this->user->id,
            'is_active' => true  
        ]);
        $category = Category::factory()->make($expectedData);
        
        $this->repository->shouldReceive('nameExistsForUser')
            ->once()
            ->with($data['name'], $data['type'], $this->user->id, null)
            ->andReturn(false);
            
        $this->repository->shouldReceive('create')
            ->once()
            ->with($expectedData)  
            ->andReturn($category);
    
        
        $result = $this->service->createCategory($data, $this->user->id);
    
        $this->assertEquals($category, $result);
    }

    public function test_create_category_fails_when_name_exists()
    {
    
        $data = [
            'name' => 'Existing Category',
            'type' => 'income'
        ];
        
      
        $this->repository->shouldReceive('nameExistsForUser')
            ->once()
            ->with($data['name'], $data['type'], $this->user->id, null)
            ->andReturn(true);
    
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ya existe una categoría con este nombre y tipo');
    
        $this->service->createCategory($data, $this->user->id);
    }

    public function test_update_category_success()
    {
        // Arrange
        $data = ['name' => 'Updated Category', 'type' => 'income'];
        $existingCategory = Category::factory()->make(['id' => 1, 'user_id' => $this->user->id]);
        $updatedCategory = Category::factory()->make(array_merge($data, ['id' => 1, 'user_id' => $this->user->id]));
        
   
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, $this->user->id)
            ->andReturn($existingCategory);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($existingCategory, $data)
            ->andReturn($updatedCategory);
    
        $result = $this->service->updateCategory(1, $data, $this->user->id);

        $this->assertEquals($updatedCategory, $result);
    }

    public function test_delete_category()
    {
    
        $category = Category::factory()->make([
            'id' => 1,
            'user_id' => $this->user->id
        ]);
        
        $this->repository->shouldReceive('findForUser')
            ->once()
            ->with(1, $this->user->id)
            ->andReturn($category);
            
        $this->repository->shouldReceive('delete')
            ->once()
            ->with($category)
            ->andReturn(true);
    
        $result = $this->service->deleteCategory(1, $this->user->id);
    
        $this->assertTrue($result);
    }

    public function test_get_category_stats()
    {
      
        $counts = [
            'income' => 5,
            'expense' => 10
        ];
        
        $expectedStats = [
            'total' => 15,
            'income_categories' => 5,
            'expense_categories' => 10
        ];
        
        $this->repository->shouldReceive('getCountByType')
            ->once()
            ->with($this->user->id)
            ->andReturn($counts);
    
        $result = $this->service->getCategoryStats($this->user->id);
    
        $this->assertEquals($expectedStats, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}