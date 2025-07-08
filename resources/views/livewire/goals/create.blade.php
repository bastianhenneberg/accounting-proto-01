<?php

use App\Models\Goal;
use Livewire\Volt\Component;

new class extends Component {
    public $name = '';
    public $description = '';
    public $target_amount = '';
    public $current_amount = 0.00;
    public $target_date = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['required', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date', 'after:today'],
        ]);

        auth()->user()->goals()->create([
            ...$validated,
            'is_achieved' => false,
        ]);

        session()->flash('success', 'Goal created successfully.');
        $this->redirect('/goals', navigate: true);
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Goal</h1>
            <p class="text-gray-600 dark:text-gray-400">Set a new savings goal to track your financial progress</p>
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
                        <flux:description>How much you already have saved towards this goal</flux:description>
                    </flux:field>
                </div>

                {{-- Target Date --}}
                <div>
                    <flux:field>
                        <flux:label>Target Date (Optional)</flux:label>
                        <flux:input 
                            wire:model="target_date" 
                            type="date"
                            min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        />
                        <flux:error name="target_date" />
                        <flux:description>When would you like to achieve this goal?</flux:description>
                    </flux:field>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <flux:button href="/goals" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                
                <flux:button type="submit" variant="primary">
                    <flux:icon.plus class="w-4 h-4 mr-2" />
                    Create Goal
                </flux:button>
            </div>
        </form>
    </div>
</div>