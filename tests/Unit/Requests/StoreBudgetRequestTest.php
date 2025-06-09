<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreBudgetRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    public function test_authorize_returns_true()
    {
        $request = new StoreBudgetRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_valid_data_passes_validation()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => now()->addDay()->format('Y-m-d'), // Cambiar a fecha futura
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'is_active' => true
        ];
    
        // Crear una instancia de request con los datos
        $request = StoreBudgetRequest::create('/test', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        
        // Simular autenticación en la request
        $this->actingAs($this->user, 'sanctum');
        
        $validator = Validator::make($data, $request->rules());
    
        $this->assertTrue($validator->passes());
    }

    public function test_category_id_is_required()
    {
        $data = [
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_category_id_must_exist()
    {
        $data = [
            'category_id' => 999999,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_name_is_required()
    {
        $data = [
            'category_id' => $this->category->id,
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_has_max_length()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => str_repeat('a', 256), // Assuming max is 255
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_amount_is_required()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_amount_must_be_numeric()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 'not-a-number',
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_amount_must_be_positive()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => -100,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_period_is_required()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('period', $validator->errors()->toArray());
    }

    public function test_period_must_be_valid_value()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'invalid-period',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('period', $validator->errors()->toArray());
    }

    public function test_start_date_is_required()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'end_date' => '2024-01-31'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    public function test_end_date_is_required()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    public function test_end_date_must_be_after_start_date()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-31',
            'end_date' => '2024-01-01'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    public function test_is_active_is_optional_and_boolean()
    {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Test Budget',
            'amount' => 1000.50,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'is_active' => 'not-boolean'
        ];

        $request = new StoreBudgetRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }
}