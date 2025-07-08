<?php

use App\Models\Account;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public $name = '';
    public $type = 'checking';
    public $balance = 0.00;
    public $currency = 'EUR';
    public $account_number = '';
    public $bank_name = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['checking', 'savings', 'credit_card', 'cash', 'investment'])],
            'balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
        ]);

        auth()->user()->accounts()->create([
            ...$validated,
            'is_active' => true,
        ]);

        session()->flash('success', 'Account created successfully.');
        $this->redirect('/accounts', navigate: true);
    }

    public function getAccountTypes(): array
    {
        return [
            'checking' => 'Checking Account',
            'savings' => 'Savings Account',
            'credit_card' => 'Credit Card',
            'cash' => 'Cash',
            'investment' => 'Investment Account',
        ];
    }

    public function getCurrencies(): array
    {
        return [
            'EUR' => 'Euro (€)',
            'USD' => 'US Dollar ($)',
            'GBP' => 'British Pound (£)',
            'CHF' => 'Swiss Franc (CHF)',
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Account</h1>
            <p class="text-gray-600 dark:text-gray-400">Add a new financial account to track your money</p>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <form wire:submit="save" class="space-y-6">
                {{-- Account Name --}}
                <div>
                    <flux:input 
                        wire:model="name" 
                        label="Account Name" 
                        placeholder="e.g., Main Checking, Emergency Savings"
                        required 
                    />
                </div>

                {{-- Account Type --}}
                <div>
                    <flux:select wire:model="type" label="Account Type" required>
                        @foreach($this->getAccountTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Initial Balance and Currency --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="balance" 
                            label="Initial Balance" 
                            type="number" 
                            step="0.01"
                            min="0"
                            required 
                        />
                    </div>
                    <div>
                        <flux:select wire:model="currency" label="Currency" required>
                            @foreach($this->getCurrencies() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                {{-- Bank Details (Optional) --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Bank Details (Optional)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:input 
                                wire:model="bank_name" 
                                label="Bank Name" 
                                placeholder="e.g., Deutsche Bank, Sparkasse"
                            />
                        </div>
                        <div>
                            <flux:input 
                                wire:model="account_number" 
                                label="Account Number" 
                                placeholder="Last 4 digits or reference"
                            />
                        </div>
                    </div>
                </div>

                {{-- Preview Card --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                    
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 rounded-full {{ $type === 'checking' ? 'bg-blue-100 dark:bg-blue-900/20' : ($type === 'savings' ? 'bg-green-100 dark:bg-green-900/20' : ($type === 'credit_card' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700')) }}">
                                @if($type === 'checking')
                                    <flux:icon.credit-card class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                @elseif($type === 'savings')
                                    <flux:icon.banknotes class="w-5 h-5 text-green-600 dark:text-green-400" />
                                @elseif($type === 'credit_card')
                                    <flux:icon.credit-card class="w-5 h-5 text-red-600 dark:text-red-400" />
                                @elseif($type === 'cash')
                                    <flux:icon.currency-euro class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                @else
                                    <flux:icon.chart-bar class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    {{ $name ?: 'Account Name' }}
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $type) }}
                                    @if($bank_name) • {{ $bank_name }} @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $currency }} {{ number_format((float)$balance, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/accounts" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Account
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>