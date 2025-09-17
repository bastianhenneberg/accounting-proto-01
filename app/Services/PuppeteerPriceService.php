<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportedAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class PuppeteerPriceService
{
    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';

    public static function extractPriceFromUrl(string $url): array
    {
        try {
            // 1. Take screenshot with Puppeteer
            $screenshotPath = self::takeScreenshot($url);

            if (!$screenshotPath) {
                return ['success' => false, 'error' => 'Screenshot failed'];
            }

            // 2. Analyze with OpenAI Vision
            $priceData = self::analyzeScreenshotWithAI($screenshotPath);

            // 3. Cleanup
            if (file_exists(storage_path('app/' . $screenshotPath))) {
                unlink(storage_path('app/' . $screenshotPath));
            }

            return $priceData;

        } catch (\Exception $e) {
            Log::error("Puppeteer price extraction failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private static function takeScreenshot(string $url): ?string
    {
        try {
            $filename = 'screenshots/' . uniqid() . '.png';
            $fullPath = storage_path('app/' . $filename);

            // Ensure screenshots directory exists
            $screenshotsDir = storage_path('app/screenshots');
            if (!is_dir($screenshotsDir)) {
                mkdir($screenshotsDir, 0755, true);
            }

            // Use Spatie Browsershot with optimized settings
            Browsershot::url($url)
                ->setDelay(5000) // More time for JavaScript
                ->windowSize(1400, 900)
                ->deviceScaleFactor(1)
                ->timeout(60)
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->save($fullPath);

            if (file_exists($fullPath) && filesize($fullPath) > 1000) {
                Log::info("Browsershot screenshot taken successfully for {$url}: " . number_format(filesize($fullPath)) . " bytes");
                return $filename;
            } else {
                Log::error("Browsershot screenshot failed or empty for {$url}");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Browsershot error: " . $e->getMessage());
            return null;
        }
    }


    private static function analyzeScreenshotWithAI(string $screenshotPath): array
    {
        try {
            $apiKey = env('ANTHROPIC_API_KEY');
            if (!$apiKey) {
                return ['success' => false, 'error' => 'Claude API key not configured'];
            }

            // Use direct file access instead of Laravel Storage
            $fullPath = storage_path('app/' . $screenshotPath);

            if (!file_exists($fullPath)) {
                return ['success' => false, 'error' => 'Screenshot file not found: ' . $fullPath];
            }

            $imageData = file_get_contents($fullPath);
            if ($imageData === false || empty($imageData)) {
                return ['success' => false, 'error' => 'Screenshot file is empty or unreadable'];
            }

            $base64Image = base64_encode($imageData);
            if ($base64Image === false) {
                return ['success' => false, 'error' => 'Failed to encode screenshot as base64'];
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post(self::CLAUDE_API_URL, [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 100,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => 'image/png',
                                        'data' => $base64Image
                                    ]
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'This is a screenshot of a financial website (likely JustETF, ExtraETF, or similar) showing an ETF price page. Please extract the current ETF price value in EUR. Look for:

- Current price like "87,10 €", "€87.10", "87.10 EUR"
- NAV (Net Asset Value)
- Fund price or unit price
- Any price in the 50-500 EUR range (typical ETF range)
- Price may be in sections like "Kurs", "Preis", "NAV", "Current Price"

Return ONLY the numeric value (e.g., 87.10). If multiple prices are shown, return the main/current/latest price. If you cannot find any clear ETF price, return "NOT_FOUND".'
                                ]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? '';

                // Extract numeric value from Claude response
                if (preg_match('/(\d+[.,]\d+)/', $content, $matches)) {
                    $price = (float) str_replace(',', '.', $matches[1]);

                    if ($price > 0 && $price < 10000) {
                        Log::info("Claude extracted price: €{$price} from screenshot");

                        return [
                            'success' => true,
                            'price' => $price,
                            'ai_response' => $content
                        ];
                    }
                }

                return ['success' => false, 'error' => "Claude couldn't extract valid price: {$content}"];

            } else {
                return ['success' => false, 'error' => 'Claude API failed: ' . $response->status()];
            }

        } catch (\Exception $e) {
            Log::error("Claude price analysis failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}