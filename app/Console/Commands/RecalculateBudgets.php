<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Console\Command;

class RecalculateBudgets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budgets:recalculate {--user= : Recalculate for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate spent amounts for all budgets based on existing transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting budget recalculation...');

        $query = Budget::query();
        
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
            $this->info("Recalculating budgets for user ID: {$userId}");
        } else {
            $this->info('Recalculating budgets for all users');
        }

        $budgets = $query->get();
        $progressBar = $this->output->createProgressBar($budgets->count());
        $progressBar->start();

        foreach ($budgets as $budget) {
            $this->recalculateBudgetSpentAmount($budget);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Successfully recalculated {$budgets->count()} budgets.");
    }

    private function recalculateBudgetSpentAmount(Budget $budget): void
    {
        // Calculate total spent amount for this budget's category within the budget period
        $spentAmount = Transaction::where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $budget->start_date)
            ->where('transaction_date', '<=', $budget->end_date)
            ->sum('amount');

        // Update the budget's spent amount
        $budget->update(['spent_amount' => $spentAmount]);
    }
}
