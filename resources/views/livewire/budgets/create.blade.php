<?php

use App\Models\Budget;
use App\Models\Category;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
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
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
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

        // Create the budget
        auth()->user()->budgets()->create([
            ...$validated,
            'spent_amount' => 0,
        ]);

        session()->flash('success', 'Budget created successfully.');
        $this->redirect('/budgets', navigate: true);
    }

    public function with(): array
    {
        return [
            'expenseCategories' => auth()->user()->categories()
                ->where('type', 'expense')
                ->active()
                ->orderBy('name')
                ->get(),
        ];
    }

    public function getPeriodOptions(): array
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }

    public function updatedPeriod(): void
    {
        // Auto-adjust dates based on period
        $now = now();
        
        switch ($this->period) {
            case 'weekly':
                $this->start_date = $now->startOfWeek()->format('Y-m-d');
                $this->end_date = $now->endOfWeek()->format('Y-m-d');
                break;
            case 'monthly':
                $this->start_date = $now->startOfMonth()->format('Y-m-d');
                $this->end_date = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'quarterly':
                $this->start_date = $now->startOfQuarter()->format('Y-m-d');
                $this->end_date = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'yearly':
                $this->start_date = $now->startOfYear()->format('Y-m-d');
                $this->end_date = $now->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getDurationInDays(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return \Carbon\Carbon::parse($this->start_date)->diffInDays(\Carbon\Carbon::parse($this->end_date)) + 1;
    }

    public function getDailyBudget(): float
    {
        $days = $this->getDurationInDays();
        return $days > 0 ? (float)$this->amount / $days : 0;
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Budget</h1>
            <p class="text-gray-600 dark:text-gray-400">Set spending limits for your expense categories</p>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <form wire:submit="save" class="space-y-6">
                {{-- Budget Name --}}
                <div>
                    <flux:input 
                        wire:model="name" 
                        label="Budget Name" 
                        placeholder="e.g., Monthly Groceries, Entertainment Budget"
                        required 
                    />
                </div>

                {{-- Amount and Category --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="amount" 
                            label="Budget Amount" 
                            type="number" 
                            step="0.01"
                            min="0.01"
                            required 
                        />
                    </div>
                    <div>
                        <flux:select wire:model="category_id" label="Category" required>
                            <option value="">Select Category</option>
                            @foreach($expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                {{-- Period --}}
                <div>
                    <flux:select wire:model.live="period" label="Budget Period" required>
                        @foreach($this->getPeriodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model.live="start_date" label="Start Date" type="date" required />
                    </div>
                    <div>
                        <flux:input wire:model.live="end_date" label="End Date" type="date" required />
                    </div>
                </div>

                {{-- Alert Threshold --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Alert Threshold ({{ $alert_threshold }}%)
                    </label>
                    <div class="space-y-2">
                        <input 
                            type="range" 
                            wire:model.live="alert_threshold"
                            min="1" 
                            max="100" 
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                        >
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>1%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Get notified when spending reaches {{ $alert_threshold }}% of budget (€{{ number_format((float)$amount * $alert_threshold / 100, 2) }})
                        </p>
                    </div>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center space-x-3">
                    <flux:checkbox wire:model="is_active" />
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Active</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Begin tracking this budget immediately</p>
                    </div>
                </div>

                {{-- Budget Insights --}}
                @if($start_date && $end_date && $amount > 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 dark:bg-blue-900/20 dark:border-blue-800">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Budget Insights</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Duration</p>
                                <p class="text-blue-800 dark:text-blue-200">{{ $this->getDurationInDays() }} days</p>
                            </div>
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Daily Budget</p>
                                <p class="text-blue-800 dark:text-blue-200">€{{ number_format($this->getDailyBudget(), 2) }}</p>
                            </div>
                            <div>
                                <p class="text-blue-600 dark:text-blue-400 font-medium">Alert At</p>
                                <p class="text-blue-800 dark:text-blue-200">€{{ number_format((float)$amount * $alert_threshold / 100, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Preview Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                    
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900/20">
                                    <flux:icon.chart-bar class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $name ?: 'Budget Name' }}
                                    </h4>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $expenseCategories->firstWhere('id', $category_id)?->name ?? 'Select Category' }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ $period }}</span>
                                        @if($start_date && $end_date)
                                            <span>•</span>
                                            <span>{{ \Carbon\Carbon::parse($start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <div class="flex items-center justify-between text-sm mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                €0.00 / €{{ number_format((float)$amount, 2) }}
                                            </span>
                                            <span class="font-medium text-gray-900 dark:text-white">0%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
                                <p class="font-medium text-green-600 dark:text-green-400">
                                    €{{ number_format((float)$amount, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/budgets" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Budget
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>