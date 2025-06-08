<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Repositories\Transaction\TransactionRepository;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    use HasAuthenticatedUser, HasLogging;

    public function __construct(
        private TransactionRepository $transactionRepository
    ) {}

    public function getTransactionsForUser(
        int $userId,
        ?string $type = null,
        ?int $accountId = null,
        ?int $categoryId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $sortBy = 'transaction_date',
        string $sortDirection = 'desc',
        ?int $perPage = null
    ): Collection|LengthAwarePaginator {
        try {
            return $this->transactionRepository->getForUser(
                $userId,
                $type,
                $accountId,
                $categoryId,
                $startDate,
                $endDate,
                $sortBy,
                $sortDirection,
                $perPage
            );
        } catch (Exception $e) {
            $this->logError('Error getting transactions for user', [
                'user_id' => $userId,
                'filters' => compact('type', 'accountId', 'categoryId', 'startDate', 'endDate')
            ], $e);
            throw $e;
        }
    }

    public function getTransactionForUser(int $transactionId, int $userId): Transaction
    {
        $transaction = $this->transactionRepository->findByIdForUserOrFail($transactionId, $userId);

        return $transaction;
    }

    public function createTransaction(array $data): Transaction
    {
        try {
            DB::beginTransaction();

            $data['user_id'] = $this->userId();
            $transaction = $this->transactionRepository->create($data);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error creating transaction', [
                'user_id' => $this->userId(),
                'data' => $data
            ], $e);
            throw $e;
        }
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        try {
            DB::beginTransaction();

            $updatedTransaction = $this->transactionRepository->update($transaction, $data);

            DB::commit();

            return $updatedTransaction;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error updating transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId(),
                'data' => $data
            ], $e);
            throw $e;
        }
    }

    public function deleteTransaction(Transaction $transaction): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->transactionRepository->delete($transaction);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error deleting transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId()
            ], $e);
            throw $e;
        }
    }

    public function restoreTransaction(int $transactionId, int $userId): Transaction
    {
        DB::beginTransaction();
        
        try {
            $transaction = $this->transactionRepository->restore($transactionId, $userId);
            
            DB::commit();
            
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error restoring transaction', [
                'transaction_id' => $transactionId,
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    public function getTransactionStats(int $userId): array
    {
        try {
            return $this->transactionRepository->getStats($userId);
        } catch (Exception $e) {
            $this->logError('Error getting transaction stats', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }
}


