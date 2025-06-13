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
    ],
];
