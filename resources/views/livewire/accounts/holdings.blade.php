<?php

use App\Models\Account;
use App\Models\Holding;
use App\Services\AssetPriceService;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public Account $account;
    public $showAddForm = false;
    public $editingHolding = null;
    public $aiExtractionInProgress = false;

    // Form fields
    public $asset_type = 'crypto';
    public $symbol = '';
    public $name = '';
    public $quantity = '';
    public $average_cost = '';

    public function mount(Account $account): void
    {
        $this->account = $account;
    }

    public function addHolding(): void
    {
        $validated = $this->validate([
            'asset_type' => ['required', Rule::in(['crypto', 'stock', 'etf', 'bond'])],
            'symbol' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'average_cost' => ['required', 'numeric', 'min:0.01'],
        ]);

        $totalInvested = $validated['quantity'] * $validated['average_cost'];
        $currentPrice = $this->getCurrentPrice($validated['symbol'], $validated['asset_type']);
        $marketValue = $validated['quantity'] * $currentPrice;

        $this->account->holdings()->create([
            ...$validated,
            'current_price' => $currentPrice,
            'market_value' => $marketValue,
            'total_invested' => $totalInvested,
            'unrealized_pnl' => $marketValue - $totalInvested,
            'last_price_update' => now(),
        ]);

        $this->reset(['asset_type', 'symbol', 'name', 'quantity', 'average_cost']);
        $this->showAddForm = false;

        session()->flash('success', __('Holding added successfully.'));
    }

    public function editHolding($holdingId): void
    {
        $holding = $this->account->holdings()->findOrFail($holdingId);

        $this->editingHolding = $holding->id;
        $this->asset_type = $holding->asset_type;
        $this->symbol = $holding->symbol;
        $this->name = $holding->name;
        $this->quantity = $holding->quantity;
        $this->average_cost = $holding->average_cost;
        $this->showAddForm = true;
    }

    public function updateHolding(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'average_cost' => ['required', 'numeric', 'min:0.01'],
        ]);

        $holding = $this->account->holdings()->findOrFail($this->editingHolding);

        $totalInvested = $validated['quantity'] * $validated['average_cost'];
        $currentPrice = $holding->current_price ?: $this->getCurrentPrice($holding->symbol, $holding->asset_type);
        $marketValue = $validated['quantity'] * $currentPrice;

        $holding->update([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'],
            'average_cost' => $validated['average_cost'],
            'total_invested' => $totalInvested,
            'market_value' => $marketValue,
            'unrealized_pnl' => $marketValue - $totalInvested,
        ]);

        $this->reset(['asset_type', 'symbol', 'name', 'quantity', 'average_cost']);
        $this->showAddForm = false;
        $this->editingHolding = null;

        session()->flash('success', __('Holding updated successfully.'));
    }

    public function cancelEdit(): void
    {
        $this->reset(['asset_type', 'symbol', 'name', 'quantity', 'average_cost']);
        $this->showAddForm = false;
        $this->editingHolding = null;
    }

    public function deleteHolding($holdingId): void
    {
        $holding = $this->account->holdings()->findOrFail($holdingId);
        $holding->delete();

        session()->flash('success', __('Holding deleted successfully.'));
    }

    public function updatePriceManually($holdingId, $newPrice): void
    {
        $holding = $this->account->holdings()->findOrFail($holdingId);

        \App\Services\AssetPriceService::setManualPrice($holding, $newPrice);

        session()->flash('success', __('Price updated successfully.'));
    }

    public function testAIExtraction($holdingId): void
    {
        try {
            $this->aiExtractionInProgress = true;

            $holding = $this->account->holdings()->findOrFail($holdingId);

            // Get the asset to check URL config
            $asset = \App\Models\SupportedAsset::where('symbol', $holding->symbol)->first();

            if (!$asset || !$asset->price_url) {
                \Flux\Flux::toast(__('No price URL configured for this asset.'), variant: 'danger');
                $this->aiExtractionInProgress = false;
                return;
            }

            // AI extraction feedback is now handled by JavaScript

            // Test Puppeteer + AI price extraction
            $result = \App\Services\PuppeteerPriceService::extractPriceFromUrl($asset->price_url);

            if ($result['success']) {
                $newPrice = $result['price'];
                $oldValue = $holding->market_value;

                // Update holding with AI-extracted price
                \App\Services\AssetPriceService::setManualPrice($holding, $newPrice);

                $newValue = $holding->fresh()->market_value;

                // Console log for completion
                $this->dispatch('ai-extraction-complete',
                    symbol: $holding->symbol,
                    price: $newPrice,
                    oldValue: $oldValue,
                    newValue: $newValue
                );

                \Flux\Flux::toast(
                    heading: '🤖 AI Extraction Successful!',
                    text: __('Price: €:price → Market Value: €:old → €:new (from :url)', [
                        'price' => number_format($newPrice, 2),
                        'old' => number_format($oldValue, 2),
                        'new' => number_format($newValue, 2),
                        'url' => parse_url($asset->price_url, PHP_URL_HOST)
                    ]),
                    variant: 'success'
                );
            } else {
                \Flux\Flux::toast(
                    heading: '🤖 AI Extraction Failed',
                    text: __('Error: :error', ['error' => $result['error']]),
                    variant: 'danger'
                );
            }

        } catch (\Exception $e) {
            \Flux\Flux::toast(__('AI extraction error: :error', ['error' => $e->getMessage()]), variant: 'danger');
        } finally {
            $this->aiExtractionInProgress = false;
        }
    }

    private function testScrapingForAsset(\App\Models\SupportedAsset $asset): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($asset->price_url);

            if (!$response->successful()) {
                return ['success' => false, 'error' => "HTTP {$response->status()}"];
            }

            $html = $response->body();

            // Try multiple price patterns
            $patterns = [
                '/(\d+[.,]\d+)\s*€/',           // 123.45 €
                '/€\s*(\d+[.,]\d+)/',           // € 123.45
                '/(\d+[.,]\d+)\s*EUR/',         // 123.45 EUR
                '/EUR\s*(\d+[.,]\d+)/',         // EUR 123.45
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $price = (float) str_replace(',', '.', $matches[1]);

                    if ($price > 0 && $price < 10000) { // Reasonable price range
                        return ['success' => true, 'price' => $price, 'pattern' => $pattern];
                    }
                }
            }

            return ['success' => false, 'error' => 'No price pattern found in HTML'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getCurrentPrice(string $symbol, string $assetType): float
    {
        return match($assetType) {
            'crypto' => \App\Services\CryptoPriceService::getPrice($symbol),
            default => 100.0
        };
    }

    public function getSupportedAssets(): array
    {
        return \App\Models\SupportedAsset::getAssetsByType($this->asset_type);
    }

    public function updatedAssetType(): void
    {
        $this->reset(['symbol', 'name']);
    }

    public function updatedSymbol(): void
    {
        $asset = \App\Models\SupportedAsset::where('symbol', $this->symbol)->first();
        $this->name = $asset?->name ?? '';
    }
}; ?>

