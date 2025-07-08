<?php

use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedAccount = '';
    public $selectedCategory = '';
    public $selectedType = '';
    public $selectedFrequency = '';
    public $selectedStatus = '';

    public function mount(): void
    {
        //
    }

    public function with(): array
    {
        $query = auth()->user()->recurringTransactions()
            ->with(['account', 'category'])
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%'))
            ->when($this->selectedAccount, fn($q) => $q->where('account_id', $this->selectedAccount))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->selectedType, fn($q) => $q->where('type', $this->selectedType))
            ->when($this->selectedFrequency, fn($q) => $q->where('frequency', $this->selectedFrequency))
            ->when($this->selectedStatus, function($q) {
                if ($this->selectedStatus === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->selectedStatus === 'inactive') {
                    $q->where('is_active', false);
                } elseif ($this->selectedStatus === 'due') {
                    $q->where('is_active', true)->where('next_execution_date', '<=', now());
                }
            })
            ->orderBy('next_execution_date')
            ->orderBy('created_at', 'desc');

        return [
            'recurringTransactions' => $query->paginate(20),
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'categories' => auth()->user()->categories()->active()->orderBy('name')->get(),
            'totalRecurring' => auth()->user()->recurringTransactions()->where('is_active', true)->count(),
            'dueCount' => auth()->user()->recurringTransactions()->where('is_active', true)->where('next_execution_date', '<=', now())->count(),
            'monthlyIncome' => auth()->user()->recurringTransactions()->where('is_active', true)->where('type', 'income')->sum('amount'),
            'monthlyExpenses' => auth()->user()->recurringTransactions()->where('is_active', true)->where('type', 'expense')->sum('amount'),
        ];
    }

    public function deleteRecurring($recurringId): void
    {
        $recurring = auth()->user()->recurringTransactions()->findOrFail($recurringId);
        $recurring->delete();
        
        session()->flash('success', 'Recurring transaction deleted successfully.');
    }

    public function toggleStatus($recurringId): void
    {
        $recurring = auth()->user()->recurringTransactions()->findOrFail($recurringId);
        $recurring->update(['is_active' => !$recurring->is_active]);
        
        $status = $recurring->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Recurring transaction {$status} successfully.");
    }

    public function processRecurring($recurringId): void
    {
        $recurring = auth()->user()->recurringTransactions()->findOrFail($recurringId);
        
        // Create the transaction
        auth()->user()->transactions()->create([
            'account_id' => $recurring->account_id,
            'category_id' => $recurring->category_id,
            'type' => $recurring->type,
            'amount' => $recurring->amount,
            'description' => $recurring->description,
            'transaction_date' => now()->toDateString(),
        ]);

        // Update next occurrence date
        $recurring->updateNextOccurrence();
        
        session()->flash('success', 'Recurring transaction processed successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedAccount(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedFrequency(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedAccount', 'selectedCategory', 'selectedType', 'selectedFrequency', 'selectedStatus']);
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recurring Transactions</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your automatic income and expenses</p>
        </div>
        <flux:button href="/recurring/create" variant="primary" wire:navigate>
            <flux:icon.plus class="w-4 h-4 mr-2" />
            Add Recurring
        </flux:button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.arrow-path class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Active Recurring</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $totalRecurring }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-full dark:bg-yellow-900/20 mr-3">
                    <flux:icon.clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Due Now</p>
                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ $dueCount }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                    <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monthly Income</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">€{{ number_format($monthlyIncome, 2) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-full dark:bg-red-900/20 mr-3">
                    <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monthly Expenses</p>
                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">€{{ number_format($monthlyExpenses, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
            <div>
                <flux:input wire:model.live="search" placeholder="Search descriptions..." />
            </div>
            
            <div>
                <flux:select wire:model.live="selectedAccount" placeholder="All Accounts">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            
            <div>
                <flux:select wire:model.live="selectedCategory" placeholder="All Categories">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            
            <div>
                <flux:select wire:model.live="selectedType" placeholder="All Types">
                    <option value="">All Types</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </flux:select>
            </div>
            
            <div>
                <flux:select wire:model.live="selectedFrequency" placeholder="All Frequencies">
                    <option value="">All Frequencies</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </flux:select>
            </div>
            
            <div>
                <flux:select wire:model.live="selectedStatus" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="due">Due Now</option>
                </flux:select>
            </div>
        </div>
        
        <div class="mt-4">
            <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                Clear All Filters
            </flux:button>
        </div>
    </div>

    {{-- Recurring Transactions List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($recurringTransactions->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($recurringTransactions as $recurring)
                    @php
                        $isDue = $recurring->is_active && $recurring->next_execution_date && $recurring->next_execution_date->isPast();
                        $isOverdue = $recurring->is_active && $recurring->next_execution_date && $recurring->next_execution_date->diffInDays(now()) > 7;
                    @endphp
                    
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-2 rounded-full {{ $recurring->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                    @if($recurring->type === 'income')
                                        <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    @else
                                        <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    @endif
                                </div>
                                
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            {{ $recurring->description }}
                                        </h3>
                                        
                                        @if(!$recurring->is_active)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Inactive
                                            </span>
                                        @elseif($isDue)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                                Due
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $recurring->account->name }}</span>
                                        <span>•</span>
                                        <span>{{ $recurring->category->name }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ $recurring->frequency }}</span>
                                    </div>
                                    
                                    @if($recurring->next_execution_date)
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            Next: {{ $recurring->next_execution_date->format('M d, Y') }}
                                            @if($isDue)
                                                <span class="text-yellow-600 dark:text-yellow-400 font-medium">
                                                    ({{ $recurring->next_execution_date->diffForHumans() }})
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-lg font-semibold {{ $recurring->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $recurring->type === 'income' ? '+' : '-' }}€{{ number_format($recurring->amount, 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">per {{ $recurring->frequency }}</p>
                                </div>
                                
                                @if($isDue && $recurring->is_active)
                                    <flux:button 
                                        wire:click="processRecurring({{ $recurring->id }})"
                                        wire:confirm="Process this recurring transaction now?"
                                        variant="primary" 
                                        size="sm">
                                        Process Now
                                    </flux:button>
                                @endif
                                
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    
                                    <flux:menu>
                                        <flux:menu.item href="/recurring/{{ $recurring->id }}" icon="eye" wire:navigate>
                                            View Details
                                        </flux:menu.item>
                                        <flux:menu.item href="/recurring/{{ $recurring->id }}/edit" icon="pencil" wire:navigate>
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.item 
                                            wire:click="toggleStatus({{ $recurring->id }})"
                                            icon="{{ $recurring->is_active ? 'pause' : 'play' }}">
                                            {{ $recurring->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>
                                        @if($recurring->is_active)
                                            <flux:menu.item 
                                                wire:click="processRecurring({{ $recurring->id }})"
                                                wire:confirm="Process this recurring transaction now?"
                                                icon="play">
                                                Process Now
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            wire:click="deleteRecurring({{ $recurring->id }})"
                                            wire:confirm="Are you sure you want to delete this recurring transaction?"
                                            icon="trash" 
                                            variant="danger">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $recurringTransactions->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon.arrow-path class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No recurring transactions found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Automate your finances by setting up recurring income and expenses</p>
                <flux:button href="/recurring/create" variant="primary" wire:navigate>
                    <flux:icon.plus class="w-4 h-4 mr-2" />
                    Create Your First Recurring Transaction
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