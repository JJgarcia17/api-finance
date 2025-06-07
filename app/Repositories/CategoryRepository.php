<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryRepository
{
    protected Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * Get all categories for a user with filters
     */
    public function getAllForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', '=', $userId);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['type'])) {
            $query->where('type', '=', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', '=', (bool) $filters['is_active']);
        }

        if (!empty($filters['color'])) {
            $query->where('color', '=', $filters['color']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find category by ID for a specific user
     */
    public function findForUser(int $id, int $userId): Category
    {
        $category = $this->model->where('id', '=', $id)
                               ->where('user_id', '=', $userId)
                               ->first();

        if (!$category) {
            throw new ModelNotFoundException('Categoría no encontrada');
        }

        return $category;
    }

    /**
     * Create a new category
     */
    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    /**
     * Update a category
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    /**
     * Delete a category (soft delete)
     */
    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    /**
     * Force delete a category
     */
    public function forceDelete(Category $category): bool
    {
        return $category->forceDelete();
    }

    /**
     * Restore a soft deleted category
     */
    public function restore(int $id, int $userId): Category
    {
        $category = $this->model->onlyTrashed()
                               ->where('id', '=', $id)
                               ->where('user_id', '=', $userId)
                               ->first();

        if (!$category) {
            throw new ModelNotFoundException('Categoría eliminada no encontrada');
        }

        $category->restore();
        return $category;
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(Category $category): Category
    {
        $category->update(['is_active' => !$category->is_active]);
        return $category->fresh();
    }

    /**
     * Check if category name exists for user (excluding current category)
     */
    public function nameExistsForUser(string $name, string $type, int $userId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', '=', $name)
                            ->where('type', '=', $type)
                            ->where('user_id', '=', $userId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get categories count by type for user
     */
    public function getCountByType(int $userId): array
    {
        return [
            'income' => $this->model->where('user_id', '=', $userId)
                                   ->where('type', '=', Category::TYPE_INCOME)
                                   ->count(),
            'expense' => $this->model->where('user_id', '=', $userId)
                                    ->where('type', '=', Category::TYPE_EXPENSE)
                                    ->count(),
        ];
    }
}
