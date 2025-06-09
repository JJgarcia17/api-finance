<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_authorize_returns_true()
    {
        $request = new UpdateAccountRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_empty_data_passes_validation()
    {
        $data = [];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_partial_valid_data_passes_validation()
    {
        $data = [
            'name' => 'Updated Account Name',
            'description' => 'Updated description'
        ];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_all_valid_data_passes_validation()
    {
        $data = [
            'name' => 'Updated Account',
            'type' => Account::TYPE_SAVINGS,
            'currency' => Account::CURRENCY_EUR,
            'initial_balance' => 2000.75,
            'current_balance' => 2500.25,
            'color' => '#33FF57',
            'icon' => 'savings',
            'description' => 'Updated account description',
            'is_active' => false,
            'include_in_total' => false
        ];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_invalid_type_fails_validation()
    {
        $data = ['type' => 'invalid_type'];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_invalid_currency_fails_validation()
    {
        $data = ['currency' => 'INVALID'];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    public function test_invalid_color_format_fails_validation()
    {
        $data = ['color' => 'invalid-color'];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }

    public function test_excessive_balance_fails_validation()
    {
        $data = ['initial_balance' => 9999999999.99];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
    }

    public function test_long_description_fails_validation()
    {
        $data = ['description' => str_repeat('a', 1001)];

        $request = new UpdateAccountRequest();
        $account = $this->account; // Capture the account in a variable
        $request->setRouteResolver(function () use ($account) {
            return new class($account) {
                private $account;
                
                public function __construct($account)
                {
                    $this->account = $account;
                }
                
                public function parameter($key)
                {
                    return $this->account;
                }
            };
        });

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }
}