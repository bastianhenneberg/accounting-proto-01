<?php

use App\Models\Goal;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedStatus = '';

    public function mount(): void
    {
        //
    }

    public function with(): array
    {
        $query = auth()->user()->goals()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->selectedStatus, function($q) {
                if ($this->selectedStatus === 'active') {
                    $q->active();
                } elseif ($this->selectedStatus === 'completed') {
                    $q->where('current_amount', '>=', 'target_amount');
                } elseif ($this->selectedStatus === 'overdue') {
                    $q->where('target_date', '<', now())->where('current_amount', '<', 'target_amount');
                }
            })
            ->orderBy('target_date')
            ->orderBy('created_at', 'desc');

        return [
            'goals' => $query->paginate(20),
            'totalGoals' => auth()->user()->goals()->active()->count(),
            'completedGoals' => auth()->user()->goals()->active()->where('current_amount', '>=', 'target_amount')->count(),
            'totalTargetAmount' => auth()->user()->goals()->active()->sum('target_amount'),
            'totalCurrentAmount' => auth()->user()->goals()->active()->sum('current_amount'),
        ];
    }

    public function deleteGoal($goalId): void
    {
        $goal = auth()->user()->goals()->findOrFail($goalId);
        $goal->delete();
        
        session()->flash('success', 'Goal deleted successfully.');
    }

    public function addContribution($goalId, $amount): void
    {
        $goal = auth()->user()->goals()->findOrFail($goalId);
        $goal->increment('current_amount', $amount);
        
        session()->flash('success', 'Contribution added successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedStatus']);
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Goals</h1>
            <p class="text-gray-600 dark:text-gray-400">Track your savings goals and financial milestones</p>
        </div>
        <flux:button href="/goals/create" variant="primary" icon="plus" wire:navigate>
            Create Goal
        </flux:button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.flag class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Goals</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $totalGoals }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                    <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Completed</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $completedGoals }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-full dark:bg-purple-900/20 mr-3">
                    <flux:icon.currency-euro class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Target Amount</p>
                    <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">€{{ number_format($totalTargetAmount, 2) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-full dark:bg-yellow-900/20 mr-3">
                    <flux:icon.banknotes class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Current Amount</p>
                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">€{{ number_format($totalCurrentAmount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <flux:input wire:model.live="search" placeholder="Search goal names..." />
            </div>
            
            <div>
                <flux:select wire:model.live="selectedStatus" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </flux:select>
            </div>
            
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    Clear Filters
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Goals List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($goals->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($goals as $goal)
                    @php
                        $isCompleted = $goal->current_amount >= $goal->target_amount;
                        $isOverdue = $goal->target_date && $goal->target_date->isPast() && !$isCompleted;
                        
                        if ($goal->target_date) {
                            $today = now()->startOfDay();
                            $targetDate = $goal->target_date->startOfDay();
                            $daysDiff = $today->diffInDays($targetDate, false);
                            
                            if ($targetDate->isFuture()) {
                                $daysRemaining = ceil($daysDiff);
                            } elseif ($targetDate->isToday()) {
                                $daysRemaining = 0;
                            } else {
                                $daysRemaining = ceil($daysDiff);
                            }
                        } else {
                            $daysRemaining = null;
                        }
                    @endphp
                    
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 rounded-full {{ $isCompleted ? 'bg-green-100 dark:bg-green-900/20' : ($isOverdue ? 'bg-red-100 dark:bg-red-900/20' : 'bg-blue-100 dark:bg-blue-900/20') }}">
                                    @if($isCompleted)
                                        <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    @elseif($isOverdue)
                                        <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    @else
                                        <flux:icon.flag class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    @endif
                                </div>
                                
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ $goal->name }}
                                    </h3>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        @if($goal->target_date)
                                            <span>{{ $goal->target_date->format('M d, Y') }}</span>
                                            @if($daysRemaining !== null)
                                                <span>•</span>
                                                @if($goal->target_date->isFuture())
                                                    <span>{{ $daysRemaining }} days left</span>
                                                @elseif($goal->target_date->isToday())
                                                    <span>Due today</span>
                                                @else
                                                    <span>{{ abs($daysRemaining) }} days overdue</span>
                                                @endif
                                            @endif
                                        @endif
                                        @if($isCompleted)
                                            <span>•</span>
                                            <span class="text-green-600 dark:text-green-400">Completed</span>
                                        @elseif($isOverdue)
                                            <span>•</span>
                                            <span class="text-red-600 dark:text-red-400">Overdue</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                €{{ number_format($goal->current_amount, 2) }} / €{{ number_format($goal->target_amount, 2) }}
                                            </span>
                                            <span class="font-medium {{ $isCompleted ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ number_format($goal->percentage_completed, 1) }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1 dark:bg-gray-700">
                                            <div class="h-2 rounded-full {{ $isCompleted ? 'bg-green-600' : 'bg-blue-600' }}" 
                                                 style="width: {{ min($goal->percentage_completed, 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
                                    <p class="font-medium {{ $goal->remaining_amount <= 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                        €{{ number_format(max(0, $goal->remaining_amount), 2) }}
                                    </p>
                                </div>
                                
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    
                                    <flux:menu>
                                        <flux:menu.item href="/goals/{{ $goal->id }}" icon="eye" wire:navigate>
                                            View Details
                                        </flux:menu.item>
                                        <flux:menu.item href="/goals/{{ $goal->id }}/edit" icon="pencil" wire:navigate>
                                            Edit Goal
                                        </flux:menu.item>
                                        @if(!$isCompleted)
                                            <flux:menu.item href="/goals/{{ $goal->id }}/contribute" icon="plus" wire:navigate>
                                                Add Contribution
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            wire:click="deleteGoal({{ $goal->id }})"
                                            wire:confirm="Are you sure you want to delete this goal?"
                                            icon="trash" 
                                            variant="danger">
                                            Delete Goal
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($goals->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $goals->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <flux:icon.flag class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No goals found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Start planning your financial future by setting savings goals</p>
                <flux:button href="/goals/create" variant="primary" icon="plus" wire:navigate>
                    Create Your First Goal
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