<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Repositories\Transaction\TransactionRepository;
use App\Services\Account\AccountBalanceService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;


class TransactionService
{
    use HasAuthenticatedUser, HasLogging;

    public function __construct(
        private TransactionRepository $transactionRepository,
        private AccountBalanceService $accountBalanceService
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
        DB::beginTransaction();
        
        try {
            $data['user_id'] = $this->userId();
            
            // Generate reference number for transfers if not provided
            if (isset($data['type']) && $data['type'] === 'transfer' && empty($data['reference_number'])) {
                $data['reference_number'] = 'TRF-' . time() . '-' . rand(1000, 9999);
            }
            
            $transaction = $this->transactionRepository->create($data);

            // Load the account relationships for balance update
            if ($transaction->type === 'transfer') {
                $transaction->load(['account', 'destinationAccount']);
                $this->accountBalanceService->processTransfer($transaction);
            } else {
                $transaction->load('account');
                $this->accountBalanceService->updateBalanceForTransaction($transaction);
            }

            DB::commit();

            $this->logInfo('Transaction created successfully with balance update', [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'destination_account_id' => $transaction->destination_account_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type
            ]);

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
        DB::beginTransaction();
        
        try {
            // Store original transaction data for balance reversion
            $originalTransaction = $transaction->replicate();
            
            // Load relationships based on transaction type
            if ($originalTransaction->type === 'transfer') {
                $originalTransaction->load(['account', 'destinationAccount']);
                $this->accountBalanceService->revertTransfer($originalTransaction);
            } else {
                $originalTransaction->load('account');
                $this->accountBalanceService->revertBalanceForTransaction($originalTransaction);
            }

            // Update the transaction
            $updatedTransaction = $this->transactionRepository->update($transaction, $data);
            
            // Load relationships and apply new balance changes
            if ($updatedTransaction->type === 'transfer') {
                $updatedTransaction->load(['account', 'destinationAccount']);
                $this->accountBalanceService->processTransfer($updatedTransaction);
            } else {
                $updatedTransaction->load('account');
                $this->accountBalanceService->updateBalanceForTransaction($updatedTransaction);
            }

            DB::commit();

            $this->logInfo('Transaction updated successfully with balance update', [
                'transaction_id' => $updatedTransaction->id,
                'account_id' => $updatedTransaction->account_id,
                'destination_account_id' => $updatedTransaction->destination_account_id,
                'old_amount' => $originalTransaction->amount,
                'new_amount' => $updatedTransaction->amount,
                'old_type' => $originalTransaction->type,
                'new_type' => $updatedTransaction->type
            ]);

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
        DB::beginTransaction();
        
        try {
            // Load the account relationships for balance update
            if ($transaction->type === 'transfer') {
                $transaction->load(['account', 'destinationAccount']);
                $this->accountBalanceService->revertTransfer($transaction);
            } else {
                $transaction->load('account');
                $this->accountBalanceService->revertBalanceForDeletedTransaction($transaction);
            }

            $result = $this->transactionRepository->delete($transaction);

            DB::commit();

            $this->logInfo('Transaction deleted successfully with balance update', [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'destination_account_id' => $transaction->destination_account_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type
            ]);

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
            
            // Load relationships and update balance for restored transaction
            if ($transaction->type === 'transfer') {
                $transaction->load(['account', 'destinationAccount']);
                $this->accountBalanceService->processTransfer($transaction);
            } else {
                $transaction->load('account');
                $this->accountBalanceService->updateBalanceForRestoredTransaction($transaction);
            }
            
            DB::commit();

            $this->logInfo('Transaction restored successfully with balance update', [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'destination_account_id' => $transaction->destination_account_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type
            ]);
            
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

    public function getTransactionStatsByCurrency(int $userId): array
    {
        try {
            return $this->transactionRepository->getStatsByCurrency($userId);
        } catch (Exception $e) {
            $this->logError('Error getting transaction stats by currency', [
                'user_id' => $userId
            ], $e);
            throw $e;
        }
    }

    public function getMonthlyTrends(int $userId, int $months = 12): array
    {
        try {
            return $this->transactionRepository->getMonthlyTrends($userId, $months);
        } catch (Exception $e) {
            $this->logError('Error getting monthly trends', [
                'user_id' => $userId,
                'months' => $months
            ], $e);
            throw $e;
        }
    }
}


