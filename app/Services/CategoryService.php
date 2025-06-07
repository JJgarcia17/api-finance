<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryService
{
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
            Log::error('Error getting categories for user', [
                'user_id' => $userId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
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
            Log::warning('Category not found', ['id' => $id, 'user_id' => $userId]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Error getting category', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            Log::info('Category created successfully', [
                'category_id' => $category->id,
                'user_id' => $userId,
                'name' => $category->name
            ]);

            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating category', [
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            Log::info('Category updated successfully', [
                'category_id' => $id,
                'user_id' => $userId,
                'changes' => $data
            ]);

            return $updatedCategory;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating category', [
                'id' => $id,
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            // Business rule: Check if category is being used
            // TODO: Add validation for related transactions when implemented

            $result = $this->repository->delete($category);

            DB::commit();

            Log::info('Category deleted successfully', [
                'category_id' => $id,
                'user_id' => $userId
            ]);

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            Log::info('Category restored successfully', [
                'category_id' => $id,
                'user_id' => $userId
            ]);

            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error restoring category', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            Log::info('Category force deleted successfully', [
                'category_id' => $id,
                'user_id' => $userId
            ]);

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error force deleting category', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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

            Log::info('Category status toggled successfully', [
                'category_id' => $id,
                'user_id' => $userId,
                'new_status' => $updatedCategory->is_active
            ]);

            return $updatedCategory;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error toggling category status', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
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
            Log::error('Error getting category stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate category business rules
     */
    private function validateCategoryData(array $data, ?int $excludeId = null): void
    {
        // Validate required fields
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

        // Check for duplicate names
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
