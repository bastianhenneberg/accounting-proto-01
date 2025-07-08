<?php

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Http\UploadedFile;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $selectedAccount = '';
    public $selectedCategory = '';
    public $selectedType = '';
    public $startDate = '';
    public $endDate = '';
    public $importFile = null;
    public $showImportModal = false;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = auth()->user()->transactions()
            ->with(['account', 'category', 'tags'])
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%'))
            ->when($this->selectedAccount, fn($q) => $q->where('account_id', $this->selectedAccount))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->selectedType, fn($q) => $q->where('type', $this->selectedType))
            ->when($this->startDate, fn($q) => $q->where('transaction_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->where('transaction_date', '<=', $this->endDate))
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        return [
            'transactions' => $query->paginate(20),
            'accounts' => auth()->user()->accounts()->active()->orderBy('name')->get(),
            'categories' => auth()->user()->categories()->active()->orderBy('name')->get(),
            'totalIncome' => auth()->user()->transactions()
                ->where('type', 'income')
                ->when($this->startDate, fn($q) => $q->where('transaction_date', '>=', $this->startDate))
                ->when($this->endDate, fn($q) => $q->where('transaction_date', '<=', $this->endDate))
                ->sum('amount'),
            'totalExpenses' => auth()->user()->transactions()
                ->where('type', 'expense')
                ->when($this->startDate, fn($q) => $q->where('transaction_date', '>=', $this->startDate))
                ->when($this->endDate, fn($q) => $q->where('transaction_date', '<=', $this->endDate))
                ->sum('amount'),
        ];
    }

    public function deleteTransaction($transactionId): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($transactionId);
        $transaction->delete();
        
        session()->flash('success', 'Transaction deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedAccount(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedAccount', 'selectedCategory', 'selectedType']);
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = auth()->user()->transactions()
            ->with(['account', 'category', 'tags'])
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%'))
            ->when($this->selectedAccount, fn($q) => $q->where('account_id', $this->selectedAccount))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->selectedType, fn($q) => $q->where('type', $this->selectedType))
            ->when($this->startDate, fn($q) => $q->where('transaction_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->where('transaction_date', '<=', $this->endDate))
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $transactions = $query->get();

        $filename = 'transactions_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'Date',
                'Description',
                'Account',
                'Category',
                'Type',
                'Amount',
                'Tags',
                'Reference Number',
                'Notes'
            ]);

            // CSV Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $transaction->description,
                    $transaction->account->name,
                    $transaction->category->name,
                    $transaction->type,
                    $transaction->amount,
                    $transaction->tags->pluck('name')->implode(', '),
                    $transaction->reference_number,
                    $transaction->notes
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        if (empty($data)) {
            session()->flash('error', 'CSV file is empty.');
            $this->reset(['importFile', 'showImportModal']);
            return;
        }

        $header = array_shift($data);
        
        // Validate required columns
        $requiredColumns = ['Date', 'Description', 'Account', 'Category', 'Type', 'Amount'];
        $missingColumns = array_diff($requiredColumns, $header);
        
        if (!empty($missingColumns)) {
            session()->flash('error', 'Missing required columns: ' . implode(', ', $missingColumns));
            $this->reset(['importFile', 'showImportModal']);
            return;
        }

        $imported = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $rowData = array_combine($header, $row);
                
                // Validate date
                if (empty($rowData['Date']) || !strtotime($rowData['Date'])) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid or missing date";
                    continue;
                }
                
                // Validate amount
                if (empty($rowData['Amount']) || !is_numeric($rowData['Amount']) || floatval($rowData['Amount']) <= 0) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid or missing amount";
                    continue;
                }
                
                // Find account
                $account = auth()->user()->accounts()
                    ->where('name', trim($rowData['Account'] ?? ''))
                    ->first();
                
                if (!$account) {
                    $errors[] = "Row " . ($index + 2) . ": Account '" . ($rowData['Account'] ?? 'N/A') . "' not found";
                    continue;
                }

                // Find category
                $category = auth()->user()->categories()
                    ->where('name', trim($rowData['Category'] ?? ''))
                    ->first();
                
                if (!$category) {
                    $errors[] = "Row " . ($index + 2) . ": Category '" . ($rowData['Category'] ?? 'N/A') . "' not found";
                    continue;
                }

                // Validate type
                $type = strtolower(trim($rowData['Type'] ?? 'expense'));
                if (!in_array($type, ['income', 'expense', 'transfer'])) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid type '" . $type . "'. Must be income, expense, or transfer";
                    continue;
                }

                // Create transaction
                $transaction = auth()->user()->transactions()->create([
                    'account_id' => $account->id,
                    'category_id' => $category->id,
                    'type' => $type,
                    'amount' => floatval($rowData['Amount']),
                    'description' => trim($rowData['Description'] ?? ''),
                    'transaction_date' => date('Y-m-d', strtotime($rowData['Date'])),
                    'reference_number' => !empty($rowData['Reference Number']) ? trim($rowData['Reference Number']) : null,
                    'notes' => !empty($rowData['Notes']) ? trim($rowData['Notes']) : null,
                ]);

                // Handle tags if present
                if (!empty($rowData['Tags'])) {
                    $tagNames = array_map('trim', explode(',', $rowData['Tags']));
                    $tags = [];
                    
                    foreach ($tagNames as $tagName) {
                        if (!empty($tagName)) {
                            $tag = auth()->user()->tags()->firstOrCreate(
                                ['name' => $tagName],
                                ['color' => '#6B7280']
                            );
                            $tags[] = $tag->id;
                        }
                    }
                    
                    if (!empty($tags)) {
                        $transaction->tags()->sync($tags);
                    }
                }

                $imported++;
                
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $this->reset(['importFile', 'showImportModal']);

        if (count($errors) > 0) {
            $errorMessage = "Imported {$imported} transactions with " . count($errors) . " errors.";
            if (count($errors) <= 5) {
                $errorMessage .= " Errors: " . implode('; ', $errors);
            } else {
                $errorMessage .= " First 5 errors: " . implode('; ', array_slice($errors, 0, 5)) . '...';
            }
            session()->flash('warning', $errorMessage);
        } else {
            session()->flash('success', "Successfully imported {$imported} transactions.");
        }
    }
}; ?>

