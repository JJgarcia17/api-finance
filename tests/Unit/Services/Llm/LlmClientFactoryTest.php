<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\Adapters\OllamaAdapter;
use App\Services\Llm\Adapters\OpenAIAdapter;
use App\Services\Llm\LlmClientFactory;
use Exception;
use Tests\TestCase;

class LlmClientFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar valores de prueba en la configuración
        config([
            'llm.provider' => 'ollama',
            'llm.ollama' => [
                'base_url' => 'http://test-ollama:11434',
                'model' => 'test-model',
            ],
            'llm.openai' => [
                'api_key' => 'test-key',
                'model' => 'test-model',
            ],
            'llm.default' => [
                'system_prompt' => 'Test prompt',
                'cache_enabled' => false,
            ],
        ]);
    }
    
    public function test_creates_client_with_default_provider(): void
    {
        $client = LlmClientFactory::create();
        
        // Verificar que se creó correctamente
        $this->assertInstanceOf(\App\Contracts\Llm\LlmClientInterface::class, $client);
        
        // La clase LlmClient es privada, por lo que solo podemos probar la interfaz
        $this->assertTrue(method_exists($client, 'generateText'));
        $this->assertTrue(method_exists($client, 'generateStructuredOutput'));
        $this->assertTrue(method_exists($client, 'generateEmbeddings'));
    }
    
    public function test_creates_client_with_specified_provider(): void
    {
        $client = LlmClientFactory::create('openai');
        
        $this->assertInstanceOf(\App\Contracts\Llm\LlmClientInterface::class, $client);
    }
    
    public function test_throws_exception_for_unsupported_provider(): void
    {
        $this->expectException(Exception::class);
        
        LlmClientFactory::create('unsupported');
    }
    
    public function test_creates_client_with_custom_config(): void
    {
        $customConfig = [
            'model' => 'custom-model',
            'system_prompt' => 'Custom prompt',
        ];
        
        $client = LlmClientFactory::create(null, $customConfig);
        
        $this->assertInstanceOf(\App\Contracts\Llm\LlmClientInterface::class, $client);
    }
}
