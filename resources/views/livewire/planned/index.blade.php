<?php

use App\Models\PlannedTransaction;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedAccount = '';
    public $selectedCategory = '';
    public $selectedType = '';
    public $selectedStatus = '';
    public $dateRange = '30';

    public function with(): array
    {
        $query = auth()->user()->plannedTransactions()
            ->with(['account', 'category'])
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%'))
            ->when($this->selectedAccount, fn($q) => $q->where('account_id', $this->selectedAccount))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->selectedType, fn($q) => $q->where('type', $this->selectedType))
            ->when($this->selectedStatus, fn($q) => $q->where('status', $this->selectedStatus))
            ->when($this->dateRange !== 'all', function($q) {
                if ($this->dateRange === 'overdue') {
                    $q->where('planned_date', '<', now()->toDateString())
                        ->whereNotIn('status', ['converted', 'cancelled']);
                } elseif ($this->dateRange === 'today') {
                    $q->where('planned_date', now()->toDateString());
                } else {
                    $q->where('planned_date', '<=', now()->addDays((int)$this->dateRange)->toDateString());
                }
            })
            ->orderBy('planned_date')
            ->orderBy('created_at', 'desc');

        return [
            'plannedTransactions' => $query->paginate(20),
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'categories' => auth()->user()->categories()->active()->orderBy('name')->get(),
            'totalPlanned' => auth()->user()->plannedTransactions()->whereIn('status', ['pending', 'confirmed'])->count(),
            'dueToday' => auth()->user()->plannedTransactions()->dueToday()->count(),
            'totalUpcoming' => auth()->user()->plannedTransactions()->upcoming(30)->count(),
            'projectedIncome' => auth()->user()->plannedTransactions()->upcoming(30)->where('type', 'income')->sum('amount'),
            'projectedExpenses' => auth()->user()->plannedTransactions()->upcoming(30)->where('type', 'expense')->sum('amount'),
        ];
    }

    public function deletePlanned($plannedId): void
    {
        $planned = auth()->user()->plannedTransactions()->findOrFail($plannedId);
        $planned->delete();

        session()->flash('success', __('Planned transaction deleted successfully.'));
    }

    public function convertToTransaction($plannedId): void
    {
        $planned = auth()->user()->plannedTransactions()->findOrFail($plannedId);
        $transaction = $planned->convertToTransaction();

        session()->flash('success', __('Planned transaction converted successfully.'));
    }

    public function confirmPlanned($plannedId): void
    {
        $planned = auth()->user()->plannedTransactions()->findOrFail($plannedId);
        $planned->update(['status' => 'confirmed']);

        session()->flash('success', __('Planned transaction confirmed.'));
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedAccount', 'selectedCategory', 'selectedType', 'selectedStatus']);
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedSelectedAccount(): void { $this->resetPage(); }
    public function updatedSelectedCategory(): void { $this->resetPage(); }
    public function updatedSelectedType(): void { $this->resetPage(); }
    public function updatedSelectedStatus(): void { $this->resetPage(); }
    public function updatedDateRange(): void { $this->resetPage(); }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Planned Transactions') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your upcoming financial transactions') }}</p>
        </div>
        <flux:button href="/planned/create" variant="primary" icon="plus" wire:navigate>
            {{ __('Add Planned Transaction') }}
        </flux:button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.calendar class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Planned') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $totalPlanned }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-full dark:bg-yellow-900/20 mr-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Due Today') }}</p>
                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ $dueToday }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                    <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Projected Income') }}</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">€{{ number_format($projectedIncome, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-full dark:bg-red-900/20 mr-3">
                    <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Projected Expenses') }}</p>
                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">€{{ number_format($projectedExpenses, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
            <div>
                <flux:input wire:model.live="search" :placeholder="__('Search descriptions...')" />
            </div>

            <div>
                <flux:select wire:model.live="selectedAccount" :placeholder="__('All Accounts')">
                    <option value="">{{ __('All Accounts') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="selectedCategory" :placeholder="__('All Categories')">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="selectedType" :placeholder="__('All Types')">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="income">{{ __('Income') }}</option>
                    <option value="expense">{{ __('Expense') }}</option>
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="selectedStatus" :placeholder="__('All Statuses')">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="confirmed">{{ __('Confirmed') }}</option>
                    <option value="converted">{{ __('Converted') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="dateRange" :placeholder="__('Time Range')">
                    <option value="overdue">{{ __('Overdue') }}</option>
                    <option value="today">{{ __('Due Today') }}</option>
                    <option value="7">{{ __('Next 7 days') }}</option>
                    <option value="30">{{ __('Next 30 days') }}</option>
                    <option value="90">{{ __('Next 90 days') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </flux:select>
            </div>

            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    {{ __('Clear Filters') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Planned Transactions List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($plannedTransactions->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($plannedTransactions as $planned)
                    @php
                        $isOverdue = $planned->isOverdue();
                        $isDueToday = $planned->isDueToday();
                        $daysUntil = $planned->days_until_due;
                    @endphp

                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 rounded-full {{ $planned->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                    @if($planned->type === 'income')
                                        <flux:icon.arrow-up class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    @else
                                        <flux:icon.arrow-down class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    @endif
                                </div>

                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ $planned->description }}
                                    </h3>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $planned->account->name }}</span>
                                        <span>•</span>
                                        <span>{{ $planned->category->name }}</span>
                                        <span>•</span>
                                        <span>{{ $planned->planned_date->format('M d, Y') }}</span>
                                        @if($isOverdue)
                                            <span>•</span>
                                            <span class="text-red-600 dark:text-red-400 font-medium">{{ __('Overdue') }}</span>
                                        @elseif($isDueToday)
                                            <span>•</span>
                                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('Due Today') }}</span>
                                        @elseif($daysUntil > 0)
                                            <span>•</span>
                                            <span>{{ $daysUntil }} {{ __('days left') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                            {{ $planned->status === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                            {{ $planned->status === 'confirmed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : '' }}
                                            {{ $planned->status === 'converted' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : '' }}
                                            {{ $planned->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : '' }}">
                                            {{ __(ucfirst($planned->status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-lg font-semibold {{ $planned->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $planned->type === 'income' ? '+' : '-' }}€{{ number_format($planned->amount, 2) }}
                                    </p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    @if($planned->status === 'pending' || $planned->status === 'confirmed')
                                        <flux:button
                                            wire:click="convertToTransaction({{ $planned->id }})"
                                            wire:confirm="{{ __('Convert this planned transaction to an actual transaction?') }}"
                                            variant="primary"
                                            size="sm">
                                            {{ __('Convert Now') }}
                                        </flux:button>
                                    @endif

                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                        <flux:menu>
                                            @if($planned->status === 'pending')
                                                <flux:menu.item
                                                    wire:click="confirmPlanned({{ $planned->id }})"
                                                    icon="check">
                                                    {{ __('Confirm') }}
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.item href="/planned/{{ $planned->id }}/edit" icon="pencil" wire:navigate>
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="deletePlanned({{ $planned->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this planned transaction?') }}"
                                                icon="trash"
                                                variant="danger">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($plannedTransactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $plannedTransactions->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <flux:icon.calendar class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('No planned transactions found') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('Start planning your finances by adding upcoming transactions') }}</p>
                <flux:button href="/planned/create" variant="primary" icon="plus" wire:navigate>
                    {{ __('Create Your First Planned Transaction') }}
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