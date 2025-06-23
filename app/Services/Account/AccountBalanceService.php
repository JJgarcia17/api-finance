<?php

namespace App\Services\Account;

use App\Models\Account;
use App\Models\Transaction;
use App\Repositories\Account\AccountRepository;
use App\Traits\HasLogging;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountBalanceService
{
    use HasLogging;

    public function __construct(
        private AccountRepository $accountRepository
    ) {}

    /**
     * Update account balance after transaction creation
     */
    public function updateBalanceForTransaction(Transaction $transaction): void
    {
        $account = $transaction->account;
        
        if (!$account) {
            throw new Exception('Account not found for transaction');
        }

        $this->logInfo('Updating account balance for transaction', [
            'account_id' => $account->id,
            'transaction_id' => $transaction->id,
            'transaction_type' => $transaction->type,
            'transaction_amount' => $transaction->amount,
            'current_balance' => $account->current_balance
        ]);

        $this->adjustAccountBalance($account, $transaction->type, $transaction->amount);
    }

    /**
     * Revert account balance changes when transaction is updated
     */
    public function revertBalanceForTransaction(Transaction $originalTransaction): void
    {
        $account = $originalTransaction->account;
        
        if (!$account) {
            throw new Exception('Account not found for transaction');
        }

        $this->logInfo('Reverting account balance for transaction', [
            'account_id' => $account->id,
            'transaction_id' => $originalTransaction->id,
            'transaction_type' => $originalTransaction->type,
            'transaction_amount' => $originalTransaction->amount,
            'current_balance' => $account->current_balance
        ]);

        // Revert by doing the opposite operation
        $oppositeType = $originalTransaction->type === 'income' ? 'expense' : 'income';
        $this->adjustAccountBalance($account, $oppositeType, $originalTransaction->amount);
    }

    /**
     * Update account balance when transaction is deleted
     */
    public function revertBalanceForDeletedTransaction(Transaction $deletedTransaction): void
    {
        $this->revertBalanceForTransaction($deletedTransaction);
    }

    /**
     * Update account balance when transaction is restored
     */
    public function updateBalanceForRestoredTransaction(Transaction $restoredTransaction): void
    {
        $this->updateBalanceForTransaction($restoredTransaction);
    }

    /**
     * Adjust account balance based on transaction type and amount
     */
    private function adjustAccountBalance(Account $account, string $transactionType, float $amount): void
    {
        $currentBalance = $account->current_balance;
        
        switch ($transactionType) {
            case 'income':
                $newBalance = $currentBalance + $amount;
                break;
            case 'expense':
                $newBalance = $currentBalance - $amount;
                break;
            case 'transfer':
                // For transfers, this method should be called twice:
                // Once for the source account (as expense) and once for destination account (as income)
                throw new Exception('Transfer transactions require special handling');
            default:
                throw new Exception("Unknown transaction type: {$transactionType}");
        }

        $this->logInfo('Adjusting account balance', [
            'account_id' => $account->id,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'old_balance' => $currentBalance,
            'new_balance' => $newBalance
        ]);

        // Update the account balance
        $this->accountRepository->updateBalance($account, $newBalance);
    }

    /**
     * Recalculate account balance from all transactions
     * This can be used for data integrity checks or repairs
     */
    public function recalculateAccountBalance(Account $account): void
    {
        $this->logInfo('Recalculating account balance from transactions', [
            'account_id' => $account->id,
            'current_balance' => $account->current_balance,
            'initial_balance' => $account->initial_balance
        ]);

        // Get all transactions for this account
        $totalIncome = $account->transactions()
            ->where('type', 'income')
            ->sum('amount');

        $totalExpenses = $account->transactions()
            ->where('type', 'expense')
            ->sum('amount');

        // Calculate the correct balance
        $calculatedBalance = $account->initial_balance + $totalIncome - $totalExpenses;

        $this->logInfo('Recalculated account balance', [
            'account_id' => $account->id,
            'initial_balance' => $account->initial_balance,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'calculated_balance' => $calculatedBalance,
            'current_balance' => $account->current_balance
        ]);

        // Update if different
        if (abs($calculatedBalance - $account->current_balance) > 0.01) {
            $this->logWarning('Account balance mismatch detected and corrected', [
                'account_id' => $account->id,
                'expected_balance' => $calculatedBalance,
                'actual_balance' => $account->current_balance,
                'difference' => $calculatedBalance - $account->current_balance
            ]);

            $this->accountRepository->updateBalance($account, $calculatedBalance);
        }
    }

    /**
     * Process a transfer transaction between two accounts
     */
    public function processTransfer(Transaction $transaction): void
    {
        if ($transaction->type !== 'transfer') {
            throw new Exception('Transaction is not a transfer');
        }

        if (!$transaction->destination_account_id) {
            throw new Exception('Transfer transaction must have a destination account');
        }

        $fromAccount = $transaction->account;
        $toAccount = Account::find($transaction->destination_account_id);

        if (!$fromAccount || !$toAccount) {
            throw new Exception('Source or destination account not found');
        }

        // Validate sufficient balance
        if ($fromAccount->current_balance < $transaction->amount) {
            throw new Exception('Insufficient balance in source account');
        }

        // Validate same currency
        if ($fromAccount->currency !== $toAccount->currency) {
            throw new Exception('Transfer between different currencies is not supported');
        }

        $this->logInfo('Processing transfer transaction', [
            'transaction_id' => $transaction->id,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => $transaction->amount,
            'from_balance_before' => $fromAccount->current_balance,
            'to_balance_before' => $toAccount->current_balance
        ]);

        DB::beginTransaction();
        try {
            // Deduct from source account
            $newFromBalance = $fromAccount->current_balance - $transaction->amount;
            $this->accountRepository->updateBalance($fromAccount, $newFromBalance);

            // Add to destination account
            $newToBalance = $toAccount->current_balance + $transaction->amount;
            $this->accountRepository->updateBalance($toAccount, $newToBalance);

            DB::commit();

            $this->logInfo('Transfer completed successfully', [
                'transaction_id' => $transaction->id,
                'from_balance_after' => $newFromBalance,
                'to_balance_after' => $newToBalance
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Transfer processing failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Revert a transfer transaction
     */
    public function revertTransfer(Transaction $transaction): void
    {
        if ($transaction->type !== 'transfer') {
            throw new Exception('Transaction is not a transfer');
        }

        if (!$transaction->destination_account_id) {
            throw new Exception('Transfer transaction must have a destination account');
        }

        $fromAccount = $transaction->account;
        $toAccount = Account::find($transaction->destination_account_id);

        if (!$fromAccount || !$toAccount) {
            throw new Exception('Source or destination account not found');
        }

        $this->logInfo('Reverting transfer transaction', [
            'transaction_id' => $transaction->id,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => $transaction->amount
        ]);

        DB::beginTransaction();
        try {
            // Add back to source account
            $newFromBalance = $fromAccount->current_balance + $transaction->amount;
            $this->accountRepository->updateBalance($fromAccount, $newFromBalance);

            // Deduct from destination account
            $newToBalance = $toAccount->current_balance - $transaction->amount;
            $this->accountRepository->updateBalance($toAccount, $newToBalance);

            DB::commit();

            $this->logInfo('Transfer reverted successfully', [
                'transaction_id' => $transaction->id
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Transfer revert failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
