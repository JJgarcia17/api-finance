<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\Account\AccountBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAccountBalances extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounts:recalculate-balances 
                            {--user-id= : Recalculate balances for specific user} 
                            {--account-id= : Recalculate balance for specific account} 
                            {--dry-run : Show what would be changed without actually changing it}
                            {--force : Force correction without confirmation for large differences}
                            {--threshold=0.01 : Minimum difference threshold to consider for correction}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate account balances based on transactions';

    public function __construct(
        private AccountBalanceService $accountBalanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {        $userId = $this->option('user-id');
        $accountId = $this->option('account-id');
        $dryRun = $this->option('dry-run');
        $threshold = (float) $this->option('threshold');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $query = Account::query();

        if ($accountId) {
            $query->where('id', $accountId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $accounts = $query->with('transactions')->get();

        if ($accounts->isEmpty()) {
            $this->warn('No accounts found with the specified criteria.');
            return;
        }

        $this->info("Found {$accounts->count()} account(s) to process...");

        $balanceDiscrepancies = 0;
        $totalProcessed = 0;

        foreach ($accounts as $account) {
            $totalProcessed++;
            
            $this->info("Processing Account #{$account->id}: {$account->name}");

            // Calculate expected balance
            $totalIncome = $account->transactions()
                ->where('type', 'income')
                ->sum('amount');

            $totalExpenses = $account->transactions()
                ->where('type', 'expense')
                ->sum('amount');

            $expectedBalance = $account->initial_balance + $totalIncome - $totalExpenses;
            $currentBalance = $account->current_balance;
            $difference = $expectedBalance - $currentBalance;

            $this->table(
                ['Field', 'Value'],
                [
                    ['Initial Balance', number_format($account->initial_balance, 2)],
                    ['Total Income', number_format($totalIncome, 2)],
                    ['Total Expenses', number_format($totalExpenses, 2)],
                    ['Expected Balance', number_format($expectedBalance, 2)],
                    ['Current Balance', number_format($currentBalance, 2)],
                    ['Difference', number_format($difference, 2)],
                ]
            );

            if (abs($difference) > $threshold) {
                $balanceDiscrepancies++;
                
                if ($difference > 0) {
                    $this->warn("⚠️  Account is SHORT by $" . number_format(abs($difference), 2));
                } else {
                    $this->warn("⚠️  Account has EXCESS of $" . number_format(abs($difference), 2));
                }                if (!$dryRun) {
                    // Agregar confirmación para cambios críticos
                    if (abs($difference) > 100 && !$this->option('force')) {
                        if (!$this->confirm("⚠️  Large balance difference detected ($" . number_format(abs($difference), 2) . "). Continue with correction?")) {
                            $this->warn("Skipped correction for Account #{$account->id}");
                            continue;
                        }
                    }
                    
                    try {
                        DB::beginTransaction();
                          $this->accountBalanceService->recalculateAccountBalance($account);
                        
                        // Log de auditoría
                        \Log::info('Account balance recalculated via command', [
                            'account_id' => $account->id,
                            'user_id' => $account->user_id,
                            'old_balance' => $currentBalance,
                            'new_balance' => $expectedBalance,
                            'difference' => $difference,
                            'command_user' => $this->argument('command') ?? 'system'
                        ]);
                        
                        DB::commit();
                        
                        $this->info("✅ Balance corrected for Account #{$account->id}");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("❌ Failed to correct balance for Account #{$account->id}: " . $e->getMessage());
                    }
                } else {
                    $this->info("🔍 Would correct balance for Account #{$account->id}");
                }
            } else {
                $this->info("✅ Account balance is correct");
            }

            $this->newLine();
        }

        // Summary
        $this->info("📊 SUMMARY:");
        $this->info("Total accounts processed: {$totalProcessed}");
        $this->info("Accounts with balance discrepancies: {$balanceDiscrepancies}");
        
        if ($dryRun && $balanceDiscrepancies > 0) {
            $this->info("To apply changes, run the command without --dry-run option");
        }

        if ($balanceDiscrepancies === 0) {
            $this->info("🎉 All account balances are correct!");
        }

        return 0;
    }
}
