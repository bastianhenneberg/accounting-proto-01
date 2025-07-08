<?php

use App\Models\Goal;
use Livewire\Volt\Component;

new class extends Component {
    public Goal $goal;
    public $name = '';
    public $description = '';
    public $target_amount = '';
    public $current_amount = '';
    public $target_date = '';

    public function mount(): void
    {
        // Extract goal ID from URL path
        $path = request()->path();
        preg_match('/goals\/(\d+)\/edit/', $path, $matches);
        
        if (empty($matches[1])) {
            abort(404, 'Goal ID not found in URL: ' . $path);
        }
        
        $goalId = $matches[1];
        
        $this->goal = auth()->user()->goals()->findOrFail($goalId);

        // Populate form with existing data
        $this->name = $this->goal->name;
        $this->description = $this->goal->description ?? '';
        $this->target_amount = $this->goal->target_amount;
        $this->current_amount = $this->goal->current_amount;
        $this->target_date = $this->goal->target_date ? $this->goal->target_date->format('Y-m-d') : '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['required', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $this->goal->update($validated);

        session()->flash('success', 'Goal updated successfully.');
        $this->redirect('/goals', navigate: true);
    }

    public function delete(): void
    {
        $this->goal->delete();
        
        session()->flash('success', 'Goal deleted successfully.');
        $this->redirect('/goals', navigate: true);
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Goal</h1>
            <p class="text-gray-600 dark:text-gray-400">Update your savings goal details</p>
        </div>

        <form wire:submit="save" class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="space-y-6">
                {{-- Goal Name --}}
                <div>
                    <flux:field>
                        <flux:label>Goal Name</flux:label>
                        <flux:input 
                            wire:model="name" 
                            placeholder="e.g., Emergency Fund, Vacation, New Car"
                            required
                        />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                {{-- Description --}}
                <div>
                    <flux:field>
                        <flux:label>Description (Optional)</flux:label>
                        <flux:textarea 
                            wire:model="description" 
                            placeholder="Add any additional details about your goal..."
                            rows="3"
                        />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                {{-- Target Amount --}}
                <div>
                    <flux:field>
                        <flux:label>Target Amount</flux:label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">€</span>
                            </div>
                            <flux:input 
                                wire:model="target_amount" 
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                class="pl-8"
                                required
                            />
                        </div>
                        <flux:error name="target_amount" />
                    </flux:field>
                </div>

                {{-- Current Amount --}}
                <div>
                    <flux:field>
                        <flux:label>Current Amount</flux:label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">€</span>
                            </div>
                            <flux:input 
                                wire:model="current_amount" 
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="pl-8"
                            />
                        </div>
                        <flux:error name="current_amount" />
                        <flux:description>How much you currently have saved towards this goal</flux:description>
                    </flux:field>
                </div>

                {{-- Target Date --}}
                <div>
                    <flux:field>
                        <flux:label>Target Date (Optional)</flux:label>
                        <flux:input 
                            wire:model="target_date" 
                            type="date"
                            min="{{ date('Y-m-d') }}"
                        />
                        <flux:error name="target_date" />
                        <flux:description>When would you like to achieve this goal?</flux:description>
                    </flux:field>
                </div>

                {{-- Progress Info --}}
                @if($goal->target_amount > 0)
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Current Progress</h3>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">
                                €{{ number_format($goal->current_amount, 2) }} / €{{ number_format($goal->target_amount, 2) }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ number_format($goal->percentage_completed, 1) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-blue-600" 
                                 style="width: {{ min($goal->percentage_completed, 100) }}%"></div>
                        </div>
                        @if($goal->remaining_amount > 0)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                €{{ number_format($goal->remaining_amount, 2) }} remaining to reach your goal
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <flux:button href="/goals" variant="ghost" wire:navigate>
                        Cancel
                    </flux:button>
                    
                    <flux:button 
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this goal? This action cannot be undone."
                        variant="danger"
                        size="sm">
                        <flux:icon.trash class="w-4 h-4 mr-2" />
                        Delete Goal
                    </flux:button>
                </div>
                
                <flux:button type="submit" variant="primary">
                    <flux:icon.check class="w-4 h-4 mr-2" />
                    Update Goal
                </flux:button>
            </div>
        </form>
    </div>
</div>