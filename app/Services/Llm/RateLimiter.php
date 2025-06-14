<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sistema de Rate Limiting para APIs LLM
 */
class RateLimiter
{
    private const CACHE_PREFIX = 'llm_rate_limit_';
    private const DEFAULT_MAX_REQUESTS = 100;
    private const DEFAULT_WINDOW_MINUTES = 60;

    public function __construct(
        private int $maxRequests = self::DEFAULT_MAX_REQUESTS,
        private int $windowMinutes = self::DEFAULT_WINDOW_MINUTES
    ) {}

    /**
     * Verificar si se puede hacer una request
     */
    public function canMakeRequest(string $provider, string $userId = 'global'): bool
    {
        $key = $this->getCacheKey($provider, $userId);
        $current = Cache::get($key, 0);
        
        return $current < $this->maxRequests;
    }

    /**
     * Registrar una request realizada
     */
    public function recordRequest(string $provider, string $userId = 'global'): void
    {
        $key = $this->getCacheKey($provider, $userId);
        $current = Cache::get($key, 0);
        
        Cache::put($key, $current + 1, now()->addMinutes($this->windowMinutes));
        
        Log::info('LLM request recorded', [
            'provider' => $provider,
            'user_id' => $userId,
            'current_requests' => $current + 1,
            'max_requests' => $this->maxRequests
        ]);
    }

    /**
     * Obtener requests restantes
     */
    public function getRemainingRequests(string $provider, string $userId = 'global'): int
    {
        $key = $this->getCacheKey($provider, $userId);
        $current = Cache::get($key, 0);
        
        return max(0, $this->maxRequests - $current);
    }

    private function getCacheKey(string $provider, string $userId): string
    {
        return self::CACHE_PREFIX . $provider . '_' . $userId . '_' . now()->format('Y-m-d-H');
    }
}
