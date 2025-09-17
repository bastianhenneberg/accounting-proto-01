<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupportedAssetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            // Cryptocurrencies
            ['asset_type' => 'crypto', 'symbol' => 'BTC', 'name' => 'Bitcoin', 'api_id' => 'bitcoin'],
            ['asset_type' => 'crypto', 'symbol' => 'XRP', 'name' => 'XRP', 'api_id' => 'ripple'],
            ['asset_type' => 'crypto', 'symbol' => 'ETH', 'name' => 'Ethereum', 'api_id' => 'ethereum'],
            ['asset_type' => 'crypto', 'symbol' => 'ADA', 'name' => 'Cardano', 'api_id' => 'cardano'],
            ['asset_type' => 'crypto', 'symbol' => 'MATIC', 'name' => 'Polygon', 'api_id' => 'matic-network'],
            ['asset_type' => 'crypto', 'symbol' => 'DOT', 'name' => 'Polkadot', 'api_id' => 'polkadot'],
            ['asset_type' => 'crypto', 'symbol' => 'LINK', 'name' => 'Chainlink', 'api_id' => 'chainlink'],
            ['asset_type' => 'crypto', 'symbol' => 'SOL', 'name' => 'Solana', 'api_id' => 'solana'],

            // US Stocks
            ['asset_type' => 'stock', 'symbol' => 'AAPL', 'name' => 'Apple Inc.', 'api_id' => 'AAPL'],
            ['asset_type' => 'stock', 'symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'api_id' => 'TSLA'],
            ['asset_type' => 'stock', 'symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'api_id' => 'MSFT'],
            ['asset_type' => 'stock', 'symbol' => 'AMZN', 'name' => 'Amazon.com Inc.', 'api_id' => 'AMZN'],
            ['asset_type' => 'stock', 'symbol' => 'GOOGL', 'name' => 'Alphabet Inc.', 'api_id' => 'GOOGL'],
            ['asset_type' => 'stock', 'symbol' => 'META', 'name' => 'Meta Platforms Inc.', 'api_id' => 'META'],
            ['asset_type' => 'stock', 'symbol' => 'NVDA', 'name' => 'NVIDIA Corp.', 'api_id' => 'NVDA'],
            ['asset_type' => 'stock', 'symbol' => 'NFLX', 'name' => 'Netflix Inc.', 'api_id' => 'NFLX'],

            // ETFs - Global
            ['asset_type' => 'etf', 'symbol' => 'MSCI_WORLD', 'name' => 'MSCI World ETF', 'api_id' => 'msci-world', 'metadata' => ['isin' => 'IE00B4L5Y983']],
            ['asset_type' => 'etf', 'symbol' => 'MSCI_EMU_SC', 'name' => 'MSCI EMU Small Cap', 'api_id' => 'msci-emu-small-cap', 'metadata' => ['isin' => 'LU0292109690']],
            ['asset_type' => 'etf', 'symbol' => 'DJ_ASIA_PAC', 'name' => 'Dow Jones Asia Pacific', 'api_id' => 'dj-asia-pacific', 'metadata' => ['isin' => 'IE00B14X4Q57']],
            ['asset_type' => 'etf', 'symbol' => 'CORE_MSCI_EUR', 'name' => 'Core MSCI Europe EUR', 'api_id' => 'core-msci-europe', 'metadata' => ['isin' => 'IE00B1YZSC51']],
            ['asset_type' => 'etf', 'symbol' => 'SP500', 'name' => 'S&P 500 ETF', 'api_id' => 'sp500-etf', 'metadata' => ['isin' => 'IE00B5BMR087']],
            ['asset_type' => 'etf', 'symbol' => 'NASDAQ100', 'name' => 'NASDAQ 100 ETF', 'api_id' => 'nasdaq100-etf', 'metadata' => ['isin' => 'IE00B53SZB19']],
            ['asset_type' => 'etf', 'symbol' => 'EMERGING_MKT', 'name' => 'Emerging Markets ETF', 'api_id' => 'emerging-markets', 'metadata' => ['isin' => 'IE00B4L5YC18']],
            ['asset_type' => 'etf', 'symbol' => 'EUROPE_STOXX', 'name' => 'STOXX Europe 600', 'api_id' => 'stoxx-europe-600', 'metadata' => ['isin' => 'LU0274211480']],
            ['asset_type' => 'etf', 'symbol' => 'DAX_ETF', 'name' => 'DAX ETF', 'api_id' => 'dax-etf', 'metadata' => ['isin' => 'DE0005933931']],
            ['asset_type' => 'etf', 'symbol' => 'FTSE_DEV', 'name' => 'FTSE Developed Markets', 'api_id' => 'ftse-developed', 'metadata' => ['isin' => 'IE00B3RBWM25']],
            ['asset_type' => 'etf', 'symbol' => 'CLEAN_ENERGY', 'name' => 'Clean Energy ETF', 'api_id' => 'clean-energy', 'metadata' => ['isin' => 'IE00BMYDM794']],
            ['asset_type' => 'etf', 'symbol' => 'REIT_GLOBAL', 'name' => 'Global REIT ETF', 'api_id' => 'global-reit', 'metadata' => ['isin' => 'IE00B1FZS467']],

            // German ETFs (Trade Republic favorites)
            ['asset_type' => 'etf', 'symbol' => 'MSCI_ACWI', 'name' => 'MSCI ACWI ETF', 'api_id' => 'msci-acwi', 'metadata' => ['isin' => 'IE00B6R52259']],
            ['asset_type' => 'etf', 'symbol' => 'FTSE_ALL_WORLD', 'name' => 'FTSE All-World ETF', 'api_id' => 'ftse-all-world', 'metadata' => ['isin' => 'IE00B3RBWM25']],
            ['asset_type' => 'etf', 'symbol' => 'MSCI_EM_IMI', 'name' => 'MSCI Emerging Markets IMI', 'api_id' => 'msci-em-imi', 'metadata' => ['isin' => 'IE00BKM4GZ66']],
        ];

        foreach ($assets as $asset) {
            \App\Models\SupportedAsset::updateOrCreate(
                ['symbol' => $asset['symbol']],
                $asset
            );
        }
    }
}
