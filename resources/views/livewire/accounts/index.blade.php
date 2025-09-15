<?php

use App\Models\Account;
use Livewire\Volt\Component;

new class extends Component {
    public $accounts = [];
    public $totalBalance = 0;

    public function mount(): void
    {
        $this->loadAccounts();
    }

    public function loadAccounts(): void
    {
        $this->accounts = auth()->user()->accounts()->orderBy('name')->get();
        $this->totalBalance = $this->accounts->sum('balance');
    }

    public function deleteAccount($accountId): void
    {
        $account = auth()->user()->accounts()->findOrFail($accountId);
        
        if ($account->transactions()->count() > 0) {
            session()->flash('error', 'Cannot delete account with existing transactions.');
            return;
        }
        
        $account->delete();
        $this->loadAccounts();
        session()->flash('success', 'Account deleted successfully.');
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Accounts') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your financial accounts') }}</p>
        </div>
        <flux:button href="/accounts/create" variant="primary" icon="plus" wire:navigate>
            {{ __('Add Account') }}
        </flux:button>
    </div>

    {{-- Summary Card --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium opacity-90">{{ __('Total Balance') }}</h3>
                <p class="text-3xl font-bold">€{{ number_format($totalBalance, 2) }}</p>
                <p class="text-sm opacity-75">Across {{ count($accounts) }} account{{ count($accounts) !== 1 ? 's' : '' }}</p>
            </div>
            <div class="p-4 bg-white/20 rounded-full">
                <flux:icon.banknotes class="w-8 h-8" />
            </div>
        </div>
    </div>

    {{-- Accounts List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($accounts->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($accounts as $account)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 rounded-full {{ $account->type === 'checking' ? 'bg-blue-100 dark:bg-blue-900/20' : ($account->type === 'savings' ? 'bg-green-100 dark:bg-green-900/20' : ($account->type === 'credit_card' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700')) }}">
                                    @if($account->type === 'checking')
                                        <flux:icon.credit-card class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    @elseif($account->type === 'savings')
                                        <flux:icon.banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    @elseif($account->type === 'credit_card')
                                        <flux:icon.credit-card class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    @elseif($account->type === 'cash')
                                        <flux:icon.currency-euro class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                                    @else
                                        <flux:icon.chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $account->name }}</h3>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="capitalize">{{ str_replace('_', ' ', $account->type) }}</span>
                                        @if($account->bank_name)
                                            <span>{{ $account->bank_name }}</span>
                                        @endif
                                        @if($account->account_number)
                                            <span>****{{ substr($account->account_number, -4) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        €{{ number_format($account->balance, 2) }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->currency }}</p>
                                </div>
                                
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    
                                    <flux:menu>
                                        <flux:menu.item href="/accounts/{{ $account->id }}" icon="eye" wire:navigate>
                                            View Details
                                        </flux:menu.item>
                                        <flux:menu.item href="/transactions?account={{ $account->id }}" icon="list-bullet" wire:navigate>
                                            View Transactions
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            wire:click="deleteAccount({{ $account->id }})"
                                            wire:confirm="Are you sure you want to delete this account? This action cannot be undone."
                                            icon="trash" 
                                            variant="danger">
                                            Delete Account
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon.credit-card class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No accounts yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first financial account</p>
                <flux:button href="/accounts/create" variant="primary" wire:navigate>
                    <flux:icon.plus class="w-4 h-4 mr-2" />
                    Add Your First Account
                </flux:button>
            </div>
        @endif
    </div>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif
</div>