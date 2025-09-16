<?php

use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public $description = '';
    public $type = 'expense';
    public $amount = 0.00;
    public $account_id = '';
    public $category_id = '';
    public $frequency = 'monthly';
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        // Ensure the account and category belong to the user
        $account = auth()->user()->accounts()->findOrFail($validated['account_id']);
        $category = auth()->user()->categories()->findOrFail($validated['category_id']);

        // Create the recurring transaction
        $recurringTransaction = auth()->user()->recurringTransactions()->create([
            ...$validated,
            'next_execution_date' => $validated['start_date'],
        ]);

        session()->flash('success', __('Recurring transaction created successfully.'));
        $this->redirect('/recurring', navigate: true);
    }

    public function with(): array
    {
        return [
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'incomeCategories' => auth()->user()->categories()->where('type', 'income')->active()->orderBy('name')->get(),
            'expenseCategories' => auth()->user()->categories()->where('type', 'expense')->active()->orderBy('name')->get(),
        ];
    }

    public function getFrequencyOptions(): array
    {
        return [
            'daily' => __('Daily'),
            'weekly' => __('Weekly'),
            'monthly' => __('Monthly'),
            'quarterly' => __('Quarterly'),
            'yearly' => __('Yearly'),
        ];
    }

    public function updatedType(): void
    {
        // Reset category when type changes
        $this->category_id = '';
    }

    public function getNextOccurrencePreview(): string
    {
        if (!$this->start_date || !$this->frequency) {
            return '';
        }

        $date = \Carbon\Carbon::parse($this->start_date);
        
        switch ($this->frequency) {
            case 'daily':
                return $date->addDay()->format('M d, Y');
            case 'weekly':
                return $date->addWeek()->format('M d, Y');
            case 'monthly':
                return $date->addMonth()->format('M d, Y');
            case 'quarterly':
                return $date->addMonths(3)->format('M d, Y');
            case 'yearly':
                return $date->addYear()->format('M d, Y');
            default:
                return '';
        }
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create Recurring Transaction') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Set up automatic income or expense transactions') }}</p>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <form wire:submit="save" class="space-y-6">
                {{-- Transaction Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Transaction Type') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $type === 'income' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" wire:model.live="type" value="income" class="sr-only">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full {{ $type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-700' }}">
                                    <flux:icon.arrow-up class="w-5 h-5 {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" />
                                </div>
                                <div>
                                    <p class="font-medium {{ $type === 'income' ? 'text-green-900 dark:text-green-100' : 'text-gray-900 dark:text-white' }}">{{ __('Income') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Recurring money coming in') }}</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $type === 'expense' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" wire:model.live="type" value="expense" class="sr-only">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full {{ $type === 'expense' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700' }}">
                                    <flux:icon.arrow-down class="w-5 h-5 {{ $type === 'expense' ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" />
                                </div>
                                <div>
                                    <p class="font-medium {{ $type === 'expense' ? 'text-red-900 dark:text-red-100' : 'text-gray-900 dark:text-white' }}">{{ __('Expense') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Recurring money going out') }}</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <flux:input 
                        wire:model="description" 
:label="__('Description')" 
:placeholder="__('e.g., Monthly Salary, Rent Payment, Netflix Subscription')"
                        required 
                    />
                </div>

                {{-- Amount and Account --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="amount" 
:label="__('Amount')" 
                            type="number" 
                            step="0.01"
                            min="0.01"
                            required 
                        />
                    </div>
                    <div>
                        <flux:select wire:model="account_id" :label="__('Account')" required>
                            <option value="">{{ __('Select Account') }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                {{-- Category --}}
                <div>
                    <flux:select wire:model="category_id" :label="__('Category')" required>
                        <option value="">{{ __('Select Category') }}</option>
                        @if($type === 'income')
                            @foreach($incomeCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        @else
                            @foreach($expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        @endif
                    </flux:select>
                </div>

                {{-- Frequency --}}
                <div>
                    <flux:select wire:model.live="frequency" :label="__('Frequency')" required>
                        @foreach($this->getFrequencyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model.live="start_date" :label="__('Start Date')" type="date" required />
                    </div>
                    <div>
                        <flux:input wire:model="end_date" :label="__('End Date (Optional)')" type="date" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Leave empty for no end date') }}</p>
                    </div>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center space-x-3">
                    <flux:checkbox wire:model="is_active" />
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Start Active') }}</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Begin processing this recurring transaction immediately') }}</p>
                    </div>
                </div>

                {{-- Preview Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Preview') }}</h3>
                    
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 rounded-full {{ $type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                @if($type === 'income')
                                    <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                                @else
                                    <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    {{ $description ?: __('Transaction Description') }}
                                </h4>
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $accounts->firstWhere('id', $account_id)?->name ?? __('Select Account') }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ $frequency }}</span>
                                    @if($start_date)
                                        <span>•</span>
                                        <span>{{ __('Starts') }} {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                @if($start_date && $frequency)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('Next occurrence') }}: {{ $this->getNextOccurrencePreview() }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="font-bold {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $type === 'income' ? '+' : '-' }}€{{ number_format((float)$amount, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('per') }} {{ __($frequency) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/recurring" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Recurring Transaction') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>