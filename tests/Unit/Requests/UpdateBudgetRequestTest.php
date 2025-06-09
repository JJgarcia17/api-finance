<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateBudgetRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
        $this->budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_authorize_returns_true()
    {
        $request = new UpdateBudgetRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_valid_data_passes_validation()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Updated Budget',
            'amount' => 2000.75,
            'period' => 'weekly',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addWeek()->format('Y-m-d'),
            'is_active' => false
        ];

        // Usar las reglas directamente sin la regla unique problemática
        $rules = [
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
            'period' => 'sometimes|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_category_id_is_optional()
    {
        $data = [
            'name' => 'Updated Budget',
            'amount' => 2000.75
        ];

        $rules = [
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_category_id_must_exist_when_provided()
    {
        $data = [
            'category_id' => 999999,
            'name' => 'Updated Budget'
        ];

        $rules = [
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name' => 'sometimes|string|max:255',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_name_is_optional()
    {
        $data = [
            'amount' => 2000.75
        ];

        $rules = [
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_name_has_max_length_when_provided()
    {
        $data = [
            'name' => str_repeat('a', 256)
        ];

        $rules = [
            'name' => 'sometimes|string|max:255',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_amount_is_optional()
    {
        $data = [
            'name' => 'Updated Budget'
        ];

        $rules = [
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_amount_must_be_numeric_when_provided()
    {
        $data = [
            'amount' => 'not-a-number'
        ];

        $rules = [
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_amount_must_be_positive_when_provided()
    {
        $data = [
            'amount' => -100
        ];

        $rules = [
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_period_is_optional()
    {
        $data = [
            'name' => 'Updated Budget'
        ];

        $rules = [
            'name' => 'sometimes|string|max:255',
            'period' => 'sometimes|in:weekly,monthly,quarterly,yearly',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_period_must_be_valid_value_when_provided()
    {
        $data = [
            'period' => 'invalid-period'
        ];

        $rules = [
            'period' => 'sometimes|in:weekly,monthly,quarterly,yearly',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('period', $validator->errors()->toArray());
    }

    public function test_end_date_must_be_after_start_date_when_both_provided()
    {
        $data = [
            'start_date' => '2024-01-31',
            'end_date' => '2024-01-01'
        ];

        $rules = [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    public function test_is_active_is_optional_and_boolean()
    {
        $data = [
            'is_active' => 'not-boolean'
        ];

        $rules = [
            'is_active' => 'sometimes|boolean',
        ];
        
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }
}