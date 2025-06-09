<?php

namespace Tests\Feature\Api;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
    }

    public function test_index_returns_user_budgets()
    {
        Budget::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true  // Forzar que sean activos
        ]);

        // Create budgets for another user (should not be returned)
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        Budget::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'is_active' => true 
        ]);

        $response = $this->getJson('/api/v1/budgets');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'user_id',
                             'category',
                             'name',
                             'amount',
                             'period',
                             'start_date',
                             'end_date',
                             'is_active',
                             'created_at',
                             'updated_at'
                         ]
                     ],
                     'links',
                     'meta'
                 ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_filters_by_category()
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

        $response = $this->getJson('/api/v1/budgets?category_id=' . $this->category->id);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_show_returns_budget_for_authenticated_user()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'category',
                         'name',
                         'amount',
                         'period',
                         'start_date',
                         'end_date',
                         'is_active',
                         'created_at',
                         'updated_at'
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'id' => $budget->id,
                         'name' => $budget->name
                     ]
                 ]);
    }

    public function test_show_returns_404_for_non_existent_budget()
    {
        $response = $this->getJson('/api/v1/budgets/999');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_other_user_budget()
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $budget = Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id
        ]);

        $response = $this->getJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(404);
    }

    public function test_store_creates_budget_with_valid_data()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'is_active' => true
        ];

        $response = $this->postJson('/api/v1/budgets', $data);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'category',
                         'name',
                         'amount',
                         'period',
                         'start_date',
                         'end_date',
                         'is_active'
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'name' => 'Test Budget',
                         'amount' => 1000.50,
                         'period' => 'monthly'
                     ]
                 ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/budgets', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'category_id',
                     'name',
                     'amount',
                     'period',
                     'start_date',
                     'end_date'
                 ]);
    }

    public function test_store_validates_category_belongs_to_user()
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        
        $data = [
            'category_id' => $otherCategory->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $response = $this->postJson('/api/v1/budgets', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category_id']);
    }

    public function test_update_modifies_budget_with_valid_data()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Original Name'
        ]);

        $data = [
            'name' => 'Updated Name',
            'amount' => 2000.75
        ];

        $response = $this->putJson('/api/v1/budgets/' . $budget->id, $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $budget->id,
                         'name' => 'Updated Name',
                         'amount' => 2000.75
                     ]
                 ]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'name' => 'Updated Name',
            'amount' => 2000.75
        ]);
    }

    public function test_update_returns_404_for_non_existent_budget()
    {
        $response = $this->putJson('/api/v1/budgets/999', ['name' => 'Updated']);

        $response->assertStatus(404);
    }

    public function test_update_returns_404_for_other_user_budget()
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $budget = Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id
        ]);

        $response = $this->putJson('/api/v1/budgets/' . $budget->id, ['name' => 'Updated']);

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_budget()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->deleteJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Presupuesto eliminado exitosamente'
                 ]);

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    public function test_destroy_returns_404_for_non_existent_budget()
    {
        $response = $this->deleteJson('/api/v1/budgets/999');

        $response->assertStatus(404);
    }

    public function test_restore_restores_deleted_budget()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'deleted_at' => now()
        ]);

        $response = $this->postJson('/api/v1/budgets/' . $budget->id . '/restore');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'amount'
                     ]
                 ]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'deleted_at' => null
        ]);
    }

    public function test_toggle_status_changes_budget_status()
    {
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true
        ]);

        $response = $this->patchJson('/api/v1/budgets/' . $budget->id . '/toggle-status');

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $budget->id,
                         'is_active' => false
                     ]
                 ]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'is_active' => false
        ]);
    }

    public function test_current_returns_current_budgets()
    {
        $now = Carbon::now();
        
        // Current budgets
        Budget::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'start_date' => $now->copy()->subDays(5),
            'end_date' => $now->copy()->addDays(5)
        ]);
        
        // Past budget
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'start_date' => $now->copy()->subDays(20),
            'end_date' => $now->copy()->subDays(10)
        ]);

        $response = $this->getJson('/api/v1/budgets/current');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'category',
                             'name',
                             'amount'
                         ]
                     ]
                 ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_access_budgets()
    {
        // Clear the authentication set in setUp()
        $this->app['auth']->forgetGuards();
        
        $response = $this->getJson('/api/v1/budgets');
        $response->assertStatus(401);
    }
}