<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $account->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your investment holdings') }}</p>
            </div>
            <flux:button wire:click="$toggle('showAddForm')" variant="primary" icon="plus">
                {{ __('Add Holding') }}
            </flux:button>
        </div>

        {{-- Add Holding Form --}}
        @if($showAddForm)
            <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingHolding ? __('Edit Holding') : __('Add New Holding') }}
                </h3>

                <form wire:submit="{{ $editingHolding ? 'updateHolding' : 'addHolding' }}" class="space-y-4">
                    @if($editingHolding)
                        {{-- Edit mode - readonly asset type and symbol --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <flux:input :label="__('Asset Type')" value="{{ __(ucfirst($asset_type)) }}" readonly />
                            </div>
                            <div>
                                <flux:input :label="__('Asset')" value="{{ $symbol }}" readonly />
                            </div>
                        </div>
                    @else
                        {{-- Add mode - selectable asset type and symbol --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <flux:select wire:model.live="asset_type" :label="__('Asset Type')" required>
                                    <option value="crypto">{{ __('Cryptocurrency') }}</option>
                                    <option value="stock">{{ __('Stock') }}</option>
                                    <option value="etf">{{ __('ETF') }}</option>
                                </flux:select>
                            </div>
                            <div>
                                <flux:select wire:model.live="symbol" :label="__('Asset')" required>
                                    <option value="">{{ __('Select Asset') }}</option>
                                    @foreach($this->getSupportedAssets() as $sym => $assetName)
                                        <option value="{{ $sym }}">{{ $assetName }} ({{ $sym }})</option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>
                    @endif

                    <div>
                        <flux:input wire:model="name" :label="__('Name')" required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:input
                                wire:model="quantity"
                                :label="__('Quantity')"
                                type="number"
                                step="0.00000001"
                                min="0"
                                required
                            />
                        </div>
                        <div>
                            <flux:input
                                wire:model="average_cost"
                                :label="__('Average Cost (EUR)')"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <flux:button wire:click="cancelEdit" variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ $editingHolding ? __('Update Holding') : __('Add Holding') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Holdings List --}}
        <div class="space-y-4">
            @forelse($account->holdings as $holding)
                <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/20">
                                <span class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                    {{ $holding->symbol === 'BTC' ? '₿' : ($holding->symbol === 'ETH' ? 'Ξ' : '🪙') }}
                                </span>
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $holding->name }}</h3>
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="uppercase font-medium">{{ $holding->symbol }}</span>
                                    <span>•</span>
                                    <span>{{ number_format((float)$holding->quantity, 8) }} {{ __('units') }}</span>
                                    @php $asset = \App\Models\SupportedAsset::where('symbol', $holding->symbol)->first(); @endphp
                                    @if($asset && $asset->current_price)
                                        <span>•</span>
                                        <span class="font-medium">€{{ number_format($asset->current_price, 2) }}/unit</span>
                                        <span class="text-xs px-1 py-0.5 rounded {{ $asset->price_source === 'ai_extracted' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' }}">
                                            {{ $asset->price_source === 'ai_extracted' ? '🤖' : '📊' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                €{{ number_format((float)$holding->market_value, 2) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Invested') }}: €{{ number_format((float)$holding->total_invested, 2) }}
                            </p>

                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="editHolding({{ $holding->id }})"
                                        icon="pencil">
                                        {{ __('Edit Holding') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        onclick="updatePricePrompt({{ $holding->id }}, '{{ $holding->symbol }}')"
                                        icon="currency-euro">
                                        {{ __('Manual Price Override') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="deleteHolding({{ $holding->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this holding?') }}"
                                        icon="trash"
                                        variant="danger">
                                        {{ __('Delete Holding') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg border border-neutral-200 p-12 text-center dark:bg-neutral-800 dark:border-neutral-700">
                    <flux:icon.chart-pie class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('No holdings yet') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('Add your first cryptocurrency, stock, or ETF to start tracking') }}</p>
                    <flux:button wire:click="$set('showAddForm', true)" variant="primary" icon="plus">
                        {{ __('Add Your First Holding') }}
                    </flux:button>
                </div>
            @endforelse
        </div>
    </div>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif

    @if(session('info'))
        <flux:toast variant="info">{{ session('info') }}</flux:toast>
    @endif

    <script>
        function updatePricePrompt(holdingId, symbol) {
            const newPrice = prompt(`Enter current price for ${symbol} (in EUR):`, '');
            if (newPrice && !isNaN(newPrice) && parseFloat(newPrice) > 0) {
                @this.call('updatePriceManually', holdingId, parseFloat(newPrice));
            }
        }

        function startAIExtraction(holdingId, symbol) {
            // Show start toast immediately via JavaScript
            if (window.Flux) {
                window.Flux.toast({
                    heading: '🤖 AI Extraction Started',
                    text: `Analyzing ${symbol} price... Please wait 15 seconds.`,
                    variant: 'info',
                    duration: 15000 // Show for full duration
                });
            }

            console.log(`🤖 AI Extraction started for ${symbol}...`);
            console.time(`AI-Extraction-${symbol}`);

            // Start the Livewire AI extraction
            @this.call('testAIExtraction', holdingId);
        }

        // Listen for AI extraction events
        document.addEventListener('livewire:init', () => {
            Livewire.on('ai-extraction-complete', (data) => {
                console.log('🎉 AI Extraction Complete:', data);
                console.log(`✅ ${data.symbol}: €${data.price} → Market Value €${data.newValue}`);
            });

            Livewire.on('ai-extraction-failed', (data) => {
                console.error('❌ AI Extraction Failed:', data);
            });

            // Toast notifications
            Livewire.on('show-toast', (data) => {
                console.log(`📢 Toast: [${data.type}] ${data.message}`);

                // Create toast element dynamically (since flux:toast might not work with events)
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                    data.type === 'success' ? 'bg-green-500 text-white' :
                    data.type === 'error' ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                toast.textContent = data.message;

                document.body.appendChild(toast);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    toast.remove();
                }, 5000);
            });
        });
    </script>
</div>