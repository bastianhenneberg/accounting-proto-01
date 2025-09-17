<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance',
        'currency',
        'crypto_symbol',
        'crypto_balance',
        'fiat_value',
        'current_price',
        'last_price_update',
        'account_number',
        'bank_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'crypto_balance' => 'decimal:8',
            'fiat_value' => 'decimal:2',
            'current_price' => 'decimal:8',
            'last_price_update' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function plannedTransactions(): HasMany
    {
        return $this->hasMany(PlannedTransaction::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function getProjectedBalanceAttribute(): float
    {
        $confirmedAmount = $this->plannedTransactions()
            ->where('status', 'confirmed')
            ->where('planned_date', '<=', now()->addDays(30))
            ->get()
            ->sum(function ($planned) {
                return $planned->type === 'income' ? $planned->amount : -$planned->amount;
            });

        return $this->balance + $confirmedAmount;
    }

    // Crypto-specific methods
    public function isCrypto(): bool
    {
        return $this->type === 'crypto';
    }

    public function getCryptoDisplayName(): string
    {
        if (!$this->isCrypto()) {
            return '';
        }

        return match($this->crypto_symbol) {
            'BTC' => 'Bitcoin',
            'XRP' => 'XRP',
            'ETH' => 'Ethereum',
            'ADA' => 'Cardano',
            'MATIC' => 'Polygon',
            'DOT' => 'Polkadot',
            'LINK' => 'Chainlink',
            'UNI' => 'Uniswap',
            'ATOM' => 'Cosmos',
            'SOL' => 'Solana',
            default => strtoupper($this->crypto_symbol ?? '')
        };
    }

    public function getFormattedCryptoBalanceAttribute(): string
    {
        if (!$this->isCrypto() || !$this->crypto_balance) {
            return '';
        }

        $decimals = $this->crypto_symbol === 'BTC' ? 8 : ($this->crypto_symbol === 'ETH' ? 6 : 2);
        return number_format($this->crypto_balance, $decimals) . ' ' . strtoupper($this->crypto_symbol);
    }

    public function getFormattedFiatValueAttribute(): string
    {
        if ($this->isCrypto()) {
            return '€' . number_format($this->fiat_value ?? 0, 2);
        }

        return '€' . number_format($this->balance, 2);
    }

    public function getPrice24hChangeAttribute(): float
    {
        if (!$this->isCrypto()) {
            return 0.0;
        }

        $priceData = \App\Services\CryptoPriceService::getPriceWithChange($this->crypto_symbol);
        return $priceData['change_24h'] ?? 0.0;
    }

    public function updateCryptoPrice(): bool
    {
        if (!$this->isCrypto()) {
            return false;
        }

        $priceData = \App\Services\CryptoPriceService::getPriceWithChange($this->crypto_symbol);

        if ($priceData['price'] > 0) {
            $this->update([
                'current_price' => $priceData['price'],
                'fiat_value' => $this->crypto_balance * $priceData['price'],
                'last_price_update' => now(),
            ]);

            return true;
        }

        return false;
    }

    public function isPriceStale(): bool
    {
        if (!$this->isCrypto()) {
            return false;
        }

        return !$this->last_price_update || $this->last_price_update->diffInMinutes(now()) > 30;
    }

    // Investment account methods
    public function isInvestment(): bool
    {
        return in_array($this->type, ['investment', 'crypto']);
    }

    public function getTotalPortfolioValueAttribute(): float
    {
        if (!$this->isInvestment()) {
            return $this->balance;
        }

        return $this->holdings()->sum('market_value') ?: 0;
    }

    public function getTotalInvestedAttribute(): float
    {
        return $this->holdings()->sum('total_invested') ?: 0;
    }

    public function getTotalUnrealizedPnlAttribute(): float
    {
        return $this->holdings()->sum('unrealized_pnl') ?: 0;
    }

    public function getPortfolioPnlPercentageAttribute(): float
    {
        if ($this->total_invested <= 0) {
            return 0.0;
        }

        return ($this->total_unrealized_pnl / $this->total_invested) * 100;
    }

    public function getCryptoHoldings()
    {
        return $this->holdings()->crypto()->get();
    }

    public function getStockHoldings()
    {
        return $this->holdings()->stocks()->get();
    }

    public function getEtfHoldings()
    {
        return $this->holdings()->etfs()->get();
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->balance, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
