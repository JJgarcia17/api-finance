<?php

namespace App\Services\Budget;

use App\Models\Budget;
use App\Repositories\Budget\BudgetRepository;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    use HasAuthenticatedUser, HasLogging;

    public function __construct(
        private BudgetRepository $budgetRepository
    ) {}

    public function getForUser(
        ?int $categoryId = null,
        ?string $period = null,
        ?bool $isActive = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->budgetRepository->getForUser(
            $this->userId(),
            $categoryId,
            $period,
            $isActive,
            $startDate,
            $endDate,
            $perPage
        );
    }

    public function findForUser(int $budgetId): Budget
    {
        $budget = $this->budgetRepository->findForUser(
            $budgetId,
            $this->userId()
        );

        if (!$budget) {
            $this->logWarning('Budget not found', [
                'budget_id' => $budgetId,
                'user_id' => $this->userId(),
            ]);
            
            throw new ModelNotFoundException('Presupuesto no encontrado');
        }

        return $budget;
    }

    public function create(array $data): Budget
    {
        DB::beginTransaction();
        
        try {
            $data['user_id'] = $this->userId();
            
            // Validar que no exista un presupuesto activo para la misma categoría y período
            $this->validateUniqueBudget($data);
            
            $budget = $this->budgetRepository->create($data);
            
            DB::commit();
            
            return $budget;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $budgetId, array $data): Budget
    {
        DB::beginTransaction();
        
        try {
            $budget = $this->findForUser($budgetId);
            
            // Validar que no exista conflicto con otros presupuestos
            $this->validateUniqueBudget($data, $budgetId);
            
            $updatedBudget = $this->budgetRepository->update($budget, $data);
            
            DB::commit();
            
            return $updatedBudget;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $budgetId): bool
    {
        DB::beginTransaction();
        
        try {
            $budget = $this->findForUser($budgetId);
            
            $result = $this->budgetRepository->delete($budget);
            
            DB::commit();
            
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restore(int $budgetId): Budget
    {
        DB::beginTransaction();
        
        try {
            $budget = $this->budgetRepository->restore(
                $budgetId,
                $this->userId()
            );

            if (!$budget) {
                throw new Exception('Presupuesto no encontrado o no se puede restaurar');
            }

            DB::commit();

            return $budget;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleStatus(int $budgetId): Budget
    {
        DB::beginTransaction();
        
        try {
            $budget = $this->findForUser($budgetId);
            
            $updatedBudget = $this->budgetRepository->update($budget, [
                'is_active' => !$budget->is_active,
            ]);
            
            DB::commit();
            
            return $updatedBudget;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveForUser(): Collection
    {
        return $this->budgetRepository->getActiveForUser(
            $this->userId()
        );
    }

    public function getCurrentBudgets(): Collection
    {
        return $this->budgetRepository->getCurrentBudgets(
            $this->userId()
        );
    }

    private function validateUniqueBudget(array $data, ?int $excludeBudgetId = null): void
    {
        // Solo validar unicidad si se está actualizando category_id o period
        if (!isset($data['category_id']) && !isset($data['period'])) {
            return;
        }
        
        // Si no tenemos category_id en los datos, obtenerlo del presupuesto existente
        if (!isset($data['category_id']) && $excludeBudgetId) {
            $existingBudget = Budget::find($excludeBudgetId);
            if (!$existingBudget) {
                return;
            }
            $categoryId = $existingBudget->category_id;
            $period = $data['period'] ?? $existingBudget->period;
        } else {
            $categoryId = $data['category_id'];
            $period = $data['period'] ?? 'monthly';
        }

        $query = Budget::query()
            ->forUser($this->userId())
            ->byCategory($categoryId)
            ->active(true)
            ->where('period', $period);

        if ($excludeBudgetId) {
            $query->where('id', '!=', $excludeBudgetId);
        }

        // Verificar solapamiento de fechas solo si tenemos fechas en los datos
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $query->where(function ($q) use ($data) {
                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                  ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                  ->orWhere(function ($subQ) use ($data) {
                      $subQ->where('start_date', '<=', $data['start_date'])
                           ->where('end_date', '>=', $data['end_date']);
                  });
            });
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'category_id' => 'Ya existe un presupuesto activo para esta categoría en el período especificado.',
            ]);
        }
    }
}