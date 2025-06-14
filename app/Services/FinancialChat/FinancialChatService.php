<?php

namespace App\Services\FinancialChat;

use App\Contracts\Llm\LlmClientInterface;
use App\Services\Account\AccountService;
use App\Services\Budget\BudgetService;
use App\Services\Transaction\TransactionService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FinancialChatService
{
    use HasAuthenticatedUser, HasLogging;    private const CONVERSATION_CACHE_PREFIX = 'financial_chat_conversation_';
    private const CONVERSATION_TTL = 60 * 60 * 2; // 2 horas
    private const MAX_CONVERSATION_HISTORY = 4; // Solo últimos 2 intercambios para evitar prompts largos

    public function __construct(
        private LlmClientInterface $llmClient,
        private FinancialContextService $contextService,
        private AccountService $accountService,
        private TransactionService $transactionService,
        private BudgetService $budgetService
    ) {}    /**
     * Procesar mensaje del usuario y generar respuesta
     */
    public function processMessage(string $userMessage, int $userId): array
    {
        try {            $this->logInfo('Processing chat message', [
                'user_id' => $userId,
                'message_length' => strlen($userMessage),
                'history_enabled' => true
            ]);

            // Obtener historial de conversación para contexto
            $conversationHistory = $this->getConversationHistory($userId);
            
            // Determinar si necesitamos contexto financiero (sin considerar historial)
            $needsFinancialContext = $this->messageNeedsFinancialContext($userMessage, $conversationHistory);
              // Solo obtener contexto financiero si es necesario
            $financialContext = [];
            if ($needsFinancialContext) {
                $financialContext = $this->contextService->buildUserFinancialContext($userId);
            }
            
            // Construir prompt para el LLM
            $prompt = $this->buildConversationPrompt(
                $userMessage,
                $conversationHistory,
                $financialContext,
                $needsFinancialContext
            );            // Generar respuesta usando LLM
            $response = $this->llmClient->generateText($prompt);
            
            // FORMATEO POST-PROCESAMIENTO: Mejorar formato si contiene datos financieros
            if ($needsFinancialContext && !empty($financialContext)) {
                $response = $this->enhanceResponseFormatting($response, $userMessage, $financialContext);
            }
              // Actualizar historial de conversación
            $this->updateConversationHistory($userId, $userMessage, $response);
              // Preparar respuesta estructurada
            $result = [
                'response' => $response,
                'timestamp' => now()->toISOString(),
                'context_used' => $needsFinancialContext,
                'suggestions' => $needsFinancialContext ? $this->generateSuggestions($userMessage, $financialContext) : []
            ];

            $this->logInfo('Chat response generated successfully', [
                'user_id' => $userId,
                'response_length' => strlen($response),
                'context_used' => $needsFinancialContext
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logError('Error processing chat message', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ], $e);
            
            return [
                'response' => 'Lo siento, ocurrió un error al procesar tu mensaje. Por favor, inténtalo de nuevo.',
                'timestamp' => now()->toISOString(),
                'context_used' => false,
                'error' => true
            ];
        }
    }    /**
     * Construir prompt para la conversación
     */    private function buildConversationPrompt(
        string $userMessage,
        array $conversationHistory,
        array $financialContext,
        bool $needsFinancialContext
    ): string {        $systemPrompt = $this->getSystemPrompt();
        
        // Log detallado del contexto
        $this->logInfo('Building conversation prompt', [
            'user_message_length' => strlen($userMessage),
            'needs_financial_context' => $needsFinancialContext,
            'has_financial_context_data' => !empty($financialContext),
            'financial_context_keys' => array_keys($financialContext),
            'accounts_count' => count($financialContext['accounts'] ?? []),
            'transactions_count' => count($financialContext['transactions'] ?? []),
            'budgets_count' => count($financialContext['budgets'] ?? []),
            'history_enabled' => true,
            'history_length' => count($conversationHistory)
        ]);        $prompt = "{$systemPrompt}\n\n";
        
        // Solo agregar contexto financiero si es necesario
        if ($needsFinancialContext && !empty($financialContext)) {
            $contextSection = $this->formatFinancialContext($financialContext);
            $prompt .= "CONTEXTO FINANCIERO DEL USUARIO:\n{$contextSection}\n\n";
        } else {
            $prompt .= "NOTA: Esta es una consulta general, responde de manera amigable y conversacional.\n\n";
        }        
        
        // Incluir historial reciente si existe
        if (!empty($conversationHistory)) {
            $historySection = $this->formatConversationHistory($conversationHistory);
            $prompt .= "Historial reciente de la conversación:\n{$historySection}\n\n";
        }
        
        $prompt .= "Usuario: {$userMessage}\n\n";
        $prompt .= "Responde de manera útil:";        $this->logInfo('Final prompt built', [
            'prompt_length' => strlen($prompt),
            'included_financial_context' => $needsFinancialContext && !empty($financialContext),
            'history_enabled' => true,
            'history_length' => count($conversationHistory)
        ]);

        return $prompt;
    }/**
     * Determinar si el mensaje necesita contexto financiero
     */
    private function messageNeedsFinancialContext(string $message, array $conversationHistory = []): bool
    {
        $messageLower = strtolower(trim($message));
        
        $this->logInfo('Analyzing message for financial context', [
            'message' => $messageLower,
            'length' => strlen($messageLower),
            'has_history' => !empty($conversationHistory)
        ]);
        
        // Primero verificar si es una pregunta de seguimiento
        if ($this->isFollowUpQuestion($messageLower, $conversationHistory)) {
            $this->logInfo('Message detected as financial follow-up', [
                'needs_context' => true
            ]);
            return true;
        }
          // Patrones específicos que NO necesitan contexto financiero (consultas generales/conversacionales)
        $simplePatterns = [
            // Saludos básicos
            'hola', 'hi', 'hello', 'hey', 'buenos días', 'buenas tardes', 'buenas noches',
            // Solicitudes de ayuda general (sin palabras financieras)
            'ayuda', 'help', 'qué puedes hacer', 'que puedes hacer', '¿qué puedes hacer?', '¿que puedes hacer?',
            // Agradecimientos y confirmaciones básicas
            'gracias', 'thank you', 'ok', 'vale', 'perfecto', 'entiendo', 'bien',
            // Presentaciones del bot
            'quién eres', 'quien eres', 'qué eres', 'que eres', 'cómo te llamas', 'como te llamas'
        ];
        
        // Para "¿puedes ayudarme?" verificar si contiene contexto financiero en la misma frase
        if (str_contains($messageLower, 'puedes ayudarme') || str_contains($messageLower, '¿puedes ayudarme?')) {
            // Si también contiene palabras financieras, necesita contexto
            foreach ($financialKeywords as $keyword) {
                if (str_contains($messageLower, $keyword)) {
                    $this->logInfo('Help request with financial context detected', [
                        'keyword_matched' => $keyword,
                        'needs_context' => true
                    ]);
                    return true;
                }
            }
            // Si no contiene palabras financieras, es ayuda general
            $this->logInfo('General help request detected', [
                'needs_context' => false
            ]);
            return false;
        }
        
        // Verificar patrones simples primero
        foreach ($simplePatterns as $pattern) {
            if ($messageLower === $pattern || str_contains($messageLower, $pattern)) {
                $this->logInfo('Message detected as simple query', [
                    'pattern_matched' => $pattern,
                    'needs_context' => false
                ]);
                return false;
            }
        }        // Patrones específicos que SÍ indican necesidad de contexto financiero
        $financialKeywords = [
            'balance', 'saldo', 'cuenta', 'cuentas', 'dinero', 'finanzas', 'financiero', 'financiera',
            'gasto', 'gastos', 'ingreso', 'ingresos', 'presupuesto', 'presupuestos',
            'transacción', 'transacciones', 'ahorro', 'ahorros', 'patrimonio',
            'deuda', 'deudas', 'inversión', 'inversiones', 'económico', 'económica',
            'budget', 'money', 'expense', 'expenses', 'income', 'saving', 'savings',
            'cuánto tengo', 'cuanto tengo', 'mi dinero', 'mis cuentas', 'mis gastos',
            // Palabras de seguimiento que pueden indicar consulta financiera
            'detalladamente', 'detallado', 'detalle', 'detalles', 'muestrame', 'muéstrame',
            'más información', 'mas informacion', 'explícame', 'explicame', 'analiza',
            'análisis', 'situación financiera', 'estado financiero'
        ];

        // Detectar seguimientos financieros específicos
        $followUpPatterns = [
            'si muestrame detalladamente', 'si muéstrame detalladamente', 'sí muestrame detalladamente', 'sí muéstrame detalladamente',
            'si detalladamente', 'sí detalladamente', 'si, detalladamente', 'sí, detalladamente',
            'muestrame detalladamente', 'muéstrame detalladamente', 'por favor detalladamente',
            'dame más información', 'dame mas información', 'más detalles', 'mas detalles',
            'explícame mejor', 'explicame mejor', 'con más detalle', 'con mas detalle'
        ];

        // Verificar patrones de seguimiento primero
        foreach ($followUpPatterns as $pattern) {
            if (str_contains($messageLower, $pattern)) {
                $this->logInfo('Financial follow-up pattern detected', [
                    'pattern_matched' => $pattern,
                    'needs_context' => true
                ]);
                return true;
            }
        }
        
        foreach ($financialKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $this->logInfo('Message detected as financial query', [
                    'keyword_matched' => $keyword,
                    'needs_context' => true
                ]);
                return true;
            }
        }
        
        // Por defecto, para mensajes ambiguos cortos (menos de 20 caracteres), no incluir contexto
        if (strlen($messageLower) < 20) {
            $this->logInfo('Short ambiguous message, no context needed', [
                'needs_context' => false
            ]);
            return false;
        }
        
        // Para mensajes más largos y ambiguos, tampoco incluir contexto por defecto
        $this->logInfo('Long ambiguous message, no context needed by default', [
            'needs_context' => false
        ]);
        return false;
    }    /**
     * Verificar si el mensaje es una pregunta de seguimiento que necesita contexto financiero
     */
    private function isFollowUpQuestion(string $messageLower, array $conversationHistory): bool
    {
        // Si no hay historial, no puede ser seguimiento
        if (empty($conversationHistory)) {
            return false;
        }

        // Patrones que indican seguimiento
        $followUpPatterns = [
            'si', 'sí', 'claro', 'por favor', 'dale', 'ok', 'perfecto',
            'si muestrame', 'sí muéstrame', 'si muéstrame', 'sí muestrame',
            'si detalladamente', 'sí detalladamente', 'si, detalladamente',
            'muestrame', 'muéstrame', 'explícame', 'explicame',
            'dame más', 'dame mas', 'más información', 'mas informacion',
            'con más detalle', 'con mas detalle', 'detalladamente'
        ];

        foreach ($followUpPatterns as $pattern) {
            if (str_contains($messageLower, $pattern)) {
                // Verificar si el último mensaje del bot mencionó datos financieros
                $lastBotResponse = end($conversationHistory)['bot_response'] ?? '';
                $lastBotLower = strtolower($lastBotResponse);
                
                $financialTerms = ['balance', 'cuenta', 'gasto', 'ingreso', 'presupuesto', 'transacción', 'ahorro'];
                foreach ($financialTerms as $term) {
                    if (str_contains($lastBotLower, $term)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }/**
     * Obtener prompt del sistema
     */
    private function getSystemPrompt(): string
    {
        return "Eres FinBot, un asistente financiero personal amigable y útil. 

INSTRUCCIONES DE FORMATO:
- Para preguntas generales (saludos, presentaciones, ayuda): responde de manera conversacional sin mencionar datos financieros específicos
- Para preguntas financieras específicas: usa los datos del contexto financiero proporcionado para dar consejos precisos
- Usa un formato limpio y legible con listas simples y emojis cuando sea apropiado
- Para datos numéricos, usa puntos (•) y líneas separadoras simples (━━━)
- NO uses caracteres especiales de tabla (┌, │, ┐, ├, ┤, └, ┘, ─)
- Siempre responde en español
- Sé conciso pero informativo
- Mantén un tono amigable y profesional

CAPACIDADES: Puedo ayudarte con análisis de tus cuentas, gastos, ingresos, presupuestos y darte consejos personalizados de ahorro e inversión.";
    }/**
     * Formatear contexto financiero para el prompt
     */
    private function formatFinancialContext(array $context): string
    {
        $formatted = [];

        // Usar el resumen general si está disponible (más conciso)
        if (isset($context['summary']) && !empty($context['summary'])) {
            $formatted[] = $context['summary'];
        }

        // Solo mostrar el total de cuentas si no hay resumen
        if (!isset($context['summary']) && isset($context['accounts']) && !empty($context['accounts'])) {
            $totalBalance = array_sum(array_column($context['accounts'], 'balance'));
            $accountCount = count($context['accounts']);
            $formatted[] = "Balance total: $" . number_format($totalBalance, 2) . " en {$accountCount} cuenta(s)";
        }

        // Solo mencionar que hay transacciones disponibles
        if (isset($context['transactions']) && !empty($context['transactions'])) {
            $transactionCount = count($context['transactions']);
            $formatted[] = "{$transactionCount} transacciones recientes disponibles";
        }

        // Solo mencionar presupuestos activos
        if (isset($context['budgets']) && !empty($context['budgets'])) {
            $budgetCount = count($context['budgets']);
            $formatted[] = "{$budgetCount} presupuesto(s) activo(s)";
        }

        if (empty($formatted)) {
            return "No hay datos financieros disponibles.";
        }        return implode(". ", $formatted) . ".";
    }    /**
     * Mejorar el formateo de la respuesta con datos financieros estructurados
     */
    private function enhanceResponseFormatting(string $response, string $userMessage, array $financialContext): string
    {
        $userMessageLower = strtolower($userMessage);
        
        // Si la respuesta ya tiene formato estructurado, simplificarla para mejor legibilidad
        if (str_contains($response, '┌') || str_contains($response, '│')) {
            return $this->simplifyTableFormat($response);
        }
        
        // Si ya tiene formato de lista (con **), mantenerlo
        if (str_contains($response, '**')) {
            return $response;
        }
        
        // Detectar si el usuario pidió información específica para formatear
        $shouldFormatTransactions = str_contains($userMessageLower, 'transacciones') || 
                                   str_contains($userMessageLower, 'transaccion') ||
                                   str_contains($userMessageLower, 'gastos') ||
                                   str_contains($userMessageLower, 'movimientos');
                                   
        $shouldFormatBalance = str_contains($userMessageLower, 'balance') ||
                              str_contains($userMessageLower, 'saldo') ||
                              str_contains($userMessageLower, 'cuentas') ||
                              str_contains($userMessageLower, 'resumen');
                              
        $shouldFormatDetailed = str_contains($userMessageLower, 'detalladamente') ||
                               str_contains($userMessageLower, 'detallado') ||
                               str_contains($userMessageLower, 'muéstrame') ||
                               str_contains($userMessageLower, 'muestrame');
        
        // Agregar formato estructurado basado en el contexto disponible
        $enhancedResponse = $response;
        
        // Si hay transacciones y se pidieron, agregarlas formateadas
        if ($shouldFormatTransactions && isset($financialContext['transactions']) && !empty($financialContext['transactions'])) {
            $transactionsList = $this->formatTransactionsList($financialContext['transactions']);
            $enhancedResponse .= "\n\n" . $transactionsList;
        }
        
        // Si hay información de balance y se pidió, agregarla formateada
        if ($shouldFormatBalance && isset($financialContext['accounts']) && !empty($financialContext['accounts'])) {
            $balanceList = $this->formatAccountsList($financialContext['accounts']);
            $enhancedResponse .= "\n\n" . $balanceList;
        }
        
        // Si se pidió información detallada, agregar resumen financiero completo
        if ($shouldFormatDetailed) {
            $detailedSummary = $this->formatDetailedSummaryList($financialContext);
            $enhancedResponse .= "\n\n" . $detailedSummary;
        }
        
        return $enhancedResponse;
    }

    /**
     * Simplificar formato de tabla a lista legible
     */
    private function simplifyTableFormat(string $response): string
    {
        // Convertir tablas ASCII a listas simples
        $simplified = $response;
        
        // Remover líneas de tabla
        $simplified = preg_replace('/[┌┐├┤└┘─│]+/', '', $simplified);
        
        // Convertir filas de tabla a elementos de lista
        if (preg_match_all('/\s*([^│\n]+)\s*\|\s*([^│\n]+)\s*/', $simplified, $matches, PREG_SET_ORDER)) {
            $listItems = [];
            foreach ($matches as $match) {
                $key = trim($match[1]);
                $value = trim($match[2]);
                if (!empty($key) && !empty($value) && 
                    $key !== 'Concepto' && $key !== 'Aspecto' && $key !== '#' && 
                    $key !== 'Descripción' && $key !== 'Nombre de Cuenta') {
                    $listItems[] = "• {$key}: {$value}";
                }
            }
            
            if (!empty($listItems)) {
                $listText = implode("\n", $listItems);
                // Reemplazar la primera tabla encontrada
                $simplified = preg_replace('/[┌│└].*?[┐│┘]/s', $listText, $simplified, 1);
            }
        }
        
        // Limpiar líneas vacías múltiples
        $simplified = preg_replace('/\n{3,}/', "\n\n", $simplified);
        
        return trim($simplified);
    }

    /**
     * Formatear transacciones como lista legible
     */
    private function formatTransactionsList(array $transactions): string
    {
        if (empty($transactions)) {
            return "📊 Transacciones Recientes\n\nNo hay transacciones disponibles.";
        }
        
        $formatted = "📊 Transacciones Recientes\n\n";
        
        $count = 1;
        foreach (array_slice($transactions, 0, 8) as $transaction) { // Máximo 8 transacciones para no saturar
            $description = $transaction['description'] ?? 'Sin descripción';
            $amount = '$' . number_format(abs($transaction['amount'] ?? 0), 2);
            $date = isset($transaction['date']) ? date('d/m/Y', strtotime($transaction['date'])) : 'N/A';
            
            $formatted .= "{$count}. {$description}\n";
            $formatted .= "   💰 {$amount} • 📅 {$date}\n\n";
            $count++;
        }
        
        return $formatted;
    }

    /**
     * Formatear cuentas como lista legible
     */
    private function formatAccountsList(array $accounts): string
    {
        if (empty($accounts)) {
            return "💰 Resumen de Cuentas\n\nNo hay cuentas disponibles.";
        }
        
        $formatted = "💰 Resumen de Cuentas\n\n";
        
        $totalBalance = 0;
        foreach ($accounts as $account) {
            $name = $account['name'] ?? 'Sin nombre';
            $type = $account['type'] ?? 'N/A';
            $balance = $account['balance'] ?? 0;
            $totalBalance += $balance;
            
            $balanceFormatted = '$' . number_format($balance, 2);
            
            $formatted .= "• {$name} ({$type})\n";
            $formatted .= "  Balance: {$balanceFormatted}\n\n";
        }
        
        $formatted .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $formatted .= "💎 Total: $" . number_format($totalBalance, 2) . "\n";
        
        return $formatted;
    }

    /**
     * Formatear resumen financiero detallado como lista legible
     */
    private function formatDetailedSummaryList(array $context): string
    {
        $summary = "📈 Resumen Financiero Completo\n\n";
        
        // Resumen general si está disponible
        if (isset($context['summary']) && !empty($context['summary'])) {
            $summary .= "📋 Situación General\n";
            $summary .= $context['summary'] . "\n\n";
        }
        
        // Métricas principales
        $summary .= "📊 Métricas Principales\n\n";
        
        if (isset($context['accounts']) && !empty($context['accounts'])) {
            $totalBalance = array_sum(array_column($context['accounts'], 'balance'));
            $accountCount = count($context['accounts']);
            
            $summary .= "• Balance Total: $" . number_format($totalBalance, 2) . "\n";
            $summary .= "• Cuentas Activas: {$accountCount}\n";
        }
        
        if (isset($context['transactions']) && !empty($context['transactions'])) {
            $transactionCount = count($context['transactions']);
            $summary .= "• Transacciones Recientes: {$transactionCount}\n";
        }
        
        if (isset($context['budgets']) && !empty($context['budgets'])) {
            $budgetCount = count($context['budgets']);
            $summary .= "• Presupuestos Activos: {$budgetCount}\n";
        }
        
        $summary .= "\n";
        
        return $summary;
    }    /**
     * Formatear historial de conversación
     */
    private function formatConversationHistory(array $history): string
    {
        if (empty($history)) {
            return '';
        }

        $formatted = [];
        foreach ($history as $exchange) {
            $formatted[] = "Usuario: {$exchange['user_message']}";
            $formatted[] = "Asistente: {$exchange['bot_response']}";
        }

        return implode("\n", $formatted);
    }/**
     * Generar sugerencias basadas en el mensaje y contexto financiero real
     */
    private function generateSuggestions(string $userMessage, array $context): array
    {
        $suggestions = [];
        $message = strtolower($userMessage);

        // Extraer datos reales del contexto
        $totalBalance = 0;
        $accountCount = 0;
        $monthlyIncome = 0;
        $monthlyExpenses = 0;
        $budgetCount = 0;
        $transactionCount = 0;

        if (isset($context['accounts']) && !empty($context['accounts'])) {
            $totalBalance = array_sum(array_column($context['accounts'], 'balance'));
            $accountCount = count($context['accounts']);
        }        if (isset($context['transactions']) && !empty($context['transactions'])) {
            $transactionCount = count($context['transactions']);
            // Calcular ingresos y gastos correctamente basado en el tipo
            foreach ($context['transactions'] as $transaction) {
                if ($transaction['type'] === 'income') {
                    $monthlyIncome += $transaction['amount'];
                } elseif ($transaction['type'] === 'expense') {
                    $monthlyExpenses += $transaction['amount'];
                }
            }
        }

        if (isset($context['budgets']) && !empty($context['budgets'])) {
            $budgetCount = count($context['budgets']);
        }

        // Calcular tasa de ahorro
        $savingsRate = 0;
        if ($monthlyIncome > 0) {
            $savingsRate = (($monthlyIncome - $monthlyExpenses) / $monthlyIncome) * 100;
        }        // Generar sugerencias contextuales basadas en el mensaje y datos reales
        if (str_contains($message, 'balance') || str_contains($message, 'saldo') || str_contains($message, 'cuanto tengo')) {
            if ($totalBalance > 0) {
                $suggestions = [
                    "Optimiza la distribución de tus $" . number_format($totalBalance, 0) . " entre {$accountCount} cuenta(s)",
                    "¿Debo mover dinero entre mis cuentas para mejor rendimiento?",
                    "Estrategia para hacer crecer mi patrimonio de $" . number_format($totalBalance, 0)
                ];
            } else {
                $suggestions = [
                    "Tu balance está en números rojos, necesitas un plan urgente",
                    "¿Cómo salir de deudas con {$accountCount} cuenta(s)?",
                    "Estrategias de emergencia para recuperar estabilidad financiera"
                ];
            }
        } elseif (str_contains($message, 'gasto') || str_contains($message, 'gastar')) {
            if ($monthlyExpenses > 0) {
                $suggestions = [
                    "Analiza tus $" . number_format($monthlyExpenses, 0) . " en gastos: ¿cuáles puedes reducir?",
                    "Top 3 categorías donde más gastas y cómo optimizarlas",
                    "Compara tus gastos actuales vs tu presupuesto disponible"
                ];
            } else {
                $suggestions = [
                    "Comienza a registrar tus gastos para tener mejor control",
                    "¿Cómo crear un sistema de seguimiento de gastos efectivo?",
                    "Establece límites de gasto para diferentes categorías"
                ];
            }
        } elseif (str_contains($message, 'presupuesto') || str_contains($message, 'budget')) {
            if ($budgetCount > 0) {
                $suggestions = [
                    "Revisa el progreso de tus {$budgetCount} presupuesto(s) activo(s)",
                    "¿Qué presupuestos necesitan ajuste basado en tus gastos reales?",
                    "Optimiza tus presupuestos según tus patrones de gasto actuales"
                ];
            } else {
                $suggestions = [
                    "Crea tu primer presupuesto basado en tus gastos de $" . number_format($monthlyExpenses, 0),
                    "¿Cuánto debería presupuestar por categoría según mis ingresos?",
                    "Sistema de presupuestos personalizados para tu situación"
                ];
            }
        } elseif (str_contains($message, 'ahorro') || str_contains($message, 'ahorrar')) {
            if ($savingsRate > 0) {
                $surplus = $monthlyIncome - $monthlyExpenses;
                $suggestions = [
                    "¿Cómo mejorar tu tasa de ahorro del " . number_format($savingsRate, 1) . "% al 20%?",
                    "Invierte tus ahorros mensuales de $" . number_format($surplus, 0) . " inteligentemente",
                    "Automtiza tus ahorros: estrategias para ahorrar sin esfuerzo"
                ];
            } else {
                $suggestions = [
                    "Plan de ahorro de emergencia con ingresos de $" . number_format($monthlyIncome, 0),
                    "Reduce gastos específicos de tus $" . number_format($monthlyExpenses, 0) . " actuales",
                    "¿Cómo empezar a ahorrar aunque sea $50 al mes?"
                ];
            }
        } elseif (str_contains($message, 'ingreso') || str_contains($message, 'salario')) {
            if ($monthlyIncome > 0) {
                $suggestions = [
                    "Estrategias para aumentar tus ingresos de $" . number_format($monthlyIncome, 0),
                    "¿Es suficiente tu salario para tus metas financieras?",
                    "Diversifica tus fuentes de ingresos más allá del salario principal"
                ];
            } else {
                $suggestions = [
                    "¿Cómo generar mis primeros ingresos?",
                    "Oportunidades de ingresos pasivos para empezar",
                    "Plan para establecer flujo de ingresos estable"
                ];
            }
        } elseif (str_contains($message, 'detalle') || str_contains($message, 'análisis') || str_contains($message, 'analisis')) {
            $suggestions = [
                "Muéstrame un análisis completo de mis {$transactionCount} transacciones",
                "¿Cuáles son mis principales patrones de gasto?",
                "Dame recomendaciones específicas para mi perfil financiero"
            ];        } else {
            // Sugerencias inteligentes basadas en el estado financiero específico del usuario
            if ($monthlyIncome == 0 && $monthlyExpenses == 0) {
                // Usuario sin transacciones recientes
                $suggestions = [
                    "Comienza registrando tus primeros ingresos y gastos",
                    "¿Cómo configuro mi primera cuenta bancaria?",
                    "Guía para organizar mis finanzas desde cero"
                ];
            } elseif ($savingsRate < 0) {
                // Usuario gastando más de lo que ingresa
                $deficit = abs($monthlyIncome - $monthlyExpenses);
                $suggestions = [
                    "¡Alerta! Gastas $" . number_format($deficit, 0) . " más de lo que ingresas",
                    "Plan de emergencia para reducir gastos urgentemente",
                    "¿Cómo puedo aumentar mis ingresos rápidamente?"
                ];
            } elseif ($savingsRate < 10) {
                // Tasa de ahorro baja
                $suggestions = [
                    "Tu tasa de ahorro del " . number_format($savingsRate, 1) . "% está por debajo del 20% recomendado",
                    "Identifica gastos innecesarios en tus $" . number_format($monthlyExpenses, 0) . " mensuales",
                    "Estrategias específicas para ahorrar más con tu perfil de gastos"
                ];
            } elseif ($budgetCount == 0 && $monthlyExpenses > 0) {
                // Sin presupuestos pero con gastos
                $suggestions = [
                    "Tienes $" . number_format($monthlyExpenses, 0) . " en gastos sin presupuesto",
                    "Crea presupuestos inteligentes basados en tus categorías de gasto",
                    "¿Cómo distribuir mejor mis ingresos de $" . number_format($monthlyIncome, 0) . "?"
                ];
            } elseif ($accountCount == 1) {
                // Solo una cuenta
                $suggestions = [
                    "¿Debería diversificar más allá de mi única cuenta?",
                    "Ventajas de tener múltiples cuentas para organizar dinero",
                    "¿Qué tipo de cuenta adicional me conviene?"
                ];
            } else {
                // Usuario en buena situación financiera
                $monthlyNetSavings = $monthlyIncome - $monthlyExpenses;
                $suggestions = [
                    "Excelente gestión: ahorras $" . number_format($monthlyNetSavings, 0) . " al mes",
                    "¿Dónde invertir tus ahorros de $" . number_format($totalBalance, 0) . "?",
                    "Estrategias avanzadas para hacer crecer tu patrimonio"
                ];
            }
        }

        $this->logInfo('Generated contextual suggestions', [
            'message_type' => $this->categorizeMessage($message),
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'savings_rate' => $savingsRate,
            'suggestions_count' => count($suggestions)
        ]);

        return array_slice(array_unique($suggestions), 0, 3);
    }

    /**
     * Categorizar el tipo de mensaje para mejor logging
     */
    private function categorizeMessage(string $message): string
    {
        if (str_contains($message, 'balance') || str_contains($message, 'saldo')) return 'balance_inquiry';
        if (str_contains($message, 'gasto')) return 'expense_inquiry';
        if (str_contains($message, 'presupuesto')) return 'budget_inquiry';
        if (str_contains($message, 'ahorro')) return 'savings_inquiry';
        if (str_contains($message, 'ingreso')) return 'income_inquiry';
        if (str_contains($message, 'detalle') || str_contains($message, 'análisis')) return 'detailed_analysis';
        return 'general_inquiry';
    }

    /**
     * Obtener historial de conversación desde cache
     */
    private function getConversationHistory(int $userId): array
    {
        $cacheKey = self::CONVERSATION_CACHE_PREFIX . $userId;
        return Cache::get($cacheKey, []);
    }

    /**
     * Actualizar historial de conversación en cache
     */
    private function updateConversationHistory(int $userId, string $userMessage, string $botResponse): void
    {
        $cacheKey = self::CONVERSATION_CACHE_PREFIX . $userId;
        $history = $this->getConversationHistory($userId);

        // Agregar nuevo intercambio
        $history[] = [
            'user_message' => $userMessage,
            'bot_response' => $botResponse,
            'timestamp' => now()->toISOString()
        ];

        // Mantener solo los últimos N intercambios
        if (count($history) > self::MAX_CONVERSATION_HISTORY) {
            $history = array_slice($history, -self::MAX_CONVERSATION_HISTORY);
        }

        Cache::put($cacheKey, $history, self::CONVERSATION_TTL);
    }

    /**
     * Obtener estadísticas de la conversación
     */
    public function getConversationStats(int $userId): array
    {
        $history = $this->getConversationHistory($userId);
        
        if (empty($history)) {
            return [
                'total_messages' => 0,
                'conversation_started' => null,
                'last_message' => null,
                'is_active' => false
            ];
        }

        $totalMessages = count($history) * 2; // Usuario + bot por cada intercambio
        $firstExchange = $history[0];
        $lastExchange = $history[count($history) - 1];

        return [
            'total_messages' => $totalMessages,
            'conversation_started' => $firstExchange['timestamp'],
            'last_message' => $lastExchange['timestamp'],
            'is_active' => Carbon::parse($lastExchange['timestamp'])->diffInHours(now()) < 1
        ];
    }

    /**
     * Limpiar historial de conversación
     */
    public function clearConversationHistory(int $userId): bool
    {
        $cacheKey = self::CONVERSATION_CACHE_PREFIX . $userId;
        return Cache::forget($cacheKey);
    }
}
