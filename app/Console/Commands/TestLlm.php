<?php

namespace App\Console\Commands;

use App\Facades\Llm;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestLlm extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'llm:test 
                            {--provider= : El proveedor a utilizar (ollama, openai, etc.)}
                            {--format= : Formato de salida (texto, json, markdown, etc.)}';
    
    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Prueba la configuración del LLM con un prompt simple';
    
    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $provider = $this->option('provider') ?: Config::get('llm.provider');
        $format = $this->option('format');
        
        $this->info("Probando LLM con proveedor: $provider");
        
        // Solicitar el prompt al usuario
        $prompt = $this->ask('Introduce un prompt para probar el LLM');
        
        try {
            $this->info('Enviando prompt al LLM...');
            $startTime = microtime(true);
            
            // Si se especifica un formato, usar generateStructuredOutput
            if ($format) {
                $this->info("Formato de salida: $format");
                $result = Llm::generateStructuredOutput($prompt, $format);
            } else {
                $result = Llm::generateText($prompt);
            }
            
            $endTime = microtime(true);
            $executionTime = number_format($endTime - $startTime, 2);
            
            $this->info("Tiempo de ejecución: $executionTime segundos");
            $this->newLine();
            
            // Mostrar resultados
            if ($format === 'json' && is_array($result)) {
                $this->info('Respuesta:');
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->info('Respuesta:');
                $this->line($result);
            }
            
            return self::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('Error al comunicarse con el LLM:');
            $this->error($e->getMessage());
            
            return self::FAILURE;
        }
    }
}
