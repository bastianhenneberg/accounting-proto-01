<?php

use App\Models\SupportedAsset;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';

    public $selectedType = '';

    public $showAddModal = false;

    public $showEditModal = false;

    public $editingAsset = null;

    public $showManualPriceForm = false;

    public $manualPriceAssetId = null;

    public $manualPrice = '';

    public $serviceStatus = [];

    public $checkingStatus = false;

    // Form fields
    public $asset_type = 'crypto';

    public $symbol = '';

    public $name = '';

    public $api_id = '';

    public $isin = '';

    public $price_url = '';

    public function with(): array
    {
        $query = SupportedAsset::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('symbol', 'like', '%'.$this->search.'%'))
            ->when($this->selectedType, fn ($q) => $q->where('asset_type', $this->selectedType))
            ->orderBy('asset_type')
            ->orderBy('name');

        return [
            'assets' => $query->paginate(20),
            'totalAssets' => SupportedAsset::count(),
            'cryptoCount' => SupportedAsset::crypto()->count(),
            'stockCount' => SupportedAsset::stocks()->count(),
            'etfCount' => SupportedAsset::etfs()->count(),
        ];
    }

    public function addAsset(): void
    {
        $validated = $this->validate([
            'asset_type' => ['required', Rule::in(['crypto', 'stock', 'etf', 'bond'])],
            'symbol' => ['required', 'string', 'max:20', 'unique:supported_assets,symbol'],
            'name' => ['required', 'string', 'max:100'],
            'api_id' => ['nullable', 'string', 'max:50'],
            'isin' => ['nullable', 'string', 'max:12'],
            'price_url' => ['nullable', 'url', 'max:500'],
        ]);

        $metadata = [];
        if ($this->isin) {
            $metadata['isin'] = $this->isin;
        }

        SupportedAsset::create([
            ...$validated,
            'metadata' => $metadata,
            'is_active' => true,
        ]);

        $this->reset(['asset_type', 'symbol', 'name', 'api_id', 'isin', 'price_url']);
        $this->showAddModal = false;

        \Flux\Flux::toast(
            heading: '✅ Asset Added',
            text: __('Asset added successfully.'),
            variant: 'success'
        );
    }

    public function editAsset($assetId): void
    {
        $asset = SupportedAsset::findOrFail($assetId);

        $this->editingAsset = $asset->id;
        $this->asset_type = $asset->asset_type;
        $this->symbol = $asset->symbol;
        $this->name = $asset->name;
        $this->api_id = $asset->api_id ?? '';
        $this->price_url = $asset->price_url ?? '';
        $this->isin = $asset->getIsin() ?? '';
        $this->showEditModal = true;
    }

    public function updateAsset(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'api_id' => ['nullable', 'string', 'max:50'],
            'price_url' => ['nullable', 'url', 'max:500'],
            'isin' => ['nullable', 'string', 'max:12'],
        ]);

        $asset = SupportedAsset::findOrFail($this->editingAsset);

        $metadata = $asset->metadata ?? [];
        if ($this->isin) {
            $metadata['isin'] = $this->isin;
        }

        $asset->update([
            'name' => $validated['name'],
            'api_id' => $validated['api_id'],
            'price_url' => $validated['price_url'],
            'metadata' => $metadata,
        ]);

        $this->reset(['asset_type', 'symbol', 'name', 'api_id', 'isin', 'price_url']);
        $this->showEditModal = false;
        $this->editingAsset = null;

        \Flux\Flux::toast(
            heading: '✅ Asset Updated',
            text: __('Asset updated successfully.'),
            variant: 'success'
        );
    }

    public function cancelEdit(): void
    {
        $this->reset(['asset_type', 'symbol', 'name', 'api_id', 'isin', 'price_url']);
        $this->showEditModal = false;
        $this->showAddModal = false;
        $this->editingAsset = null;
    }

    public function deleteAsset($assetId): void
    {
        $asset = SupportedAsset::findOrFail($assetId);
        $asset->delete();

        session()->flash('success', __('Asset deleted successfully.'));
    }

    public function toggleAsset($assetId): void
    {
        $asset = SupportedAsset::findOrFail($assetId);
        $asset->update(['is_active' => ! $asset->is_active]);

        session()->flash('success', __('Asset status updated.'));
    }

    public function showManualPriceModal($assetId): void
    {
        $asset = SupportedAsset::findOrFail($assetId);
        $this->manualPriceAssetId = $asset->id;
        $this->manualPrice = $asset->current_price ? (string) $asset->current_price : '';
        $this->showManualPriceForm = true;
    }

    public function saveManualPrice(): void
    {
        $validated = $this->validate([
            'manualPrice' => ['required', 'numeric', 'min:0.00000001', 'max:999999999'],
        ]);

        $asset = SupportedAsset::findOrFail($this->manualPriceAssetId);
        $asset->updatePrice((float) $validated['manualPrice'], 'manual');

        $this->reset(['showManualPriceForm', 'manualPriceAssetId', 'manualPrice']);

        \Flux\Flux::toast(
            heading: '✅ Manual Price Set',
            text: __(':name price updated to €:price', [
                'name' => $asset->name,
                'price' => number_format($asset->current_price, 2),
            ]),
            variant: 'success'
        );
    }

    public function cancelManualPrice(): void
    {
        $this->reset(['showManualPriceForm', 'manualPriceAssetId', 'manualPrice']);
    }

    public function checkServiceStatus(): void
    {
        $this->checkingStatus = true;
        $this->serviceStatus = \App\Services\SystemStatusService::checkAllServices();
        $this->checkingStatus = false;

        \Flux\Flux::toast(
            heading: '🔍 Service Status Check Complete',
            text: __('All services have been checked'),
            variant: 'info'
        );
    }

    public function updateAssetPrice($assetId): void
    {
        $asset = SupportedAsset::findOrFail($assetId);

        // Start feedback is now handled by JavaScript

        // Try AI extraction first, then API fallback
        $result = $asset->price_url ? $asset->updatePriceViaAI() : $asset->updatePriceViaAPI();

        if ($result['success']) {
            \Flux\Flux::toast(
                heading: '🤖 Price Updated',
                text: __(':name price updated to €:price', [
                    'name' => $asset->name,
                    'price' => number_format($result['price'], 2),
                ]),
                variant: 'success'
            );
        } else {
            \Flux\Flux::toast(
                heading: '❌ Price Update Failed',
                text: __(':name: :error', [
                    'name' => $asset->name,
                    'error' => $result['error'],
                ]),
                variant: 'danger'
            );
        }
    }

    public function updateAllPrices(): void
    {
        $assets = SupportedAsset::active()->get();
        $totalAssets = $assets->count();

        // Immediate start feedback
        \Flux\Flux::toast(
            heading: '🤖 Bulk Price Update Started',
            text: __('Updating :count assets... This will take :time minutes.', [
                'count' => $totalAssets,
                'time' => ceil($totalAssets / 4), // Estimate: ~4 assets per minute
            ]),
            variant: 'info',
            duration: 10000
        );

        $this->dispatch('bulk-update-started', count: $totalAssets);

        $updated = 0;
        $failed = 0;

        foreach ($assets as $index => $asset) {
            $this->dispatch('bulk-update-progress',
                current: $index + 1,
                total: $totalAssets,
                asset: $asset->name
            );

            $result = $asset->price_url ? $asset->updatePriceViaAI() : $asset->updatePriceViaAPI();

            if ($result['success']) {
                $updated++;
                $this->dispatch('asset-price-updated',
                    asset: $asset->name,
                    price: $result['price']
                );
            } else {
                $failed++;
                $this->dispatch('asset-price-failed',
                    asset: $asset->name,
                    error: $result['error']
                );
            }

            // Rate limiting for APIs
            sleep(1);
        }

        \Flux\Flux::toast(
            heading: '📊 Bulk Price Update Complete',
            text: __('✅ Updated :updated assets, ❌ :failed failed', [
                'updated' => $updated,
                'failed' => $failed,
            ]),
            variant: $failed > 0 ? 'warning' : 'success'
        );

        $this->dispatch('bulk-update-complete', updated: $updated, failed: $failed);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedType']);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Asset Management') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Manage supported cryptocurrencies, stocks, and ETFs') }}</p>
        </div>
        <div class="flex space-x-2">
            <flux:button wire:click="checkServiceStatus" variant="ghost" icon="signal" :disabled="$checkingStatus">
                {{ $checkingStatus ? __('Checking...') : __('Service Status') }}
            </flux:button>
            <flux:button wire:click="updateAllPrices" variant="outline" icon="arrow-path">
                {{ __('Update All Prices') }}
            </flux:button>
            <flux:button wire:click="$set('showAddModal', true)" variant="primary" icon="plus">
                {{ __('Add Asset') }}
            </flux:button>
        </div>
    </div>

    {{-- Service Status Display --}}
    @if(!empty($serviceStatus))
        <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Service Status') }}</h3>
            <div class="grid gap-3 md:grid-cols-3">
                {{-- Puppeteer Status --}}
                <div class="flex items-center p-3 rounded-lg {{ $serviceStatus['puppeteer']['status'] === 'ok' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <div class="flex-shrink-0">
                        @if($serviceStatus['puppeteer']['status'] === 'ok')
                            <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium {{ $serviceStatus['puppeteer']['status'] === 'ok' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                            Puppeteer/Browsershot
                        </p>
                        <p class="text-xs {{ $serviceStatus['puppeteer']['status'] === 'ok' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $serviceStatus['puppeteer']['message'] }}
                        </p>
                    </div>
                </div>

                {{-- Claude AI Status --}}
                <div class="flex items-center p-3 rounded-lg {{ $serviceStatus['claude_ai']['status'] === 'ok' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <div class="flex-shrink-0">
                        @if($serviceStatus['claude_ai']['status'] === 'ok')
                            <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium {{ $serviceStatus['claude_ai']['status'] === 'ok' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                            Claude AI Vision
                        </p>
                        <p class="text-xs {{ $serviceStatus['claude_ai']['status'] === 'ok' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $serviceStatus['claude_ai']['message'] }}
                        </p>
                    </div>
                </div>

                {{-- Crypto API Status --}}
                <div class="flex items-center p-3 rounded-lg {{ $serviceStatus['crypto_api']['status'] === 'ok' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <div class="flex-shrink-0">
                        @if($serviceStatus['crypto_api']['status'] === 'ok')
                            <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium {{ $serviceStatus['crypto_api']['status'] === 'ok' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                            CoinGecko API
                        </p>
                        <p class="text-xs {{ $serviceStatus['crypto_api']['status'] === 'ok' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $serviceStatus['crypto_api']['message'] }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Last checked:') }} {{ $serviceStatus['timestamp'] ?? now() }}
            </div>
        </div>
    @endif

    {{-- URL Recommendations Info --}}
    <div class="bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/50 p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <flux:icon.information-circle class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    {{ __('🤖 Best URLs for AI Price Extraction') }}
                </h3>
                <div class="grid gap-3 md:grid-cols-2 text-xs">
                    {{-- German Stocks --}}
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded p-3">
                        <p class="font-medium text-gray-900 dark:text-white mb-2">🇩🇪 {{ __('Deutsche Aktien') }}</p>
                        <code class="block bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded text-xs break-all">
                            https://www.finanzen.net/aktien/[name]-aktie
                        </code>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('z.B. rheinmetall-aktie, bmw-aktie, sap-aktie') }}
                        </p>
                    </div>

                    {{-- US Stocks --}}
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded p-3">
                        <p class="font-medium text-gray-900 dark:text-white mb-2">🇺🇸 {{ __('US Stocks') }}</p>
                        <code class="block bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded text-xs break-all">
                            https://finviz.com/quote.ashx?t=[SYMBOL]
                        </code>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('z.B. TSLA, AAPL, MSFT, NVDA') }}
                        </p>
                    </div>

                    {{-- ETFs --}}
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded p-3">
                        <p class="font-medium text-gray-900 dark:text-white mb-2">📊 {{ __('ETFs') }}</p>
                        <code class="block bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded text-xs break-all">
                            https://extraetf.com/de/etf-profile/[ISIN]
                        </code>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('oder justetf.com, onvista.de') }}
                        </p>
                    </div>

                    {{-- Warning --}}
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded p-3">
                        <p class="font-medium text-red-900 dark:text-red-200 mb-2">⚠️ {{ __('Nicht empfohlen') }}</p>
                        <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                            <li>❌ Yahoo Finance (zu dynamisch)</li>
                            <li>❌ Google Finance (Cookie-Warnung)</li>
                            <li>❌ MarketWatch (komplexes Layout)</li>
                        </ul>
                    </div>
                </div>
            </div>
            <button
                onclick="this.closest('.bg-gradient-to-r').style.display='none'"
                class="flex-shrink-0 ml-2 text-blue-400 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-200">
                <flux:icon.x-mark class="w-5 h-5" />
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.squares-plus class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Assets') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $totalAssets }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-full dark:bg-orange-900/20 mr-3">
                    <span class="text-orange-600 dark:text-orange-400 font-bold">₿</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Cryptocurrencies') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $cryptoCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full dark:bg-blue-900/20 mr-3">
                    <flux:icon.building-office-2 class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Stocks') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stockCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 p-4 dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full dark:bg-green-900/20 mr-3">
                    <flux:icon.chart-bar class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('ETFs') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $etfCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <flux:input wire:model.live="search" :placeholder="__('Search assets...')" />
            </div>
            <div>
                <flux:select wire:model.live="selectedType" :placeholder="__('All Types')">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="crypto">{{ __('Cryptocurrency') }}</option>
                    <option value="stock">{{ __('Stock') }}</option>
                    <option value="etf">{{ __('ETF') }}</option>
                    <option value="bond">{{ __('Bond') }}</option>
                </flux:select>
            </div>
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    {{ __('Clear Filters') }}
                </flux:button>
            </div>
        </div>
    </div>


    {{-- Assets List --}}
    <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
        @if($assets->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($assets as $asset)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-2 rounded-full {{ $asset->asset_type === 'crypto' ? 'bg-orange-100 dark:bg-orange-900/20' : ($asset->asset_type === 'stock' ? 'bg-blue-100 dark:bg-blue-900/20' : 'bg-green-100 dark:bg-green-900/20') }}">
                                    @if($asset->asset_type === 'crypto')
                                        <span class="text-orange-600 dark:text-orange-400 font-bold">₿</span>
                                    @elseif($asset->asset_type === 'stock')
                                        <flux:icon.building-office-2 class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    @else
                                        <flux:icon.chart-bar class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    @endif
                                </div>

                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $asset->name }}</h3>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="uppercase font-medium">{{ $asset->symbol }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ $asset->asset_type }}</span>
                                        @if($asset->getIsin())
                                            <span>•</span>
                                            <span>ISIN: {{ $asset->getIsin() }}</span>
                                        @endif
                                    </div>
                                    @if($asset->current_price)
                                        <div class="flex items-center space-x-2 text-sm mt-1">
                                            <span class="font-medium text-gray-900 dark:text-white">€{{ number_format($asset->current_price, 2) }}</span>
                                            <span class="text-xs px-2 py-1 rounded-full {{ $asset->price_source === 'ai_extracted' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400' : ($asset->price_source === 'api' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                                {{ $asset->price_source === 'ai_extracted' ? '🤖 AI' : ($asset->price_source === 'api' ? 'API' : 'Manual') }}
                                            </span>
                                            @if($asset->last_price_update)
                                                <span class="text-xs text-gray-400">{{ $asset->last_price_update->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-400 mt-1">{{ __('No price data') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $asset->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $asset->is_active ? __('Active') : __('Inactive') }}
                                </span>

                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item
                                            wire:click="showManualPriceModal({{ $asset->id }})"
                                            icon="pencil-square">
                                            {{ __('✏️ Set Manual Price') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            onclick="startAssetUpdate({{ $asset->id }}, '{{ $asset->name }}', '{{ $asset->price_url ? 'AI' : 'API' }}')"
                                            icon="camera">
                                            {{ $asset->price_url ? __('🤖 AI Update Price') : __('📊 API Update Price') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="editAsset({{ $asset->id }})"
                                            icon="pencil">
                                            {{ __('Edit Asset') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="toggleAsset({{ $asset->id }})"
                                            icon="{{ $asset->is_active ? 'pause' : 'play' }}">
                                            {{ $asset->is_active ? __('Deactivate') : __('Activate') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="deleteAsset({{ $asset->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this asset?') }}"
                                            icon="trash"
                                            variant="danger">
                                            {{ __('Delete Asset') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($assets->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $assets->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <flux:icon.squares-plus class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('No assets found') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('Add assets to make them available for holdings') }}</p>
                <flux:button wire:click="$set('showAddForm', true)" variant="primary" icon="plus">
                    {{ __('Add Your First Asset') }}
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Add Asset Modal --}}
    <flux:modal wire:model.self="showAddModal" variant="flyout" class="w-full max-w-2xl">
        <form wire:submit="addAsset" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add New Asset') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Add a new cryptocurrency, stock, ETF, or bond to the system.') }}
                </flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:select wire:model="asset_type" :label="__('Asset Type')" required>
                        <option value="crypto">{{ __('Cryptocurrency') }}</option>
                        <option value="stock">{{ __('Stock') }}</option>
                        <option value="etf">{{ __('ETF') }}</option>
                        <option value="bond">{{ __('Bond') }}</option>
                    </flux:select>
                </div>
                <div>
                    <flux:input wire:model="symbol" :label="__('Symbol')" :placeholder="__('e.g., LINK, AAPL')" required />
                </div>
            </div>

            <div>
                <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g., Chainlink')" required />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:input wire:model="api_id" :label="__('API ID (Optional)')" :placeholder="__('CoinGecko ID or API symbol')" />
                </div>
                <div>
                    <flux:input wire:model="isin" :label="__('ISIN (Optional)')" :placeholder="__('e.g., IE00B4L5Y983')" />
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ __('AI Price Extraction (Optional)') }}</h4>
                <flux:input wire:model="price_url" :label="__('Price URL')" :placeholder="__('e.g., https://extraetf.com/de/etf-profile/...')" />
                <flux:text class="mt-2 text-xs">
                    {{ __('Configure a URL for automatic price extraction using Puppeteer + AI vision.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Add Asset') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Asset Modal --}}
    <flux:modal wire:model.self="showEditModal" variant="flyout" class="w-full max-w-2xl">
        <form wire:submit="updateAsset" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Asset') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Update asset information and configuration.') }}
                </flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:input :label="__('Asset Type')" value="{{ __(ucfirst($asset_type)) }}" readonly />
                </div>
                <div>
                    <flux:input :label="__('Symbol')" value="{{ $symbol }}" readonly />
                </div>
            </div>

            <div>
                <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g., Chainlink')" required />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:input wire:model="api_id" :label="__('API ID (Optional)')" :placeholder="__('CoinGecko ID or API symbol')" />
                </div>
                <div>
                    <flux:input wire:model="isin" :label="__('ISIN (Optional)')" :placeholder="__('e.g., IE00B4L5Y983')" />
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ __('AI Price Extraction (Optional)') }}</h4>
                <flux:input wire:model="price_url" :label="__('Price URL')" :placeholder="__('e.g., https://extraetf.com/de/etf-profile/...')" />
                <flux:text class="mt-2 text-xs">
                    {{ __('Configure a URL for automatic price extraction using Puppeteer + AI vision.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Update Asset') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Manual Price Modal --}}
    <flux:modal wire:model.self="showManualPriceForm" class="min-w-[22rem]">
        <form wire:submit="saveManualPrice" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Set Manual Price') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('This will manually set the price and mark it as manually updated.') }}
                </flux:text>
            </div>

            <div>
                <flux:input
                    wire:model="manualPrice"
                    :label="__('Price (EUR)')"
                    type="number"
                    step="0.00000001"
                    min="0"
                    :placeholder="__('e.g., 10.45')"
                    required
                    autofocus />
                @error('manualPrice')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Save Price') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            // Individual asset update events
            Livewire.on('asset-update-started', (data) => {
                console.log(`🤖 Individual Update Started: ${data.asset}`);
                console.time(`Asset-Update-${data.asset}`);
            });

            // Bulk update events
            Livewire.on('bulk-update-started', (data) => {
                console.log(`🚀 Bulk Price Update Started: ${data.count} assets`);
                console.time('Bulk-Price-Update');
            });

            Livewire.on('bulk-update-progress', (data) => {
                console.log(`📊 Progress: ${data.current}/${data.total} - Processing ${data.asset}...`);
            });

            Livewire.on('asset-price-updated', (data) => {
                console.log(`✅ ${data.asset}: €${data.price}`);
            });

            Livewire.on('asset-price-failed', (data) => {
                console.error(`❌ ${data.asset}: ${data.error}`);
            });

            Livewire.on('bulk-update-complete', (data) => {
                console.timeEnd('Bulk-Price-Update');
                console.log(`🎉 Bulk Update Complete: ${data.updated} updated, ${data.failed} failed`);
            });
        });

        function startAssetUpdate(assetId, assetName, updateType) {
            // Prevent double-clicks
            const timerId = `Asset-Update-${assetName}`;

            // Show immediate toast
            if (window.Flux) {
                window.Flux.toast({
                    heading: `${updateType === 'AI' ? '🤖' : '📊'} Price Update Started`,
                    text: `Updating ${assetName}... Please wait.`,
                    variant: 'info',
                    duration: 10000
                });
            }

            console.log(`${updateType === 'AI' ? '🤖' : '📊'} Individual Update Started: ${assetName}`);

            // Safe timer handling
            try {
                console.time(timerId);
            } catch (e) {
                // Timer already exists, ignore
            }

            // Start the Livewire update
            @this.call('updateAssetPrice', assetId);
        }
    </script>
</div>