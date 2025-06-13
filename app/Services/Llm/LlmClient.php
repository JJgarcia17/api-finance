<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClientInterface;
use App\Services\Llm\Adapters\LlmAdapterInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cliente principal para interactuar con LLMs a través de adaptadores
 */
class LlmClient implements LlmClientInterface
{
    /**
     * El adaptador LLM a utilizar
     * 
     * @var LlmAdapterInterface
     */
    protected LlmAdapterInterface $adapter;
    
    /**
     * El proveedor actual (nombre)
     * 
     * @var string
     */
    protected string $provider;
    
    /**
     * El modelo a utilizar
     * 
     * @var string
     */
    protected string $model;
    
    /**
     * Si la caché está habilitada
     * 
     * @var bool
     */
    protected bool $cacheEnabled;
    
    /**
     * Tiempo de vida de la caché en segundos
     * 
     * @var int
     */
    protected int $cacheTtl;
    
    /**
     * El prompt de sistema a utilizar
     * 
     * @var string
     */
    protected string $systemPrompt;
    
    /**
     * Constructor del cliente LLM
     * 
     * @param LlmAdapterInterface $adapter El adaptador a utilizar
     * @param array $config Configuración del cliente
     */
    public function __construct(LlmAdapterInterface $adapter, array $config = [])
    {
        $this->adapter = $adapter;
        
        // Inicializar adaptador con su configuración específica
        $this->adapter->initialize($config);
        
        // Guardar referencia al proveedor
        $this->provider = $this->adapter->getProviderName();
        
        // Configurar cliente
        $this->model = $config['model'] ?? 'default-model';
        $this->cacheEnabled = $config['cache_enabled'] ?? true;
        $this->cacheTtl = $config['cache_ttl'] ?? 60 * 60 * 24; // 24 horas por defecto
        $this->systemPrompt = $config['system_prompt'] ?? '';
    }
    
    /**
     * Genera texto utilizando el LLM
     *
     * @param string $prompt El prompt del usuario
     * @param array $options Opciones adicionales para la generación
     * @return string La respuesta generada por el LLM
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateText(string $prompt, array $options = []): string
    {
        // Verificar si el resultado está en caché
        $cacheKey = $this->generateCacheKey($prompt, $options);
        
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Generar respuesta mediante el adaptador
            $result = $this->adapter->generateText($prompt, $this->systemPrompt, $options);
            
            // Guardar en caché si está habilitado
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->logError('Error al generar texto con ' . $this->provider, [
                'model' => $this->model,
                'prompt_length' => strlen($prompt),
            ], $e);
            
            throw $e;
        }
    }
    
    /**
     * Genera salida estructurada en un formato específico
     *
     * @param string $prompt El prompt del usuario
     * @param string $format El formato deseado (json, markdown, etc)
     * @param array $options Opciones adicionales para la generación
     * @return mixed La respuesta generada por el LLM en el formato especificado
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateStructuredOutput(string $prompt, string $format, array $options = []): mixed
    {
        $formattedPrompt = $this->prepareStructuredPrompt($prompt, $format);
        
        $result = $this->generateText($formattedPrompt, $options);
        
        return $this->parseStructuredOutput($result, $format);
    }
    
    /**
     * Genera embeddings para un texto dado
     *
     * @param string $text El texto para generar embeddings
     * @return array Vector de embeddings
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateEmbeddings(string $text): array
    {
        try {
            return $this->adapter->generateEmbeddings($text);
            
        } catch (Exception $e) {
            $this->logError('Error al generar embeddings con ' . $this->provider, [
                'model' => $this->model,
                'text_length' => strlen($text),
            ], $e);
            
            throw $e;
        }
    }
    
    /**
     * Prepara un prompt para generar salida estructurada
     *
     * @param string $prompt
     * @param string $format
     * @return string
     */
    protected function prepareStructuredPrompt(string $prompt, string $format): string
    {
        $formatInstructions = match ($format) {
            'json' => 'Responde únicamente con un JSON válido y bien formateado. No incluyas ningún texto adicional ni marcadores de código.',
            'markdown' => 'Responde utilizando formato Markdown.',
            'html' => 'Responde con HTML válido y bien formateado.',
            'csv' => 'Responde con datos en formato CSV válido.',
            default => "Responde utilizando el formato {$format}.",
        };
        
        return "{$prompt}\n\n{$formatInstructions}";
    }
    
    /**
     * Parsea la salida estructurada según el formato
     *
     * @param string $output
     * @param string $format
     * @return mixed
     */
    protected function parseStructuredOutput(string $output, string $format): mixed
    {
        try {
            return match ($format) {
                'json' => $this->parseJsonOutput($output),
                default => $output,
            };
        } catch (Exception $e) {
            $this->logWarning('Error al parsear la salida estructurada', [
                'format' => $format,
                'output' => $output,
            ], $e);
            
            return $output;
        }
    }
    
    /**
     * Parsea la salida JSON y maneja casos especiales
     *
     * @param string $output
     * @return mixed
     */
    protected function parseJsonOutput(string $output): mixed
    {
        // Eliminar marcadores de código si están presentes
        $output = preg_replace('/```json\s*|\s*```/', '', $output);
        
        return json_decode($output, true);
    }
    
    /**
     * Genera una clave de caché para un prompt y opciones
     *
     * @param string $prompt
     * @param array $options
     * @return string
     */
    protected function generateCacheKey(string $prompt, array $options): string
    {
        $keyParts = [
            'llm',
            $this->provider,
            $this->model,
            md5($prompt),
            md5($this->systemPrompt),
            md5(json_encode($options)),
        ];
        
        return implode(':', $keyParts);
    }
    
    /**
     * Log de error con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @param Exception|null $exception
     * @return void
     */
    protected function logError(string $message, array $context = [], ?Exception $exception = null): void
    {
        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['trace'] = $exception->getTraceAsString();
        }
        
        Log::error($message, $context);
    }
    
    /**
     * Log de advertencia con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @param Exception|null $exception
     * @return void
     */
    protected function logWarning(string $message, array $context = [], ?Exception $exception = null): void
    {
        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['trace'] = $exception->getTraceAsString();
        }
        
        Log::warning($message, $context);
    }
}
