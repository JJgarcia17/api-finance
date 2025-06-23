<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferBalanceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $accountA;
    private Account $accountB;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Cuenta A: $1000 inicial
        $this->accountA = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Account A',
            'current_balance' => 1000.00,
            'initial_balance' => 1000.00,
            'currency' => 'USD',
            'is_active' => true
        ]);
        
        // Cuenta B: $500 inicial  
        $this->accountB = Account::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Account B', 
            'current_balance' => 500.00,
            'initial_balance' => 500.00,
            'currency' => 'USD',
            'is_active' => true
        ]);
    }

    public function test_transfer_correctly_debits_and_credits_accounts()
    {
        // Verificar balances iniciales
        $this->assertEquals(1000.00, $this->accountA->fresh()->current_balance);
        $this->assertEquals(500.00, $this->accountB->fresh()->current_balance);

        // Realizar transferencia de $300 de A a B
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->accountA->id,
                'to_account_id' => $this->accountB->id,
                'amount' => 300.00,
                'description' => 'Test débito y crédito'
            ]);

        $response->assertStatus(201);

        // Verificar DÉBITO en cuenta origen (A)
        $this->accountA->refresh();
        $this->assertEquals(700.00, $this->accountA->current_balance, 
            'La cuenta origen debe ser debitada (reducir balance)');

        // Verificar CRÉDITO en cuenta destino (B)  
        $this->accountB->refresh();
        $this->assertEquals(800.00, $this->accountB->current_balance,
            'La cuenta destino debe ser creditada (aumentar balance)');

        // Verificar que la suma total se mantiene
        $totalBalance = $this->accountA->current_balance + $this->accountB->current_balance;
        $this->assertEquals(1500.00, $totalBalance,
            'El total de dinero en el sistema debe mantenerse igual');
    }

    public function test_transfer_deletion_correctly_reverts_debit_and_credit()
    {
        // Crear transferencia
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->accountA->id,
                'to_account_id' => $this->accountB->id,
                'amount' => 300.00,
                'description' => 'Test reversión'
            ]);

        $transferId = $response->json('data.id');
        
        // Verificar que la transferencia afectó los balances
        $this->accountA->refresh();
        $this->accountB->refresh();
        $this->assertEquals(700.00, $this->accountA->current_balance);
        $this->assertEquals(800.00, $this->accountB->current_balance);

        // Eliminar la transferencia
        $deleteResponse = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/transfers/{$transferId}");

        $deleteResponse->assertStatus(200);

        // Verificar REVERSIÓN DEL DÉBITO (devolver dinero a cuenta origen)
        $this->accountA->refresh();
        $this->assertEquals(1000.00, $this->accountA->current_balance,
            'El débito debe revertirse (devolver dinero a cuenta origen)');

        // Verificar REVERSIÓN DEL CRÉDITO (quitar dinero de cuenta destino)
        $this->accountB->refresh(); 
        $this->assertEquals(500.00, $this->accountB->current_balance,
            'El crédito debe revertirse (quitar dinero de cuenta destino)');

        // Verificar que volvemos al estado inicial
        $totalBalance = $this->accountA->current_balance + $this->accountB->current_balance;
        $this->assertEquals(1500.00, $totalBalance,
            'Después de revertir, el balance total debe ser el original');
    }

    public function test_multiple_transfers_maintain_balance_integrity()
    {
        $initialTotal = $this->accountA->current_balance + $this->accountB->current_balance;

        // Transferencia 1: A -> B ($200)
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->accountA->id,
                'to_account_id' => $this->accountB->id,
                'amount' => 200.00,
                'description' => 'Transfer 1'
            ])->assertStatus(201);

        // Transferencia 2: B -> A ($100)  
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->accountB->id,
                'to_account_id' => $this->accountA->id,
                'amount' => 100.00,
                'description' => 'Transfer 2'
            ])->assertStatus(201);

        // Transferencia 3: A -> B ($50)
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transfers', [
                'from_account_id' => $this->accountA->id,
                'to_account_id' => $this->accountB->id,
                'amount' => 50.00,
                'description' => 'Transfer 3'
            ])->assertStatus(201);

        // Verificar balances finales
        $this->accountA->refresh();
        $this->accountB->refresh();

        // A: 1000 - 200 + 100 - 50 = 850
        $this->assertEquals(850.00, $this->accountA->current_balance);
        
        // B: 500 + 200 - 100 + 50 = 650  
        $this->assertEquals(650.00, $this->accountB->current_balance);

        // Verificar integridad total
        $finalTotal = $this->accountA->current_balance + $this->accountB->current_balance;
        $this->assertEquals($initialTotal, $finalTotal,
            'El balance total debe mantenerse después de múltiples transferencias');
    }
}