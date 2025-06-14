<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Provider
    |--------------------------------------------------------------------------
    |
    | Este valor determina qué proveedor LLM se utilizará por defecto.
    | Valores soportados: "ollama", "openai" u otros proveedores configurados.
    |
    */
    'provider' => env('LLM_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Configuración para Ollama (Modelos locales)
    |--------------------------------------------------------------------------
    */
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3'),
        'timeout' => env('OLLAMA_TIMEOUT', 60),
        'options' => [
            'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
            'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
            'context_window' => (int) env('OLLAMA_CONTEXT_WINDOW', 2048),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración para OpenAI
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'options' => [
            'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
            'top_p' => (float) env('OPENAI_TOP_P', 0.9),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1024),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración para OpenRouter (Modelos gratuitos y de pago)
    |--------------------------------------------------------------------------
    */
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-r1-0528-qwen3-8b:free'),
        'timeout' => env('OPENROUTER_TIMEOUT', 120),
        'site_url' => env('OPENROUTER_SITE_URL', ''),
        'site_name' => env('OPENROUTER_SITE_NAME', ''),
        'options' => [
            'temperature' => (float) env('OPENROUTER_TEMPERATURE', 0.7),
            'top_p' => (float) env('OPENROUTER_TOP_P', 0.9),
            'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 2048),
        ],
    ],    /*
    |--------------------------------------------------------------------------
    | Configuración para pruebas (Mock)
    |--------------------------------------------------------------------------
    */
    'mock' => [
        'model' => 'test-model',
        'system_prompt' => 'System prompt de prueba',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración predeterminada
    |--------------------------------------------------------------------------
    |
    | Configuración genérica aplicable a cualquier proveedor de LLM
    |
    */
    'default' => [
        'cache_ttl' => (int) env('LLM_CACHE_TTL', 60 * 60 * 24), // 24 horas en segundos
        'cache_enabled' => (bool) env('LLM_CACHE_ENABLED', true),
        'system_prompt' => env('LLM_SYSTEM_PROMPT', 'Eres un asistente de finanzas personales útil y conciso.'),

        // Configuración de resiliencia
        'rate_limit_max_requests' => env('LLM_RATE_LIMIT_MAX_REQUESTS', 100),
        'rate_limit_window_minutes' => env('LLM_RATE_LIMIT_WINDOW_MINUTES', 60),
        
        'circuit_breaker_failure_threshold' => env('LLM_CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),
        'circuit_breaker_recovery_time_minutes' => env('LLM_CIRCUIT_BREAKER_RECOVERY_TIME_MINUTES', 10),
        'circuit_breaker_timeout_seconds' => env('LLM_CIRCUIT_BREAKER_TIMEOUT_SECONDS', 30),
        
        'retry_max_attempts' => env('LLM_RETRY_MAX_ATTEMPTS', 3),
        'retry_base_delay_ms' => env('LLM_RETRY_BASE_DELAY_MS', 1000),
        'retry_backoff_multiplier' => env('LLM_RETRY_BACKOFF_MULTIPLIER', 2.0),
        
        // Configuración de métricas
        'metrics_enabled' => env('LLM_METRICS_ENABLED', true),
        'metrics_retention_hours' => env('LLM_METRICS_RETENTION_HOURS', 24 * 7), // 1 semana
    ],
];
