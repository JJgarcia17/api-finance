<?php

namespace App\Services\Llm\Adapters;

/**
 * Interfaz que deben implementar todos los adaptadores LLM
 */
interface LlmAdapterInterface
{
    /**
     * Inicializa el adaptador con la configuración proporcionada
     * 
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void;
    
    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string;
    
    /**
     * Genera texto utilizando el LLM
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     * 
     * @throws \Exception Si ocurre un error durante la generación
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string;
    
    /**
     * Genera embeddings para un texto
     * 
     * @param string $text El texto para generar embeddings
     * @return array El vector de embeddings
     * 
     * @throws \Exception Si ocurre un error durante la generación
     */
    public function generateEmbeddings(string $text): array;
}
