<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_get_all_categories()
    {
        // Arrange
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Salario',
            'type' => 'income'
        ]);
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alimentación',
            'type' => 'expense'
        ]);
        Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Transporte',
            'type' => 'expense'
        ]);
        Category::factory()->count(2)->create(); // Other user's categories

        // Act
        $response = $this->getJson('/api/v1/categories');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'color',
                        'icon',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_get_categories_with_filters()
    {
        // Arrange - Crear categorías con nombres únicos
        $incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'name' => 'Unique Income Category ' . time(),
            'color' => '#00FF00'
        ]);
        
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'expense', 
            'name' => 'Unique Expense Category ' . time(),
            'color' => '#FF0000'
        ]);

        // Act & Assert - Filter by type
        $response = $this->getJson('/api/v1/categories?type=income');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Act & Assert - Filter by color
        $response = $this->getJson('/api/v1/categories?color=' . urlencode('#FF0000'));
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_show_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->getJson("/api/v1/categories/{$category->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_show_category_not_found()
    {
        // Act
        $response = $this->getJson('/api/v1/categories/999');

        // Assert
        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_create_category()
    {
        // Arrange
        $data = [
            'name' => 'Test Category ' . time(),
            'type' => 'expense',
            'color' => '#FF5733',
            'icon' => 'fas fa-shopping-cart',
            'is_active' => true
        ];

        // Act
        $response = $this->postJson('/api/v1/categories', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.type', $data['type']);

        $this->assertDatabaseHas('categories', array_merge($data, ['user_id' => $this->user->id]));
    }

    public function test_create_category_validation_fails()
    {
        // Arrange
        $data = [
            'name' => '', // Invalid: empty name
            'type' => 'invalid_type', // Invalid type
            'color' => 'not-a-color' // Invalid color format
        ];

        // Act
        $response = $this->postJson('/api/v1/categories', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'color', 'icon']);
    }

    public function test_update_category()
    {
        // Arrange
        
        $category = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Category ' . time(),
            'type' => 'expense'
        ]);
        
        $updateData = [
            'name' => 'Updated Category ' . time(),
            'color' => '#00FF00',
            'icon' => 'fas fa-edit',
            'type' => 'expense'
        ];

        // Act
        $response = $this->putJson("/api/v1/categories/{$category->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $updateData['name']);

        $this->assertDatabaseHas('categories', array_merge($updateData, ['id' => $category->id]));
    }

    public function test_delete_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_restore_category()
    {
        // Arrange
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $category->delete(); // Soft delete

        // Act
        $response = $this->postJson("/api/v1/categories/{$category->id}/restore");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null
        ]);
    }
    public function test_toggle_status()
    {
        // Arrange
        $category = Category::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        // Act
        $response = $this->postJson("/api/v1/categories/{$category->id}/toggle-status");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_unauthorized_access_without_authentication()
    {
        // Arrange - Clear authentication
        $this->app['auth']->forgetGuards();
        
        // Act
        $response = $this->getJson('/api/v1/categories');
        
        // Assert
        $response->assertStatus(401);
    }

    public function test_cannot_access_other_user_categories()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->getJson("/api/v1/categories/{$otherCategory->id}");

        // Assert
        $response->assertStatus(404); // Route model binding will return 404 for unauthorized access
    }
}