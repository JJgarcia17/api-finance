<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker para APIs LLM
 */
class CircuitBreaker
{
    private const CACHE_PREFIX = 'llm_circuit_breaker_';
    
    public function __construct(
        private int $failureThreshold = 5,
        private int $recoveryTimeMinutes = 10,
        private int $timeoutSeconds = 30
    ) {}

    /**
     * Verificar si el circuito está abierto
     */
    public function isOpen(string $provider): bool
    {
        $state = $this->getState($provider);
        
        if ($state['status'] === 'open') {
            // Verificar si es tiempo de intentar recovery
            if (now()->diffInMinutes($state['opened_at']) >= $this->recoveryTimeMinutes) {
                $this->setState($provider, 'half-open');
                return false; // Permitir una request de prueba
            }
            return true;
        }
        
        return false;
    }

    /**
     * Registrar éxito en la operación
     */
    public function recordSuccess(string $provider): void
    {
        $state = $this->getState($provider);
        
        if ($state['status'] === 'half-open') {
            // Recovery exitoso, cerrar circuito
            $this->setState($provider, 'closed', 0);
            Log::info('Circuit breaker closed after recovery', ['provider' => $provider]);
        } elseif ($state['status'] === 'closed') {
            // Reset failure count en operaciones exitosas
            $this->setState($provider, 'closed', 0);
        }
    }

    /**
     * Registrar fallo en la operación
     */
    public function recordFailure(string $provider): void
    {
        $state = $this->getState($provider);
        $newFailureCount = $state['failure_count'] + 1;
        
        if ($state['status'] === 'half-open') {
            // Falló durante recovery, volver a abrir
            $this->setState($provider, 'open', $newFailureCount);
            Log::warning('Circuit breaker opened again after failed recovery', ['provider' => $provider]);
        } elseif ($newFailureCount >= $this->failureThreshold) {
            // Alcanzado el límite, abrir circuito
            $this->setState($provider, 'open', $newFailureCount);
            Log::warning('Circuit breaker opened due to failure threshold', [
                'provider' => $provider,
                'failure_count' => $newFailureCount,
                'threshold' => $this->failureThreshold
            ]);
        } else {
            // Incrementar contador de fallos
            $this->setState($provider, 'closed', $newFailureCount);
        }
    }

    /**
     * Obtener estado del circuito
     */
    private function getState(string $provider): array
    {
        $key = self::CACHE_PREFIX . $provider;
        
        return Cache::get($key, [
            'status' => 'closed',
            'failure_count' => 0,
            'opened_at' => null
        ]);
    }

    /**
     * Establecer estado del circuito
     */
    private function setState(string $provider, string $status, int $failureCount = null): void
    {
        $key = self::CACHE_PREFIX . $provider;
        $state = $this->getState($provider);
        
        $state['status'] = $status;
        if ($failureCount !== null) {
            $state['failure_count'] = $failureCount;
        }
        if ($status === 'open') {
            $state['opened_at'] = now();
        }
        
        Cache::put($key, $state, now()->addHour()); // TTL de 1 hora
    }

    /**
     * Obtener estado actual para monitoreo
     */
    public function getStatus(string $provider): array
    {
        return $this->getState($provider);
    }
}
