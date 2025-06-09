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
}