<?php

namespace App\Services\FinancialChat;

use App\Services\Account\AccountService;
use App\Services\Budget\BudgetService;
use App\Services\Transaction\TransactionService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Carbon\Carbon;

class FinancialContextService
{
    use HasAuthenticatedUser, HasLogging;

    public function __construct(
        private AccountService $accountService,
        private TransactionService $transactionService,
        private BudgetService $budgetService
    ) {}    /**
     * Construir contexto financiero completo del usuario
     */
    public function buildUserFinancialContext(int $userId): array
    {
        try {
            $this->logInfo('Building financial context', ['user_id' => $userId]);

            $accounts = $this->getAccountsContext($userId);
            $transactions = $this->getTransactionsContext($userId);
            $budgets = $this->getBudgetsContext($userId);
            $summary = $this->getQuickSummary($userId);

            // Log detallado de lo que se encontró
            $this->logInfo('Financial context built', [
                'user_id' => $userId,
                'accounts_count' => count($accounts),
                'transactions_count' => count($transactions),
                'budgets_count' => count($budgets),
                'has_summary' => !empty($summary)
            ]);

            return [
                'accounts' => $accounts,
                'transactions' => $transactions,
                'budgets' => $budgets,
                'summary' => $summary,
                'generated_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            $this->logError('Error building financial context', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'accounts' => [],
                'transactions' => [],
                'budgets' => [],
                'summary' => 'No hay datos financieros disponibles',
                'generated_at' => now()->toISOString()
            ];
        }
    }/**
     * Obtener resumen financiero rápido
     */
    public function getQuickSummary(int $userId): string
    {
        try {
            $accounts = $this->accountService->getAccountsForUser($userId);
            $totalBalance = $accounts->sum('current_balance');
            $accountCount = $accounts->count();            // Transacciones del mes actual
            $currentMonth = Carbon::now()->startOfMonth();
            $monthlyTransactions = $this->transactionService->getTransactionsForUser(
                $userId,
                startDate: $currentMonth->format('Y-m-d'),
                endDate: Carbon::now()->format('Y-m-d')
            );

            $monthlyIncome = $monthlyTransactions->where('type', 'income')->sum('amount');
            $monthlyExpenses = $monthlyTransactions->where('type', 'expense')->sum('amount');
            $netThisMonth = $monthlyIncome - $monthlyExpenses;            // Presupuestos activos
            $activeBudgets = $this->budgetService->getActiveForUser();
            $budgetCount = $activeBudgets->count();

            return sprintf(
                "Balance total: $%s en %d cuenta(s). Este mes: ingresos $%s, gastos $%s (neto: $%s). %d presupuesto(s) activo(s).",
                number_format($totalBalance, 2),
                $accountCount,
                number_format($monthlyIncome, 2),
                number_format($monthlyExpenses, 2),
                number_format($netThisMonth, 2),
                $budgetCount
            );

        } catch (\Exception $e) {
            $this->logError('Error generating quick summary', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return 'No se pudo generar el resumen financiero en este momento.';
        }
    }    /**
     * Obtener contexto de cuentas
     */
    private function getAccountsContext(int $userId): array
    {
        try {
            $accounts = $this->accountService->getAccountsForUser($userId);
            
            return $accounts->map(function ($account) {
                return [
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $account->current_balance,
                    'currency' => $account->currency ?? 'USD',
                    'is_active' => $account->is_active
                ];
            })->toArray();

        } catch (\Exception $e) {
            $this->logError('Error getting accounts context', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }    /**
     * Obtener contexto de transacciones recientes
     */
    private function getTransactionsContext(int $userId, int $limit = 5): array
    {
        try {
            $transactions = $this->transactionService->getTransactionsForUser(
                $userId,
                perPage: $limit
            );
            
            return $transactions->map(function ($transaction) {
                return [
                    'date' => $transaction->transaction_date,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'category' => $transaction->category?->name,
                    'account' => $transaction->account?->name
                ];
            })->toArray();

        } catch (\Exception $e) {
            $this->logError('Error getting transactions context', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }    /**
     * Obtener contexto de presupuestos
     */
    private function getBudgetsContext(int $userId): array
    {
        try {
            $budgets = $this->budgetService->getActiveForUser();
            
            return $budgets->map(function ($budget) {
                // Nota: Como no hay método getBudgetSpent, usamos 0 como placeholder
                // Esto se puede implementar más tarde
                $spent = 0;
                $remaining = $budget->amount - $spent;
                $percentageUsed = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

                return [
                    'name' => $budget->name,
                    'amount' => $budget->amount,
                    'spent' => $spent,
                    'remaining' => $remaining,
                    'percentage_used' => round($percentageUsed, 2),
                    'category' => $budget->category?->name,
                    'period' => $budget->period,
                    'start_date' => $budget->start_date,
                    'end_date' => $budget->end_date,
                    'is_exceeded' => $spent > $budget->amount
                ];
            })->toArray();

        } catch (\Exception $e) {
            $this->logError('Error getting budgets context', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener insights sobre patrones de gasto
     */
    public function getSpendingInsights(int $userId): array
    {
        try {
            $lastMonth = Carbon::now()->subMonth();            $transactions = $this->transactionService->getTransactionsForUser(
                $userId,
                startDate: $lastMonth->startOfMonth()->format('Y-m-d'),
                endDate: $lastMonth->endOfMonth()->format('Y-m-d')
            );

            $expenses = $transactions->where('type', 'expense');
            
            // Top categorías de gasto
            $topCategories = $expenses->groupBy('category.name')
                ->map(function ($categoryTransactions) {
                    return [
                        'total' => $categoryTransactions->sum('amount'),
                        'count' => $categoryTransactions->count()
                    ];
                })
                ->sortByDesc('total')
                ->take(5);

            // Transacciones más grandes
            $largestTransactions = $expenses->sortByDesc('amount')->take(5)->map(function ($transaction) {
                return [
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'category' => $transaction->category?->name,
                    'date' => $transaction->date
                ];
            });

            return [
                'top_categories' => $topCategories->toArray(),
                'largest_transactions' => $largestTransactions->toArray(),
                'total_expenses' => $expenses->sum('amount'),
                'average_transaction' => $expenses->count() > 0 ? $expenses->avg('amount') : 0
            ];

        } catch (\Exception $e) {
            $this->logError('Error getting spending insights', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
