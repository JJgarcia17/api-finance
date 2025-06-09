<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Resources\Budget\BudgetResource;
use App\Models\Budget;
use App\Services\Budget\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $budgets = $this->budgetService->getForUser(
            $request->integer('category_id'),
            $request->string('period'),
            $request->has('is_active') ? $request->boolean('is_active') : null,
            $request->string('start_date'),
            $request->string('end_date'),
            $request->integer('per_page', 15)
        );
        
        return BudgetResource::collection($budgets);
    }

    public function store(StoreBudgetRequest $request): BudgetResource
    {
        $budget = $this->budgetService->create($request->validated());
        
        return new BudgetResource($budget);
    }

    public function show(Budget $budget): BudgetResource
    {
        $budget = $this->budgetService->findForUser($budget->id);
        
        return new BudgetResource($budget);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): BudgetResource
    {
        $budget = $this->budgetService->update($budget->id, $request->validated());
        
        return new BudgetResource($budget);
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->budgetService->delete($budget->id);
        
        return response()->json([
            'message' => 'Presupuesto eliminado exitosamente'
        ]);
    }

    public function restore(Budget $budget): BudgetResource
    {
        $budget = $this->budgetService->restore($budget->id);
        
        return new BudgetResource($budget);
    }

    // toggleStatus is already correctly implemented
    public function toggleStatus(Budget $budget): BudgetResource
    {
        $budget = $this->budgetService->toggleStatus($budget->id);
        
        return new BudgetResource($budget);
    }

    public function active(): AnonymousResourceCollection
    {
        $budgets = $this->budgetService->getActiveForUser();
        
        return BudgetResource::collection($budgets);
    }

    public function current(): AnonymousResourceCollection
    {
        $budgets = $this->budgetService->getCurrentBudgets();
        
        return BudgetResource::collection($budgets);
    }
}