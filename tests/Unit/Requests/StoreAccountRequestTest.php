<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_authorize_returns_true()
    {
        $request = new StoreAccountRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_valid_data_passes_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.50,
            'current_balance' => 1000.50,
            'color' => '#FF5733',
            'icon' => 'bank',
            'description' => 'Test account description',
            'is_active' => true,
            'include_in_total' => true
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_missing_required_fields_fails_validation()
    {
        $data = [];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
        $this->assertArrayHasKey('icon', $validator->errors()->toArray());
    }

    public function test_invalid_type_fails_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => 'invalid_type',
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.50,
            'color' => '#FF5733',
            'icon' => 'bank'
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_invalid_currency_fails_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => 'INVALID',
            'initial_balance' => 1000.50,
            'color' => '#FF5733',
            'icon' => 'bank'
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    public function test_invalid_color_format_fails_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.50,
            'color' => 'invalid-color',
            'icon' => 'bank'
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }

    public function test_excessive_balance_fails_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 9999999999.99,
            'color' => '#FF5733',
            'icon' => 'bank'
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
    }

    public function test_duplicate_name_for_same_user_fails_validation()
    {
        // Create existing account
        Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Existing Account'
        ]);

        $data = [
            'name' => 'Existing Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.50,
            'color' => '#FF5733',
            'icon' => 'bank'
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_long_description_fails_validation()
    {
        $data = [
            'name' => 'Test Account',
            'type' => Account::TYPE_BANK,
            'currency' => Account::CURRENCY_USD,
            'initial_balance' => 1000.50,
            'color' => '#FF5733',
            'icon' => 'bank',
            'description' => str_repeat('a', 1001)
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }
}