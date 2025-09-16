<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use Livewire\Volt\Component;

new class extends Component {
    public $totalBalance = 0;
    public $monthlyIncome = 0;
    public $monthlyExpenses = 0;
    public $monthlyNet = 0;
    public $projectedTotalBalance = 0;
    public $recentTransactions = [];
    public $budgets = [];
    public $goals = [];
    public $upcomingPlanned = [];

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $user = auth()->user();
        
        // Calculate total balance across all accounts
        $this->totalBalance = $user->accounts()
            ->where('is_active', true)
            ->sum('balance');

        // Calculate projected total balance including planned transactions
        $activeAccounts = $user->accounts()->where('is_active', true)->get();
        $this->projectedTotalBalance = $activeAccounts->sum('projected_balance');

        // Calculate this month's income and expenses
        $this->monthlyIncome = $user->transactions()
            ->where('type', 'income')
            ->thisMonth()
            ->sum('amount');

        $this->monthlyExpenses = $user->transactions()
            ->where('type', 'expense')
            ->thisMonth()
            ->sum('amount');

        // Get recent transactions
        $this->recentTransactions = $user->transactions()
            ->with(['account', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get active budgets
        $this->budgets = $user->budgets()
            ->with('category')
            ->active()
            ->current()
            ->limit(3)
            ->get();

        // Get active goals
        $this->goals = $user->goals()
            ->active()
            ->orderBy('target_date')
            ->limit(3)
            ->get();

        // Get upcoming planned transactions (show all, but mark confirmed ones)
        $this->upcomingPlanned = $user->plannedTransactions()
            ->with(['account', 'category'])
            ->where('planned_date', '>=', now()->toDateString())
            ->where('planned_date', '<=', now()->addDays(7)->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('planned_date')
            ->limit(5)
            ->get();

        // Calculate monthly net
        $this->monthlyNet = $this->monthlyIncome - $this->monthlyExpenses;
    }

}; ?>

<div>
    <div class="space-y-6">
        {{-- Overview Cards --}}
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {{-- Total Balance --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Total Balance') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            €{{ number_format($totalBalance, 2) }}
                        </p>
                        @if($projectedTotalBalance != $totalBalance)
                            <p class="text-sm {{ $projectedTotalBalance >= $totalBalance ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ __('Projected') }}: €{{ number_format($projectedTotalBalance, 2) }}
                            </p>
                        @endif
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full dark:bg-blue-900/20">
                        <flux:icon.wallet class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            {{-- Monthly Income --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Income</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            €{{ number_format($monthlyIncome, 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full dark:bg-green-900/20">
                        <flux:icon.arrow-up class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            {{-- Monthly Expenses --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Expenses</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                            €{{ number_format($monthlyExpenses, 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full dark:bg-red-900/20">
                        <flux:icon.arrow-down class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>

            {{-- Net Income --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Net</p>
                        <p class="text-2xl font-bold {{ $monthlyNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            €{{ number_format($monthlyNet, 2) }}
                        </p>
                    </div>
                    <div class="p-3 {{ $monthlyNet >= 0 ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }} rounded-full">
                        <flux:icon.calculator class="w-6 h-6 {{ $monthlyNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Recent Transactions --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                    <flux:link href="/transactions" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        {{ __('View all') }}
                    </flux:link>
                </div>
                <div class="space-y-3">
                    @forelse($recentTransactions as $transaction)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                    @if($transaction->type === 'income')
                                        <flux:icon.plus class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    @else
                                        <flux:icon.minus class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->description ?: $transaction->category->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->account->name }} • {{ $transaction->transaction_date->format('M d') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->type === 'income' ? '+' : '-' }}€{{ number_format($transaction->amount, 2) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No transactions yet</p>
                    @endforelse
                </div>
            </div>

            {{-- Upcoming Planned Transactions --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Upcoming Planned') }}</h3>
                    <flux:link href="/planned" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        {{ __('View all') }}
                    </flux:link>
                </div>
                <div class="space-y-3">
                    @forelse($upcomingPlanned as $planned)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full {{ $planned->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                    @if($planned->type === 'income')
                                        <flux:icon.plus class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    @else
                                        <flux:icon.minus class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $planned->description }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $planned->account->name }} • {{ $planned->planned_date->format('M d') }}
                                        @if($planned->status === 'confirmed')
                                            • <span class="text-blue-600 dark:text-blue-400 font-medium">✓ {{ __('Confirmed') }}</span>
                                        @else
                                            • <span class="text-gray-500 dark:text-gray-400">{{ __('Pending') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium {{ $planned->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $planned->type === 'income' ? '+' : '-' }}€{{ number_format($planned->amount, 2) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">{{ __('No upcoming planned transactions') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Budgets & Goals --}}
            <div class="space-y-6">
                {{-- Active Budgets --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Budgets</h3>
                        <flux:link href="/budgets" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View all') }}
                        </flux:link>
                    </div>
                    <div class="space-y-3">
                        @forelse($budgets as $budget)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $budget->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        €{{ number_format($budget->spent_amount, 0) }} / €{{ number_format($budget->amount, 0) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                    <div class="h-2 rounded-full {{ $budget->percentage_used > 100 ? 'bg-red-600' : ($budget->percentage_used > 80 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                                         style="width: {{ min($budget->percentage_used, 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">{{ __('No active budgets') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Goals --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Goals') }}</h3>
                        <flux:link href="/goals" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View all') }}
                        </flux:link>
                    </div>
                    <div class="space-y-3">
                        @forelse($goals as $goal)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $goal->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        €{{ number_format($goal->current_amount, 0) }} / €{{ number_format($goal->target_amount, 0) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($goal->percentage_completed, 100) }}%"></div>
                                </div>
                                @if($goal->target_date)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Target') }}: {{ $goal->target_date->format('M d, Y') }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">{{ __('No active goals') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>