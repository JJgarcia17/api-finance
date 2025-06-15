<?php

namespace App\Services\Account;

use App\Models\Account;
use App\Repositories\Account\AccountRepository;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;
use InvalidArgumentException;

class AccountService
{
    use HasAuthenticatedUser, HasLogging;
    
    public function __construct(
        private AccountRepository $accountRepository
    ) {}

    /**
     * Get accounts for user with filtering
     */
    public function getAccountsForUser(
        int $userId,
        ?string $type = null,
        ?bool $isActive = null,
        ?bool $includeInTotal = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        ?int $perPage = null
    ): Collection|LengthAwarePaginator {
        try {
            return $this->accountRepository->getAllForUser(
                $userId,
                $type,
                $isActive,
                $includeInTotal,
                $sortBy,
                $sortDirection,
                $perPage
            );
        } catch (Exception $e) {
            $this->logError('Error getting accounts for user', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Get account for user
     */
    public function getAccountForUser(int $accountId, int $userId): Account
    {
        try {
            return $this->accountRepository->findForUser($accountId, $userId);
        } catch (Exception $e) {
            $this->logError('Error getting account for user', [
                'account_id' => $accountId,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Create a new account
     */
    public function createAccount(array $data): Account
    {
        try {
            DB::beginTransaction();

            $data['user_id'] = $this->userId();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['include_in_total'] = $data['include_in_total'] ?? true;
            
            if (!isset($data['current_balance'])) {
                $data['current_balance'] = $data['initial_balance'] ?? 0;
            }

            $this->validateAccountData($data);

            // Check for duplicate names
            if ($this->accountRepository->nameExistsForUser($data['name'], $data['user_id'])) {
                throw new InvalidArgumentException('Ya existe una cuenta con este nombre');
            }

            $account = $this->accountRepository->create($data);

            DB::commit();

            return $account;
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw $e; // Re-throw validation errors as-is
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Handle database constraint violations
            if (str_contains($e->getMessage(), 'accounts_user_name_unique')) {
                throw new InvalidArgumentException('Ya existe una cuenta con este nombre');
            }
            
            $this->logError('Database error creating account', [
                'data' => $data,
                'error' => $e->getMessage()
            ], $e);
            throw new Exception('Error al crear la cuenta en la base de datos');
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error creating account', [
                'data' => $data
            ], $e);
            throw $e;
        }
    }

    /**
     * Update an account
     */
    public function updateAccount(Account $account, array $data): Account
    {
        try {
            DB::beginTransaction();

            $this->validateAccountData($data, $account->id);

            if (isset($data['name']) && 
                $this->accountRepository->nameExistsForUser($data['name'], $account->user_id, $account->id)) {
                throw new InvalidArgumentException('Ya existe una cuenta con este nombre');
            }

            $updatedAccount = $this->accountRepository->update($account, $data);

            DB::commit();

            return $updatedAccount;
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw $e; // Re-throw validation errors as-is
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Handle database constraint violations
            if (str_contains($e->getMessage(), 'accounts_user_name_unique')) {
                throw new InvalidArgumentException('Ya existe una cuenta con este nombre');
            }
            
            $this->logError('Database error updating account', [
                'account_id' => $account->id,
                'data' => $data,
                'error' => $e->getMessage()
            ], $e);
            throw new Exception('Error al actualizar la cuenta en la base de datos');
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error updating account', [
                'account_id' => $account->id,
                'data' => $data
            ], $e);
            throw $e;
        }
    }

    /**
     * Delete an account
     */
    public function deleteAccount(Account $account): bool
    {
        try {
            DB::beginTransaction();

            if ($account->transactions()->exists()) {
                throw new InvalidArgumentException('No se puede eliminar una cuenta que tiene transacciones asociadas');
            }

            $result = $this->accountRepository->delete($account);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error deleting account', [
                'account_id' => $account->id
            ], $e);
            throw $e;
        }
    }

    /**
     * Restore a deleted account
     */
    public function restoreAccount(Account $account): bool
    {
        try {
            $result = $this->accountRepository->restore($account);

            return $result;
        } catch (Exception $e) {
            $this->logError('Error restoring account', [
                'account_id' => $account->id
            ], $e);
            throw $e;
        }
    }

    /**
     * Force delete an account
     */
    public function forceDeleteAccount(Account $account): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->accountRepository->forceDelete($account);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error force deleting account', [
                'account_id' => $account->id
            ], $e);
            throw $e;
        }
    }

    /**
     * Toggle account status
     */
    public function toggleAccountStatus(Account $account): Account
    {
        try {
            $updatedAccount = $this->accountRepository->toggleStatus($account);

           return $updatedAccount;
        } catch (Exception $e) {
            $this->logError('Error toggling account status', [
                'account_id' => $account->id
            ], $e);
            throw $e;
        }
    }

    /**
     * Get account statistics for user
     */
    public function getAccountStats(int $userId): array
    {
        try {
            return $this->accountRepository->getStatsForUser($userId);
        } catch (Exception $e) {
            $this->logError('Error getting account stats', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Update account balance
     */
    public function updateAccountBalance(Account $account, float $newBalance): Account
    {
        try {
            $updatedAccount = $this->accountRepository->updateBalance($account, $newBalance);

            return $updatedAccount;
        } catch (Exception $e) {
            $this->logError('Error updating account balance', [
                'account_id' => $account->id,
                'new_balance' => $newBalance
            ], $e);
            throw $e;
        }
    }

    /**
     * Get active accounts for user
     */
    public function getActiveAccountsForUser(int $userId): Collection
    {
        try {
            return $this->accountRepository->getActiveForUser($userId);
        } catch (Exception $e) {
            $this->logError('Error getting active accounts for user', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    /**
     * Validate account data
     */
    private function validateAccountData(array $data, ?int $excludeId = null): void
    {
   
        if (isset($data['type']) && !in_array($data['type'], Account::TYPES)) {
            throw new InvalidArgumentException('Tipo de cuenta inválido');
        }

        if (isset($data['currency']) && !in_array($data['currency'], Account::CURRENCIES)) {
            throw new InvalidArgumentException('Moneda inválida');
        }

        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            throw new InvalidArgumentException('Formato de color inválido');
        }

        if (isset($data['initial_balance']) && !is_numeric($data['initial_balance'])) {
            throw new InvalidArgumentException('El saldo inicial debe ser un número');
        }

        if (isset($data['current_balance']) && !is_numeric($data['current_balance'])) {
            throw new InvalidArgumentException('El saldo actual debe ser un número');
        }
    }
}