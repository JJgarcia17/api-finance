<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\LlmClient;
use Tests\Mocks\Llm\MockLlmAdapter;
use Tests\TestCase;

class LlmClientTest extends TestCase
{
    protected MockLlmAdapter $mockAdapter;
    protected LlmClient $llmClient;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un adaptador simulado
        $this->mockAdapter = new MockLlmAdapter();
        
        // Configuración básica para el cliente
        $config = [
            'model' => 'test-model',
            'cache_enabled' => false,
            'system_prompt' => 'Eres un asistente de prueba.'
        ];
        
        // Crear el cliente con el adaptador simulado
        $this->llmClient = new LlmClient($this->mockAdapter, $config);
    }
    
    public function test_generate_text_returns_expected_response(): void
    {
        // Configurar una respuesta específica para esta prueba
        $this->mockAdapter->setResponse('default', 'Esta es una respuesta personalizada');
        
        // Ejecutar el método a probar
        $result = $this->llmClient->generateText('¿Cómo estás?');
        
        // Verificar el resultado
        $this->assertEquals('Esta es una respuesta personalizada', $result);
    }
    
    public function test_generate_structured_output_returns_parsed_json(): void
    {
        // Configurar una respuesta JSON específica
        $jsonResponse = '{"name": "Test", "value": 123}';
        $this->mockAdapter->setResponse('json_example', $jsonResponse);
        
        // Ejecutar el método a probar con un prompt que contiene la palabra 'json'
        // Esto es importante porque el MockLlmAdapter verifica esta palabra clave
        $result = $this->llmClient->generateStructuredOutput('Dame datos en json', 'json');
        
        // Verificar que se haya parseado correctamente el JSON
        $this->assertIsArray($result);
        $this->assertEquals('Test', $result['name']);
        $this->assertEquals(123, $result['value']);
    }
    
    public function test_generate_embeddings_returns_vector(): void
    {
        // Configurar un vector de embeddings específico
        $embeddings = [0.5, 0.6, 0.7, 0.8, 0.9];
        $this->mockAdapter->setResponse('embeddings', $embeddings);
        
        // Ejecutar el método a probar
        $result = $this->llmClient->generateEmbeddings('Texto de prueba');
        
        // Verificar el resultado
        $this->assertEquals($embeddings, $result);
    }
}
