<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryCollection;
use App\Services\CategoryService;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            
            // Text filters
            if ($request->filled('search')) {
                $filters['search'] = $request->get('search');
            }
            
            if ($request->filled('color')) {
                $filters['color'] = $request->get('color');
            }
            
            // Enum/Boolean filters
            if ($request->has('type')) {
                $filters['type'] = $request->get('type');
            }
            
            if ($request->has('is_active')) {
                $filters['is_active'] = $request->get('is_active');
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page
            
            $userId = auth()->id();
            $categories = $this->categoryService->getCategoriesForUser($userId, $filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryCollection($categories),
                'message' => 'Categorías obtenidas exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@index', [
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $category = $this->categoryService->createCategory($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Categoría creada exitosamente'
            ], 201);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@store', [
                'user_id' => auth()->id(),
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show($id): JsonResponse
    {
        try {
            $category = Category::where('user_id', auth()->id())->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Categoría obtenida exitosamente'
            ]);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error in CategoryController@show', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $updatedCategory = $this->categoryService->updateCategory($category->id, $request->validated(), auth()->id());
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($updatedCategory),
                'message' => 'Categoría actualizada exitosamente'
            ],200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category->id, auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@destroy', [
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Restore a soft deleted category
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $userId = auth()->id();
            $category = $this->categoryService->restoreCategory($id, $userId);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Categoría restaurada exitosamente'
            ]);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría eliminada no encontrada'
            ], 404);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@restore', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }


    /**
     * Toggle category status
     */
    public function toggleStatus(Category $category): JsonResponse
    {
        try {
            $updatedCategory = $this->categoryService->toggleCategoryStatus($category->id, auth()->id());
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($updatedCategory),
                'message' => 'Estado de la categoría actualizado exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@toggleStatus', [
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get category statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $stats = $this->categoryService->getCategoryStats($userId);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error in CategoryController@stats', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
