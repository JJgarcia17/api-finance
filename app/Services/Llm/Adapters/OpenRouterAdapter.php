<?php

namespace App\Services\Llm\Adapters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador para OpenRouter - Acceso a múltiples modelos LLM gratuitos y de pago
 * Compatible con la API de OpenAI pero con acceso a modelos como DeepSeek, Claude, etc.
 */
class OpenRouterAdapter implements LlmAdapterInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private string $siteUrl;
    private string $siteName;
    private array $options;

    /**
     * Inicializa el adaptador con la configuración proporcionada
     * 
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://openrouter.ai/api/v1';
        $this->model = $config['model'] ?? 'deepseek/deepseek-r1-0528-qwen3-8b:free';
        $this->timeout = $config['timeout'] ?? 120;
        $this->siteUrl = $config['site_url'] ?? '';
        $this->siteName = $config['site_name'] ?? '';
        $this->options = $config['options'] ?? [];

        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('OpenRouter API key is required');
        }
    }

    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string
    {
        return 'openrouter';
    }

    /**
     * Genera texto utilizando OpenRouter
     * 
     * @param string $prompt El prompt del usuario
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string
    {
        try {
            // Construir mensajes
            $messages = [];
            
            if (!empty($systemPrompt)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }
            
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];

            // Preparar headers
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            // Agregar headers opcionales para ranking en OpenRouter
            if (!empty($this->siteUrl)) {
                $headers['HTTP-Referer'] = $this->siteUrl;
            }
            
            if (!empty($this->siteName)) {
                $headers['X-Title'] = $this->siteName;
            }

            // Preparar datos de la petición
            $data = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? $this->options['temperature'] ?? 0.7,
                'top_p' => $options['top_p'] ?? $this->options['top_p'] ?? 0.9,
                'max_tokens' => $options['max_tokens'] ?? $this->options['max_tokens'] ?? 2048,
            ];

            Log::info('OpenRouter request', [
                'model' => $this->model,
                'messages_count' => count($messages),
                'prompt_length' => strlen($prompt),
                'system_prompt_length' => strlen($systemPrompt),
                'temperature' => $data['temperature'],
                'max_tokens' => $data['max_tokens']
            ]);

            // Realizar petición HTTP
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/chat/completions', $data);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('OpenRouter API error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'model' => $this->model
                ]);
                
                throw new \Exception("OpenRouter API error: " . $response->status() . " - " . $errorBody);
            }

            $responseData = $response->json();
            
            // Validar respuesta
            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error('Invalid OpenRouter response structure', [
                    'response' => $responseData
                ]);
                throw new \Exception('Invalid response structure from OpenRouter');
            }

            $generatedText = $responseData['choices'][0]['message']['content'];

            // Log de éxito
            Log::info('OpenRouter response generated successfully', [
                'model' => $this->model,
                'response_length' => strlen($generatedText),
                'usage' => $responseData['usage'] ?? null
            ]);

            return $generatedText;

        } catch (\Exception $e) {
            Log::error('OpenRouter generation failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);
            
            throw $e;
        }
    }

    /**
     * Genera salida estructurada en un formato específico
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param string $format El formato deseado
     * @param array $options Opciones específicas del proveedor
     * @return string La salida estructurada generada
     */
    public function generateStructuredOutput(string $prompt, string $systemPrompt = '', string $format = 'json', array $options = []): string
    {
        // Para formato JSON, modificar el system prompt
        if ($format === 'json') {
            $structuredSystemPrompt = $systemPrompt . "\n\nResponde siempre en formato JSON válido.";
            return $this->generateText($prompt, $structuredSystemPrompt, $options);
        }
        
        return $this->generateText($prompt, $systemPrompt, $options);
    }

    /**
     * Genera embeddings (no implementado para OpenRouter)
     * 
     * @param string $text El texto para generar embeddings
     * @return array Los embeddings generados
     */
    public function generateEmbeddings(string $text): array
    {
        throw new \Exception('Embeddings generation not supported by OpenRouter adapter');
    }

    /**
     * Obtener información del modelo actual
     * 
     * @return array
     */
    public function getModelInfo(): array
    {
        return [
            'provider' => 'openrouter',
            'model' => $this->model,
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'options' => $this->options
        ];
    }

    /**
     * Verificar si el servicio está disponible
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get($this->baseUrl . '/models');
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OpenRouter availability check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
