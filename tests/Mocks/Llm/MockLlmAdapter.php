<?php

namespace Tests\Mocks\Llm;

use App\Services\Llm\Adapters\LlmAdapterInterface;

class MockLlmAdapter implements LlmAdapterInterface
{
    /**
     * Respuestas predefinidas para las pruebas
     */
    protected array $responses = [
        'default' => 'Esta es una respuesta de prueba del LLM',
        'json_example' => '{"status": "success", "data": {"message": "Esto es un JSON de prueba"}}',
        'embeddings' => [0.1, 0.2, 0.3, 0.4, 0.5]
    ];
    
    /**
     * Inicializa el adaptador con la configuración proporcionada
     * 
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        // No se requiere inicialización real para el mock
    }
    
    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string
    {
        return 'mock';
    }
    
    /**
     * Simula la generación de texto
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string
    {
        // Podemos personalizar la respuesta basada en el prompt para pruebas específicas
        if (str_contains($prompt, 'json') && isset($this->responses['json_example'])) {
            return $this->responses['json_example'];
        }
        
        return $this->responses['default'];
    }
    
    /**
     * Simula la generación de embeddings
     * 
     * @param string $text El texto para generar embeddings
     * @return array El vector de embeddings
     */
    public function generateEmbeddings(string $text): array
    {
        return $this->responses['embeddings'];
    }
    
    /**
     * Configura una respuesta personalizada para las pruebas
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setResponse(string $key, mixed $value): self
    {
        $this->responses[$key] = $value;
        return $this;
    }
}
