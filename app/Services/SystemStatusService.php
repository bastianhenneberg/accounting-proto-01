<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SystemStatusService
{
    public static function checkAllServices(): array
    {
        return [
            'puppeteer' => self::checkPuppeteer(),
            'claude_ai' => self::checkClaudeAI(),
            'crypto_api' => self::checkCryptoAPI(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public static function checkPuppeteer(): array
    {
        try {
            // Check if Chromium is installed
            $chromiumPath = '/usr/bin/chromium';
            if (! file_exists($chromiumPath) && ! file_exists('/usr/bin/chromium-browser')) {
                return [
                    'status' => 'error',
                    'message' => 'Chromium not found',
                    'available' => false,
                ];
            }

            // Check if we can create screenshots directory
            $screenshotsDir = storage_path('app/screenshots');
            if (! is_dir($screenshotsDir)) {
                @mkdir($screenshotsDir, 0755, true);
            }

            if (! is_writable($screenshotsDir)) {
                return [
                    'status' => 'error',
                    'message' => 'Screenshots directory not writable',
                    'available' => false,
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Puppeteer/Browsershot ready',
                'available' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Puppeteer status check failed: '.$e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'available' => false,
            ];
        }
    }

    public static function checkClaudeAI(): array
    {
        try {
            $apiKey = env('ANTHROPIC_API_KEY');

            if (! $apiKey) {
                return [
                    'status' => 'error',
                    'message' => 'ANTHROPIC_API_KEY not configured',
                    'available' => false,
                ];
            }

            // Quick ping to Claude API with minimal tokens
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 10,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Hi',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'ok',
                    'message' => 'Claude AI connected',
                    'available' => true,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Claude API error: '.$response->status(),
                'available' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Claude AI status check failed: '.$e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'available' => false,
            ];
        }
    }

    public static function checkCryptoAPI(): array
    {
        try {
            $response = Http::timeout(5)->get('https://api.coingecko.com/api/v3/ping');

            if ($response->successful()) {
                return [
                    'status' => 'ok',
                    'message' => 'CoinGecko API connected',
                    'available' => true,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'CoinGecko API error: '.$response->status(),
                'available' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Crypto API status check failed: '.$e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'available' => false,
            ];
        }
    }
}
