<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\PlannedTransaction;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialCalendarService
{
    public function getMonthData(Carbon $month, int $userId): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        // Get actual transactions for the month
        $actualTransactions = User::find($userId)->transactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['account', 'category'])
            ->get();

        // Get planned transactions for the month
        $plannedTransactions = PlannedTransaction::where('user_id', $userId)
            ->whereBetween('planned_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['account', 'category'])
            ->get();

        // Get recurring transactions and calculate occurrences
        $recurringOccurrences = $this->getRecurringOccurrences($userId, $startDate, $endDate);

        // Format calendar data
        $calendarData = $this->formatCalendarData($actualTransactions, $plannedTransactions, $recurringOccurrences, $startDate, $endDate);

        return [
            'month' => $month->format('Y-m'),
            'month_name' => $month->format('F Y'),
            'days' => $calendarData['days'],
            'monthly_summary' => $calendarData['summary'],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function getRecurringOccurrences(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        $recurring = RecurringTransaction::where('user_id', $userId)
            ->where('is_active', true)
            ->where(function($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate);
            })
            ->with(['account', 'category'])
            ->get();

        $occurrences = collect();

        foreach ($recurring as $transaction) {
            $occurrenceDate = Carbon::parse($transaction->next_execution_date);

            // Generate occurrences within the date range
            while ($occurrenceDate->lte($endDate) && $occurrences->count() < 200) {
                if ($occurrenceDate->gte($startDate)) {
                    $occurrences->push([
                        'date' => $occurrenceDate->toDateString(),
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type,
                        'account' => $transaction->account,
                        'category' => $transaction->category,
                        'source' => 'recurring',
                        'recurring_id' => $transaction->id,
                    ]);
                }

                // Calculate next occurrence based on frequency
                switch ($transaction->frequency) {
                    case 'daily':
                        $occurrenceDate->addDay();
                        break;
                    case 'weekly':
                        $occurrenceDate->addWeek();
                        break;
                    case 'monthly':
                        $occurrenceDate->addMonth();
                        break;
                    case 'quarterly':
                        $occurrenceDate->addMonths(3);
                        break;
                    case 'yearly':
                        $occurrenceDate->addYear();
                        break;
                    default:
                        break 2;
                }
            }
        }

        return $occurrences;
    }

    private function formatCalendarData(Collection $actualTransactions, Collection $plannedTransactions, Collection $recurringOccurrences, Carbon $startDate, Carbon $endDate): array
    {
        $days = [];
        $summary = [
            'actual_income' => 0,
            'actual_expenses' => 0,
            'recurring_income' => 0,
            'recurring_expenses' => 0,
            'planned_income' => 0,
            'planned_expenses' => 0,
            'total_transactions' => 0,
        ];

        // Initialize all days in the month
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateKey = $current->toDateString();
            $days[$dateKey] = [
                'date' => $current->copy(),
                'actual' => [],
                'recurring' => [],
                'planned' => [],
                'total_impact' => 0,
                'transaction_count' => 0,
                'has_transactions' => false,
            ];
            $current->addDay();
        }

        // Add actual transactions
        foreach ($actualTransactions as $actual) {
            $dateKey = $actual->transaction_date->toDateString();
            if (isset($days[$dateKey])) {
                $impact = $actual->type === 'income' ? $actual->amount : -$actual->amount;

                $days[$dateKey]['actual'][] = [
                    'id' => $actual->id,
                    'description' => $actual->description,
                    'amount' => $actual->amount,
                    'type' => $actual->type,
                    'account' => $actual->account,
                    'category' => $actual->category,
                    'impact' => $impact,
                ];

                $days[$dateKey]['total_impact'] += $impact;
                $days[$dateKey]['transaction_count']++;
                $days[$dateKey]['has_transactions'] = true;

                // Update summary
                if ($actual->type === 'income') {
                    $summary['actual_income'] += $actual->amount;
                } else {
                    $summary['actual_expenses'] += $actual->amount;
                }
                $summary['total_transactions']++;
            }
        }

        // Add planned transactions
        foreach ($plannedTransactions as $planned) {
            $dateKey = $planned->planned_date->toDateString();
            if (isset($days[$dateKey])) {
                $impact = $planned->type === 'income' ? $planned->amount : -$planned->amount;

                $days[$dateKey]['planned'][] = [
                    'id' => $planned->id,
                    'description' => $planned->description,
                    'amount' => $planned->amount,
                    'type' => $planned->type,
                    'status' => $planned->status,
                    'account' => $planned->account,
                    'category' => $planned->category,
                    'impact' => $impact,
                ];

                $days[$dateKey]['total_impact'] += $impact;
                $days[$dateKey]['transaction_count']++;
                $days[$dateKey]['has_transactions'] = true;

                // Update summary
                if ($planned->type === 'income') {
                    $summary['planned_income'] += $planned->amount;
                } else {
                    $summary['planned_expenses'] += $planned->amount;
                }
                $summary['total_transactions']++;
            }
        }

        // Add recurring occurrences
        foreach ($recurringOccurrences as $recurring) {
            $dateKey = $recurring['date'];
            if (isset($days[$dateKey])) {
                $impact = $recurring['type'] === 'income' ? $recurring['amount'] : -$recurring['amount'];

                $days[$dateKey]['recurring'][] = [
                    'id' => $recurring['recurring_id'],
                    'description' => $recurring['description'],
                    'amount' => $recurring['amount'],
                    'type' => $recurring['type'],
                    'account' => $recurring['account'],
                    'category' => $recurring['category'],
                    'impact' => $impact,
                ];

                $days[$dateKey]['total_impact'] += $impact;
                $days[$dateKey]['transaction_count']++;
                $days[$dateKey]['has_transactions'] = true;

                // Update summary
                if ($recurring['type'] === 'income') {
                    $summary['recurring_income'] += $recurring['amount'];
                } else {
                    $summary['recurring_expenses'] += $recurring['amount'];
                }
                $summary['total_transactions']++;
            }
        }

        // Calculate net projected (actual + future)
        $summary['net_projected'] = $summary['actual_income'] + $summary['recurring_income'] + $summary['planned_income'] - $summary['actual_expenses'] - $summary['recurring_expenses'] - $summary['planned_expenses'];

        return ['days' => $days, 'summary' => $summary];
    }

    public function getDayDetail(Carbon $date, int $userId): array
    {
        $monthData = $this->getMonthData($date, $userId);
        $dateKey = $date->toDateString();

        return $monthData['days'][$dateKey] ?? [
            'date' => $date,
            'recurring' => [],
            'planned' => [],
            'total_impact' => 0,
            'transaction_count' => 0,
            'has_transactions' => false,
        ];
    }
}
