<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SupportedAsset extends Model
{
    protected $fillable = [
        'asset_type',
        'symbol',
        'name',
        'api_id',
        'price_url',
        'current_price',
        'last_price_update',
        'price_source',
        'currency',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:8',
            'last_price_update' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('asset_type', $type);
    }

    public function scopeCrypto(Builder $query): Builder
    {
        return $query->byType('crypto');
    }

    public function scopeStocks(Builder $query): Builder
    {
        return $query->byType('stock');
    }

    public function scopeEtfs(Builder $query): Builder
    {
        return $query->byType('etf');
    }

    public static function getAssetsByType(string $type): array
    {
        return self::active()
            ->byType($type)
            ->orderBy('name')
            ->pluck('name', 'symbol')
            ->toArray();
    }

    public static function getAllAssetsByType(): array
    {
        $assets = self::active()->orderBy('asset_type')->orderBy('name')->get();

        return $assets->groupBy('asset_type')->map(function ($group) {
            return $group->pluck('name', 'symbol')->toArray();
        })->toArray();
    }

    public function isCrypto(): bool
    {
        return $this->asset_type === 'crypto';
    }

    public function isStock(): bool
    {
        return $this->asset_type === 'stock';
    }

    public function isEtf(): bool
    {
        return $this->asset_type === 'etf';
    }

    public function getApiId(): string
    {
        return $this->api_id ?: strtolower($this->symbol);
    }

    public function getIsin(): ?string
    {
        return $this->metadata['isin'] ?? null;
    }

    public function getWkn(): ?string
    {
        return $this->metadata['wkn'] ?? null;
    }

    public function updatePrice(float $price, string $source = 'manual'): void
    {
        $this->update([
            'current_price' => $price,
            'last_price_update' => now(),
            'price_source' => $source,
        ]);

        // Update all holdings that use this asset
        $this->updateAllHoldings();
    }

    public function updatePriceViaAI(): array
    {
        if (!$this->price_url) {
            return ['success' => false, 'error' => 'No price URL configured'];
        }

        $result = \App\Services\PuppeteerPriceService::extractPriceFromUrl($this->price_url);

        if ($result['success']) {
            $this->updatePrice($result['price'], 'ai_extracted');
            return ['success' => true, 'price' => $result['price']];
        }

        return $result;
    }

    public function updatePriceViaAPI(): array
    {
        if ($this->asset_type === 'crypto') {
            // Use working getPrice method for crypto
            $price = \App\Services\CryptoPriceService::getPrice($this->symbol);

            if ($price > 0) {
                $this->updatePrice($price, 'api');
                return ['success' => true, 'price' => $price];
            }
        } elseif ($this->asset_type === 'stock') {
            // Alpha Vantage for US stocks
            $result = \App\Services\AssetPriceService::getStockPrice($this->symbol);

            if (isset($result['price']) && $result['price'] > 0) {
                $this->updatePrice($result['price'], 'api');
                return ['success' => true, 'price' => $result['price']];
            }
        }

        return ['success' => false, 'error' => 'API returned no valid price'];
    }

    private function updateAllHoldings(): void
    {
        $holdings = \App\Models\Holding::where('symbol', $this->symbol)->get();

        foreach ($holdings as $holding) {
            $marketValue = $holding->quantity * $this->current_price;
            $holding->update([
                'current_price' => $this->current_price,
                'market_value' => $marketValue,
                'unrealized_pnl' => $marketValue - $holding->total_invested,
                'last_price_update' => now(),
            ]);
        }
    }

    public function hasRecentPrice(): bool
    {
        return $this->last_price_update && $this->last_price_update->isAfter(now()->subDay());
    }

    public function needsPriceUpdate(): bool
    {
        return !$this->current_price || !$this->hasRecentPrice();
    }
}
