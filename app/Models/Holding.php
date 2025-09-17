<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Holding extends Model
{
    protected $fillable = [
        'account_id',
        'asset_type',
        'symbol',
        'name',
        'quantity',
        'average_cost',
        'current_price',
        'market_value',
        'total_invested',
        'unrealized_pnl',
        'last_price_update',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'average_cost' => 'decimal:8',
            'current_price' => 'decimal:8',
            'market_value' => 'decimal:2',
            'total_invested' => 'decimal:2',
            'unrealized_pnl' => 'decimal:2',
            'last_price_update' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeCrypto(Builder $query): Builder
    {
        return $query->where('asset_type', 'crypto');
    }

    public function scopeStocks(Builder $query): Builder
    {
        return $query->where('asset_type', 'stock');
    }

    public function scopeEtfs(Builder $query): Builder
    {
        return $query->where('asset_type', 'etf');
    }

    public function isCrypto(): bool
    {
        return $this->asset_type === 'crypto';
    }

    public function isStock(): bool
    {
        return $this->asset_type === 'stock';
    }

    public function getFormattedQuantityAttribute(): string
    {
        $decimals = $this->isCrypto() ? ($this->symbol === 'BTC' ? 8 : 6) : 0;
        return number_format((float) $this->quantity, $decimals);
    }

    public function getFormattedMarketValueAttribute(): string
    {
        return '€' . number_format((float) $this->market_value, 2);
    }

    public function getPnlPercentageAttribute(): float
    {
        if ((float) $this->total_invested <= 0) {
            return 0.0;
        }

        return ((float) $this->unrealized_pnl / (float) $this->total_invested) * 100;
    }

    public function updateMarketValue(): void
    {
        if ($this->current_price && $this->quantity) {
            $newMarketValue = $this->quantity * $this->current_price;
            $newPnl = $newMarketValue - $this->total_invested;

            $this->update([
                'market_value' => $newMarketValue,
                'unrealized_pnl' => $newPnl,
                'last_price_update' => now(),
            ]);
        }
    }

    public function addTransaction(float $quantity, float $price, string $type): void
    {
        if ($type === 'buy') {
            $newQuantity = $this->quantity + $quantity;
            $newInvested = $this->total_invested + ($quantity * $price);
            $newAverageCost = $newQuantity > 0 ? $newInvested / $newQuantity : 0;

            $this->update([
                'quantity' => $newQuantity,
                'total_invested' => $newInvested,
                'average_cost' => $newAverageCost,
            ]);
        } elseif ($type === 'sell') {
            $sellValue = $quantity * $price;
            $avgCostBasis = $this->average_cost * $quantity;

            $this->update([
                'quantity' => $this->quantity - $quantity,
                'total_invested' => $this->total_invested - $avgCostBasis,
            ]);
        }

        $this->updateMarketValue();
    }
}
