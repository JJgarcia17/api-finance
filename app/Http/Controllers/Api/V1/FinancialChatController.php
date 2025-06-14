<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialChat\SendMessageRequest;
use App\Services\FinancialChat\FinancialChatService;
use App\Services\FinancialChat\FinancialContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialChatController extends Controller
{
    public function __construct(
        private FinancialChatService $chatService,
        private FinancialContextService $contextService
    ) {}    /**
     * Enviar mensaje al bot financiero
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        try {
            // Aumentar el límite de tiempo de ejecución para Ollama
            set_time_limit(180); // 3 minutos
            
            $userId = auth('sanctum')->id();
            $message = $request->validated()['message'];

            $response = $this->chatService->processMessage($message, $userId);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el mensaje',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de la conversación
     */
    public function getConversationStats(Request $request): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $stats = $this->chatService->getConversationStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de conversación',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Limpiar historial de conversación
     */
    public function clearConversation(Request $request): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $result = $this->chatService->clearConversationHistory($userId);

            return response()->json([
                'success' => true,
                'message' => 'Historial de conversación limpiado exitosamente',
                'data' => ['cleared' => $result]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar conversación',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener sugerencias predefinidas
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        try {
            $suggestions = [
                'general' => [
                    '¿Cómo están mis finanzas?',
                    '¿Cuál es mi balance actual?',
                    'Muéstrame mis gastos de este mes',
                    '¿Tengo presupuestos activos?'
                ],
                'analysis' => [
                    'Analiza mis patrones de gasto',
                    '¿En qué categoría gasto más?',
                    'Compara mis ingresos y gastos',
                    '¿Cuáles son mis transacciones más grandes?'
                ],
                'budgets' => [
                    '¿Cómo van mis presupuestos?',
                    '¿He excedido algún presupuesto?',
                    'Dame consejos para mis presupuestos',
                    '¿Qué presupuestos necesito ajustar?'
                ],
                'planning' => [
                    'Dame consejos de ahorro',
                    '¿Cómo puedo mejorar mis finanzas?',
                    'Ayúdame a planificar mi presupuesto',
                    '¿Qué metas financieras debería tener?'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sugerencias',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener resumen financiero rápido
     */
    public function getFinancialSummary(Request $request): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $summary = $this->contextService->getQuickSummary($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen financiero',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
