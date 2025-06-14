<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClientInterface;
use App\Services\Llm\Adapters\LlmAdapterInterface;
use App\Services\Llm\RateLimiter;
use App\Services\Llm\CircuitBreaker;
use App\Services\Llm\RetryHandler;
use App\Services\Llm\LlmMetrics;
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
     * Rate limiter para controlar requests
     * 
     * @var RateLimiter
     */
    protected RateLimiter $rateLimiter;

    /**
     * Circuit breaker para manejo de fallos
     * 
     * @var CircuitBreaker
     */
    protected CircuitBreaker $circuitBreaker;

    /**
     * Retry handler para reintentos
     * 
     * @var RetryHandler
     */
    protected RetryHandler $retryHandler;

    /**
     * Sistema de métricas
     * 
     * @var LlmMetrics
     */
    protected LlmMetrics $metrics;
    
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

        // Inicializar componentes de resiliencia
        $this->rateLimiter = new RateLimiter(
            $config['rate_limit_max_requests'] ?? 100,
            $config['rate_limit_window_minutes'] ?? 60
        );
        
        $this->circuitBreaker = new CircuitBreaker(
            $config['circuit_breaker_failure_threshold'] ?? 5,
            $config['circuit_breaker_recovery_time_minutes'] ?? 10,
            $config['circuit_breaker_timeout_seconds'] ?? 30
        );
        
        $this->retryHandler = RetryHandler::create([
            'max_retries' => $config['retry_max_attempts'] ?? 3,
            'base_delay_ms' => $config['retry_base_delay_ms'] ?? 1000,
            'backoff_multiplier' => $config['retry_backoff_multiplier'] ?? 2.0
        ]);

        // Inicializar sistema de métricas
        $this->metrics = new LlmMetrics();
    }
    
    /**
     * Genera texto utilizando el LLM con resiliencia
     *
     * @param string $prompt El prompt del usuario
     * @param array $options Opciones adicionales para la generación
     * @return string La respuesta generada por el LLM
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateText(string $prompt, array $options = []): string
    {
        // Verificar rate limiting
        $userId = $options['user_id'] ?? 'global';
        if (!$this->rateLimiter->canMakeRequest($this->provider, $userId)) {
            throw new Exception("Rate limit exceeded for provider {$this->provider}");
        }

        // Verificar circuit breaker
        if ($this->circuitBreaker->isOpen($this->provider)) {
            throw new Exception("Circuit breaker is open for provider {$this->provider}");
        }

        // Verificar caché si está habilitada
        if ($this->cacheEnabled) {
            $cacheKey = $this->generateCacheKey($prompt, $options);
            $cachedResult = Cache::get($cacheKey);
            
            if ($cachedResult) {
                // Registrar cache hit en métricas
                $this->metrics->recordRequest($this->provider, [
                    'latency_ms' => 0,
                    'prompt_length' => strlen($prompt),
                    'response_length' => strlen($cachedResult),
                    'user_id' => $userId,
                    'cache_hit' => true
                ]);

                $this->logInfo('Cache hit for LLM request', [
                    'provider' => $this->provider,
                    'prompt_hash' => md5($prompt)
                ]);
                return $cachedResult;
            }
        }

        try {
            // Registrar request en rate limiter
            $this->rateLimiter->recordRequest($this->provider, $userId);

            // Medir tiempo de ejecución
            $startTime = microtime(true);

            // Ejecutar con retry handler
            $result = $this->retryHandler->execute(function() use ($prompt, $options) {
                return $this->adapter->generateText(
                    $prompt,
                    $this->systemPrompt,
                    $options
                );
            }, "generateText:{$this->provider}");

            $executionTime = (microtime(true) - $startTime) * 1000; // ms

            // Registrar éxito en circuit breaker
            $this->circuitBreaker->recordSuccess($this->provider);

            // Registrar métricas
            $this->metrics->recordRequest($this->provider, [
                'latency_ms' => (int) $executionTime,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($result),
                'user_id' => $userId,
                'cache_hit' => false
            ]);

            // Guardar en caché si está habilitada
            if ($this->cacheEnabled && $result) {
                $cacheKey = $this->generateCacheKey($prompt, $options);
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            $this->logInfo('LLM text generation successful', [
                'provider' => $this->provider,
                'model' => $this->model,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($result),
                'execution_time_ms' => $executionTime,
                'user_id' => $userId
            ]);

            return $result;

        } catch (Exception $e) {
            // Registrar fallo en circuit breaker
            $this->circuitBreaker->recordFailure($this->provider);

            // Registrar error en métricas
            $this->metrics->recordError($this->provider, $this->categorizeError($e), [
                'error_message' => $e->getMessage(),
                'user_id' => $userId
            ]);

            $this->logError('LLM text generation failed', [
                'provider' => $this->provider,
                'model' => $this->model,
                'prompt_length' => strlen($prompt),
                'error' => $e->getMessage(),
                'user_id' => $userId
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
     * Log de información con formato estándar del proyecto
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Categorizar tipo de error para métricas
     * 
     * @param Exception $e
     * @return string
     */
    protected function categorizeError(Exception $e): string
    {
        $message = strtolower($e->getMessage());
        
        if (str_contains($message, 'timeout') || str_contains($message, 'time out')) {
            return 'timeout';
        }
        
        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return 'rate_limit';
        }
        
        if (str_contains($message, 'authentication') || str_contains($message, '401')) {
            return 'authentication';
        }
        
        if (str_contains($message, 'service unavailable') || str_contains($message, '503')) {
            return 'service_unavailable';
        }
        
        if (str_contains($message, 'connection') || str_contains($message, 'network')) {
            return 'connection';
        }
        
        return 'unknown';
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
