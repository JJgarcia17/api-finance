<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Category\CategoryCollection;
use App\Services\Category\CategoryService;
use App\Models\Category;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use InvalidArgumentException;

class CategoryController extends Controller
{
    use HasAuthenticatedUser, HasLogging;
    
    public function __construct(
        private CategoryService $categoryService
    ) {}

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
            $perPage = min($perPage, 100); 
            
            $categories = $this->categoryService->getCategoriesForUser($this->userId(), $filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryCollection($categories),
                'message' => 'Categorías obtenidas exitosamente',
                'status' => 200
            ], 200);
            
        } catch (Exception $e) {
            $this->logError('Error al obtener categorías', [
                'filters' => $filters ?? []
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated(), $this->userId());

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Categoría creada exitosamente',
                'status' => 201
            ], 201);
            
        } catch (Exception $e) {
            $this->logCrudError('create', 'categoría', 'store', $e, [
                'data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = Category::where('user_id', $this->userId())->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Categoría obtenida exitosamente',
                'status' => 200
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            $this->logWarning('Categoría no encontrada', [
                'category_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'status' => 404
            ], 404);
            
        } catch (Exception $e) {
            $this->logError('Error al obtener categoría', [
                'category_id' => $id
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        try {
            $category = Category::where('user_id', $this->userId())->findOrFail($id);
            
            $updatedCategory = $this->categoryService->updateCategory($category->id, $request->validated(), $this->userId());

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($updatedCategory),
                'message' => 'Categoría actualizada exitosamente',
                'status' => 200
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            $this->logError('Categoría no encontrada para actualizar', [
                'category_id' => $id,
                'data' => $request->validated()
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'status' => 404
            ], 404);
            
        } catch (Exception $e) {
            $this->logError('Error al actualizar categoría en update', [
                'category_id' => $id,
                'data' => $request->validated()
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = Category::where('user_id', $this->userId())->findOrFail($id);
            
            $this->categoryService->deleteCategory($category->id, $this->userId());
                 
            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente',
                'status' => 200
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            $this->logError('Categoría no encontrada para eliminar', [
                'category_id' => $id
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'status' => 404
            ], 404);
            
        } catch (Exception $e) {
            $this->logCrudError('delete', 'categoría', 'destroy', $e, [
                'category_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Toggle category status (active/inactive)
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $category = Category::where('user_id', $this->userId())->findOrFail($id);
            
            $updatedCategory = $this->categoryService->toggleCategoryStatus($category->id, $this->userId());
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($updatedCategory),
                'message' => 'Estado de categoría actualizado exitosamente',
                'status' => 200
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            $this->logWarning('Categoría no encontrada para cambiar estado', [
                'category_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'status' => 404
            ], 404);
            
        } catch (Exception $e) {
            $this->logError('Error al cambiar estado de categoría', [
                'category_id' => $id
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get categories by type
     */
    public function getByType(Request $request, string $type)
    {
        try {
            // Validar que el tipo sea válido
            if (!in_array($type, ['income', 'expense'])) {
                throw new InvalidArgumentException('Tipo de categoría inválido. Debe ser income o expense.');
            }
    
            $userId = $this->userId();
            
            // Usar el método existente con filtro de tipo
            $categories = $this->categoryService->getCategoriesForUser(
                $userId, 
                ['type' => $type], // Filtrar por tipo
                1000 // Un número alto para obtener todas las categorías
            );
    
            return response()->json([
                'success' => true,
                'data' => $categories->items(), // Obtener solo los elementos sin paginación
                'message' => 'Categorías obtenidas exitosamente'
            ]);
    
        } catch (InvalidArgumentException $e) {
            $this->logError('Tipo de categoría inválido', [
                'type' => $type,
                'error_message' => $e->getMessage()
            ], $e);
    
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
    
        } catch (Exception $e) {
            $this->logCrudError('read', 'categorías por tipo', 'getByType', $e, [
                'type' => $type
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Restore a soft deleted category
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $restoredCategory = $this->categoryService->restoreCategory((int)$id, $this->userId());
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($restoredCategory),
                'message' => 'Categoría restaurada exitosamente',
                'status' => 200
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            $this->logError('Categoría no encontrada para restaurar', [
                'category_id' => $id
            ], $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'status' => 404
            ], 404);
            
        } catch (Exception $e) {
            $this->logCrudError('restore', 'categoría', 'restore', $e, [
                'category_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la categoría',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }
}
