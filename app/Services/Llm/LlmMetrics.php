<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sistema de métricas para APIs LLM
 */
class LlmMetrics
{
    private const CACHE_PREFIX = 'llm_metrics_';
    private const METRICS_TTL = 60 * 60 * 24; // 24 horas

    /**
     * Registrar una métrica de request
     */
    public function recordRequest(string $provider, array $metadata = []): void
    {
        $timestamp = now();
        $hour = $timestamp->format('Y-m-d-H');
        
        // Incrementar contador de requests por hora
        $this->incrementCounter("requests_{$provider}_{$hour}");
        
        // Registrar latencia si está disponible
        if (isset($metadata['latency_ms'])) {
            $this->recordLatency($provider, $metadata['latency_ms']);
        }
        
        // Registrar tamaño de tokens si está disponible
        if (isset($metadata['tokens'])) {
            $this->recordTokenUsage($provider, $metadata['tokens']);
        }
        
        Log::info('LLM request metric recorded', [
            'provider' => $provider,
            'hour' => $hour,
            'metadata' => $metadata
        ]);
    }

    /**
     * Registrar error
     */
    public function recordError(string $provider, string $errorType, array $metadata = []): void
    {
        $timestamp = now();
        $hour = $timestamp->format('Y-m-d-H');
        
        // Incrementar contador de errores
        $this->incrementCounter("errors_{$provider}_{$hour}");
        $this->incrementCounter("errors_{$provider}_{$errorType}_{$hour}");
        
        Log::warning('LLM error metric recorded', [
            'provider' => $provider,
            'error_type' => $errorType,
            'hour' => $hour,
            'metadata' => $metadata
        ]);
    }

    /**
     * Registrar latencia
     */
    private function recordLatency(string $provider, int $latencyMs): void
    {
        $key = "latency_{$provider}";
        $latencies = Cache::get($key, []);
        
        $latencies[] = [
            'timestamp' => now()->timestamp,
            'latency_ms' => $latencyMs
        ];
        
        // Mantener solo últimas 1000 latencias
        if (count($latencies) > 1000) {
            $latencies = array_slice($latencies, -1000);
        }
        
        Cache::put($key, $latencies, self::METRICS_TTL);
    }

    /**
     * Registrar uso de tokens
     */
    private function recordTokenUsage(string $provider, array $tokens): void
    {
        $hour = now()->format('Y-m-d-H');
        
        if (isset($tokens['prompt_tokens'])) {
            $this->addToCounter("tokens_prompt_{$provider}_{$hour}", $tokens['prompt_tokens']);
        }
        
        if (isset($tokens['completion_tokens'])) {
            $this->addToCounter("tokens_completion_{$provider}_{$hour}", $tokens['completion_tokens']);
        }
        
        if (isset($tokens['total_tokens'])) {
            $this->addToCounter("tokens_total_{$provider}_{$hour}", $tokens['total_tokens']);
        }
    }

    /**
     * Obtener métricas de un proveedor
     */
    public function getMetrics(string $provider, int $hoursBack = 24): array
    {
        $metrics = [
            'provider' => $provider,
            'period_hours' => $hoursBack,
            'requests' => [],
            'errors' => [],
            'latency' => $this->getLatencyStats($provider),
            'tokens' => [],
            'summary' => []
        ];

        $now = now();
        
        for ($i = 0; $i < $hoursBack; $i++) {
            $hour = $now->copy()->subHours($i)->format('Y-m-d-H');
            
            $requests = $this->getCounter("requests_{$provider}_{$hour}");
            $errors = $this->getCounter("errors_{$provider}_{$hour}");
            $tokens = $this->getCounter("tokens_total_{$provider}_{$hour}");
            
            $metrics['requests'][$hour] = $requests;
            $metrics['errors'][$hour] = $errors;
            $metrics['tokens'][$hour] = $tokens;
        }

        // Calcular resumen
        $metrics['summary'] = [
            'total_requests' => array_sum($metrics['requests']),
            'total_errors' => array_sum($metrics['errors']),
            'total_tokens' => array_sum($metrics['tokens']),
            'error_rate' => $this->calculateErrorRate($metrics['requests'], $metrics['errors']),
            'avg_latency_ms' => $metrics['latency']['avg'] ?? null
        ];

        return $metrics;
    }

    /**
     * Obtener estadísticas de latencia
     */
    private function getLatencyStats(string $provider): array
    {
        $key = "latency_{$provider}";
        $latencies = Cache::get($key, []);
        
        if (empty($latencies)) {
            return [];
        }
        
        $values = array_column($latencies, 'latency_ms');
        sort($values);
        
        return [
            'count' => count($values),
            'min' => min($values),
            'max' => max($values),
            'avg' => (int) array_sum($values) / count($values),
            'p50' => $this->percentile($values, 50),
            'p95' => $this->percentile($values, 95),
            'p99' => $this->percentile($values, 99)
        ];
    }

    /**
     * Calcular percentil
     */
    private function percentile(array $values, int $percentile): int
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if ($index == (int) $index) {
            return $values[$index];
        }
        
        $lower = $values[(int) $index];
        $upper = $values[(int) $index + 1];
        
        return (int) ($lower + ($upper - $lower) * ($index - (int) $index));
    }

    /**
     * Calcular tasa de error
     */
    private function calculateErrorRate(array $requests, array $errors): float
    {
        $totalRequests = array_sum($requests);
        $totalErrors = array_sum($errors);
        
        if ($totalRequests === 0) {
            return 0.0;
        }
        
        return round(($totalErrors / $totalRequests) * 100, 2);
    }

    /**
     * Incrementar contador
     */
    private function incrementCounter(string $key): void
    {
        $this->addToCounter($key, 1);
    }

    /**
     * Agregar valor a contador
     */
    private function addToCounter(string $key, int $value): void
    {
        $fullKey = self::CACHE_PREFIX . $key;
        $current = Cache::get($fullKey, 0);
        Cache::put($fullKey, $current + $value, self::METRICS_TTL);
    }

    /**
     * Obtener valor de contador
     */
    private function getCounter(string $key): int
    {
        $fullKey = self::CACHE_PREFIX . $key;
        return Cache::get($fullKey, 0);
    }

    /**
     * Limpiar métricas antiguas
     */
    public function cleanupOldMetrics(): void
    {
        // Este método se puede llamar desde un comando artisan
        Log::info('Cleaning up old LLM metrics');
        
        // Laravel Cache no tiene un método directo para limpiar por patrón
        // pero se puede implementar según el driver usado
    }
}
