<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class StoreTransactionRequestTest extends TestCase
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
        $request = new StoreTransactionRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_valid_data_passes_validation()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'reference_number' => 'REF123',
            'notes' => 'Test notes',
            'is_recurring' => false,
            'tags' => ['tag1', 'tag2']
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_required_fields()
    {
        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $data = [];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('account_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertArrayHasKey('transaction_date', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_account_id()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'account_id' => $otherAccount->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d')
        ];

        $request = new StoreTransactionRequest();
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
            'account_id' => $this->account->id,
            'category_id' => $otherCategory->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d')
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_type()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'invalid_type',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d')
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_amount()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 0,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d')
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_future_date()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->addDay()->format('Y-m-d')
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('transaction_date', $validator->errors()->toArray());
    }

    public function test_validation_fails_when_recurring_frequency_missing_for_recurring_transaction()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'is_recurring' => true
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('recurring_frequency', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_recurring_transaction()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'is_recurring' => true,
            'recurring_frequency' => 'monthly'
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_tags()
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'tags' => ['valid_tag', str_repeat('a', 51)] // Tag too long
        ];

        $request = new StoreTransactionRequest();
        $request->setUserResolver(fn() => $this->user);
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tags.1', $validator->errors()->toArray());
    }
}