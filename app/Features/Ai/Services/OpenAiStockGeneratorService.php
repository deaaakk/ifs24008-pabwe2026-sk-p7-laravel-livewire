<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    private const SYSTEM_PROMPT = 'Kamu adalah asisten inventaris. Balas HANYA dengan JSON valid berbentuk '
        .'{"items":[{"name":string,"sku":string,"description":string,"quantity":integer,'
        .'"unit":string,"price":number,"min_stock":integer}]}. '
        .'Gunakan bahasa Indonesia, harga dalam Rupiah, dan description boleh Markdown.';

    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }

        $response = Http::withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->acceptJson()
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => "Buat {$count} item stok barang untuk: {$prompt}"],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke OpenAI gagal (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) ? ($decoded['items'] ?? []) : [];

        $result = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $dto = GeneratedStockItem::fromArray($item);

            if ($dto->name === '') {
                continue;
            }

            $result[] = $dto;
        }

        return array_slice($result, 0, $count);
    }
}
