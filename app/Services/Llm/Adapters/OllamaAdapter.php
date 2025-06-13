<?php

namespace App\Services\Llm\Adapters;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador para Ollama (modelos locales)
 */
class OllamaAdapter implements LlmAdapterInterface
{
    /**
     * URL base de Ollama
     * 
     * @var string
     */
    protected string $baseUrl;
    
    /**
     * Modelo a utilizar
     * 
     * @var string
     */
    protected string $model;
    
    /**
     * Timeout para peticiones en segundos
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
        $this->baseUrl = $config['base_url'] ?? 'http://localhost:11434';
        $this->model = $config['model'] ?? 'llama3';
        $this->timeout = $config['timeout'] ?? 60;
        
        // Validar configuración esencial
        if (empty($this->model)) {
            throw new Exception('Se requiere un modelo para Ollama');
        }
    }
    
    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string
    {
        return 'ollama';
    }
    
    /**
     * Genera texto utilizando Ollama
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string
    {
        try {
            $endpoint = "/api/generate";
            $url = "{$this->baseUrl}{$endpoint}";
            
            // Configurar opciones para la petición
            $temperature = $options['temperature'] ?? 0.7;
            $topP = $options['top_p'] ?? 0.9;
            
            // Preparar el payload para Ollama
            $payload = [
                'model' => $this->model,
                'prompt' => $prompt,
                'temperature' => $temperature,
                'top_p' => $topP,
                'stream' => false,
            ];
            
            // Añadir el prompt de sistema si está presente
            if (!empty($systemPrompt)) {
                $payload['system'] = $systemPrompt;
            }
            
            // Realizar la solicitud HTTP a Ollama
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
            
            // Verificar si la solicitud fue exitosa
            if ($response->failed()) {
                throw new Exception("Error al llamar a Ollama API: " . $response->body());
            }
            
            $data = $response->json();
            
            // Extraer el contenido generado
            return $data['response'] ?? '';
            
        } catch (Exception $e) {
            Log::error("Error al generar texto con Ollama: {$e->getMessage()}", [
                'model' => $this->model,
                'prompt_length' => strlen($prompt),
            ]);
            
            throw new Exception("Error al generar texto con Ollama: {$e->getMessage()}");
        }
    }
    
    /**
     * Genera embeddings utilizando Ollama
     * 
     * @param string $text El texto para generar embeddings
     * @return array El vector de embeddings
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateEmbeddings(string $text): array
    {
        try {
            $endpoint = "/api/embeddings";
            $url = "{$this->baseUrl}{$endpoint}";
            
            // Preparar el payload para Ollama
            $payload = [
                'model' => $this->model,
                'prompt' => $text,
            ];
            
            // Realizar la solicitud HTTP a Ollama
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
            
            // Verificar si la solicitud fue exitosa
            if ($response->failed()) {
                throw new Exception("Error al llamar a Ollama API para embeddings: " . $response->body());
            }
            
            $data = $response->json();
            
            // Extraer el vector de embeddings
            return $data['embedding'] ?? [];
            
        } catch (Exception $e) {
            Log::error("Error al generar embeddings con Ollama: {$e->getMessage()}", [
                'model' => $this->model,
                'text_length' => strlen($text),
            ]);
            
            throw new Exception("Error al generar embeddings con Ollama: {$e->getMessage()}");
        }
    }
}
