<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TransactionImportService
{
    public function parseTransactions(string $text): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (!$apiKey) {
            throw new \Exception('Anthropic API key not configured');
        }

        $currentYear = now()->year;

        $prompt = <<<PROMPT
Du bist ein Experte für das Parsen von Banktransaktionen. Analysiere den folgenden Text und extrahiere alle Transaktionen.

Für jede Transaktion, extrahiere:
- description: Eine klare Beschreibung (z.B. Händlername)
- amount: Der Betrag als positive Zahl (ohne € Zeichen)
- date: Das Datum im Format YYYY-MM-DD (verwende das Jahr {$currentYear} wenn nicht angegeben)

Wichtig:
- Ignoriere Zeilen wie "Entgelt", "Lastschrift", "ARN" Nummern
- Der Betrag ist die negative Zahl (z.B. "-6,70 €" wird zu 6.70)
- Verwende Kommas als Dezimaltrenner (z.B. "6,70" wird zu 6.70)
- Extrahiere nur echte Ausgaben-Transaktionen

Antworte NUR mit einem JSON Array, kein zusätzlicher Text:
[
  {
    "description": "ARAL Station",
    "amount": 6.70,
    "date": "{$currentYear}-09-16"
  }
]
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt . "\n\nText:\n" . $text,
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to parse transactions: ' . $response->body());
        }

        $content = $response->json('content.0.text');

        // Remove markdown code blocks if present
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        $transactions = json_decode(trim($content), true);

        if (!is_array($transactions)) {
            throw new \Exception('Invalid response format from AI');
        }

        return $transactions;
    }
}
