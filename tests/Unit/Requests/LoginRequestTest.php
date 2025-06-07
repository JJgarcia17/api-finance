<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    
    public function test_authorize_returns_true()
    {
        $request = new LoginRequest();
        $this->assertTrue($request->authorize());
    }

    
    public function test_valid_data_passes_validation()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    
    public function test_email_is_required()
    {
        $data = [
            'password' => 'password123'
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    
    public function test_email_must_be_valid_format()
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    
    public function test_password_is_required()
    {
        $data = [
            'email' => 'test@example.com'
        ];

        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    
    public function test_prepare_for_validation_filters_extra_fields()
    {
        $request = new LoginRequest();
        $request->merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Should be removed',
            'extra_field' => 'Should be removed'
        ]);

        $request->prepareForValidation();

        $this->assertEquals([
            'email' => 'test@example.com',
            'password' => 'password123'
        ], $request->all());
    }
}