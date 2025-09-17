<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AssetPriceService
{
    private const CRYPTO_API_URL = 'https://api.coingecko.com/api/v3/simple/price';
    private const ALPHA_VANTAGE_URL = 'https://www.alphavantage.co/query';
    private const CACHE_TTL = 900; // 15 minutes

    private static array $cryptoMapping = [
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

    public static function updateHoldingPrice(Holding $holding): bool
    {
        $priceData = match($holding->asset_type) {
            'crypto' => self::getCryptoPrice($holding->symbol),
            'stock', 'etf' => self::getStockPrice($holding->symbol),
            default => ['price' => 0, 'change_24h' => 0]
        };

        if ($priceData['price'] > 0) {
            $holding->update([
                'current_price' => $priceData['price'],
                'market_value' => $holding->quantity * $priceData['price'],
                'unrealized_pnl' => ($holding->quantity * $priceData['price']) - $holding->total_invested,
                'last_price_update' => now(),
            ]);

            return true;
        }

        // If API fails, keep existing price but mark as stale
        Log::warning("Failed to update price for {$holding->symbol} via API");
        return false;
    }

    public static function setManualPrice(Holding $holding, float $manualPrice): void
    {
        $newMarketValue = $holding->quantity * $manualPrice;

        $holding->update([
            'current_price' => $manualPrice,
            'market_value' => $newMarketValue,
            'unrealized_pnl' => $newMarketValue - $holding->total_invested,
            'last_price_update' => now(),
        ]);

        Log::info("Manual price set for {$holding->symbol}: €{$manualPrice}");
    }

    public static function updateAllHoldings(): int
    {
        $holdings = Holding::all();
        $updated = 0;

        // Group by asset type for efficient batch updates
        $cryptoSymbols = $holdings->where('asset_type', 'crypto')->pluck('symbol')->unique();
        $stockSymbols = $holdings->where('asset_type', 'stock')->pluck('symbol')->unique();

        // Batch update crypto prices
        if ($cryptoSymbols->isNotEmpty()) {
            $cryptoPrices = self::getBatchCryptoPrices($cryptoSymbols->toArray());
            foreach ($holdings->where('asset_type', 'crypto') as $holding) {
                if (isset($cryptoPrices[$holding->symbol])) {
                    $priceData = $cryptoPrices[$holding->symbol];
                    $holding->update([
                        'current_price' => $priceData['price'],
                        'market_value' => $holding->quantity * $priceData['price'],
                        'unrealized_pnl' => ($holding->quantity * $priceData['price']) - $holding->total_invested,
                        'last_price_update' => now(),
                    ]);
                    $updated++;
                }
            }
        }

        // Individual stock updates (free APIs are limited)
        foreach ($holdings->whereIn('asset_type', ['stock', 'etf']) as $holding) {
            if (self::updateHoldingPrice($holding)) {
                $updated++;
            }
        }

        return $updated;
    }

    private static function getCryptoPrice(string $symbol): array
    {
        $cacheKey = "crypto_price_change_{$symbol}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            try {
                $coingeckoId = self::$cryptoMapping[strtoupper($symbol)] ?? null;
                if (!$coingeckoId) {
                    return ['price' => 0, 'change_24h' => 0];
                }

                $response = Http::timeout(10)->get(self::CRYPTO_API_URL, [
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

            } catch (\Exception $e) {
                Log::error("Crypto price error for {$symbol}: " . $e->getMessage());
            }

            return ['price' => 0, 'change_24h' => 0];
        });
    }

    private static function getBatchCryptoPrices(array $symbols): array
    {
        $coingeckoIds = collect($symbols)
            ->map(fn($symbol) => self::$cryptoMapping[strtoupper($symbol)] ?? null)
            ->filter()
            ->unique();

        if ($coingeckoIds->isEmpty()) {
            return [];
        }

        try {
            $response = Http::timeout(15)->get(self::CRYPTO_API_URL, [
                'ids' => $coingeckoIds->implode(','),
                'vs_currencies' => 'eur',
                'include_24hr_change' => 'true'
            ]);

            if (!$response->successful()) {
                return [];
            }

            $prices = $response->json();
            $result = [];

            foreach ($symbols as $symbol) {
                $coingeckoId = self::$cryptoMapping[strtoupper($symbol)] ?? null;
                if ($coingeckoId && isset($prices[$coingeckoId])) {
                    $coinData = $prices[$coingeckoId];
                    $result[$symbol] = [
                        'price' => (float) ($coinData['eur'] ?? 0),
                        'change_24h' => (float) ($coinData['eur_24h_change'] ?? 0),
                    ];
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Batch crypto price update failed: " . $e->getMessage());
            return [];
        }
    }

    private static function getStockPrice(string $symbol): array
    {
        $cacheKey = "stock_price_{$symbol}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            // Get asset from database
            $asset = \App\Models\SupportedAsset::where('symbol', $symbol)->first();
            if (!$asset) {
                return ['price' => 100.0, 'change_24h' => 0.0];
            }

            // Try Alpha Vantage for US stocks
            if ($asset->asset_type === 'stock' && !str_contains($asset->api_id ?? '', '.de')) {
                $alphaResult = self::getAlphaVantagePrice($asset);
                if ($alphaResult['price'] > 0) {
                    return $alphaResult;
                }
            }

            // Try Puppeteer + AI if URL is configured
            if ($asset->price_url) {
                $puppeteerResult = PuppeteerPriceService::extractPriceFromUrl($asset->price_url);
                if ($puppeteerResult['success']) {
                    return [
                        'price' => $puppeteerResult['price'],
                        'change_24h' => 0,
                        'source' => 'puppeteer_ai'
                    ];
                }
            }

            // No price available - use manual updates for accuracy
            Log::info("No API price available for {$symbol} - use manual price update");
            return ['price' => 0, 'change_24h' => 0];
        });
    }

    private static function getAlphaVantagePrice(\App\Models\SupportedAsset $asset): array
    {
        try {
            $apiKey = env('ALPHA_VANTAGE_API_KEY', 'demo'); // Demo key for testing
            $apiSymbol = $asset->api_id ?: $asset->symbol;

            $response = Http::timeout(15)->get(self::ALPHA_VANTAGE_URL, [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $apiSymbol,
                'apikey' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $quote = $data['Global Quote'] ?? [];

                if (!empty($quote)) {
                    $currentPrice = (float) ($quote['05. price'] ?? 0);
                    $change = (float) ($quote['09. change'] ?? 0);
                    $changePercent = (float) str_replace('%', '', $quote['10. change percent'] ?? '0');

                    if ($currentPrice > 0) {
                        Log::info("Alpha Vantage price fetched for {$asset->symbol}: €{$currentPrice}");

                        return [
                            'price' => $currentPrice,
                            'change_24h' => $changePercent,
                            'source' => 'alpha_vantage'
                        ];
                    }
                }
            }

            Log::warning("Alpha Vantage failed for {$asset->symbol}");
            return ['price' => 0, 'change_24h' => 0];

        } catch (\Exception $e) {
            Log::error("Alpha Vantage API error for {$asset->symbol}: " . $e->getMessage());
            return ['price' => 0, 'change_24h' => 0];
        }
    }


    private static function scrapePrice(\App\Models\SupportedAsset $asset): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($asset->price_url);

            if ($response->successful()) {
                $html = $response->body();

                // Basic price pattern matching
                if (preg_match('/(\d+[.,]\d+)\s*€/', $html, $matches)) {
                    $price = (float) str_replace(',', '.', $matches[1]);

                    if ($price > 0) {
                        Log::info("Price scraped for {$asset->symbol} from {$asset->price_url}: €{$price}");

                        return [
                            'price' => $price,
                            'change_24h' => 0,
                            'source' => 'web_scraped'
                        ];
                    }
                }

                Log::warning("No price found in scraped content for {$asset->symbol}");
                return ['price' => 0, 'change_24h' => 0];

            } else {
                Log::warning("Failed to scrape {$asset->price_url} for {$asset->symbol}");
                return ['price' => 0, 'change_24h' => 0];
            }

        } catch (\Exception $e) {
            Log::error("Scraping error for {$asset->symbol}: " . $e->getMessage());
            return ['price' => 0, 'change_24h' => 0];
        }
    }

    public static function getSupportedAssets(): array
    {
        return \App\Models\SupportedAsset::getAllAssetsByType();
    }
}