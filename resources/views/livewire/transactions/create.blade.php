<?php

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithFileUploads;
    public $description = '';
    public $type = 'expense';
    public $amount = 0.00;
    public $account_id = '';
    public $category_id = '';
    public $transaction_date = '';
    public $notes = '';
    public $selectedTags = [];
    public $showTagModal = false;
    public $newTagName = '';
    public $newTagColor = '#3B82F6';
    public $receipt;

    public function mount(): void
    {
        $this->transaction_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'selectedTags' => ['array'],
            'selectedTags.*' => ['exists:tags,id'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // Max 5MB
        ]);

        // Ensure the account and category belong to the user
        $account = auth()->user()->accounts()->findOrFail($validated['account_id']);
        $category = auth()->user()->categories()->findOrFail($validated['category_id']);

        // Handle receipt upload
        $receiptPath = null;
        $receiptFilename = null;
        $receiptSize = null;
        
        if ($this->receipt) {
            $originalName = $this->receipt->getClientOriginalName();
            $extension = $this->receipt->getClientOriginalExtension();
            $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
            
            $receiptPath = $this->receipt->storeAs('receipts', $filename, 'public');
            $receiptFilename = $originalName;
            $receiptSize = $this->receipt->getSize();
        }

        // Create the transaction
        $transaction = auth()->user()->transactions()->create([
            'description' => $validated['description'] ?: $category->name,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'transaction_date' => $validated['transaction_date'],
            'notes' => $validated['notes'],
            'receipt_path' => $receiptPath,
            'receipt_filename' => $receiptFilename,
            'receipt_size' => $receiptSize,
        ]);

        // Attach tags if any
        if (!empty($validated['selectedTags'])) {
            $transaction->tags()->attach($validated['selectedTags']);
        }

        session()->flash('success', 'Transaction created successfully.');
        $this->redirect('/transactions', navigate: true);
    }

    public function with(): array
    {
        return [
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'incomeCategories' => auth()->user()->categories()->where('type', 'income')->active()->orderBy('name')->get(),
            'expenseCategories' => auth()->user()->categories()->where('type', 'expense')->active()->orderBy('name')->get(),
            'tags' => auth()->user()->tags()->orderBy('name')->get(),
        ];
    }

    public function updatedType(): void
    {
        // Reset category when type changes
        $this->category_id = '';
    }

    public function createTag(): void
    {
        $validated = $this->validate([
            'newTagName' => ['required', 'string', 'max:50'],
            'newTagColor' => ['required', 'string', 'size:7'],
        ]);

        $tag = auth()->user()->tags()->create([
            'name' => $validated['newTagName'],
            'color' => $validated['newTagColor'],
        ]);

        $this->selectedTags[] = $tag->id;
        $this->reset(['newTagName']);
        $this->newTagColor = '#3B82F6';
        $this->showTagModal = false;
    }

    public function closeTagModal(): void
    {
        $this->showTagModal = false;
        $this->reset(['newTagName']);
        $this->newTagColor = '#3B82F6';
    }

    public function removeTag($tagId): void
    {
        $this->selectedTags = array_filter($this->selectedTags, fn($id) => $id != $tagId);
    }

    public function getDefaultColors(): array
    {
        return [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
        ];
    }
}; ?>

