<?php

namespace App\Services\Llm\Adapters;

/**
 * Adaptador mock para desarrollo y pruebas locales
 * No requiere servicios externos como Ollama u OpenAI
 */
class MockAdapter implements LlmAdapterInterface
{    /**
     * Respuestas predefinidas con formato mejorado
     */
    protected array $responses = [
        'saludo' => '¡Hola! 👋 Soy FinBot, tu asistente financiero personal. 

Estoy aquí para ayudarte con todas tus consultas sobre finanzas. 

**¿En qué puedo ayudarte hoy?**
• Analizar tu situación financiera
• Revisar tus gastos e ingresos
• Gestionar presupuestos
• Consejos de ahorro e inversión',

        'estado_finanzas' => '📊 **Estado de tus Finanzas**

He revisado tu situación financiera actual y me complace decirte que tienes un balance bastante saludable:

✅ **Puntos Positivos:**
   • Ingresos estables
   • Gastos controlados
   • Balance general saludable

¿Hay algún aspecto específico que te gustaría que analice más a fondo?',

        'gastos' => '💰 **Análisis de Gastos**

He analizado tus patrones de gasto principales:

**Distribución por Categorías:**
┌─────────────────┬─────────┐
│ Categoría       │ Porcentaje │
├─────────────────┼─────────┤
│ Alimentación    │ 35%      │
│ Transporte      │ 25%      │
│ Entretenimiento │ 15%      │
│ Otros           │ 25%      │
└─────────────────┴─────────┘

📈 **Observaciones:**
• Alimentación y transporte son tus mayores gastos (normal)
• Hay oportunidades de optimización

¿Te interesa que te dé consejos específicos para reducir gastos en estas categorías?',

        'patrones_gastos' => '📈 **Análisis de Patrones de Gasto**

He identificado algunas tendencias interesantes en tus hábitos:

**Patrones Detectados:**
1. **Fines de Semana**: Mayor gasto en entretenimiento
2. **Días Laborales**: Gastos más controlados
3. **Fin de Mes**: Ligero incremento en gastos esenciales

**Recomendaciones:**
• Planificar un presupuesto semanal para entretenimiento
• Considerar alternativas económicas para fines de semana

¿Te gustaría que creemos un plan para equilibrar mejor estos gastos?',

        'presupuesto' => '📋 **Estado de Presupuestos**

¡Tus presupuestos están funcionando muy bien!

**Resumen General:**
┌─────────────────┬─────────┬────────────┐
│ Presupuesto     │ Estado   │ Observación │
├─────────────────┼─────────┼────────────┤
│ General         │ ✅ 80%   │ Saludable   │
│ Entretenimiento │ ⚠️ Alto  │ Revisar     │
│ Alimentación    │ ✅ Bien  │ Controlado  │
│ Transporte      │ ✅ Bien  │ Normal      │
└─────────────────┴─────────┴────────────┘

**Nota:** El presupuesto de "Entretenimiento" está un poco elevado este mes, pero nada preocupante.

¿Quieres que ajustemos algún presupuesto específico?',

        'ahorros' => '💎 **Estrategia de Ahorros**

Para mejorar tus ahorros, te recomiendo la estrategia **50/30/20:**

**Distribución Recomendada:**
├── 50% → Necesidades (vivienda, comida, servicios)
├── 30% → Deseos (entretenimiento, compras opcionales)
└── 20% → Ahorros e inversiones

**Tu Situación Actual:**
• Estás ahorrando: **15%** mensual
• Meta objetivo: **20%** mensual
• Diferencia: Solo **5%** más ¡Muy cerca!

**Plan Sugerido:**
1. Automatizar transferencias a ahorros
2. Revisar gastos opcionales
3. Incrementar gradualmente el porcentaje

¿Te ayudo a crear un plan específico para llegar al 20%?',

        'ingresos' => '💹 **Análisis de Ingresos**

¡Excelentes noticias sobre tus ingresos!

**Tendencia Reciente:**
• Crecimiento: **+8%** en los últimos 3 meses
• Estabilidad: Muy buena
• Proyección: Positiva

**Oportunidades:**
1. **Aumentar Ahorros**: Aprovechar el incremento
2. **Nuevas Inversiones**: Considerar opciones de crecimiento
3. **Fondo de Emergencia**: Fortalecer tu seguridad financiera

Este aumento te da una gran oportunidad para mejorar tu situación financiera. ¿En qué área te gustaría enfocar este incremento?',

        'consejos' => '🎯 **Consejos Personalizados**

Basándome en tu perfil financiero, aquí van mis recomendaciones:

**Prioridad Alta:**
1. **Automatizar Ahorros**
   → Meta: Alcanzar el 20% mensual
   → Acción: Configurar transferencia automática

2. **Revisar Suscripciones**
   → Cancelar servicios no utilizados
   → Evaluar alternativas más económicas

3. **Fondo de Emergencia**
   → Aprovechar el aumento de ingresos
   → Meta: 3-6 meses de gastos

**Siguiente Paso:**
¿Cuál de estos consejos te interesa implementar primero?',

        'resumen' => '📊 **Resumen Financiero**

¡Perfecto! Te doy un resumen de tu situación:

**Estado General:** ✅ Muy Bueno

**Puntos Clave:**
┌─────────────────┬─────────────────┐
│ Aspecto         │ Estado          │
├─────────────────┼─────────────────┤
│ Balance         │ ✅ Saludable    │
│ Gastos Principales │ Alimentación, Transporte │
│ Ingresos        │ ✅ En aumento   │
│ Ahorros         │ ⚠️ Cerca del objetivo │
└─────────────────┴─────────────────┘

**Conclusión:** ¡Vas muy bien en tu camino financiero!

¿Hay algo específico que quieras mejorar o profundizar?',

        'ayuda' => '🤖 **¿Cómo puedo ayudarte?**

Estas son mis principales capacidades:

**Análisis Financiero:**
• 📊 Revisar tu situación general
• 💰 Analizar patrones de gastos
• 📈 Evaluar ingresos y tendencias

**Gestión de Presupuestos:**
• 📋 Estado de presupuestos actuales
• ⚙️ Ajustar límites y categorías
• 📊 Comparar gastos vs. presupuestado

**Estrategias de Ahorro:**
• 💎 Planes personalizados
• 🎯 Metas de ahorro
• 💹 Consejos de inversión

**Preguntas Frecuentes:**
• "¿Cómo están mis finanzas?"
• "Analiza mis gastos"
• "Dame consejos de ahorro"

¿Qué te interesa más explorar?'
    ];

