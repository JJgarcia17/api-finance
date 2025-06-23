<?php

namespace App\Services\Transfer;

use App\Models\Transaction;
use App\Models\Category;
use App\Services\Transaction\TransactionService;
use App\Traits\HasAuthenticatedUser;
use App\Traits\HasLogging;
use Exception;
use Illuminate\Support\Facades\DB;

class TransferService
{
    use HasAuthenticatedUser, HasLogging;

    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Create a new transfer between accounts
     */
    public function createTransfer(array $data): Transaction
    {
        DB::beginTransaction();
        
        try {
            // Get or create a default transfer category
            $transferCategory = $this->getOrCreateTransferCategory();

            // Prepare transaction data
            $transactionData = [
                'account_id' => $data['from_account_id'],
                'destination_account_id' => $data['to_account_id'],
                'category_id' => $transferCategory->id,
                'type' => 'transfer',
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Transferencia entre cuentas',
                'transaction_date' => now()->format('Y-m-d'),
                'reference_number' => 'TRF-' . time() . '-' . rand(1000, 9999),
            ];

            // Create the transaction using the existing transaction service
            $transaction = $this->transactionService->createTransaction($transactionData);

            DB::commit();

            $this->logInfo('Transfer created successfully', [
                'transfer_id' => $transaction->id,
                'from_account_id' => $data['from_account_id'],
                'to_account_id' => $data['to_account_id'],
                'amount' => $data['amount'],
                'reference_number' => $transaction->reference_number
            ]);

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Error creating transfer', [
                'user_id' => $this->userId(),
                'data' => $data
            ], $e);
            throw $e;
        }
    }

    /**
     * Get all transfers for the authenticated user
     */
    public function getUserTransfers(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $sortBy = 'transaction_date',
        ?string $sortDirection = 'desc',
        ?int $perPage = null
    ) {
        return $this->transactionService->getTransactionsForUser(
            $this->userId(),
            'transfer', // Only transfer type
            null, // No account filter
            null, // No category filter
            $startDate,
            $endDate,
            $sortBy,
            $sortDirection,
            $perPage
        );
    }

    /**
     * Get a specific transfer by ID
     */
    public function getTransfer(int $transferId): Transaction
    {
        return $this->transactionService->getTransactionForUser($transferId, $this->userId());
    }

    /**
     * Delete a transfer
     */
    public function deleteTransfer(int $transferId): bool
    {
        $transfer = $this->getTransfer($transferId);
        
        if ($transfer->type !== 'transfer') {
            throw new Exception('La transacción especificada no es una transferencia');
        }

        return $this->transactionService->deleteTransaction($transfer);
    }

    /**
     * Get transfer statistics for the user
     */
    public function getTransferStats(): array
    {
        $userId = $this->userId();
        
        // Get all transfer transactions
        $transfers = Transaction::where('user_id', $userId)
            ->where('type', 'transfer')
            ->whereNull('deleted_at')
            ->get();

        $totalTransfers = $transfers->count();
        $totalAmount = $transfers->sum('amount');
        
        // Current month transfers
        $currentMonth = now()->startOfMonth();
        $currentMonthTransfers = $transfers->where('transaction_date', '>=', $currentMonth);
        $currentMonthCount = $currentMonthTransfers->count();
        $currentMonthAmount = $currentMonthTransfers->sum('amount');

        // This week transfers
        $weekStart = now()->startOfWeek();
        $thisWeekTransfers = $transfers->where('transaction_date', '>=', $weekStart);
        $thisWeekCount = $thisWeekTransfers->count();
        $thisWeekAmount = $thisWeekTransfers->sum('amount');

        return [
            'total_transfers' => $totalTransfers,
            'total_amount' => $totalAmount,
            'current_month_transfers' => $currentMonthCount,
            'current_month_amount' => $currentMonthAmount,
            'this_week_transfers' => $thisWeekCount,
            'this_week_amount' => $thisWeekAmount,
            'average_transfer_amount' => $totalTransfers > 0 ? $totalAmount / $totalTransfers : 0,
        ];
    }

    /**
     * Get or create a default transfer category
     */
    private function getOrCreateTransferCategory(): Category
    {
        $userId = $this->userId();
        
        // Try to find existing transfer category
        $category = Category::where('user_id', $userId)
            ->where('name', 'Transferencias')
            ->whereIn('type', ['income', 'expense']) // Use existing category types
            ->first();

        // Create if doesn't exist - use 'expense' type as fallback
        if (!$category) {
            $category = Category::create([
                'user_id' => $userId,
                'name' => 'Transferencias',
                'type' => 'expense', // Use existing type
                'color' => '#10B981', // Green color
                'icon' => 'transfer',
                'description' => 'Categoría automática para transferencias entre cuentas',
                'is_active' => true,
            ]);

            $this->logInfo('Transfer category created', [
                'category_id' => $category->id,
                'user_id' => $userId
            ]);
        }

        return $category;
    }
}