<?php

use App\Models\Account;
use App\Models\Category;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public $commandOpen = false;
    public $incomeModalOpen = false;
    public $expenseModalOpen = false;
    public $importModalOpen = false;
    public $search = '';

    // Income form
    public $incomeDescription = '';
    public $incomeAmount = '';
    public $incomeAccountId = '';
    public $incomeCategoryId = '';
    public $incomeDate = '';

    // Expense form
    public $expenseDescription = '';
    public $expenseAmount = '';
    public $expenseAccountId = '';
    public $expenseCategoryId = '';
    public $expenseDate = '';

    // Import form
    public $importText = '';
    public $importAccountId = '';
    public $importCategoryId = '';
    public $parsedTransactions = [];
    public $importing = false;

    public function mount(): void
    {
        $this->incomeDate = now()->format('Y-m-d');
        $this->expenseDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'accounts' => auth()->user()->accounts,
            'incomeCategories' => auth()->user()->categories()->where('type', 'income')->get(),
            'expenseCategories' => auth()->user()->categories()->where('type', 'expense')->get(),
        ];
    }

    public function toggleCommand(): void
    {
        $this->commandOpen = !$this->commandOpen;
        if ($this->commandOpen) {
            $this->search = '';
        }
    }

    public function openIncomeModal(): void
    {
        $this->commandOpen = false;
        $this->incomeModalOpen = true;
    }

    public function openExpenseModal(): void
    {
        $this->commandOpen = false;
        $this->expenseModalOpen = true;
    }

    public function openImportModal(): void
    {
        $this->commandOpen = false;
        $this->importModalOpen = true;
    }

    public function parseImportText(): void
    {
        $this->validate([
            'importText' => ['required', 'string', 'min:10'],
        ]);

        $this->importing = true;

        try {
            $service = new \App\Services\TransactionImportService();
            $this->parsedTransactions = $service->parseTransactions($this->importText);

            if (empty($this->parsedTransactions)) {
                Flux\Flux::toast(
                    text: 'Keine Transaktionen gefunden',
                    variant: 'warning',
                );
            }
        } catch (\Exception $e) {
            Flux\Flux::toast(
                text: 'Fehler beim Parsen: ' . $e->getMessage(),
                variant: 'danger',
            );
        } finally {
            $this->importing = false;
        }
    }

    public function importTransactions(): void
    {
        $validated = $this->validate([
            'importAccountId' => ['required', 'exists:accounts,id'],
            'parsedTransactions.*.date' => ['required', 'date'],
            'parsedTransactions.*.category_id' => ['nullable', 'exists:categories,id'],
        ]);

        auth()->user()->accounts()->findOrFail($validated['importAccountId']);

        $count = 0;
        foreach ($this->parsedTransactions as $transaction) {
            // Use individual category or skip if not set
            if (empty($transaction['category_id'])) {
                continue;
            }

            auth()->user()->categories()->findOrFail($transaction['category_id']);

            auth()->user()->transactions()->create([
                'description' => $transaction['description'],
                'type' => 'expense',
                'amount' => $transaction['amount'],
                'account_id' => $validated['importAccountId'],
                'category_id' => $transaction['category_id'],
                'transaction_date' => $transaction['date'],
            ]);
            $count++;
        }

        $this->dispatch('transaction-created');
        $this->importModalOpen = false;
        $this->reset(['importText', 'importAccountId', 'importCategoryId', 'parsedTransactions']);

        Flux\Flux::toast(
            text: "$count Ausgaben importiert",
            variant: 'success',
        );
    }

    public function createIncome(): void
    {
        $validated = $this->validate([
            'incomeDescription' => ['required', 'string', 'max:255'],
            'incomeAmount' => ['required', 'numeric', 'min:0.01'],
            'incomeAccountId' => ['required', 'exists:accounts,id'],
            'incomeCategoryId' => ['required', 'exists:categories,id'],
            'incomeDate' => ['required', 'date'],
        ]);

        auth()->user()->accounts()->findOrFail($validated['incomeAccountId']);
        auth()->user()->categories()->findOrFail($validated['incomeCategoryId']);

        auth()->user()->transactions()->create([
            'description' => $validated['incomeDescription'],
            'type' => 'income',
            'amount' => $validated['incomeAmount'],
            'account_id' => $validated['incomeAccountId'],
            'category_id' => $validated['incomeCategoryId'],
            'transaction_date' => $validated['incomeDate'],
        ]);

        $this->dispatch('transaction-created');
        $this->incomeModalOpen = false;
        $this->reset(['incomeDescription', 'incomeAmount', 'incomeAccountId', 'incomeCategoryId']);
        $this->incomeDate = now()->format('Y-m-d');

        Flux\Flux::toast(
            text: 'Einnahme erstellt',
            variant: 'success',
        );
    }

    public function createExpense(): void
    {
        $validated = $this->validate([
            'expenseDescription' => ['required', 'string', 'max:255'],
            'expenseAmount' => ['required', 'numeric', 'min:0.01'],
            'expenseAccountId' => ['required', 'exists:accounts,id'],
            'expenseCategoryId' => ['required', 'exists:categories,id'],
            'expenseDate' => ['required', 'date'],
        ]);

        auth()->user()->accounts()->findOrFail($validated['expenseAccountId']);
        auth()->user()->categories()->findOrFail($validated['expenseCategoryId']);

        auth()->user()->transactions()->create([
            'description' => $validated['expenseDescription'],
            'type' => 'expense',
            'amount' => $validated['expenseAmount'],
            'account_id' => $validated['expenseAccountId'],
            'category_id' => $validated['expenseCategoryId'],
            'transaction_date' => $validated['expenseDate'],
        ]);

        $this->dispatch('transaction-created');
        $this->expenseModalOpen = false;
        $this->reset(['expenseDescription', 'expenseAmount', 'expenseAccountId', 'expenseCategoryId']);
        $this->expenseDate = now()->format('Y-m-d');

        Flux\Flux::toast(
            text: 'Ausgabe erstellt',
            variant: 'success',
        );
    }
}; ?>

