<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\Account\AccountResource;
use App\Http\Resources\Account\AccountCollection;
use App\Models\Account;
use App\Services\Account\AccountService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class AccountController extends Controller
{
    use HasAuthenticatedUser, HasLogging;
    
    public function __construct(
        private AccountService $accountService
    ) {}

    /**
     * Display a listing of accounts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $accounts = $this->accountService->getAccountsForUser(
                userId: $this->userId(),
                type: $request->get('type'),
                isActive: $request->boolean('is_active'),
                includeInTotal: $request->boolean('include_in_total'),
                sortBy: $request->get('sort_by', 'name'),
                sortDirection: $request->get('sort_direction', 'asc'),
                perPage: $request->get('per_page')
            );

            return response()->json([
                'success' => true,
                'data' => new AccountCollection($accounts),
                'message' => 'Cuentas obtenidas exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al obtener cuentas en AccountController@index', [
                'user_id' => $this->userId(),
                'filters' => $request->only(['type', 'is_active', 'include_in_total', 'sort_by', 'sort_direction', 'per_page'])
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cuentas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Store a newly created account
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->accountService->createAccount($request->validated());

            return response()->json([
                'success' => true,
                'data' => new AccountResource($account),
                'message' => 'Cuenta creada exitosamente',
                'status' => 201
            ], 201);
        } catch (Exception $e) {
            $this->logError('Error al crear cuenta en AccountController@store', [
                'user_id' => $this->userId(),
                'data' => $request->validated()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Display the specified account
     */
    public function show(Account $account): JsonResponse
    {
        try {
            $account = $this->accountService->getAccountForUser($account->id, $this->userId());

            return response()->json([
                'success' => true,
                'data' => new AccountResource($account),
                'message' => 'Cuenta obtenida exitosamente',
                'status' => 200
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta no encontrada',
                'status' => 404
            ], 404);
        } catch (Exception $e) {
            $this->logError('Error al obtener cuenta en AccountController@show', [
                'account_id' => $account->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Update the specified account
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        try {
            $updatedAccount = $this->accountService->updateAccount($account, $request->validated());

            return response()->json([
                'success' => true,
                'data' => new AccountResource($updatedAccount),
                'message' => 'Cuenta actualizada exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al actualizar cuenta en AccountController@update', [
                'account_id' => $account->id,
                'user_id' => $this->userId(),
                'data' => $request->validated()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Remove the specified account
     */
    public function destroy(Account $account): JsonResponse
    {
        try {
            $this->accountService->deleteAccount($account);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente',
                'status' => 200
            ], 200);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 422
            ], 422);
        } catch (Exception $e) {
            $this->logError('Error al eliminar cuenta en AccountController@destroy', [
                'account_id' => $account->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Restore a soft deleted account
     */
    public function restore(Account $account): JsonResponse
    {
        try {
            $this->accountService->restoreAccount($account);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta restaurada exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al restaurar cuenta en AccountController@restore', [
                'account_id' => $account->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Toggle account status
     */
    public function toggleStatus(Account $account): JsonResponse
    {
        try {
            $updatedAccount = $this->accountService->toggleAccountStatus($account);

            return response()->json([
                'success' => true,
                'data' => new AccountResource($updatedAccount),
                'message' => 'Estado de la cuenta actualizado exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al cambiar estado de cuenta en AccountController@toggleStatus', [
                'account_id' => $account->id,
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la cuenta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get account statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->accountService->getAccountStats($this->userId());

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente',
                'status' => 200
            ], 200);
        } catch (Exception $e) {
            $this->logError('Error al obtener estadísticas en AccountController@stats', [
                'user_id' => $this->userId()
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'status' => 500
            ], 500);
        }
    }
}