<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Transaction</h1>
            <p class="text-gray-600 dark:text-gray-400">Record a new income or expense transaction</p>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
            <form wire:submit="save" class="space-y-6">
                {{-- Transaction Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Transaction Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $type === 'income' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" wire:model.live="type" value="income" class="sr-only">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-full {{ $type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-700' }}">
                                    <flux:icon.arrow-up class="w-5 h-5 {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" />
                                </div>
                                <div>
                                    <p class="font-medium {{ $type === 'income' ? 'text-green-900 dark:text-green-100' : 'text-gray-900 dark:text-white' }}">Income</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Money coming in</p>
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
                                    <p class="font-medium {{ $type === 'expense' ? 'text-red-900 dark:text-red-100' : 'text-gray-900 dark:text-white' }}">Expense</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Money going out</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Amount and Date --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input 
                            wire:model="amount" 
                            label="Amount" 
                            type="number" 
                            step="0.01"
                            min="0.01"
                            required 
                        />
                    </div>
                    <div>
                        <flux:input wire:model="transaction_date" label="Transaction Date" type="date" required />
                    </div>
                </div>

                {{-- Account and Category --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select wire:model="account_id" label="Account" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} (€{{ number_format($account->balance, 2) }})</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select wire:model="category_id" label="Category" required>
                            <option value="">Select Category</option>
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
                </div>

                {{-- Description --}}
                <div>
                    <flux:input 
                        wire:model="description" 
                        label="Description (Optional)" 
                        placeholder="Optional description for this transaction"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">If empty, the category name will be used</p>
                </div>

                {{-- Tags --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags (Optional)</label>
                        <flux:button 
                            type="button" 
                            variant="ghost" 
                            size="sm"
                            wire:click="$set('showTagModal', true)">
                            <flux:icon.plus class="w-4 h-4 mr-1" />
                            Create Tag
                        </flux:button>
                    </div>
                    
                    {{-- Selected Tags --}}
                    @if(!empty($selectedTags))
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach($selectedTags as $tagId)
                                @php $tag = $tags->firstWhere('id', $tagId); @endphp
                                @if($tag)
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full" 
                                          style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                        {{ $tag->name }}
                                        <button type="button" wire:click="removeTag({{ $tag->id }})" class="ml-2 text-sm hover:text-red-500">×</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Available Tags --}}
                    @if($tags->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                @if(!in_array($tag->id, $selectedTags))
                                    <button type="button" 
                                            wire:click="$set('selectedTags.{{ count($selectedTags) }}', {{ $tag->id }})"
                                            class="inline-flex items-center px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                                        <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                        {{ $tag->name }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No tags available. Create your first tag!</p>
                    @endif
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                    <textarea 
                        wire:model="notes" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Additional notes about this transaction..."></textarea>
                </div>

                {{-- Receipt Upload --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Receipt (Optional)</label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        <div class="text-center">
                            <flux:icon.camera class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                            <input 
                                type="file" 
                                wire:model="receipt" 
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="hidden"
                                id="receipt-upload"
                            />
                            <label for="receipt-upload" class="cursor-pointer">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    Click to upload receipt or drag and drop
                                </span>
                                <br>
                                <span class="text-xs text-gray-500 dark:text-gray-500">
                                    PNG, JPG, PDF up to 5MB
                                </span>
                            </label>
                        </div>
                        
                        @if($receipt)
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <div class="flex items-center space-x-3">
                                    <flux:icon.document class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $receipt->getClientOriginalName() }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ round($receipt->getSize() / 1024, 2) }} KB
                                        </p>
                                    </div>
                                    <button 
                                        type="button" 
                                        wire:click="$set('receipt', null)"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <flux:icon.trash class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                    @error('receipt')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Preview Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                    
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
                                    @php
                                        $allCategories = collect($incomeCategories)->merge($expenseCategories);
                                        $selectedCategory = $allCategories->firstWhere('id', $category_id);
                                    @endphp
                                    {{ $description ?: ($selectedCategory?->name ?? 'Select Category') }}
                                </h4>
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $accounts->firstWhere('id', $account_id)?->name ?? 'Select Account' }}</span>
                                    @if($transaction_date)
                                        <span>•</span>
                                        <span>{{ \Carbon\Carbon::parse($transaction_date)->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                @if(!empty($selectedTags))
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach($selectedTags as $tagId)
                                            @php $tag = $tags->firstWhere('id', $tagId); @endphp
                                            @if($tag)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full" 
                                                      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                                    {{ $tag->name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="font-bold {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $type === 'income' ? '+' : '-' }}€{{ number_format((float)$amount, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="/transactions" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Transaction
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Tag Modal --}}
    @if($showTagModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeTagModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-gray-800">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-gray-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Create New Tag
                                </h3>
                                <div class="mt-4 space-y-4">
                                    {{-- Tag Name --}}
                                    <div>
                                        <flux:input 
                                            wire:model="newTagName" 
                                            label="Tag Name" 
                                            placeholder="e.g., Business, Personal, Groceries"
                                            required 
                                        />
                                    </div>

                                    {{-- Color Selection --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tag Color</label>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            @foreach($this->getDefaultColors() as $color)
                                                <button type="button" 
                                                        wire:click="$set('newTagColor', '{{ $color }}')"
                                                        class="w-8 h-8 rounded-full border-2 {{ $newTagColor === $color ? 'border-gray-900 dark:border-white' : 'border-gray-300 dark:border-gray-600' }}"
                                                        style="background-color: {{ $color }}"></button>
                                            @endforeach
                                        </div>
                                        
                                        {{-- Custom Color Input --}}
                                        <div class="flex items-center space-x-2">
                                            <input 
                                                type="color" 
                                                wire:model.live="newTagColor"
                                                class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600"
                                            >
                                            <span class="text-sm text-gray-500 dark:text-gray-400">or choose custom color</span>
                                        </div>
                                    </div>

                                    {{-- Preview --}}
                                    @if($newTagName)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview</label>
                                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full" 
                                                  style="background-color: {{ $newTagColor }}20; color: {{ $newTagColor }}">
                                                {{ $newTagName }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700">
                        <flux:button 
                            type="button" 
                            variant="primary" 
                            wire:click="createTag"
                            class="w-full sm:w-auto sm:ml-3">
                            Create Tag
                        </flux:button>
                        <flux:button 
                            type="button" 
                            variant="ghost" 
                            wire:click="closeTagModal"
                            class="mt-3 w-full sm:mt-0 sm:w-auto">
                            Cancel
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>