<div x-data="{
    init() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.code === 'Space') {
                e.preventDefault();
                $wire.toggleCommand();
                setTimeout(() => {
                    document.querySelector('[data-command-input] input')?.focus();
                }, 100);
            }
        });
    }
}">
    {{-- Command Palette --}}
    <div
        x-show="$wire.commandOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-start justify-center pt-20"
        @keydown.escape="$wire.toggleCommand()"
    >
        <div class="fixed inset-0 bg-black/50" @click="$wire.toggleCommand()"></div>

        <div
            class="relative w-full max-w-lg"
            x-show="$wire.commandOpen"
            x-transition
            x-init="$watch('$wire.commandOpen', value => { if (value) { setTimeout(() => $el.querySelector('input')?.focus(), 150) } })"
        >
            <flux:command>
                <flux:command.input
                    wire:model.live="search"
                    placeholder="Quick Actions..."
                    clearable
                    closable
                />

                <flux:command.items>
                    <flux:command.item
                        icon="arrow-trending-up"
                        wire:click="openIncomeModal"
                        kbd="⌘I"
                    >
                        Einnahme hinzufügen
                    </flux:command.item>

                    <flux:command.item
                        icon="arrow-trending-down"
                        wire:click="openExpenseModal"
                        kbd="⌘E"
                    >
                        Ausgabe hinzufügen
                    </flux:command.item>

                    <flux:command.item
                        icon="document-arrow-down"
                        wire:click="openImportModal"
                        kbd="⌘M"
                    >
                        Ausgaben importieren (KI)
                    </flux:command.item>
                </flux:command.items>
            </flux:command>
        </div>
    </div>

    {{-- Income Flyout Modal --}}
    <flux:modal wire:model="incomeModalOpen" variant="flyout">
        <form wire:submit="createIncome" class="space-y-6">
            <div>
                <flux:heading size="lg">Einnahme hinzufügen</flux:heading>
                <flux:text class="mt-2">Erfasse eine neue Einnahme</flux:text>
            </div>

            <flux:field>
                <flux:label>Beschreibung</flux:label>
                <flux:input
                    wire:model="incomeDescription"
                    placeholder="Was war das?"
                    autofocus
                />
                <flux:error name="incomeDescription" />
            </flux:field>

            <flux:field>
                <flux:label>Betrag</flux:label>
                <flux:input
                    wire:model="incomeAmount"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                />
                <flux:error name="incomeAmount" />
            </flux:field>

            <flux:field>
                <flux:label>Konto</flux:label>
                <flux:select wire:model="incomeAccountId" placeholder="Konto wählen..." variant="listbox">
                    @foreach($accounts as $account)
                        <flux:select.option value="{{ $account->id }}">
                            {{ $account->name }} ({{ number_format($account->balance, 2) }} {{ $account->currency }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="incomeAccountId" />
            </flux:field>

            <flux:field>
                <flux:label>Kategorie</flux:label>
                <flux:select wire:model="incomeCategoryId" placeholder="Kategorie wählen..." variant="listbox">
                    @foreach($incomeCategories as $category)
                        <flux:select.option value="{{ $category->id }}">
                            {{ $category->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="incomeCategoryId" />
            </flux:field>

            <flux:field>
                <flux:label>Datum</flux:label>
                <flux:input
                    wire:model="incomeDate"
                    type="date"
                />
                <flux:error name="incomeDate" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" type="button" wire:click="$set('incomeModalOpen', false)">
                    Abbrechen
                </flux:button>
                <flux:button
                    variant="primary"
                    type="submit"
                    icon="check"
                >
                    Einnahme erstellen
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Expense Flyout Modal --}}
    <flux:modal wire:model="expenseModalOpen" variant="flyout">
        <form wire:submit="createExpense" class="space-y-6">
            <div>
                <flux:heading size="lg">Ausgabe hinzufügen</flux:heading>
                <flux:text class="mt-2">Erfasse eine neue Ausgabe</flux:text>
            </div>

            <flux:field>
                <flux:label>Beschreibung</flux:label>
                <flux:input
                    wire:model="expenseDescription"
                    placeholder="Was war das?"
                    autofocus
                />
                <flux:error name="expenseDescription" />
            </flux:field>

            <flux:field>
                <flux:label>Betrag</flux:label>
                <flux:input
                    wire:model="expenseAmount"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                />
                <flux:error name="expenseAmount" />
            </flux:field>

            <flux:field>
                <flux:label>Konto</flux:label>
                <flux:select wire:model="expenseAccountId" placeholder="Konto wählen..." variant="listbox">
                    @foreach($accounts as $account)
                        <flux:select.option value="{{ $account->id }}">
                            {{ $account->name }} ({{ number_format($account->balance, 2) }} {{ $account->currency }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="expenseAccountId" />
            </flux:field>

            <flux:field>
                <flux:label>Kategorie</flux:label>
                <flux:select wire:model="expenseCategoryId" placeholder="Kategorie wählen..." variant="listbox">
                    @foreach($expenseCategories as $category)
                        <flux:select.option value="{{ $category->id }}">
                            {{ $category->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="expenseCategoryId" />
            </flux:field>

            <flux:field>
                <flux:label>Datum</flux:label>
                <flux:input
                    wire:model="expenseDate"
                    type="date"
                />
                <flux:error name="expenseDate" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" type="button" wire:click="$set('expenseModalOpen', false)">
                    Abbrechen
                </flux:button>
                <flux:button
                    variant="primary"
                    type="submit"
                    icon="check"
                >
                    Ausgabe erstellen
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Import Flyout Modal --}}
    <flux:modal wire:model="importModalOpen" variant="flyout" class="md:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Ausgaben importieren (KI)</flux:heading>
                <flux:text class="mt-2">Füge deinen Kontoauszug-Text ein und lass die KI die Transaktionen extrahieren</flux:text>
            </div>

            @if(empty($parsedTransactions))
                {{-- Step 1: Paste text --}}
                <flux:field>
                    <flux:label>Kontoauszug Text</flux:label>
                    <flux:textarea
                        wire:model="importText"
                        placeholder="Füge hier deinen Kontoauszug-Text ein..."
                        rows="10"
                        autofocus
                    />
                    <flux:error name="importText" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" type="button" wire:click="$set('importModalOpen', false)">
                        Abbrechen
                    </flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="parseImportText"
                        icon="sparkles"
                        :disabled="$importing"
                    >
                        <span wire:loading.remove wire:target="parseImportText">Mit KI analysieren</span>
                        <span wire:loading wire:target="parseImportText">Analysiere...</span>
                    </flux:button>
                </div>
            @else
                {{-- Step 2: Review and import --}}
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Gefundene Transaktionen ({{ count($parsedTransactions) }})</flux:label>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-y-auto">
                            @foreach($parsedTransactions as $index => $transaction)
                                <div class="p-3 space-y-3">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium truncate">{{ $transaction['description'] }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                                Original: {{ $transaction['date'] }}
                                            </div>
                                        </div>
                                        <div class="font-medium text-lg ml-4">{{ number_format($transaction['amount'], 2) }} €</div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <flux:field>
                                            <flux:label class="text-xs">Datum</flux:label>
                                            <flux:input
                                                wire:model="parsedTransactions.{{ $index }}.date"
                                                type="date"
                                                size="sm"
                                            />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label class="text-xs">Kategorie</flux:label>
                                            <flux:select
                                                wire:model="parsedTransactions.{{ $index }}.category_id"
                                                placeholder="Wählen..."
                                                variant="listbox"
                                                size="sm"
                                            >
                                                @foreach($expenseCategories as $category)
                                                    <flux:select.option value="{{ $category->id }}">
                                                        {{ $category->name }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>Standard-Konto (für alle Transaktionen)</flux:label>
                        <flux:select wire:model="importAccountId" placeholder="Konto wählen..." variant="listbox">
                            @foreach($accounts as $account)
                                <flux:select.option value="{{ $account->id }}">
                                    {{ $account->name }} ({{ number_format($account->balance, 2) }} {{ $account->currency }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="importAccountId" />
                    </flux:field>
                </div>

                <div class="flex gap-2">
                    <flux:button variant="ghost" type="button" wire:click="$set('parsedTransactions', [])">
                        Zurück
                    </flux:button>
                    <flux:spacer />
                    <flux:button variant="ghost" type="button" wire:click="$set('importModalOpen', false)">
                        Abbrechen
                    </flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="importTransactions"
                        icon="check"
                    >
                        {{ count($parsedTransactions) }} Ausgaben importieren
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
