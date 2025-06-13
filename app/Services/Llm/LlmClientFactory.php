<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClientInterface;
use App\Services\Llm\Adapters\LlmAdapterInterface;
use App\Services\Llm\Adapters\OllamaAdapter;
use App\Services\Llm\Adapters\OpenAIAdapter;
use Exception;
use Illuminate\Support\Facades\Config;

class LlmClientFactory
{
    /**
     * Crea un cliente LLM con la configuración especificada
     *
     * @param string|null $provider El proveedor a usar, si es null se usará el predeterminado de la configuración
     * @param array $customConfig Configuración personalizada que sobrescribe la configuración por defecto
     * @return LlmClientInterface El cliente LLM configurado
     * 
     * @throws Exception Si el proveedor no está soportado
     */
    public static function create(?string $provider = null, array $customConfig = []): LlmClientInterface
    {
        // Si no se especifica un proveedor, usar el de la configuración
        $provider = $provider ?? Config::get('llm.provider', 'ollama');
        
        // Obtener la configuración para el proveedor seleccionado
        $providerConfig = Config::get("llm.{$provider}", []);
        if (empty($providerConfig)) {
            throw new Exception("Proveedor LLM no soportado: {$provider}");
        }
        
        // Combinar con la configuración por defecto y la personalizada
        $defaultConfig = Config::get('llm.default', []);
        $config = array_merge($defaultConfig, $providerConfig, $customConfig);
        
        // Crear el adaptador apropiado
        $adapter = self::createAdapter($provider);
        
        // Crear el cliente LLM con el adaptador
        return new LlmClient($adapter, $config);
    }
    
    /**
     * Crea un adaptador para el proveedor especificado
     *
     * @param string $provider
     * @return LlmAdapterInterface
     * 
     * @throws Exception Si el proveedor no está soportado
     */    protected static function createAdapter(string $provider): LlmAdapterInterface
    {
        return match ($provider) {
            'ollama' => new OllamaAdapter(),
            'openai' => new OpenAIAdapter(),
            'mock' => app()->environment('testing') ? app()->make('Tests\Mocks\Llm\MockLlmAdapter') : throw new Exception("Proveedor mock solo disponible en entorno de pruebas"),
            default => throw new Exception("Adaptador para proveedor '{$provider}' no implementado")
        };
    }
}
