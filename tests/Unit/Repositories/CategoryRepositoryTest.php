<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Sanctum\Sanctum;

class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CategoryRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CategoryRepository(new Category());
        $this->user = User::factory()->create();

    }

    public function test_get_all_categories_for_user()
    {
        // Arrange
        Category::factory()->count(3)->create(['user_id' => $this->user->id]);
        Category::factory()->count(2)->create(); // Other user's categories

        // Act
        $categories = $this->repository->getAllForUser($this->user->id);

        // Assert
        $this->assertCount(3, $categories->items());
        $this->assertTrue(collect($categories->items())->every(fn($cat) => $cat->user_id === $this->user->id));
    }

    public function test_get_categories_with_filters()
    {
        // Arrange
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'income',
            'is_active' => true
        ]);
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Food',
            'type' => 'expense',
            'is_active' => false
        ]);

        // Act & Assert
        $incomeCategories = $this->repository->getAllForUser($this->user->id, ['type' => 'income']);
        $this->assertCount(1, $incomeCategories->items());
        $this->assertEquals('income', $incomeCategories->items()[0]->type);

        $activeCategories = $this->repository->getAllForUser($this->user->id, ['is_active' => true]);
        $this->assertCount(1, $activeCategories->items());
        $this->assertTrue($activeCategories->items()[0]->is_active);
    }

    public function test_find_category_by_id()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        // Act
        $found = $this->repository->findForUser($category->id, $this->user->id);

        // Assert
        $this->assertEquals($category->id, $found->id);
        $this->assertEquals($category->name, $found->name);
    }

    public function test_find_throws_exception_when_not_found()
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->findForUser(999, $this->user->id);
    }

    public function test_create_category()
    {
        // Arrange
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test Category',
            'type' => 'income',
            'color' => '#FF5733',
            'is_active' => true
        ];

        // Act
        $category = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($data['name'], $category->name);
        $this->assertEquals($this->user->id, $category->user_id);
        $this->assertDatabaseHas('categories', array_merge($data, ['user_id' => $this->user->id]));
    }

    public function test_update_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $updateData = ['name' => 'Updated Name', 'color' => '#000000'];

        // Act
        Sanctum::actingAs($this->user);
        $updated = $this->repository->update($category, $updateData); // ← Cambio aquí

        // Assert
        $this->assertEquals($updateData['name'], $updated->name);
        $this->assertEquals($updateData['color'], $updated->color);
        $this->assertDatabaseHas('categories', array_merge($updateData, ['id' => $category->id]));
    }

    public function test_delete_category_soft_delete()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        // Act
        $result = $this->repository->delete($category);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_restore_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $category->delete();

        // Act
        $restored = $this->repository->restore($category->id, $this->user->id);

        // Assert
        $this->assertInstanceOf(Category::class, $restored);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_force_delete_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        // Act
        $result = $this->repository->forceDelete($category);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_toggle_status()
    {
        // Arrange
        $category = Category::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        // Act
        $toggled = $this->repository->toggleStatus($category);

        // Assert
        $this->assertFalse($toggled->is_active);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_name_exists_for_user()
    {
        // Arrange
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Existing Category',
            'type' => Category::TYPE_EXPENSE // Add the type parameter
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->nameExistsForUser('Existing Category', Category::TYPE_EXPENSE, $this->user->id));
        $this->assertFalse($this->repository->nameExistsForUser('Non Existing', Category::TYPE_EXPENSE, $this->user->id));
    }

    public function test_get_counts_by_type()
    {
        // Arrange
        Category::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);
        Category::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'type' => 'expense'
        ]);
    
        // Act
        $counts = $this->repository->getCountByType($this->user->id);
    
        // Assert
        $this->assertEquals(3, $counts['income']);
        $this->assertEquals(5, $counts['expense']);
    }
}