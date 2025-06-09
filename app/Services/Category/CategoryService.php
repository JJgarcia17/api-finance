<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Repositories\Category\CategoryRepository;
use App\Traits\HasLogging;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Exception;

class CategoryService
{
    use HasLogging;
    
    protected CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated categories for user with filters
     */
    public function getCategoriesForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllForUser($userId, $filters, $perPage);
        } catch (Exception $e) {
            $this->logError('Error getting categories for user', [
                'user_id' => $userId,
                'filters' => $filters
            ], $e);
            throw $e;
        }
    }

    /**
     * Get category by ID for user
     */
    public function getCategoryForUser(int $id, int $userId): Category
    {
        try {
            return $this->repository->findForUser($id, $userId);
        } catch (ModelNotFoundException $e) {
            $this->logError('Category not found', ['id' => $id, 'user_id' => $userId], $e);
            throw $e;
        } catch (Exception $e) {
            $this->logError('Error getting category', [
                'id' => $id,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Create a new category
     */
    public function createCategory(array $data, int $userId): Category
    {
        DB::beginTransaction();
        
        try {
            // Add user_id and default values
            $data['user_id'] = $userId;
            $data['is_active'] = $data['is_active'] ?? true;

            // Validate business rules
            $this->validateCategoryData($data);

            $category = $this->repository->create($data);

            DB::commit();

            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error creating category', [
                'data' => $data,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Update an existing category
     */
    public function updateCategory(int $id, array $data, int $userId): Category
    {
        DB::beginTransaction();
        
        try {
            $category = $this->repository->findForUser($id, $userId);

            // Validate business rules
            $this->validateCategoryData($data, $id);

            $updatedCategory = $this->repository->update($category, $data);

            DB::commit();

            return $updatedCategory;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error updating category', [
                'id' => $id,
                'data' => $data,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Delete a category (soft delete)
     */
    public function deleteCategory(int $id, int $userId): bool
    {
        DB::beginTransaction();
        
        try {
            $category = $this->repository->findForUser($id, $userId);

            $result = $this->repository->delete($category);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error deleting category', [
                'id' => $id,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Restore a soft deleted category
     */
    public function restoreCategory(int $id, int $userId): Category
    {
        DB::beginTransaction();
        
        try {
            $category = $this->repository->restore($id, $userId);

            DB::commit();

            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error restoring category', [
                'id' => $id,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Force delete a category
     */
    public function forceDeleteCategory(int $id, int $userId): bool
    {
        DB::beginTransaction();
        
        try {
            $category = $this->repository->findForUser($id, $userId);

            $result = $this->repository->forceDelete($category);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error force deleting category', [
                'id' => $id,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Toggle category status
     */
    public function toggleCategoryStatus(int $id, int $userId): Category
    {
        DB::beginTransaction();
        
        try {
            $category = $this->repository->findForUser($id, $userId);
            $updatedCategory = $this->repository->toggleStatus($category);

            DB::commit();

            return $updatedCategory;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error toggling category status', [
                'id' => $id,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Get category statistics for user
     */
    public function getCategoryStats(int $userId): array
    {
        try {
            $counts = $this->repository->getCountByType($userId);
            
            return [
                'total' => $counts['income'] + $counts['expense'],
                'income_categories' => $counts['income'],
                'expense_categories' => $counts['expense']
            ];
        } catch (Exception $e) {
            $this->logError('Error getting category stats', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Validate category business rules
     */
    private function validateCategoryData(array $data, ?int $excludeId = null): void
    {
     
        if (empty($data['name'])) {
            throw new Exception('El nombre de la categoría es requerido');
        }

        if (empty($data['type']) || !in_array($data['type'], [Category::TYPE_INCOME, Category::TYPE_EXPENSE])) {
            throw new Exception('El tipo de categoría debe ser income o expense');
        }

        // Validate color format
        if (!empty($data['color']) && !preg_match('/^#[a-fA-F0-9]{6}$/', $data['color'])) {
            throw new Exception('El color debe estar en formato hexadecimal válido');
        }

        if (isset($data['user_id'])) {
            $exists = $this->repository->nameExistsForUser(
                $data['name'],
                $data['type'],
                $data['user_id'],
                $excludeId
            );

            if ($exists) {
                throw new Exception('Ya existe una categoría con este nombre y tipo');
            }
        }
    }
}
