<?php

namespace Tests\Unit\Services\Llm\Adapters;

use App\Services\Llm\Adapters\OpenAIAdapter;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIAdapterTest extends TestCase
{
    protected OpenAIAdapter $adapter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adapter = new OpenAIAdapter();
    }
    
    public function test_initialize_with_valid_config(): void
    {
        $config = [
            'api_key' => 'test-api-key',
            'model' => 'gpt-test',
            'timeout' => 30,
        ];
        
        // No debería lanzar excepciones
        $this->adapter->initialize($config);
        
        // Verificar que el proveedor es correcto
        $this->assertEquals('openai', $this->adapter->getProviderName());
    }
    
    public function test_initialize_with_missing_api_key_throws_exception(): void
    {
        $this->expectException(Exception::class);
        
        $config = [
            'api_key' => '', // API key vacía
            'model' => 'gpt-test',
        ];
        
        $this->adapter->initialize($config);
    }
    
    public function test_generate_text_returns_expected_response(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Esta es una respuesta simulada de OpenAI'
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'gpt-test',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateText('Test prompt', 'System prompt');
        
        // Verificar la respuesta
        $this->assertEquals('Esta es una respuesta simulada de OpenAI', $result);
        
        // Verificar que la petición HTTP se hizo correctamente
        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.openai.com/v1/chat/completions' &&
                   $request->method() == 'POST' &&
                   isset($request->data()['model']) &&
                   isset($request->data()['messages']) &&
                   $request->data()['model'] == 'gpt-test' &&
                   $request->data()['messages'][0]['role'] == 'system' &&
                   $request->data()['messages'][0]['content'] == 'System prompt' &&
                   $request->data()['messages'][1]['role'] == 'user' &&
                   $request->data()['messages'][1]['content'] == 'Test prompt' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }
    
    public function test_generate_text_with_empty_system_prompt(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Respuesta sin system prompt'
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'gpt-test',
        ]);
        
        // Ejecutar el método sin system prompt
        $result = $this->adapter->generateText('Test prompt');
        
        // Verificar la respuesta
        $this->assertEquals('Respuesta sin system prompt', $result);
        
        // Verificar que la petición HTTP se hizo correctamente con un solo mensaje
        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.openai.com/v1/chat/completions' &&
                   count($request->data()['messages']) == 1 &&
                   $request->data()['messages'][0]['role'] == 'user';
        });
    }
    
    public function test_generate_text_handles_api_error(): void
    {
        // Configurar el mock de HTTP para simular un error
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Error simulado'
                ]
            ], 500)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'gpt-test',
        ]);
        
        // Verificar que se lanza una excepción
        $this->expectException(Exception::class);
        
        // Ejecutar el método
        $this->adapter->generateText('Test prompt');
    }
    
    public function test_generate_embeddings_returns_vector(): void
    {
        // Vector de embeddings simulado
        $embeddings = array_fill(0, 10, 0.1);
        
        // Configurar el mock de HTTP
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => $embeddings
                    ]
                ]
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'api_key' => 'test-api-key',
            'model' => 'gpt-test',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateEmbeddings('Test text');
        
        // Verificar la respuesta
        $this->assertEquals($embeddings, $result);
        
        // Verificar que la petición HTTP se hizo correctamente
        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.openai.com/v1/embeddings' &&
                   $request->method() == 'POST' &&
                   isset($request->data()['model']) &&
                   isset($request->data()['input']) &&
                   $request->data()['input'] == 'Test text' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }
}
