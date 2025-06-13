<?php

namespace App\Contracts\Llm;

use Exception;

/**
 * Interfaz para el cliente LLM
 */
interface LlmClientInterface
{
    /**
     * Genera texto utilizando el LLM
     *
     * @param string $prompt El prompt del usuario
     * @param array $options Opciones adicionales para la generación
     * @return string La respuesta generada por el LLM
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateText(string $prompt, array $options = []): string;
    
    /**
     * Genera salida estructurada en un formato específico
     *
     * @param string $prompt El prompt del usuario
     * @param string $format El formato deseado (json, markdown, etc)
     * @param array $options Opciones adicionales para la generación
     * @return mixed La respuesta generada por el LLM en el formato especificado
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateStructuredOutput(string $prompt, string $format, array $options = []): mixed;
    
    /**
     * Genera embeddings para un texto dado
     *
     * @param string $text El texto para generar embeddings
     * @return array Vector de embeddings
     * 
     * @throws Exception Si ocurre un error durante la generación
     */
    public function generateEmbeddings(string $text): array;
}
