<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Budget;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->updateAccountBalance($transaction, 'create');
        $this->updateBudgets($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        // Handle account balance updates for changes
        $this->updateAccountBalanceForUpdate($transaction);

        // Get the original category before update
        $originalCategoryId = $transaction->getOriginal('category_id');
        $originalAmount = $transaction->getOriginal('amount');

        // If category or amount changed, update both old and new budgets
        if ($originalCategoryId !== $transaction->category_id || $originalAmount !== $transaction->amount) {
            // Update budgets for the old category
            if ($originalCategoryId) {
                $this->updateBudgetsForCategory($transaction->user_id, $originalCategoryId);
            }
        }

        // Update budgets for current category
        $this->updateBudgets($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->updateAccountBalance($transaction, 'delete');
        $this->updateBudgets($transaction);
    }

    private function updateBudgets(Transaction $transaction): void
    {
        // Only update budgets for expense transactions
        if ($transaction->type !== 'expense' || !$transaction->category_id) {
            return;
        }

        $this->updateBudgetsForCategory($transaction->user_id, $transaction->category_id);
    }

    private function updateBudgetsForCategory(int $userId, int $categoryId): void
    {
        // Get all active budgets for this category
        $budgets = Budget::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->get();

        foreach ($budgets as $budget) {
            $this->recalculateBudgetSpentAmount($budget);
        }
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

    private function updateAccountBalance(Transaction $transaction, string $action): void
    {
        if (!$transaction->account) {
            return;
        }

        if ($action === 'create') {
            // When creating: income increases balance, expense decreases balance
            if ($transaction->type === 'income') {
                $transaction->account->increment('balance', $transaction->amount);
            } else {
                $transaction->account->decrement('balance', $transaction->amount);
            }
        } elseif ($action === 'delete') {
            // When deleting: reverse the original transaction
            if ($transaction->type === 'income') {
                $transaction->account->decrement('balance', $transaction->amount);
            } else {
                $transaction->account->increment('balance', $transaction->amount);
            }
        }
    }

    private function updateAccountBalanceForUpdate(Transaction $transaction): void
    {
        // Get original values
        $originalAccountId = $transaction->getOriginal('account_id');
        $originalType = $transaction->getOriginal('type');
        $originalAmount = $transaction->getOriginal('amount');

        // If account, type, or amount changed, we need to update balances
        if ($originalAccountId !== $transaction->account_id ||
            $originalType !== $transaction->type ||
            $originalAmount !== $transaction->amount) {

            // Reverse the original transaction effect
            if ($originalAccountId) {
                $originalAccount = $transaction->user->accounts()->find($originalAccountId);
                if ($originalAccount) {
                    if ($originalType === 'income') {
                        $originalAccount->decrement('balance', $originalAmount);
                    } else {
                        $originalAccount->increment('balance', $originalAmount);
                    }
                }
            }

            // Apply the new transaction effect
            if ($transaction->account) {
                if ($transaction->type === 'income') {
                    $transaction->account->increment('balance', $transaction->amount);
                } else {
                    $transaction->account->decrement('balance', $transaction->amount);
                }
            }
        }
    }
}