<div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transactions</h1>
                <p class="text-gray-600 dark:text-gray-400">Track all your income and expenses</p>
            </div>
            <div class="flex space-x-2">
                <flux:dropdown>
                    <flux:button variant="outline">
                        <flux:icon.arrow-down-tray class="w-4 h-4 mr-2" />
                        Import/Export
                    </flux:button>
                    
                    <flux:menu>
                        <flux:menu.item wire:click="exportCsv" icon="arrow-down-tray">
                            Export CSV
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item wire:click="$set('showImportModal', true)" icon="arrow-up-tray">
                            Import CSV
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                
                <flux:button href="/transactions/create" variant="primary" wire:navigate>
                    <flux:icon.plus class="w-4 h-4 mr-2" />
                    Add Transaction
                </flux:button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid gap-4 md:grid-cols-3 mb-6">
            <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                        <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Income</p>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">€{{ number_format($totalIncome, 2) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-full dark:bg-red-900/20 mr-3">
                        <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Expenses</p>
                        <p class="text-lg font-semibold text-red-600 dark:text-red-400">€{{ number_format($totalExpenses, 2) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                        <flux:icon.calculator class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Net Amount</p>
                        <p class="text-lg font-semibold {{ ($totalIncome - $totalExpenses) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            €{{ number_format($totalIncome - $totalExpenses, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div>
                    <flux:input wire:model.live="search" placeholder="Search descriptions..." />
                </div>
                
                <div>
                    <flux:select wire:model.live="selectedAccount" placeholder="All Accounts">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                
                <div>
                    <flux:select wire:model.live="selectedCategory" placeholder="All Categories">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                
                <div>
                    <flux:select wire:model.live="selectedType" placeholder="All Types">
                        <option value="">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="transfer">Transfer</option>
                    </flux:select>
                </div>
                
                <div>
                    <flux:input wire:model.live="startDate" type="date" />
                </div>
                
                <div class="flex items-end space-x-2">
                    <flux:input wire:model.live="endDate" type="date" class="flex-1" />
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                        Clear
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
            @if($transactions->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($transactions as $transaction)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="p-2 rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : ($transaction->type === 'expense' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-blue-100 dark:bg-blue-900/20') }}">
                                        @if($transaction->type === 'income')
                                            <flux:icon.arrow-up class="w-5 h-5 text-green-600 dark:text-green-400" />
                                        @elseif($transaction->type === 'expense')
                                            <flux:icon.arrow-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                                        @else
                                            <flux:icon.arrow-path class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        @endif
                                    </div>
                                    
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            {{ $transaction->description ?: $transaction->category->name }}
                                        </h3>
                                        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span>{{ $transaction->account->name }}</span>
                                            <span>•</span>
                                            <span>{{ $transaction->category->name }}</span>
                                            <span>•</span>
                                            <span>{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                        </div>
                                        @if($transaction->tags->count() > 0)
                                            <div class="flex items-center space-x-1 mt-1">
                                                @foreach($transaction->tags as $tag)
                                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full" 
                                                          style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="text-lg font-semibold {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $transaction->type === 'income' ? '+' : '-' }}€{{ number_format($transaction->amount, 2) }}
                                        </p>
                                    </div>
                                    
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        
                                        <flux:menu>
                                            <flux:menu.item href="/transactions/{{ $transaction->id }}" icon="eye" wire:navigate>
                                                View Details
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item 
                                                wire:click="deleteTransaction({{ $transaction->id }})"
                                                wire:confirm="Are you sure you want to delete this transaction?"
                                                icon="trash" 
                                                variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <flux:icon.banknotes class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No transactions found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Start tracking your finances by adding transactions</p>
                    <flux:button href="/transactions/create" variant="primary" wire:navigate>
                        <flux:icon.plus class="w-4 h-4 mr-2" />
                        Add Your First Transaction
                    </flux:button>
                </div>
            @endif
        </div>

    {{-- Import Modal --}}
    @if($showImportModal)
        <flux:modal name="import-modal" class="space-y-6">
            <div>
                <flux:heading size="lg">Import Transactions</flux:heading>
                <flux:subheading>Upload a CSV file to import transactions</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:label>CSV File</flux:label>
                    <flux:input type="file" wire:model="importFile" accept=".csv,.txt" />
                    @error('importFile') 
                        <flux:error>{{ $message }}</flux:error> 
                    @enderror
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">CSV Format Requirements:</h4>
                    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <li>• <strong>Date:</strong> YYYY-MM-DD format</li>
                        <li>• <strong>Description:</strong> Transaction description</li>
                        <li>• <strong>Account:</strong> Must match existing account name</li>
                        <li>• <strong>Category:</strong> Must match existing category name</li>
                        <li>• <strong>Type:</strong> income, expense, or transfer</li>
                        <li>• <strong>Amount:</strong> Numeric value (positive)</li>
                        <li>• <strong>Tags:</strong> Comma-separated tag names (optional)</li>
                        <li>• <strong>Reference Number:</strong> Optional reference</li>
                        <li>• <strong>Notes:</strong> Optional notes</li>
                    </ul>
                </div>
            </div>

            <div class="flex space-x-2">
                <flux:button wire:click="importCsv" variant="primary" :disabled="!$importFile">
                    Import Transactions
                </flux:button>
                <flux:button wire:click="$set('showImportModal', false)" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </flux:modal>
    @endif

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    @if(session('warning'))
        <flux:toast variant="warning">{{ session('warning') }}</flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif
</div>