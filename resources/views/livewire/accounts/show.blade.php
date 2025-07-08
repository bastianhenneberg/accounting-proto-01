<?php

use App\Models\Account;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Account $account;
    public $dateRange = '30'; // Last 30 days by default

    public function mount(): void
    {
        // Extract account ID from URL path
        $path = request()->path();
        preg_match('/accounts\/(\d+)/', $path, $matches);
        
        if (empty($matches[1])) {
            abort(404, 'Account ID not found in URL: ' . $path);
        }
        
        $accountId = $matches[1];
        
        $this->account = auth()->user()->accounts()
            ->findOrFail($accountId);
    }

    public function with(): array
    {
        $startDate = now()->subDays((int)$this->dateRange);
        
        $transactions = $this->account->transactions()
            ->with(['category', 'tags'])
            ->where('transaction_date', '>=', $startDate)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'totalIncome' => $this->account->transactions()
                ->where('type', 'income')
                ->where('transaction_date', '>=', $startDate)
                ->sum('amount'),
            'totalExpenses' => $this->account->transactions()
                ->where('type', 'expense')
                ->where('transaction_date', '>=', $startDate)
                ->sum('amount'),
            'transactionCount' => $this->account->transactions()
                ->where('transaction_date', '>=', $startDate)
                ->count(),
            'averageTransaction' => $this->account->transactions()
                ->where('transaction_date', '>=', $startDate)
                ->avg('amount') ?? 0,
        ];

        return [
            'transactions' => $transactions,
            'stats' => $stats,
        ];
    }

    public function deleteAccount(): void
    {
        if ($this->account->transactions()->count() > 0) {
            session()->flash('error', 'Cannot delete account with existing transactions.');
            return;
        }
        
        $this->account->delete();
        
        session()->flash('success', 'Account deleted successfully.');
        $this->redirect('/accounts', navigate: true);
    }

    public function updatedDateRange(): void
    {
        $this->resetPage();
    }

    public function getDateRangeOptions(): array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
            '365' => 'Last year',
            'all' => 'All time',
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <flux:button href="/accounts" variant="ghost" wire:navigate>
                    <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                    Back to Accounts
                </flux:button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $account->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $account->type) }} Account</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <flux:button href="/transactions/create?account={{ $account->id }}" variant="primary" wire:navigate>
                    <flux:icon.plus class="w-4 h-4 mr-2" />
                    Add Transaction
                </flux:button>
                <flux:dropdown>
                    <flux:button variant="outline" icon="ellipsis-vertical" />
                    
                    <flux:menu>
                        <flux:menu.item href="/accounts/{{ $account->id }}/edit" icon="pencil" wire:navigate>
                            Edit Account
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item 
                            wire:click="deleteAccount"
                            wire:confirm="Are you sure you want to delete this account? This action cannot be undone."
                            icon="trash" 
                            variant="danger">
                            Delete Account
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-4">
            {{-- Account Overview --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="p-4 rounded-full {{ $account->type === 'checking' ? 'bg-blue-100 dark:bg-blue-900/20' : ($account->type === 'savings' ? 'bg-green-100 dark:bg-green-900/20' : ($account->type === 'credit_card' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700')) }}">
                            @if($account->type === 'checking')
                                <flux:icon.credit-card class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            @elseif($account->type === 'savings')
                                <flux:icon.banknotes class="w-8 h-8 text-green-600 dark:text-green-400" />
                            @elseif($account->type === 'credit_card')
                                <flux:icon.credit-card class="w-8 h-8 text-red-600 dark:text-red-400" />
                            @elseif($account->type === 'cash')
                                <flux:icon.currency-euro class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
                            @else
                                <flux:icon.chart-bar class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Current Balance</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                €{{ number_format($account->balance, 2) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->currency }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Account Details</h3>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Type</span>
                                    <span class="text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $account->type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Currency</span>
                                    <span class="text-gray-900 dark:text-white">{{ $account->currency }}</span>
                                </div>
                                @if($account->bank_name)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Bank</span>
                                        <span class="text-gray-900 dark:text-white">{{ $account->bank_name }}</span>
                                    </div>
                                @endif
                                @if($account->account_number)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Account #</span>
                                        <span class="text-gray-900 dark:text-white font-mono">****{{ substr($account->account_number, -4) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $account->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Period Stats --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 mt-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Period Statistics</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total Income</span>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                    €{{ number_format($stats['totalIncome'], 2) }}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total Expenses</span>
                                <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                    €{{ number_format($stats['totalExpenses'], 2) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Net</span>
                                <span class="text-sm font-medium {{ ($stats['totalIncome'] - $stats['totalExpenses']) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    €{{ number_format($stats['totalIncome'] - $stats['totalExpenses'], 2) }}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Transactions</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $stats['transactionCount'] }}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Average</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    €{{ number_format($stats['averageTransaction'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h2>
                            
                            <div class="flex items-center space-x-3">
                                <flux:select wire:model.live="dateRange">
                                    @foreach($this->getDateRangeOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>
                                
                                <flux:button href="/transactions?account={{ $account->id }}" variant="outline" size="sm" wire:navigate>
                                    View All
                                </flux:button>
                            </div>
                        </div>
                    </div>

                    @if($transactions->count() > 0)
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($transactions as $transaction)
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="p-2 rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : ($transaction->type === 'expense' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-blue-100 dark:bg-blue-900/20') }}">
                                                @if($transaction->type === 'income')
                                                    <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                                                @elseif($transaction->type === 'expense')
                                                    <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                                                @else
                                                    <flux:icon.arrow-path class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                                @endif
                                            </div>
                                            
                                            <div>
                                                <h3 class="font-medium text-gray-900 dark:text-white">
                                                    {{ $transaction->description ?: $transaction->category->name }}
                                                </h3>
                                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span>{{ $transaction->category->name }}</span>
                                                    <span>•</span>
                                                    <span>{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                                    @if($transaction->transaction_date->diffInDays(now()) === 0)
                                                        <span>•</span>
                                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Today</span>
                                                    @endif
                                                </div>
                                                @if($transaction->tags->count() > 0)
                                                    <div class="flex items-center space-x-1 mt-1">
                                                        @foreach($transaction->tags as $tag)
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full" 
                                                                  style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                                                {{ $tag->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4">
                                            <div class="text-right">
                                                <p class="text-lg font-semibold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $transaction->type === 'income' ? '+' : '-' }}€{{ number_format($transaction->amount, 2) }}
                                                </p>
                                            </div>
                                            
                                            <flux:button href="/transactions/{{ $transaction->id }}" variant="ghost" size="sm" wire:navigate>
                                                <flux:icon.eye class="w-4 h-4" />
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <flux:icon.banknotes class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No transactions found</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">
                                @if($dateRange !== 'all')
                                    No transactions in the selected time period.
                                @else
                                    This account has no transactions yet.
                                @endif
                            </p>
                            <flux:button href="/transactions/create?account={{ $account->id }}" variant="primary" wire:navigate>
                                <flux:icon.plus class="w-4 h-4 mr-2" />
                                Add First Transaction
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif
</div>