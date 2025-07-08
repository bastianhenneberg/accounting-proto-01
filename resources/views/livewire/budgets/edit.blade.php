<?php

use App\Models\Budget;
use App\Models\Category;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public Budget $budget;
    public $name = '';
    public $amount = 0.00;
    public $category_id = '';
    public $period = 'monthly';
    public $start_date = '';
    public $end_date = '';
    public $alert_threshold = 80;
    public $is_active = true;

    public function mount(): void
    {
        // Extract budget ID from URL path
        $path = request()->path();
        preg_match('/budgets\/(\d+)\/edit/', $path, $matches);
        
        if (empty($matches[1])) {
            abort(404, 'Budget ID not found in URL: ' . $path);
        }
        
        $budgetId = $matches[1];
        
        $this->budget = auth()->user()->budgets()->findOrFail($budgetId);

        // Populate form with existing data
        $this->name = $this->budget->name;
        $this->amount = $this->budget->amount;
        $this->category_id = $this->budget->category_id;
        $this->period = $this->budget->period;
        $this->start_date = $this->budget->start_date->format('Y-m-d');
        $this->end_date = $this->budget->end_date->format('Y-m-d');
        $this->alert_threshold = $this->budget->alert_threshold ?? 80;
        $this->is_active = $this->budget->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category_id' => ['required', 'exists:categories,id'],
            'period' => ['required', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'alert_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        // Ensure the category belongs to the user and is expense type
        $category = auth()->user()->categories()
            ->where('type', 'expense')
            ->findOrFail($validated['category_id']);

        $this->budget->update($validated);

        session()->flash('success', 'Budget updated successfully.');
        $this->redirect('/budgets', navigate: true);
    }

    public function delete(): void
    {
        $this->budget->delete();
        
        session()->flash('success', 'Budget deleted successfully.');
        $this->redirect('/budgets', navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => auth()->user()->categories()->where('type', 'expense')->orderBy('name')->get(),
            'periods' => [
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'yearly' => 'Yearly',
            ],
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Budget</h1>
            <p class="text-gray-600 dark:text-gray-400">Update your budget details and settings</p>
        </div>

        <form wire:submit="save" class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="space-y-6">
                {{-- Budget Name --}}
                <div>
                    <flux:field>
                        <flux:label>Budget Name</flux:label>
                        <flux:input 
                            wire:model="name" 
                            placeholder="e.g., Groceries, Entertainment, Transportation"
                            required
                        />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                {{-- Category --}}
                <div>
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="category_id" placeholder="Select a category" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category_id" />
                        <flux:description>Only expense categories are available for budgets</flux:description>
                    </flux:field>
                </div>

                {{-- Budget Amount --}}
                <div>
                    <flux:field>
                        <flux:label>Budget Amount</flux:label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">€</span>
                            </div>
                            <flux:input 
                                wire:model="amount" 
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                class="pl-8"
                                required
                            />
                        </div>
                        <flux:error name="amount" />
                    </flux:field>
                </div>

                {{-- Period --}}
                <div>
                    <flux:field>
                        <flux:label>Budget Period</flux:label>
                        <flux:select wire:model="period" required>
                            @foreach($periods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="period" />
                    </flux:field>
                </div>

                {{-- Date Range --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:field>
                            <flux:label>Start Date</flux:label>
                            <flux:input 
                                wire:model="start_date" 
                                type="date"
                                required
                            />
                            <flux:error name="start_date" />
                        </flux:field>
                    </div>
                    
                    <div>
                        <flux:field>
                            <flux:label>End Date</flux:label>
                            <flux:input 
                                wire:model="end_date" 
                                type="date"
                                required
                            />
                            <flux:error name="end_date" />
                        </flux:field>
                    </div>
                </div>

                {{-- Alert Threshold --}}
                <div>
                    <flux:field>
                        <flux:label>Alert Threshold (%)</flux:label>
                        <flux:input 
                            wire:model="alert_threshold" 
                            type="number"
                            min="1"
                            max="100"
                            placeholder="80"
                            required
                        />
                        <flux:error name="alert_threshold" />
                        <flux:description>Receive alerts when spending reaches this percentage of the budget</flux:description>
                    </flux:field>
                </div>

                {{-- Is Active --}}
                <div>
                    <flux:field>
                        <flux:checkbox wire:model="is_active">
                            Active Budget
                        </flux:checkbox>
                        <flux:error name="is_active" />
                        <flux:description>Inactive budgets won't be included in tracking and alerts</flux:description>
                    </flux:field>
                </div>

                {{-- Budget Progress --}}
                @if($budget->amount > 0)
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Current Budget Status</h3>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">
                                €{{ number_format($budget->spent_amount, 2) }} / €{{ number_format($budget->amount, 2) }}
                            </span>
                            <span class="font-medium {{ $budget->isExceeded() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ number_format($budget->percentage_used, 1) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="h-2 rounded-full {{ $budget->isExceeded() ? 'bg-red-600' : 'bg-blue-600' }}" 
                                 style="width: {{ min($budget->percentage_used, 100) }}%"></div>
                        </div>
                        @if($budget->remaining_amount != 0)
                            <p class="text-xs {{ $budget->remaining_amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                                @if($budget->remaining_amount > 0)
                                    €{{ number_format($budget->remaining_amount, 2) }} remaining
                                @else
                                    €{{ number_format(abs($budget->remaining_amount), 2) }} over budget
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <flux:button href="/budgets" variant="ghost" wire:navigate>
                        Cancel
                    </flux:button>
                    
                    <flux:button 
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this budget? This action cannot be undone."
                        variant="danger"
                        size="sm">
                        <flux:icon.trash class="w-4 h-4 mr-2" />
                        Delete Budget
                    </flux:button>
                </div>
                
                <flux:button type="submit" variant="primary">
                    <flux:icon.check class="w-4 h-4 mr-2" />
                    Update Budget
                </flux:button>
            </div>
        </form>
    </div>
</div>