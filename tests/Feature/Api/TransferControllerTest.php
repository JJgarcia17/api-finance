<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $fromAccount;
    private Account $toAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->fromAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'From Account ' . uniqid(),
            'current_balance' => 1000.00,
            'currency' => 'USD',
            'is_active' => true
        ]);
        $this->toAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'To Account ' . uniqid(),
            'current_balance' => 500.00,
            'currency' => 'USD',
            'is_active' => true
        ]);
    }

    public function test_can_create_transfer()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->toAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'reference_number',
                    'amount',
                    'description',
                    'transfer_date',
                    'from_account',
                    'to_account'
                ],
                'message'
            ]);

        // Verify balances were updated
        $this->fromAccount->refresh();
        $this->toAccount->refresh();
        
        $this->assertEquals(800.00, $this->fromAccount->current_balance);
        $this->assertEquals(700.00, $this->toAccount->current_balance);
    }

    public function test_transfer_requires_from_account()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'to_account_id' => $this->toAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_account_id']);
    }

    public function test_transfer_requires_to_account()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_transfer_prevents_same_account()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->fromAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_transfer_requires_amount()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->toAccount->id,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_transfer_fails_with_insufficient_balance()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->toAccount->id,
                'amount' => 1500.00, // More than available balance
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(500);
    }

    public function test_can_get_user_transfers()
    {
        // Create a transfer first
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->toAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transfers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'reference_number',
                        'amount',
                        'description',
                        'from_account',
                        'to_account'
                    ]
                ],
                'message'
            ]);
    }

    public function test_can_get_transfer_stats()
    {
        // Create a transfer first
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $this->toAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transfers/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_transfers',
                    'total_amount',
                    'current_month_transfers',
                    'current_month_amount',
                    'this_week_transfers',
                    'this_week_amount',
                    'average_transfer_amount'
                ],
                'message'
            ]);
    }

    public function test_can_get_available_accounts()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transfers/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'current_balance',
                        'currency'
                    ]
                ],
                'message'
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_cannot_access_other_user_accounts()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Account ' . uniqid()
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->fromAccount->id,
                'to_account_id' => $otherAccount->id,
                'amount' => 200.00,
                'description' => 'Transfer test'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }
}