<?php

namespace Tests\Unit\Services\Llm\Adapters;

use App\Services\Llm\Adapters\OpenRouterAdapter;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterAdapterTest extends TestCase
{
    protected OpenRouterAdapter $adapter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adapter = new OpenRouterAdapter();
    }
    
    public function test_initialize_with_valid_config(): void
    {
        $config = [
            'api_key' => 'test-api-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
            'timeout' => 30,
        ];
        
        // No debería lanzar excepciones
        $this->adapter->initialize($config);
        
        // Verificar que el proveedor es correcto
        $this->assertEquals('openrouter', $this->adapter->getProviderName());
    }
    
    public function test_initialize_with_missing_api_key_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $config = [
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        ];
        
        $this->adapter->initialize($config);
    }
    
    public function test_generate_text_returns_expected_response(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Esta es una respuesta simulada de OpenRouter'
                        ]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 15,
                    'total_tokens' => 25
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
            'site_url' => 'http://test.com',
            'site_name' => 'Test Site'
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateText('Test prompt', 'System prompt');
        
        // Verificar la respuesta
        $this->assertEquals('Esta es una respuesta simulada de OpenRouter', $result);
        
        // Verificar que la petición HTTP se hizo correctamente
        Http::assertSent(function ($request) {
            return $request->url() == 'https://openrouter.ai/api/v1/chat/completions' &&
                   $request->method() == 'POST' &&
                   isset($request->data()['model']) &&
                   isset($request->data()['messages']) &&
                   $request->data()['model'] == 'deepseek/deepseek-r1-0528-qwen3-8b:free' &&
                   count($request->data()['messages']) == 2 &&
                   $request->data()['messages'][0]['role'] == 'system' &&
                   $request->data()['messages'][1]['role'] == 'user' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key') &&
                   $request->hasHeader('HTTP-Referer', 'http://test.com') &&
                   $request->hasHeader('X-Title', 'Test Site');
        });
    }
    
    public function test_generate_text_handles_api_error(): void
    {
        // Configurar el mock de HTTP para simular un error
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'API key invalid'
                ]
            ], 401)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'invalid-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        ]);
        
        // Verificar que se lanza una excepción
        $this->expectException(Exception::class);
        
        // Ejecutar el método
        $this->adapter->generateText('Test prompt');
    }
    
    public function test_generate_structured_output_json(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"status": "success", "data": "test"}'
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateStructuredOutput('Test prompt', 'System prompt', 'json');
        
        // Verificar la respuesta
        $this->assertEquals('{"status": "success", "data": "test"}', $result);
    }
    
    public function test_is_available_returns_true_when_service_is_up(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    ['id' => 'deepseek/deepseek-r1-0528-qwen3-8b:free']
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->isAvailable();
        
        // Verificar la respuesta
        $this->assertTrue($result);
    }
    
    public function test_is_available_returns_false_when_service_is_down(): void
    {
        // Configurar el mock de HTTP para simular error
        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([], 500)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->isAvailable();
        
        // Verificar la respuesta
        $this->assertFalse($result);
    }
}
