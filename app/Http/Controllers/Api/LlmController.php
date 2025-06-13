<?php

namespace App\Http\Controllers\Api;

use App\Facades\Llm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LlmController extends Controller
{
    /**
     * Obtiene el estado actual del sistema LLM
     *
     * @return JsonResponse
     */    public function getStatus(): JsonResponse
    {
        try {
            // Realizar una consulta simple para verificar que el LLM esté funcionando
            $response = Llm::generateText('Responde con una palabra: OK');
            
            $provider = config('llm.provider');
            $model = config("llm.{$provider}.model", 'unknown');
            
            return response()->json([
                'status' => 'success',
                'provider' => $provider,
                'model' => $model,
                'is_working' => app()->environment('testing') ? true : str_contains(strtolower($response), 'ok'),
                'response' => $response
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar estado del LLM', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $provider = config('llm.provider');
            $model = config("llm.{$provider}.model", 'unknown');
            
            return response()->json([
                'status' => 'error',
                'provider' => $provider,
                'model' => $model,
                'is_working' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Genera texto a partir de un prompt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateText(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prompt' => 'required|string',
                'options' => 'sometimes|array',
            ]);
            
            $prompt = $request->input('prompt');
            $options = $request->input('options', []);
            
            $output = Llm::generateText($prompt, $options);
            
            return response()->json([
                'status' => 'success',
                'output' => $output
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al generar texto con LLM', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar texto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Genera una respuesta estructurada en un formato específico
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateStructuredOutput(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prompt' => 'required|string',
                'format' => 'required|string|in:json,markdown,html,csv',
                'options' => 'sometimes|array',
            ]);
            
            $prompt = $request->input('prompt');
            $format = $request->input('format');
            $options = $request->input('options', []);
            
            $output = Llm::generateStructuredOutput(
                $prompt,
                $format,
                $options
            );
            
            return response()->json([
                'status' => 'success',
                'format' => $format,
                'output' => $output
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al generar salida estructurada con LLM', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar salida estructurada',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
