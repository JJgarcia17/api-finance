<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class StoreCategoryRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authorize_returns_true()
    {
        // Arrange
        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn() => $this->user);

        // Act & Assert
        $this->assertTrue($request->authorize());
    }

    public function test_validation_passes_with_valid_data()
    {
        // Arrange
        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn() => $this->user);
        
        $data = [
            'name' => 'Test Category',
            'type' => 'income',
            'color' => '#FF5733',
            'icon' => 'fas fa-dollar-sign', // Add the required icon field
            'description' => 'Test description',
            'is_active' => true
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_required_fields()
    {
        // Arrange
        $request = new StoreCategoryRequest();
        $data = [];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_type()
    {
        // Arrange
        $request = new StoreCategoryRequest();
        $data = [
            'name' => 'Test',
            'type' => 'invalid_type',
            'color' => '#FF5733'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_color_format()
    {
        // Arrange
        $request = new StoreCategoryRequest();
        $data = [
            'name' => 'Test',
            'type' => 'income',
            'color' => 'invalid-color'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }
    public function test_validation_passes_with_duplicate_name_for_different_user()
    {
        // Arrange
        $otherUser = User::factory()->create();
        Category::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Category Name',
            'type' => 'income' // Add type to match validation data
        ]);
        
        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn() => $this->user);
        
        $data = [
            'name' => 'Category Name',
            'type' => 'income',
            'color' => '#FF5733',
            'icon' => 'fas fa-dollar-sign' // Add the required icon field
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}