<?php

namespace Tests\Unit\Services\Llm\Adapters;

use App\Services\Llm\Adapters\OllamaAdapter;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaAdapterTest extends TestCase
{
    protected OllamaAdapter $adapter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adapter = new OllamaAdapter();
    }
    
    public function test_initialize_with_valid_config(): void
    {
        $config = [
            'base_url' => 'http://test-ollama:11434',
            'model' => 'test-model',
            'timeout' => 30,
        ];
        
        // No debería lanzar excepciones
        $this->adapter->initialize($config);
        
        // Verificar que el proveedor es correcto
        $this->assertEquals('ollama', $this->adapter->getProviderName());
    }
    
    public function test_initialize_with_missing_model_throws_exception(): void
    {
        $this->expectException(Exception::class);
        
        $config = [
            'base_url' => 'http://test-ollama:11434',
            'model' => '', // Modelo vacío
        ];
        
        $this->adapter->initialize($config);
    }
    
    public function test_generate_text_returns_expected_response(): void
    {
        // Configurar el mock de HTTP
        Http::fake([
            'http://localhost:11434/api/generate' => Http::response([
                'response' => 'Esta es una respuesta simulada de Ollama'
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'base_url' => 'http://localhost:11434',
            'model' => 'test-model',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateText('Test prompt', 'System prompt');
        
        // Verificar la respuesta
        $this->assertEquals('Esta es una respuesta simulada de Ollama', $result);
        
        // Verificar que la petición HTTP se hizo correctamente
        Http::assertSent(function ($request) {
            return $request->url() == 'http://localhost:11434/api/generate' &&
                   $request->method() == 'POST' &&
                   isset($request->data()['model']) &&
                   isset($request->data()['prompt']) &&
                   $request->data()['model'] == 'test-model' &&
                   $request->data()['prompt'] == 'Test prompt' &&
                   isset($request->data()['system']) &&
                   $request->data()['system'] == 'System prompt';
        });
    }
    
    public function test_generate_text_handles_api_error(): void
    {
        // Configurar el mock de HTTP para simular un error
        Http::fake([
            'http://localhost:11434/api/generate' => Http::response([
                'error' => 'Error simulado'
            ], 500)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'base_url' => 'http://localhost:11434',
            'model' => 'test-model',
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
            'http://localhost:11434/api/embeddings' => Http::response([
                'embedding' => $embeddings
            ], 200)
        ]);
        
        // Inicializar adaptador
        $this->adapter->initialize([
            'base_url' => 'http://localhost:11434',
            'model' => 'test-model',
        ]);
        
        // Ejecutar el método
        $result = $this->adapter->generateEmbeddings('Test text');
        
        // Verificar la respuesta
        $this->assertEquals($embeddings, $result);
        
        // Verificar que la petición HTTP se hizo correctamente
        Http::assertSent(function ($request) {
            return $request->url() == 'http://localhost:11434/api/embeddings' &&
                   $request->method() == 'POST' &&
                   isset($request->data()['model']) &&
                   isset($request->data()['prompt']) &&
                   $request->data()['model'] == 'test-model' &&
                   $request->data()['prompt'] == 'Test text';
        });
    }
}
