<?php

use App\Models\Transaction;
use Livewire\Volt\Component;

new class extends Component {
    public Transaction $transaction;

    public function mount(): void
    {
        // Debug: Let's see what we have
        $url = request()->url();
        $path = request()->path();
        
        // Extract transaction ID from URL path
        preg_match('/transactions\/(\d+)/', $path, $matches);
        
        if (empty($matches[1])) {
            abort(404, 'Transaction ID not found in URL: ' . $path);
        }
        
        $transactionId = $matches[1];
        
        $this->transaction = auth()->user()->transactions()
            ->with(['account', 'category', 'tags'])
            ->findOrFail($transactionId);
    }

    public function deleteTransaction(): void
    {
        $this->transaction->delete();
        
        session()->flash('success', 'Transaction deleted successfully.');
        $this->redirect('/transactions', navigate: true);
    }
}; ?>

<div class="p-6">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <flux:button href="/transactions" variant="ghost" wire:navigate>
                    <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                    Back to Transactions
                </flux:button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transaction Details</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $transaction->transaction_date->format('F d, Y') }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <flux:button href="/transactions/{{ $transaction->id }}/edit" variant="outline" wire:navigate>
                    <flux:icon.pencil class="w-4 h-4 mr-2" />
                    Edit
                </flux:button>
                <flux:button 
                    wire:click="deleteTransaction"
                    wire:confirm="{{ __('Are you sure you want to delete this transaction? This action cannot be undone.') }}"
                    variant="danger">
                    <flux:icon.trash class="w-4 h-4 mr-2" />
                    Delete
                </flux:button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Main Transaction Card --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="flex items-start space-x-4">
                        <div class="p-4 rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : ($transaction->type === 'expense' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-blue-100 dark:bg-blue-900/20') }}">
                            @if($transaction->type === 'income')
                                <flux:icon.arrow-up class="w-8 h-8 text-green-600 dark:text-green-400" />
                            @elseif($transaction->type === 'expense')
                                <flux:icon.arrow-down class="w-8 h-8 text-red-600 dark:text-red-400" />
                            @else
                                <flux:icon.arrow-path class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            @endif
                        </div>
                        
                        <div class="flex-1">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $transaction->description ?: $transaction->category->name }}
                            </h2>
                            
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="capitalize font-medium {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : ($transaction->type === 'expense' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400') }}">
                                        {{ $transaction->type }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ $transaction->category->name }}</span>
                                    <span>•</span>
                                    <span>{{ $transaction->account->name }}</span>
                                </div>
                                
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <flux:icon.calendar class="w-4 h-4" />
                                    <span>{{ $transaction->transaction_date->format('l, F d, Y') }}</span>
                                    <span>•</span>
                                    <flux:icon.clock class="w-4 h-4" />
                                    <span>{{ $transaction->created_at->format('g:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-3xl font-bold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}€{{ number_format($transaction->amount, 2) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->account->currency }}</p>
                        </div>
                    </div>

                    {{-- Tags --}}
                    @if($transaction->tags->count() > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($transaction->tags as $tag)
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full" 
                                          style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                        <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($transaction->notes)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Notes</h3>
                            <div class="bg-gray-50 rounded-lg p-4 dark:bg-gray-700">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $transaction->notes }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Receipt --}}
                    @if($transaction->receipt_path)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Receipt</h3>
                            <div class="bg-gray-50 rounded-lg p-4 dark:bg-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900/20">
                                            <flux:icon.document class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->receipt_filename }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ round($transaction->receipt_size / 1024, 2) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ \Storage::disk('public')->url($transaction->receipt_path) }}" 
                                           target="_blank" 
                                           class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </a>
                                        <a href="{{ \Storage::disk('public')->url($transaction->receipt_path) }}" 
                                           download="{{ $transaction->receipt_filename }}"
                                           class="p-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors">
                                            <flux:icon.arrow-down-tray class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Account Information --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account Information</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 rounded-full {{ $transaction->account->type === 'checking' ? 'bg-blue-100 dark:bg-blue-900/20' : ($transaction->account->type === 'savings' ? 'bg-green-100 dark:bg-green-900/20' : ($transaction->account->type === 'credit_card' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700')) }}">
                                @if($transaction->account->type === 'checking')
                                    <flux:icon.credit-card class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                @elseif($transaction->account->type === 'savings')
                                    <flux:icon.banknotes class="w-4 h-4 text-green-600 dark:text-green-400" />
                                @elseif($transaction->account->type === 'credit_card')
                                    <flux:icon.credit-card class="w-4 h-4 text-red-600 dark:text-red-400" />
                                @elseif($transaction->account->type === 'cash')
                                    <flux:icon.currency-euro class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                @else
                                    <flux:icon.chart-bar class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $transaction->account->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $transaction->account->type) }}</p>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Current Balance</span>
                                <span class="font-medium text-gray-900 dark:text-white">€{{ number_format($transaction->account->balance, 2) }}</span>
                            </div>
                            @if($transaction->account->bank_name)
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Bank</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $transaction->account->bank_name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Category Information --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Category</h3>
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $transaction->category->color }}"></div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $transaction->category->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $transaction->category->type }}</p>
                        </div>
                    </div>
                    
                    @if($transaction->category->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">{{ $transaction->category->description }}</p>
                    @endif
                </div>

                {{-- Transaction Metadata --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Transaction Details</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Transaction ID</span>
                            <span class="font-mono text-gray-700 dark:text-gray-300">#{{ $transaction->id }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Created</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $transaction->created_at->format('M d, Y g:i A') }}</span>
                        </div>
                        @if($transaction->updated_at != $transaction->created_at)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Last Modified</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $transaction->updated_at->format('M d, Y g:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <flux:button href="/transactions?account={{ $transaction->account->id }}" variant="outline" size="sm" class="w-full" wire:navigate>
                            <flux:icon.list-bullet class="w-4 h-4 mr-2" />
                            View Account Transactions
                        </flux:button>
                        <flux:button href="/transactions?category={{ $transaction->category->id }}" variant="outline" size="sm" class="w-full" wire:navigate>
                            <flux:icon.folder class="w-4 h-4 mr-2" />
                            View Category Transactions
                        </flux:button>
                        <flux:button href="/transactions/create?duplicate={{ $transaction->id }}" variant="outline" size="sm" class="w-full" wire:navigate>
                            <flux:icon.document-duplicate class="w-4 h-4 mr-2" />
                            Duplicate Transaction
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>