    /**
     * Inicializa el adaptador con la configuración proporcionada
     * 
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        // No requiere inicialización para el mock
    }

    /**
     * Obtiene el nombre del proveedor
     * 
     * @return string
     */
    public function getProviderName(): string
    {
        return 'mock';
    }    /**
     * Genera texto utilizando respuestas predefinidas inteligentes
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param array $options Opciones específicas del proveedor
     * @return string El texto generado
     */
    public function generateText(string $prompt, string $systemPrompt = '', array $options = []): string
    {        // Extraer información financiera del prompt si está disponible
        $balance = $this->extractBalance($prompt);
        $accounts = $this->extractAccounts($prompt);
        $income = $this->extractIncome($prompt);
        $expenses = $this->extractExpenses($prompt);
        $budgets = $this->extractBudgets($prompt);
        $transactions = $this->extractTransactions($prompt);
        
        // Normalizar el prompt para análisis
        $promptLower = strtolower($prompt);
        
        // Saludos y consultas generales
        if (str_contains($promptLower, 'hola') || str_contains($promptLower, 'buenos') || str_contains($promptLower, 'hi') || str_contains($promptLower, 'hey')) {
            return $this->responses['saludo'];
        }
          // Preguntas sobre balance actual - usar datos reales si están disponibles
        if (str_contains($promptLower, 'balance actual') || str_contains($promptLower, 'cuál es mi balance') || str_contains($promptLower, 'balance total')) {
            if ($balance && $accounts) {
                return $this->formatBalanceResponse($balance, $accounts, $income, $expenses);
            }
            return $this->responses['estado_finanzas'];
        }
        
        // Preguntas sobre estado financiero general
        if (str_contains($promptLower, 'cómo están') || str_contains($promptLower, 'como estan') || str_contains($promptLower, 'estado') || str_contains($promptLower, 'situación financiera') || str_contains($promptLower, 'finanzas')) {
            if ($balance && $accounts) {
                return $this->formatFinancialStatusResponse($balance, $accounts, $income, $expenses);
            }
            return $this->responses['estado_finanzas'];
        }
        
        // Resumen detallado - detectar "detalladamente", "muéstrame detalladamente", etc.
        if (str_contains($promptLower, 'detalladamente') || str_contains($promptLower, 'detallado') || str_contains($promptLower, 'muéstrame') || str_contains($promptLower, 'muestrame')) {
            if ($balance && $accounts) {
                return $this->formatDetailedFinancialReport($balance, $accounts, $income, $expenses, $budgets);
            }
            return $this->responses['resumen'];
        }
          // Análisis de patrones de gasto
        if (str_contains($promptLower, 'patrones') || str_contains($promptLower, 'patrón') || str_contains($promptLower, 'analiza mis') || str_contains($promptLower, 'analizar')) {
            return $this->responses['patrones_gastos'];
        }
        
        // Transacciones específicas - detectar cuando se mencionan transacciones
        if (str_contains($promptLower, 'transacciones') || str_contains($promptLower, 'transacción') || str_contains($promptLower, 'lista') || str_contains($promptLower, 'detallada')) {
            if ($transactions) {
                return $this->formatTransactionsResponse($transactions, $expenses);
            }
            return "He encontrado información sobre tus transacciones recientes. " . $this->responses['gastos'];
        }
        
        // Gastos específicos
        if (str_contains($promptLower, 'gasto') || str_contains($promptLower, 'gastar') || str_contains($promptLower, 'categoría') || str_contains($promptLower, 'categoria')) {
            return $this->responses['gastos'];
        }
        
        // Presupuestos
        if (str_contains($promptLower, 'presupuesto') || str_contains($promptLower, 'budget')) {
            if ($budgets) {
                return "Tienes {$budgets} presupuesto(s) activo(s) que están funcionando bien. " . $this->responses['presupuesto'];
            }
            return $this->responses['presupuesto'];
        }
        
        // Ahorros
        if (str_contains($promptLower, 'ahorro') || str_contains($promptLower, 'ahorrar') || str_contains($promptLower, 'guardar dinero')) {
            return $this->responses['ahorros'];
        }
        
        // Ingresos
        if (str_contains($promptLower, 'ingreso') || str_contains($promptLower, 'salario') || str_contains($promptLower, 'sueldo')) {
            return $this->responses['ingresos'];
        }
        
        // Consejos y recomendaciones
        if (str_contains($promptLower, 'consejo') || str_contains($promptLower, 'recomendación') || str_contains($promptLower, 'recomendacion') || str_contains($promptLower, 'sugerencia')) {
            return $this->responses['consejos'];
        }
          // Resumen
        if (str_contains($promptLower, 'resumen') || str_contains($promptLower, 'resume') || str_contains($promptLower, 'overview') || str_contains($promptLower, 'dame un')) {
            if ($balance && $accounts) {
                return $this->formatFinancialSummaryResponse($balance, $accounts, $income, $expenses, $budgets);
            }
            return $this->responses['resumen'];
        }
        
        // Ayuda general
        if (str_contains($promptLower, 'ayuda') || str_contains($promptLower, 'help') || str_contains($promptLower, 'qué puedes') || str_contains($promptLower, 'que puedes')) {
            return $this->responses['ayuda'];
        }
        
        // Respuesta por defecto más inteligente
        return $this->responses['saludo'];
    }
    
