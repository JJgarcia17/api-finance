<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Models\Transaction;
use App\Services\Transaction\TransactionService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    use HasAuthenticatedUser, HasLogging;
    
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Display a listing of transactions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $transactions = $this->transactionService->getTransactionsForUser(
                userId: $this->userId(),
                type: $request->get('type'),
                accountId: $request->get('account_id'),
                categoryId: $request->get('category_id'),
                startDate: $request->get('start_date'),
                endDate: $request->get('end_date'),
                sortBy: $request->get('sort_by', 'transaction_date'),
                sortDirection: $request->get('sort_direction', 'desc'),
                perPage: $request->get('per_page', 15) // Asegurar que siempre haya paginación
            );

            return response()->json([
                'success' => true,
                'data' => new TransactionCollection($transactions),
                'message' => 'Transacciones obtenidas exitosamente',
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al obtener transacciones en TransactionController@index', [
                'user_id' => $this->userId(),
                'filters' => $request->only(['type', 'account_id', 'category_id', 'start_date', 'end_date'])
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las transacciones',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Store a newly created transaction
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createTransaction($request->validated());

            return response()->json([
                'success' => true,
                'data' => new TransactionResource($transaction),
                'message' => 'Transacción creada exitosamente',
            ], 201);
        } catch (Exception $e) {
            $this->logError('Error al crear transacción en TransactionController@store', [
                'user_id' => $this->userId(),
                'data' => $request->validated()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la transacción',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Display the specified transaction
     */
    public function show(Transaction $transaction): JsonResponse
    {
        try {
            $validatedTransaction = $this->transactionService->getTransactionForUser(
                $transaction->id, 
                $this->userId()
            );

            return response()->json([
                'success' => true,
                'data' => new TransactionResource($validatedTransaction),
                'message' => 'Transacción obtenida exitosamente',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transacción no encontrada',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error showing transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la transacción',
            ], 500);
        }
    }

    /**
     * Update the specified transaction
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        try {
            $updatedTransaction = $this->transactionService->updateTransaction($transaction, $request->validated());

            return response()->json([
                'success' => true,
                'data' => new TransactionResource($updatedTransaction),
                'message' => 'Transacción actualizada exitosamente',
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al actualizar transacción en TransactionController@update', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId(),
                'data' => $request->validated()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la transacción',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Remove the specified transaction
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        try {
            $this->transactionService->deleteTransaction($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transacción eliminada exitosamente',
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al eliminar transacción en TransactionController@destroy', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la transacción',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Restore a soft deleted transaction
     */
    public function restore(Transaction $transaction): JsonResponse
    {
        try {
            $restoredTransaction = $this->transactionService->restoreTransaction(
                $transaction->id,
                $this->userId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction restored successfully',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al restaurar transacción en TransactionController@restore', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error restoring transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->transactionService->getTransactionStats($this->userId());

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente',
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al obtener estadísticas en TransactionController@stats', [
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}