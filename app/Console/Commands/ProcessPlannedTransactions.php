<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlannedTransaction;
use Illuminate\Console\Command;

class ProcessPlannedTransactions extends Command
{
    protected $signature = 'planned:process-due {--dry-run : Show what would be processed without actually converting}';

    protected $description = 'Process planned transactions that are due today and convert them to actual transactions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Get all planned transactions that are due today and should be auto-converted
        $duePlanned = PlannedTransaction::with(['user', 'account', 'category'])
            ->where('planned_date', '<=', now()->toDateString())
            ->where('auto_convert', true)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        if ($duePlanned->isEmpty()) {
            $this->info('No planned transactions found that are due for processing.');
            return Command::SUCCESS;
        }

        $this->info("Found {$duePlanned->count()} planned transaction(s) due for processing:");

        $processed = 0;
        $errors = 0;

        foreach ($duePlanned as $planned) {
            try {
                $this->line("- {$planned->description} (€{$planned->amount}) for {$planned->user->name}");

                if (!$isDryRun) {
                    $transaction = $planned->convertToTransaction();
                    $this->info("  ✓ Converted to transaction #{$transaction->id}");
                    $processed++;
                } else {
                    $this->info("  [DRY RUN] Would convert to transaction");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to convert: {$e->getMessage()}");
                $errors++;
            }
        }

        if (!$isDryRun) {
            $this->info("\nProcessing completed:");
            $this->info("- Successfully processed: {$processed}");
            if ($errors > 0) {
                $this->warn("- Errors: {$errors}");
            }
        } else {
            $this->info("\n[DRY RUN] No actual conversions were performed.");
        }

        return Command::SUCCESS;
    }
}