    /**
     * Extraer balance del prompt
     */
    private function extractBalance(string $prompt): ?string
    {
        if (preg_match('/Balance total: \$([0-9,]+\.\d{2})/', $prompt, $matches)) {
            return '$' . $matches[1];
        }
        return null;
    }
    
    /**
     * Extraer número de cuentas del prompt
     */
    private function extractAccounts(string $prompt): ?string
    {
        if (preg_match('/en (\d+) cuenta\(s\)/', $prompt, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Extraer ingresos del prompt
     */
    private function extractIncome(string $prompt): ?string
    {
        if (preg_match('/ingresos \$([0-9,]+\.\d{2})/', $prompt, $matches)) {
            return '$' . $matches[1];
        }
        return null;
    }
    
    /**
     * Extraer gastos del prompt
     */
    private function extractExpenses(string $prompt): ?string
    {
        if (preg_match('/gastos \$([0-9,]+\.\d{2})/', $prompt, $matches)) {
            return '$' . $matches[1];
        }
        return null;
    }    /**
     * Extraer número de presupuestos del prompt
     */
    private function extractBudgets(string $prompt): ?string
    {
        if (preg_match('/(\d+) presupuesto\(s\) activo\(s\)/', $prompt, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extraer transacciones del prompt
     */
    private function extractTransactions(string $prompt): ?array
    {
        // Buscar patrones de transacciones en el prompt
        if (str_contains($prompt, 'Transacciones recientes') || str_contains($prompt, 'transacciones')) {
            // Buscar listas numeradas o patrones específicos
            $lines = explode("\n", $prompt);
            $transactions = [];
            
            foreach ($lines as $line) {
                // Buscar patrones como "1. Descripción ($amount)" o "• Descripción ($amount)"
                if (preg_match('/^\s*[\d\.\•\-\*]\s*(.+?)\s*\(\$([0-9,]+\.?\d*)\)/', $line, $matches)) {
                    $transactions[] = [
                        'description' => trim($matches[1]),
                        'amount' => '$' . $matches[2]
                    ];
                }
                // También buscar patrones simples como "Descripción $amount"
                elseif (preg_match('/([A-Za-z\s]+)\s+\$([0-9,]+\.?\d*)/', $line, $matches)) {
                    $description = trim($matches[1]);
                    if (strlen($description) > 5 && !str_contains($description, 'total') && !str_contains($description, 'balance')) {
                        $transactions[] = [
                            'description' => $description,
                            'amount' => '$' . $matches[2]
                        ];
                    }
                }
            }
            
            return !empty($transactions) ? $transactions : null;
        }
        
        return null;
    }

    /**
     * Formatear respuesta de balance con estructura mejorada
     */
    private function formatBalanceResponse(string $balance, string $accounts, ?string $income, ?string $expenses): string
    {
        $response = "💰 **Tu Balance Actual**\n\n";
        $response .= "**Resumen General:**\n";
        $response .= "┌─────────────────┬─────────────────┐\n";
        $response .= "│ Balance Total   │ {$balance}      │\n";
        $response .= "│ Cuentas         │ {$accounts} cuenta(s)    │\n";
        
        if ($income && $expenses) {
            $response .= "│ Ingresos (mes)  │ {$income}       │\n";
            $response .= "│ Gastos (mes)    │ {$expenses}     │\n";
            
            // Calcular flujo de efectivo
            $incomeNum = (float) str_replace(['$', ','], '', $income);
            $expenseNum = (float) str_replace(['$', ','], '', $expenses);
            $cashFlow = $incomeNum - $expenseNum;
            $cashFlowFormatted = '$' . number_format($cashFlow, 2);
            $cashFlowIcon = $cashFlow > 0 ? '✅' : '⚠️';
            
            $response .= "│ Flujo Efectivo  │ {$cashFlowIcon} {$cashFlowFormatted}    │\n";
        }
        
        $response .= "└─────────────────┴─────────────────┘\n\n";
        $response .= "¿Te gustaría que analice algún aspecto específico de tus finanzas?";
        
        return $response;
    }

    /**
     * Formatear respuesta de estado financiero con estructura mejorada
     */
    private function formatFinancialStatusResponse(string $balance, string $accounts, ?string $income, ?string $expenses): string
    {
        $response = "📊 **Estado de tus Finanzas**\n\n";
        $response .= "He revisado tu situación financiera y tengo buenas noticias:\n\n";
        
        $response .= "**Resumen Actual:**\n";
        $response .= "• Balance: **{$balance}** distribuido en **{$accounts} cuenta(s)**\n";
        
        if ($income && $expenses) {
            $response .= "• Ingresos mensuales: **{$income}**\n";
            $response .= "• Gastos mensuales: **{$expenses}**\n";
            
            // Calcular y mostrar ratio de ahorro
            $incomeNum = (float) str_replace(['$', ','], '', $income);
            $expenseNum = (float) str_replace(['$', ','], '', $expenses);
            $savingsRatio = (($incomeNum - $expenseNum) / $incomeNum) * 100;
            $savingsFormatted = number_format($savingsRatio, 1);
            
            $response .= "• Tasa de ahorro: **{$savingsFormatted}%**\n\n";
            
            // Evaluación del estado
            if ($savingsRatio >= 20) {
                $response .= "✅ **Excelente gestión financiera!** Tu tasa de ahorro está por encima del 20% recomendado.\n";
            } elseif ($savingsRatio >= 10) {
                $response .= "✅ **Buena gestión financiera.** Estás ahorrando un porcentaje saludable.\n";
            } else {
                $response .= "⚠️ **Oportunidad de mejora.** Considera incrementar tu tasa de ahorro.\n";
            }
        } else {
            $response .= "\n✅ **Estado general:** Tus finanzas se ven estables.\n";
        }
        
        $response .= "\n¿Hay algún aspecto específico que te gustaría que analice más a fondo?";
        
        return $response;
    }

    /**
     * Formatear reporte financiero detallado
     */
    private function formatDetailedFinancialReport(string $balance, string $accounts, ?string $income, ?string $expenses, ?string $budgets): string
    {
        $response = "📈 **Reporte Financiero Detallado**\n\n";
        
        // Sección 1: Overview
        $response .= "## 1. Resumen General\n\n";
        $response .= "┌─────────────────┬─────────────────┬─────────────┐\n";
        $response .= "│ Concepto        │ Valor           │ Estado      │\n";
        $response .= "├─────────────────┼─────────────────┼─────────────┤\n";
        $response .= "│ Balance Total   │ {$balance}      │ ✅ Saludable │\n";
        $response .= "│ Cuentas Activas │ {$accounts} cuenta(s)    │ ✅ Diversificado │\n";
        
        if ($income && $expenses) {
            $incomeNum = (float) str_replace(['$', ','], '', $income);
            $expenseNum = (float) str_replace(['$', ','], '', $expenses);
            $savingsAmount = $incomeNum - $expenseNum;
            $savingsFormatted = '$' . number_format($savingsAmount, 2);
            
            $response .= "│ Ingresos Mes    │ {$income}       │ ✅ Estables   │\n";
            $response .= "│ Gastos Mes      │ {$expenses}     │ ✅ Controlados │\n";
            $response .= "│ Ahorro Mes      │ {$savingsFormatted}      │ ";
            
            if ($savingsAmount > 0) {
                $response .= "✅ Positivo  │\n";
            } else {
                $response .= "⚠️ Revisar   │\n";
            }
        }
        
        if ($budgets) {
            $response .= "│ Presupuestos    │ {$budgets} activo(s)     │ ✅ Gestionados │\n";
        }
        
        $response .= "└─────────────────┴─────────────────┴─────────────┘\n\n";
        
        // Sección 2: Análisis de Flujo de Efectivo
        if ($income && $expenses) {
            $response .= "## 2. Análisis de Flujo de Efectivo\n\n";
            
            $incomeNum = (float) str_replace(['$', ','], '', $income);
            $expenseNum = (float) str_replace(['$', ','], '', $expenses);
            $savingsRatio = (($incomeNum - $expenseNum) / $incomeNum) * 100;
            
            $response .= "**Distribución Actual vs. Recomendada (50/30/20):**\n\n";
            $response .= "```\n";
            $response .= "Necesidades (50%):     " . str_repeat("█", 25) . " Actual: ~60%\n";
            $response .= "Deseos (30%):          " . str_repeat("█", 15) . " Actual: ~25%\n";
            $response .= "Ahorros (20%):         " . str_repeat("█", (int)($savingsRatio/2)) . " Actual: " . number_format($savingsRatio, 1) . "%\n";
            $response .= "```\n\n";
        }
        
        // Sección 3: Recomendaciones
        $response .= "## 3. Recomendaciones Prioritarias\n\n";
        $response .= "**🎯 Acciones Sugeridas:**\n\n";
        $response .= "1. **Optimizar Ahorros**\n";
        $response .= "   • Automatizar transferencias mensuales\n";
        $response .= "   • Meta: Alcanzar 20% de tasa de ahorro\n\n";
        
        $response .= "2. **Revisar Gastos Recurrentes**\n";
        $response .= "   • Suscripciones no utilizadas\n";
        $response .= "   • Servicios duplicados\n\n";
        
        $response .= "3. **Diversificar Ingresos**\n";
        $response .= "   • Considerar ingresos pasivos\n";
        $response .= "   • Evaluar oportunidades de crecimiento\n\n";
          $response .= "¿Te gustaría profundizar en alguna de estas recomendaciones?";
        
        return $response;
    }

    /**
     * Formatear respuesta de resumen financiero
     */
    private function formatFinancialSummaryResponse(string $balance, string $accounts, ?string $income, ?string $expenses, ?string $budgets): string
    {
        $response = "📊 **Resumen Financiero Rápido**\n\n";
        $response .= "¡Perfecto! Te doy un resumen de tu situación:\n\n";
        
        $response .= "**Estado General:** ✅ Muy Bueno\n\n";
        
        $response .= "**Datos Clave:**\n";
        $response .= "┌─────────────────┬─────────────────┐\n";
        $response .= "│ Balance Total   │ {$balance}      │\n";
        $response .= "│ Cuentas         │ {$accounts} cuenta(s)    │\n";
        
        if ($income && $expenses) {
            $response .= "│ Ingresos Mes    │ {$income}       │\n";
            $response .= "│ Gastos Mes      │ {$expenses}     │\n";
            
            // Calcular flujo de efectivo
            $incomeNum = (float) str_replace(['$', ','], '', $income);
            $expenseNum = (float) str_replace(['$', ','], '', $expenses);
            $cashFlow = $incomeNum - $expenseNum;
            $cashFlowFormatted = '$' . number_format($cashFlow, 2);
            $cashFlowStatus = $cashFlow > 0 ? '✅ Positivo' : '⚠️ Atención';
            
            $response .= "│ Flujo Efectivo  │ {$cashFlowFormatted} ({$cashFlowStatus}) │\n";
        }
        
        if ($budgets) {
            $response .= "│ Presupuestos    │ {$budgets} activo(s)     │\n";
        }
        
        $response .= "└─────────────────┴─────────────────┘\n\n";
        
        $response .= "**Aspectos Destacados:**\n";
        $response .= "• 💰 Balance saludable y estable\n";
        $response .= "• 📈 Gastos principales: alimentación y transporte\n";
        $response .= "• 📊 Tendencia de ingresos: En aumento\n\n";
        
        $response .= "**Conclusión:** ¡Vas muy bien en tu camino financiero!\n\n";
        $response .= "¿Hay algo específico que quieras mejorar o profundizar?";
        
        return $response;
    }/**
     * Genera salida estructurada en un formato específico
     * 
     * @param string $prompt El prompt formateado
     * @param string $systemPrompt El prompt de sistema
     * @param string $format El formato deseado
     * @param array $options Opciones específicas del proveedor
     * @return string La salida estructurada generada
     */    public function generateStructuredOutput(string $prompt, string $systemPrompt = '', string $format = 'json', array $options = []): string
    {
        if ($format === 'json') {
            $contextualSuggestions = $this->generateContextualSuggestions($prompt);
            
            return json_encode([
                'response' => $this->generateText($prompt, $systemPrompt, $options),
                'confidence' => 0.95,
                'suggestions' => $contextualSuggestions,
                'metadata' => [
                    'provider' => 'mock',
                    'generated_at' => now()->toISOString()
                ]
            ]);
        }
        
        return $this->generateText($prompt, $systemPrompt, $options);
    }

    /**
     * Generar sugerencias contextuales basadas en el prompt
     */
    private function generateContextualSuggestions(string $prompt): array
    {
        $balance = $this->extractBalance($prompt);
        $accounts = $this->extractAccounts($prompt);
        $income = $this->extractIncome($prompt);
        $expenses = $this->extractExpenses($prompt);
        $budgets = $this->extractBudgets($prompt);
        
        $promptLower = strtolower($prompt);
        
        // Si hay datos financieros reales, generar sugerencias específicas
        if ($balance && $accounts) {
            if (str_contains($promptLower, 'balance') || str_contains($promptLower, 'saldo')) {
                return [
                    "¿Cómo optimizar mi balance de {$balance}?",
                    "Estrategias para hacer crecer mis {$accounts} cuentas",
                    "¿Dónde debería invertir parte de mi balance?"
                ];
            }
            
            if (str_contains($promptLower, 'gasto') && $expenses) {
                return [
                    "¿Cómo reducir mis gastos de {$expenses}?",
                    "Analiza mis categorías de gasto principales",
                    "Compara mis gastos con mi balance de {$balance}"
                ];
            }
            
            if (str_contains($promptLower, 'ingreso') && $income) {
                return [
                    "¿Cómo aumentar mis ingresos de {$income}?",
                    "Estrategias para diversificar mis fuentes de ingreso",
                    "¿Es óptima mi relación ingreso-gasto actual?"
                ];
            }
            
            if (str_contains($promptLower, 'presupuesto') && $budgets) {
                return [
                    "¿Cómo van mis {$budgets} presupuestos activos?",
                    "Ajustar presupuestos según mis gastos de {$expenses}",
                    "Crear nuevos presupuestos para optimizar mis finanzas"
                ];
            }
            
            if (str_contains($promptLower, 'detalle') || str_contains($promptLower, 'muestra')) {
                return [
                    "Análisis completo de mi situación financiera",
                    "¿Qué oportunidades de mejora tengo?",
                    "Plan personalizado para optimizar mis {$balance}"
                ];
            }
            
            // Sugerencias generales con datos reales
            return [
                "Analiza mi balance de {$balance} en detalle",
                ($income && $expenses) ? "Compara mis ingresos de {$income} vs gastos de {$expenses}" : "¿Cómo están mis finanzas en general?",
                "Dame consejos específicos para mi situación"
            ];
        }
        
        // Sugerencias generales si no hay datos específicos
        return [
            '¿Cómo están mis finanzas?',
            'Analiza mis patrones de gasto',
            'Dame consejos de ahorro personalizados'
        ];
    }

    /**
     * Genera embeddings simulados
     * 
     * @param string $text El texto para generar embeddings
     * @return array Los embeddings generados
     */
    public function generateEmbeddings(string $text): array
    {
        // Generar embeddings simulados basados en el hash del texto
        $hash = md5($text);
        $embeddings = [];
        
        for ($i = 0; $i < 384; $i++) {
            $embeddings[] = (float) (hexdec(substr($hash, $i % 32, 2)) / 255) - 0.5;
        }
        
        return $embeddings;
    }
}
