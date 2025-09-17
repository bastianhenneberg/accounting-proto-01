<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CryptoPriceService
{
    private const API_URL = 'https://api.coingecko.com/api/v3/simple/price';
    private const CACHE_TTL = 900; // 15 minutes

    private static array $coinGeckoMapping = [
        'BTC' => 'bitcoin',
        'XRP' => 'ripple',
        'ETH' => 'ethereum',
        'ADA' => 'cardano',
        'MATIC' => 'matic-network',
        'DOT' => 'polkadot',
        'LINK' => 'chainlink',
        'UNI' => 'uniswap',
        'ATOM' => 'cosmos',
        'SOL' => 'solana',
    ];

    public static function getPrice(string $symbol): float
    {
        $cacheKey = "crypto_price_{$symbol}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            try {
                $coingeckoId = self::getCoingeckoId($symbol);
                if (!$coingeckoId) {
                    return 0.0;
                }

                $response = Http::timeout(10)->get(self::API_URL, [
                    'ids' => $coingeckoId,
                    'vs_currencies' => 'eur'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return (float) ($data[$coingeckoId]['eur'] ?? 0);
                }

                Log::warning("Failed to fetch crypto price for {$symbol}");
                return 0.0;

            } catch (\Exception $e) {
                Log::error("Crypto price API error for {$symbol}: " . $e->getMessage());
                return 0.0;
            }
        });
    }

    public static function getPriceWithChange(string $symbol): array
    {
        $cacheKey = "crypto_price_change_{$symbol}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            try {
                $coingeckoId = self::getCoingeckoId($symbol);
                if (!$coingeckoId) {
                    return ['price' => 0.0, 'change_24h' => 0.0];
                }

                $response = Http::timeout(10)->get(self::API_URL, [
                    'ids' => $coingeckoId,
                    'vs_currencies' => 'eur',
                    'include_24hr_change' => 'true'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $coinData = $data[$coingeckoId] ?? [];

                    return [
                        'price' => (float) ($coinData['eur'] ?? 0),
                        'change_24h' => (float) ($coinData['eur_24h_change'] ?? 0),
                    ];
                }

                Log::warning("Failed to fetch crypto price with change for {$symbol}");
                return ['price' => 0.0, 'change_24h' => 0.0];

            } catch (\Exception $e) {
                Log::error("Crypto price API error for {$symbol}: " . $e->getMessage());
                return ['price' => 0.0, 'change_24h' => 0.0];
            }
        });
    }

    public static function updateAccountPrices(array $accounts): int
    {
        $symbols = collect($accounts)->pluck('crypto_symbol')->filter()->unique();
        if ($symbols->isEmpty()) {
            return 0;
        }

        $coingeckoIds = $symbols->map(fn($symbol) => self::getCoingeckoId($symbol))->filter();
        if ($coingeckoIds->isEmpty()) {
            return 0;
        }

        try {
            $response = Http::timeout(15)->get(self::API_URL, [
                'ids' => $coingeckoIds->implode(','),
                'vs_currencies' => 'eur',
                'include_24hr_change' => 'true'
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to fetch crypto prices: " . $response->status());
                return 0;
            }

            $prices = $response->json();
            $updated = 0;

            foreach ($accounts as $account) {
                if (!$account->crypto_symbol) continue;

                $coingeckoId = self::getCoingeckoId($account->crypto_symbol);
                if (!$coingeckoId || !isset($prices[$coingeckoId])) continue;

                $priceData = $prices[$coingeckoId];
                $currentPrice = (float) ($priceData['eur'] ?? 0);

                if ($currentPrice > 0) {
                    $account->update([
                        'current_price' => $currentPrice,
                        'fiat_value' => $account->crypto_balance * $currentPrice,
                        'last_price_update' => now(),
                    ]);

                    // Clear cache for this symbol
                    Cache::forget("crypto_price_{$account->crypto_symbol}");
                    Cache::forget("crypto_price_change_{$account->crypto_symbol}");

                    $updated++;
                }
            }

            return $updated;

        } catch (\Exception $e) {
            Log::error("Crypto price update failed: " . $e->getMessage());
            return 0;
        }
    }

    public static function getSupportedCryptocurrencies(): array
    {
        return [
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
        ];
    }

    private static function getCoingeckoId(string $symbol): ?string
    {
        return self::$coinGeckoMapping[strtoupper($symbol)] ?? null;
    }
}
