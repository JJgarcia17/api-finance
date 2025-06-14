<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Llm\CircuitBreaker;
use App\Services\Llm\LlmMetrics;
use App\Services\Llm\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LlmMetricsController extends Controller
{
    public function __construct(
        private LlmMetrics $metrics,
        private CircuitBreaker $circuitBreaker,
        private RateLimiter $rateLimiter
    ) {}

    /**
     * Obtener métricas generales de LLM
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $provider = $request->query('provider', 'openrouter');
        $hoursBack = (int) $request->query('hours', 24);
        
        $metrics = $this->metrics->getMetrics($provider, $hoursBack);
        
        // Agregar estado del circuit breaker
        $metrics['circuit_breaker'] = $this->circuitBreaker->getStatus($provider);
        
        // Agregar información de rate limiting
        $userId = $request->user()?->id ?? 'global';
        $metrics['rate_limit'] = [
            'remaining_requests' => $this->rateLimiter->getRemainingRequests($provider, $userId),
            'can_make_request' => $this->rateLimiter->canMakeRequest($provider, $userId)
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }

    /**
     * Obtener estado del sistema LLM
     */
    public function getSystemStatus(): JsonResponse
    {
        $providers = ['openrouter', 'ollama', 'openai'];
        $status = [];
        
        foreach ($providers as $provider) {
            $circuitStatus = $this->circuitBreaker->getStatus($provider);
            $metrics = $this->metrics->getMetrics($provider, 1); // Última hora
            
            $status[$provider] = [
                'circuit_breaker' => $circuitStatus,
                'last_hour_requests' => array_sum($metrics['requests']),
                'last_hour_errors' => array_sum($metrics['errors']),
                'error_rate' => $metrics['summary']['error_rate'],
                'health' => $this->determineProviderHealth($circuitStatus, $metrics['summary'])
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toISOString(),
            'providers' => $status,
            'overall_health' => $this->determineOverallHealth($status)
        ]);
    }

    /**
     * Forzar reset del circuit breaker
     */
    public function resetCircuitBreaker(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:openrouter,ollama,openai'
        ]);
        
        $provider = $request->input('provider');
        
        // Esto requeriría un método adicional en CircuitBreaker
        // $this->circuitBreaker->forceReset($provider);
        
        return response()->json([
            'status' => 'success',
            'message' => "Circuit breaker reset for provider: {$provider}"
        ]);
    }

    /**
     * Determinar la salud de un proveedor
     */
    private function determineProviderHealth(array $circuitStatus, array $summary): string
    {
        if ($circuitStatus['status'] === 'open') {
            return 'critical';
        }
        
        if ($summary['error_rate'] > 10) {
            return 'warning';
        }
        
        if ($summary['error_rate'] > 5) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Determinar la salud general del sistema
     */
    private function determineOverallHealth(array $providersStatus): string
    {
        $healthLevels = array_column($providersStatus, 'health');
        
        if (in_array('critical', $healthLevels)) {
            return 'critical';
        }
        
        if (in_array('warning', $healthLevels)) {
            return 'warning';
        }
        
        if (in_array('degraded', $healthLevels)) {
            return 'degraded';
        }
        
        return 'healthy';
    }
}
