<?php

namespace App\Services\Llm\Adapters;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador para la API de OpenAI
 */
class OpenAIAdapter implements LlmAdapterInterface
{
    /**
     * La URL base de la API de OpenAI
     * 
     * @var string
     */
    protected string $baseUrl;
    
    /**
     * La clave de API de OpenAI
     * 
     * @var string
     */
    protected string $apiKey;
    
    /**
     * El modelo a utilizar
     * 
     * @var string
     */
    protected string $model;
    
    /**
     * Timeout para las peticiones en segundos
     * 
     * @var int
     */
    protected int $timeout;
    
    /**
     * Inicializa el adaptador con la configuración proporcionada
     * 
     * @param array $config
     * @return void
     * 
     * @throws Exception Si falta configuración esencial
     */
    public function initialize(array $config): void
    {
        $this->baseUrl = $config['base_url'] ?? 'https://api.openai.com/v1';
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-3.5-turbo';
        $this->timeout = $config['timeout'] ?? 60;
        
        // Validar configuración esencial
        if (empty($this->apiKey)) {
            throw new Exception('Se requiere una clave de API para OpenAI');
        }
    }
    
    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string
    {
        return 'openai';
    }
    
    /**
     * Genera texto utilizando la API de OpenAI
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     * 
     * @throws Exception Si ocurre un error en la solicitud a la API
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string
    {
        try {
            $endpoint = "/chat/completions";
            $url = "{$this->baseUrl}{$endpoint}";
            
            // Configurar opciones para la petición
            $temperature = $options['temperature'] ?? 0.7;
            $maxTokens = $options['max_tokens'] ?? 1024;
            
            // Preparar los mensajes para ChatGPT
            $messages = [];
            
            // Añadir el prompt de sistema si está presente
            if (!empty($systemPrompt)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }
            
            // Añadir el prompt del usuario
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];
            
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ];
            
            // Realizar la solicitud HTTP a OpenAI
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post($url, $payload);
            
            // Verificar si la solicitud fue exitosa
            if ($response->failed()) {
                throw new Exception("Error al llamar a OpenAI API: " . $response->body());
            }
            
            $data = $response->json();
            
            // Extraer el contenido generado
            return $data['choices'][0]['message']['content'] ?? '';
            
        } catch (Exception $e) {
            Log::error("Error al generar texto con OpenAI: {$e->getMessage()}", [
                'model' => $this->model,
                'prompt_length' => strlen($prompt),
            ]);
            
            throw new Exception("Error al generar texto con OpenAI: {$e->getMessage()}");
        }
    }
    
    /**
     * Genera embeddings utilizando la API de OpenAI
     * 
     * @param string $text El texto para generar embeddings
     * @return array El vector de embeddings
     * 
     * @throws Exception Si ocurre un error en la solicitud a la API
     */
    public function generateEmbeddings(string $text): array
    {
        try {
            $endpoint = "/embeddings";
            $url = "{$this->baseUrl}{$endpoint}";
            
            $payload = [
                'model' => 'text-embedding-3-small', // Modelo específico para embeddings
                'input' => $text,
            ];
            
            // Realizar la solicitud HTTP a OpenAI
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post($url, $payload);
            
            // Verificar si la solicitud fue exitosa
            if ($response->failed()) {
                throw new Exception("Error al llamar a OpenAI API para embeddings: " . $response->body());
            }
            
            $data = $response->json();
            
            // Extraer el vector de embeddings
            return $data['data'][0]['embedding'] ?? [];
            
        } catch (Exception $e) {
            Log::error("Error al generar embeddings con OpenAI: {$e->getMessage()}", [
                'text_length' => strlen($text),
            ]);
            
            throw new Exception("Error al generar embeddings con OpenAI: {$e->getMessage()}");
        }
    }
}
