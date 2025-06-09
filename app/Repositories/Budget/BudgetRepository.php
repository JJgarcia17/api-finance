<?php

namespace App\Repositories\Budget;

use App\Models\Budget;
use App\Traits\HasLogging;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class BudgetRepository
{
    use HasLogging;

    public function getForUser(
        int $userId,
        ?int $categoryId = null,
        ?string $period = null,
        ?bool $isActive = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {

        return Budget::query()
            ->with(['category', 'user'])
            ->forUser($userId)
            ->byCategory($categoryId)
            ->byPeriod($period)
            ->active($isActive)
            ->byDateRange($startDate, $endDate)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findForUser(int $budgetId, int $userId): ?Budget
    {
        return Budget::query()
            ->with(['category', 'user'])
            ->forUser($userId)
            ->find($budgetId);
    }

    public function create(array $data): Budget
    {
        
        return Budget::create($data);
    }

    public function update(Budget $budget, array $data): Budget
    {
        $this->logInfo('Updating budget', [
            'budget_id' => $budget->id,
            'data' => $data,
        ]);
        
        $budget->update($data);
        
        return $budget->fresh(['category', 'user']);
    }

    public function delete(Budget $budget): bool
    {
        $this->logInfo('Deleting budget', ['budget_id' => $budget->id]);
        
        return $budget->delete();
    }

    public function restore(int $budgetId, int $userId): ?Budget
    {
        $budget = Budget::onlyTrashed()
            ->forUser($userId)
            ->find($budgetId);

        if ($budget) {
            $this->logInfo('Restoring budget', ['budget_id' => $budgetId]);
            $budget->restore();
            return $budget->fresh(['category', 'user']);
        }

        return null;
    }

    public function getActiveForUser(int $userId): Collection
    {
        return Budget::query()
            ->with(['category'])
            ->forUser($userId)
            ->active(true)
            ->orderBy('name')
            ->get();
    }

    public function getBudgetsByCategory(int $userId, int $categoryId): Collection
    {
        return Budget::query()
            ->with(['category'])
            ->forUser($userId)
            ->byCategory($categoryId)
            ->active(true)
            ->get();
    }

    public function getCurrentBudgets(int $userId): Collection
    {
        $now = Carbon::now();
        
        return Budget::query()
            ->with(['category'])
            ->forUser($userId)
            ->active(true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();
    }
}