<?php

namespace App\Console\Commands;

use App\Services\Llm\LlmMetrics;
use Illuminate\Console\Command;

class CleanupLlmMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:cleanup-metrics 
                            {--hours=168 : Hours back to keep metrics (default: 1 week)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old LLM metrics data';

    /**
     * Execute the console command.
     */
    public function handle(LlmMetrics $metrics): int
    {
        $hoursBack = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up LLM metrics older than {$hoursBack} hours");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        try {
            if (!$dryRun) {
                $metrics->cleanupOldMetrics();
                $this->info('✅ LLM metrics cleanup completed successfully');
            } else {
                $this->info('✅ DRY RUN completed - metrics would be cleaned up');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error cleaning up metrics: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
