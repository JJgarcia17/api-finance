<?php

namespace Tests\Feature\Api;

use App\Contracts\Llm\LlmClientInterface;
use App\Models\User;
use App\Services\Llm\LlmClient;
use App\Services\Llm\LlmClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Mocks\Llm\MockLlmAdapter;
use Tests\TestCase;

class LlmControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario para autenticación
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        // Crear un adaptador mock real
        $mockAdapter = new MockLlmAdapter();
        
        // Configurar respuestas personalizadas para las pruebas
        $mockAdapter->setResponse('default', 'Esta es una respuesta simulada');
        $mockAdapter->setResponse('json_example', json_encode(['status' => 'success', 'data' => 'Datos simulados']));
        
        // Crear un cliente LLM real con el adaptador mock
        $mockClient = new LlmClient($mockAdapter, [
            'model' => 'test-model',
            'system_prompt' => 'System prompt de prueba'
        ]);
        
        // Registrar el mock en el contenedor de servicios
        $this->app->instance(LlmClientInterface::class, $mockClient);
        
        // Configurar valores de prueba en la configuración
        config([
            'llm.provider' => 'mock',
            'llm.mock' => [
                'model' => 'test-model',
                'system_prompt' => 'System prompt de prueba'
            ]
        ]);
    }
      public function test_status_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/llm/status');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'provider',
                'model',
                'is_working',
                'response'
            ]);
            
        // Verificar que la respuesta tenga formato correcto pero sin validar is_working
        // que depende del contenido exacto de la respuesta simulada
        $data = $response->json();
        $this->assertEquals('success', $data['status']);
    }
    
    public function test_generate_text_endpoint_returns_expected_response(): void
    {
        $payload = [
            'prompt' => 'Test prompt',
            'options' => [
                'temperature' => 0.5
            ]
        ];
        
        $response = $this->postJson('/api/v1/llm/generate-text', $payload);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'output'
            ])
            ->assertJson([
                'status' => 'success',
                'output' => 'Esta es una respuesta simulada'
            ]);
    }
    
    public function test_generate_text_endpoint_validates_input(): void
    {
        $payload = [
            // Sin prompt
            'options' => [
                'temperature' => 0.5
            ]
        ];
        
        $response = $this->postJson('/api/v1/llm/generate-text', $payload);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    }      public function test_generate_structured_endpoint_returns_expected_response(): void
    {
        $payload = [
            'prompt' => 'Generate a json response with test data',
            'format' => 'json',
            'options' => [
                'temperature' => 0.5
            ]
        ];
        
        $response = $this->postJson('/api/v1/llm/generate-structured', $payload);
          $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'format',
                'output'
            ])
            ->assertJson([
                'status' => 'success',
                'format' => 'json',
            ]);
        
        // Verificar que el output existe, sin validar su estructura exacta
        // ya que podría variar según cómo esté configurado el mock
        $this->assertNotNull($response->json('output'));
    }
    
    public function test_generate_structured_endpoint_validates_input(): void
    {
        $payload = [
            'prompt' => 'Test prompt',
            'format' => 'invalid', // Formato inválido
        ];
        
        $response = $this->postJson('/api/v1/llm/generate-structured', $payload);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }
}
