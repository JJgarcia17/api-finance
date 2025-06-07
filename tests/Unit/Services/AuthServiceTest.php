<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    
    public function test_register_creates_user_and_returns_token()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->authService->register($userData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('Usuario registrado exitosamente', $result['message']);
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    
    public function test_login_with_valid_credentials_returns_success()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->authService->login($credentials);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('Inicio de sesión exitoso', $result['message']);
    }

    
    public function test_login_with_invalid_credentials_returns_failure()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ];

        $result = $this->authService->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertEquals('Credenciales incorrectas', $result['message']);
        $this->assertArrayNotHasKey('user', $result);
        $this->assertArrayNotHasKey('token', $result);
    }

    
    public function test_login_with_nonexistent_email_returns_failure()
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $result = $this->authService->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertEquals('Credenciales incorrectas', $result['message']);
    }

    
    public function test_logout_revokes_current_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $user->withAccessToken($token->accessToken);

        $result = $this->authService->logout($user);

        $this->assertTrue($result['success']);
        $this->assertEquals('Sesión cerrada exitosamente', $result['message']);
    }

    
    public function test_refresh_token_creates_new_token()
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('old-token');
        $user->withAccessToken($oldToken->accessToken);

        $result = $this->authService->refreshToken($user);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEquals($oldToken->plainTextToken, $result['token']);
    }
}