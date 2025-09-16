<?php

use App\Models\PlannedTransaction;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public $description = '';
    public $type = 'expense';
    public $amount = '';
    public $account_id = '';
    public $category_id = '';
    public $planned_date = '';
    public $notes = '';
    public $auto_convert = true;

    public function mount(): void
    {
        $this->planned_date = now()->addDay()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'planned_date' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'auto_convert' => ['boolean'],
        ]);

        // Ensure the account and category belong to the user
        auth()->user()->accounts()->findOrFail($validated['account_id']);
        auth()->user()->categories()->findOrFail($validated['category_id']);

        auth()->user()->plannedTransactions()->create([
            ...$validated,
            'status' => 'pending',
        ]);

        session()->flash('success', __('Planned transaction created successfully.'));
        $this->redirect('/planned', navigate: true);
    }

    public function with(): array
    {
        return [
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'incomeCategories' => auth()->user()->categories()->where('type', 'income')->active()->orderBy('name')->get(),
            'expenseCategories' => auth()->user()->categories()->where('type', 'expense')->active()->orderBy('name')->get(),
        ];
    }

    public function updatedType(): void
    {
        $this->category_id = '';
    }

    public function getProjectedImpact(): array
    {
        if (!$this->account_id || !$this->amount) {
            return ['current' => 0, 'projected' => 0];
        }

        $account = auth()->user()->accounts()->find($this->account_id);
        if (!$account) {
            return ['current' => 0, 'projected' => 0];
        }

        $impact = $this->type === 'income' ? (float)$this->amount : -(float)$this->amount;

        return [
            'current' => $account->balance,
            'projected' => $account->balance + $impact,
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create Planned Transaction') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Plan a future income or expense transaction') }}</p>
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
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Future money coming in') }}</p>
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
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Future money going out') }}</p>
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
                        :placeholder="__('e.g., Annual Insurance, Tax Refund, Birthday Gift')"
                        required
                    />
                </div>

                {{-- Amount, Account, Date --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <flux:input
                            wire:model.live="amount"
                            :label="__('Amount')"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                        />
                    </div>
                    <div>
                        <flux:select wire:model.live="account_id" :label="__('Account')" required>
                            <option value="">{{ __('Select Account') }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:input wire:model="planned_date" :label="__('Planned Date')" type="date" min="{{ date('Y-m-d', strtotime('tomorrow')) }}" required />
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

                {{-- Notes --}}
                <div>
                    <flux:textarea
                        wire:model="notes"
                        :label="__('Notes (Optional)')"
                        :placeholder="__('Add any additional details or reminders...')"
                        rows="3"
                    />
                </div>

                {{-- Auto Convert Option --}}
                <div class="flex items-center space-x-3">
                    <flux:checkbox wire:model="auto_convert" />
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Auto Convert') }}</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Automatically convert to transaction on the planned date') }}</p>
                    </div>
                </div>

                {{-- Preview Section --}}
                @if($account_id && $amount)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Balance Impact Preview') }}</h3>

                        @php $impact = $this->getProjectedImpact(); @endphp
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Current Balance') }}</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">€{{ number_format($impact['current'], 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Transaction') }}</p>
                                    <p class="text-lg font-semibold {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $type === 'income' ? '+' : '-' }}€{{ number_format((float)$amount, 2) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Projected Balance') }}</p>
                                    <p class="text-lg font-semibold {{ $impact['projected'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        €{{ number_format($impact['projected'], 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/planned" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Planned Transaction') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>