<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class UpdateTransactionRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_id' => $this->user->id]);
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_authorize_returns_true()
    {
        $request = new UpdateTransactionRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_validation_passes_with_empty_data()
    {
        $data = [];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_partial_valid_data()
    {
        $data = [
            'amount' => 150.75,
            'description' => 'Updated transaction'
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_all_valid_data()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 200.25,
            'description' => 'Updated transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'reference_number' => 'REF456',
            'notes' => 'Updated notes',
            'is_recurring' => true,
            'recurring_frequency' => 'weekly',
            'tags' => ['updated', 'test']
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_account_id()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'account_id' => $otherAccount->id
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('account_id', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_category_id()
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'category_id' => $otherCategory->id
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_type()
    {
        $data = [
            'type' => 'invalid_type'
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_amount()
    {
        $data = [
            'amount' => 0
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_future_date()
    {
        $data = [
            'transaction_date' => now()->addDay()->format('Y-m-d')
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('transaction_date', $validator->errors()->toArray());
    }

    public function test_validation_fails_when_recurring_frequency_missing_for_recurring_transaction()
    {
        $data = [
            'is_recurring' => true
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('recurring_frequency', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_recurring_transaction_update()
    {
        $data = [
            'is_recurring' => true,
            'recurring_frequency' => 'daily'
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_tags()
    {
        $data = [
            'tags' => ['valid_tag', str_repeat('a', 51)] // Tag too long
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tags.1', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_excessive_amount()
    {
        $data = [
            'amount' => 9999999999.99 // Exceeds max
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_long_description()
    {
        $data = [
            'description' => str_repeat('a', 256) // Exceeds max length
        ];

        $request = new UpdateTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }
}