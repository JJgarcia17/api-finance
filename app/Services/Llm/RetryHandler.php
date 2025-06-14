<?php

namespace App\Services\Llm;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Sistema de retry con backoff exponencial para APIs LLM
 */
class RetryHandler
{
    public function __construct(
        private int $maxRetries = 3,
        private int $baseDelayMs = 1000,
        private float $backoffMultiplier = 2.0,
        private array $retryableExceptions = [
            'Connection timeout',
            'Rate limit exceeded',
            'Service unavailable',
            '429',
            '503',
            '504'
        ]
    ) {}

    /**
     * Ejecutar operación con retry
     */
    public function execute(callable $operation, string $context = ''): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                if ($attempt > 0) {
                    $delay = $this->calculateDelay($attempt);
                    Log::info('LLM retry attempt', [
                        'context' => $context,
                        'attempt' => $attempt,
                        'delay_ms' => $delay
                    ]);
                    usleep($delay * 1000); // Convertir ms a microsegundos
                }

                return $operation();

            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                if (!$this->isRetryable($e) || $attempt > $this->maxRetries) {
                    Log::error('LLM operation failed permanently', [
                        'context' => $context,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'retryable' => $this->isRetryable($e)
                    ]);
                    throw $e;
                }

                Log::warning('LLM operation failed, will retry', [
                    'context' => $context,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'next_delay_ms' => $this->calculateDelay($attempt + 1)
                ]);
            }
        }

        throw $lastException ?? new Exception('Unknown error in retry handler');
    }

    /**
     * Calcular delay con backoff exponencial
     */
    private function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * pow($this->backoffMultiplier, $attempt - 1);
        
        // Agregar jitter para evitar thundering herd
        $jitter = random_int(0, (int)($delay * 0.1));
        
        return (int)($delay + $jitter);
    }

    /**
     * Verificar si la excepción es reintentable
     */
    private function isRetryable(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        foreach ($this->retryableExceptions as $retryablePattern) {
            if (str_contains($message, strtolower($retryablePattern))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Crear instancia con configuración personalizada
     */
    public static function create(array $config = []): self
    {
        return new self(
            $config['max_retries'] ?? 3,
            $config['base_delay_ms'] ?? 1000,
            $config['backoff_multiplier'] ?? 2.0,
            $config['retryable_exceptions'] ?? []
        );
    }
}
