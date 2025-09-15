<?php

use App\Models\Budget;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedCategory = '';
    public $selectedStatus = '';

    public function mount(): void
    {
        //
    }

    public function with(): array
    {
        $query = auth()->user()->budgets()
            ->with(['category'])
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->selectedStatus, function($q) {
                if ($this->selectedStatus === 'active') {
                    $q->active();
                } elseif ($this->selectedStatus === 'over_budget') {
                    $q->whereRaw('spent_amount > amount');
                }
            })
            ->orderBy('created_at', 'desc');

        return [
            'budgets' => $query->paginate(20),
            'categories' => auth()->user()->categories()->active()->orderBy('name')->get(),
            'totalBudgets' => auth()->user()->budgets()->active()->count(),
            'overBudgetCount' => auth()->user()->budgets()->active()->whereRaw('spent_amount > amount')->count(),
            'totalBudgetAmount' => auth()->user()->budgets()->active()->sum('amount'),
            'totalSpentAmount' => auth()->user()->budgets()->active()->sum('spent_amount'),
        ];
    }

    public function deleteBudget($budgetId): void
    {
        $budget = auth()->user()->budgets()->findOrFail($budgetId);
        $budget->delete();
        
        session()->flash('success', __('Budget deleted successfully.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedCategory', 'selectedStatus']);
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Budgets') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Track your spending against budget limits') }}</p>
        </div>
        <flux:button href="/budgets/create" variant="primary" icon="plus" wire:navigate>
            {{ __('Create Budget') }}
        </flux:button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.chart-bar class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Budgets') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $totalBudgets }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-full dark:bg-red-900/20 mr-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Over Budget') }}</p>
                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">{{ $overBudgetCount }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                    <flux:icon.currency-euro class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Budget') }}</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">€{{ number_format($totalBudgetAmount, 2) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-full dark:bg-yellow-900/20 mr-3">
                    <flux:icon.banknotes class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Spent') }}</p>
                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">€{{ number_format($totalSpentAmount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <flux:input wire:model.live="search" placeholder="{{ __('Search budget names...') }}" />
            </div>
            
            <div>
                <flux:select wire:model.live="selectedCategory" placeholder="{{ __('All Categories') }}">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            
            <div>
                <flux:select wire:model.live="selectedStatus" placeholder="{{ __('All Statuses') }}">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="over_budget">{{ __('Over Budget') }}</option>
                </flux:select>
            </div>
            
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    {{ __('Clear Filters') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Budgets List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($budgets->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($budgets as $budget)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 rounded-full {{ $budget->percentage_used > 100 ? 'bg-red-100 dark:bg-red-900/20' : ($budget->percentage_used > 80 ? 'bg-yellow-100 dark:bg-yellow-900/20' : 'bg-green-100 dark:bg-green-900/20') }}">
                                    <flux:icon.chart-bar class="w-6 h-6 {{ $budget->percentage_used > 100 ? 'text-red-600 dark:text-red-400' : ($budget->percentage_used > 80 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}" />
                                </div>
                                
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ $budget->name }}
                                    </h3>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $budget->category->name }}</span>
                                        <span>•</span>
                                        <span>{{ $budget->period }}</span>
                                        @if($budget->start_date && $budget->end_date)
                                            <span>•</span>
                                            <span>{{ $budget->start_date->format('M d') }} - {{ $budget->end_date->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                €{{ number_format($budget->spent_amount, 2) }} / €{{ number_format($budget->amount, 2) }}
                                            </span>
                                            <span class="font-medium {{ $budget->percentage_used > 100 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ number_format($budget->percentage_used, 1) }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1 dark:bg-gray-700">
                                            <div class="h-2 rounded-full {{ $budget->percentage_used > 100 ? 'bg-red-600' : ($budget->percentage_used > 80 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                                                 style="width: {{ min($budget->percentage_used, 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Remaining') }}</p>
                                    <p class="font-medium {{ $budget->remaining_amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        €{{ number_format($budget->remaining_amount, 2) }}
                                    </p>
                                </div>
                                
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    
                                    <flux:menu>
                                        <flux:menu.item href="/budgets/{{ $budget->id }}" icon="eye" wire:navigate>
                                            {{ __('View Details') }}
                                        </flux:menu.item>
                                        <flux:menu.item href="/budgets/{{ $budget->id }}/edit" icon="pencil" wire:navigate>
                                            {{ __('Edit Budget') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            wire:click="deleteBudget({{ $budget->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this budget?') }}"
                                            icon="trash" 
                                            variant="danger">
                                            {{ __('Delete Budget') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($budgets->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $budgets->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <flux:icon.chart-bar class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('No budgets found') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('Start managing your finances by creating budget limits') }}</p>
                <flux:button href="/budgets/create" variant="primary" icon="plus" wire:navigate>
                    {{ __('Create Your First Budget') }}
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