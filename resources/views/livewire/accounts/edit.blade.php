<?php

use App\Models\Account;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public Account $account;
    public $name = '';
    public $type = 'checking';
    public $balance = 0.00;
    public $currency = 'EUR';
    public $account_number = '';
    public $bank_name = '';
    public $is_active = true;
    public $originalBalance = 0;

    public function mount(): void
    {
        // Extract account ID from URL path
        $path = request()->path();
        preg_match('/accounts\/(\d+)\/edit/', $path, $matches);
        
        if (empty($matches[1])) {
            abort(404, 'Account ID not found in URL: ' . $path);
        }
        
        $accountId = $matches[1];
        
        $this->account = auth()->user()->accounts()->findOrFail($accountId);

        // Populate form with existing data
        $this->name = $this->account->name;
        $this->type = $this->account->type;
        $this->balance = $this->account->balance;
        $this->currency = $this->account->currency;
        $this->account_number = $this->account->account_number ?? '';
        $this->bank_name = $this->account->bank_name ?? '';
        $this->is_active = $this->account->is_active;
        $this->originalBalance = $this->account->balance;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['checking', 'savings', 'credit_card', 'cash', 'investment'])],
            'balance' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $this->account->update($validated);

        session()->flash('success', 'Account updated successfully.');
        $this->redirect('/accounts/' . $this->account->id, navigate: true);
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

    public function getBalanceDifference(): float
    {
        return (float)$this->balance - $this->originalBalance;
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center space-x-4 mb-2">
                <flux:button href="/accounts/{{ $account->id }}" variant="ghost" wire:navigate>
                    <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                    Back to Account
                </flux:button>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Account</h1>
            <p class="text-gray-600 dark:text-gray-400">Update account information and settings</p>
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

                {{-- Balance and Currency --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model.live="balance" 
                            label="Current Balance" 
                            type="number" 
                            step="0.01"
                            required 
                        />
                        @if($this->getBalanceDifference() != 0)
                            <p class="text-xs mt-1 {{ $this->getBalanceDifference() > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $this->getBalanceDifference() > 0 ? '+' : '' }}€{{ number_format($this->getBalanceDifference(), 2) }} from original
                            </p>
                        @endif
                    </div>
                    <div>
                        <flux:select wire:model="currency" label="Currency" required>
                            @foreach($this->getCurrencies() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                {{-- Bank Details --}}
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

                {{-- Account Status --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex items-center space-x-3">
                        <flux:checkbox wire:model="is_active" />
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Account</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inactive accounts are hidden from most views</p>
                        </div>
                    </div>
                </div>

                {{-- Preview Card --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                    
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 rounded-full {{ $type === 'checking' ? 'bg-blue-100 dark:bg-blue-900/20' : ($type === 'savings' ? 'bg-green-100 dark:bg-green-900/20' : ($type === 'credit_card' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700')) }}">
                                    @if($type === 'checking')
                                        <flux:icon.credit-card class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    @elseif($type === 'savings')
                                        <flux:icon.banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    @elseif($type === 'credit_card')
                                        <flux:icon.credit-card class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    @elseif($type === 'cash')
                                        <flux:icon.currency-euro class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                                    @else
                                        <flux:icon.chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $name ?: 'Account Name' }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                                        {{ str_replace('_', ' ', $type) }}
                                        @if($bank_name) • {{ $bank_name }} @endif
                                        @if(!$is_active) • <span class="text-red-500">Inactive</span> @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $currency }} {{ number_format((float)$balance, 2) }}
                                </p>
                                @if($account_number)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        ****{{ substr($account_number, -4) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Warning for Balance Changes --}}
                @if($this->getBalanceDifference() != 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 dark:bg-yellow-900/20 dark:border-yellow-800">
                        <div class="flex items-start space-x-3">
                            <flux:icon.exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Balance Change Warning</h4>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                    You're changing the balance by {{ $this->getBalanceDifference() > 0 ? '+' : '' }}€{{ number_format($this->getBalanceDifference(), 2) }}. 
                                    This will not create a transaction record. Consider creating a manual adjustment transaction instead.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Account Statistics --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 dark:bg-blue-900/20 dark:border-blue-800">
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Account Statistics</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-blue-600 dark:text-blue-400 font-medium">Total Transactions</p>
                            <p class="text-blue-800 dark:text-blue-200">{{ $account->transactions()->count() }}</p>
                        </div>
                        <div>
                            <p class="text-blue-600 dark:text-blue-400 font-medium">Account Age</p>
                            <p class="text-blue-800 dark:text-blue-200">{{ $account->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/accounts/{{ $account->id }}" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Update Account
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>