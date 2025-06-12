<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TransactionRepository
{
    public function __construct(
        private Transaction $model
    ) {}

    public function findByIdForUserOrFail(int $id, int $userId): Transaction
    {
        return $this->model->forUser($userId)->findOrFail($id);
    }

    public function getForUser(
        int $userId,
        ?string $type = null,
        ?int $accountId = null,
        ?int $categoryId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $sortBy = 'transaction_date',
        string $sortDirection = 'desc',
        ?int $perPage = null
    ): Collection|LengthAwarePaginator {
        $query = $this->model->forUser($userId)
            ->with(['account', 'category']);

        if ($type) {
            $query->byType($type);
        }

        if ($accountId) {
            $query->byAccount($accountId);
        }

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $query->orderBy($sortBy, $sortDirection);

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return $transaction->delete();
    }

    public function restore(int $id, int $userId): Transaction
    {
        $transaction = $this->model->onlyTrashed()
                              ->forUser($userId)
                              ->find($id);


        $transaction->restore();
        return $transaction;
    }

    public function getStats(int $userId): array
    {
        $totalIncome = $this->model->forUser($userId)->byType('income')->sum('amount');
        $totalExpenses = $this->model->forUser($userId)->byType('expense')->sum('amount');
        $totalTransactions = $this->model->forUser($userId)->count();
        
        $currentMonth = now();
        $currentMonthIncome = $this->model->forUser($userId)
            ->byType('income')
            ->whereMonth('transaction_date', $currentMonth->month)
            ->whereYear('transaction_date', $currentMonth->year)
            ->sum('amount');
            
        $currentMonthExpenses = $this->model->forUser($userId)
            ->byType('expense')
            ->whereMonth('transaction_date', $currentMonth->month)
            ->whereYear('transaction_date', $currentMonth->year)
            ->sum('amount');
            
        $currentMonthTransactions = $this->model->forUser($userId)
            ->whereMonth('transaction_date', $currentMonth->month)
            ->whereYear('transaction_date', $currentMonth->year)
            ->count();

        return [
            'total_transactions' => $totalTransactions,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance' => $totalIncome - $totalExpenses,
            'current_month_transactions' => $currentMonthTransactions,
            'current_month_income' => $currentMonthIncome,
            'current_month_expenses' => $currentMonthExpenses
        ];
    }

    /**
     * Get transaction statistics by currency for user
     */
    public function getStatsByCurrency(int $userId): array
    {
        // Get all transactions with account currency information
        $transactions = $this->model->forUser($userId)
            ->with('account')
            ->get();
        
        // Group by currency
        $statsByCurrency = $transactions->groupBy(function ($transaction) {
            return $transaction->account->currency;
        })->map(function ($currencyTransactions, $currency) {
            $income = $currencyTransactions->where('type', 'income')->sum('amount');
            $expenses = $currencyTransactions->where('type', 'expense')->sum('amount');
            
            return [
                'currency' => $currency,
                'total_income' => (float) $income,
                'total_expenses' => (float) $expenses,
                'net_balance' => (float) ($income - $expenses),
                'transaction_count' => $currencyTransactions->count()
            ];
        });

        return $statsByCurrency->values()->toArray();
    }
    
    public function getMonthlyTrends(int $userId, int $months = 12): array
    {
        // Obtener transacciones de los últimos meses usando Eloquent
        $startDate = now()->subMonths($months)->startOfMonth();
        
        $transactions = $this->model->forUser($userId)
            ->where('transaction_date', '>=', $startDate)
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Si no hay transacciones, retornar array vacío
        if ($transactions->isEmpty()) {
            return [];
        }

        // Agrupar transacciones por mes usando Eloquent Collection
        $groupedByMonth = $transactions->groupBy(function ($transaction) {
            return $transaction->transaction_date->format('Y-m');
        });

        // Procesar cada grupo de mes
        $monthlyData = $groupedByMonth->map(function ($monthTransactions, $monthKey) {
            // Usar la primera transacción para obtener fecha del mes
            $firstTransaction = $monthTransactions->first();
            $date = $firstTransaction->transaction_date;
            
            // Filtrar ingresos y gastos usando Collection methods
            $incomeTransactions = $monthTransactions->where('type', 'income');
            $expenseTransactions = $monthTransactions->where('type', 'expense');
            
            $income = $incomeTransactions->sum('amount');
            $expenses = $expenseTransactions->sum('amount');
            
            return [
                'month' => $date->format('M'), // Ene, Feb, etc.
                'year' => (int) $date->format('Y'),
                'month_number' => (int) $date->format('m'),
                'income' => (float) $income,
                'expenses' => (float) $expenses,
                'balance' => (float) ($income - $expenses),
                'transaction_count' => $monthTransactions->count()
            ];
        });

        // Convertir a array, ordenar por fecha (más antiguos primero para el gráfico)
        $result = $monthlyData->values()
            ->sortBy(function ($item) {
                return $item['year'] * 12 + $item['month_number'];
            })
            ->take($months)
            ->values()
            ->toArray();
        
        return $result;
    }
}