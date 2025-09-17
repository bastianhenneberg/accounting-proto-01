<?php

use App\Models\SupportedAsset;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedType = '';
    public $showAddForm = false;
    public $editingAsset = null;

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
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('symbol', 'like', '%' . $this->search . '%'))
            ->when($this->selectedType, fn($q) => $q->where('asset_type', $this->selectedType))
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
        $this->showAddForm = false;

        session()->flash('success', __('Asset added successfully.'));
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
        $this->showAddForm = true;
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
        $this->showAddForm = false;
        $this->editingAsset = null;

        session()->flash('success', __('Asset updated successfully.'));
    }

    public function cancelEdit(): void
    {
        $this->reset(['asset_type', 'symbol', 'name', 'api_id', 'isin', 'price_url']);
        $this->showAddForm = false;
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
        $asset->update(['is_active' => !$asset->is_active]);

        session()->flash('success', __('Asset status updated.'));
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
                    'price' => number_format($result['price'], 2)
                ]),
                variant: 'success'
            );
        } else {
            \Flux\Flux::toast(
                heading: '❌ Price Update Failed',
                text: __(':name: :error', [
                    'name' => $asset->name,
                    'error' => $result['error']
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
                'time' => ceil($totalAssets / 4) // Estimate: ~4 assets per minute
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
                'failed' => $failed
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

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedSelectedType(): void { $this->resetPage(); }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Asset Management') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Manage supported cryptocurrencies, stocks, and ETFs') }}</p>
        </div>
        <div class="flex space-x-2">
            <flux:button wire:click="updateAllPrices" variant="outline" icon="arrow-path">
                {{ __('Update All Prices') }}
            </flux:button>
            <flux:button wire:click="$toggle('showAddForm')" variant="primary" icon="plus">
                {{ __('Add Asset') }}
            </flux:button>
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

    {{-- Add Asset Form --}}
    @if($showAddForm)
        <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $editingAsset ? __('Edit Asset') : __('Add New Asset') }}
            </h3>

            <form wire:submit="{{ $editingAsset ? 'updateAsset' : 'addAsset' }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        @if($editingAsset)
                            <flux:input :label="__('Asset Type')" value="{{ __(ucfirst($asset_type)) }}" readonly />
                        @else
                            <flux:select wire:model="asset_type" :label="__('Asset Type')" required>
                                <option value="crypto">{{ __('Cryptocurrency') }}</option>
                                <option value="stock">{{ __('Stock') }}</option>
                                <option value="etf">{{ __('ETF') }}</option>
                                <option value="bond">{{ __('Bond') }}</option>
                            </flux:select>
                        @endif
                    </div>
                    <div>
                        @if($editingAsset)
                            <flux:input :label="__('Symbol')" value="{{ $symbol }}" readonly />
                        @else
                            <flux:input wire:model="symbol" :label="__('Symbol')" :placeholder="__('e.g., MSCI_WORLD, AAPL')" required />
                        @endif
                    </div>
                </div>

                <div>
                    <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g., MSCI World ETF')" required />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="api_id" :label="__('API ID (Optional)')" :placeholder="__('CoinGecko ID or Yahoo symbol')" />
                    </div>
                    <div>
                        <flux:input wire:model="isin" :label="__('ISIN (Optional)')" :placeholder="__('e.g., IE00B4L5Y983')" />
                    </div>
                </div>

                {{-- AI Price Extraction Configuration --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ __('AI Price Extraction (Optional)') }}</h4>
                    <div class="space-y-4">
                        <div>
                            <flux:input wire:model="price_url" :label="__('Price URL')" :placeholder="__('e.g., https://extraetf.com/de/etf-profile/IE00B4K48X80')" />
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Configure a URL for automatic price extraction using Puppeteer + AI vision. The system will take a screenshot and extract the price automatically.') }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <flux:button wire:click="cancelEdit" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingAsset ? __('Update Asset') : __('Add Asset') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

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