<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.com/v1/',
            'openai.model' => 'gpt-test',
            'openai.timeout' => 5,
        ]);
    }

    private function fakeContent(mixed $content, int $status = 200): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status),
        ]);
    }

    public function test_it_parses_items_and_skips_invalid_ones(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'Pulpen', 'sku' => 'PLP', 'quantity' => 10, 'unit' => 'pcs', 'price' => 3000, 'min_stock' => 2],
            'bukan-array',
            ['name' => '   '],
            ['name' => 'Buku'],
        ]]));

        $items = (new OpenAiStockGeneratorService())->generate('alat tulis', 5);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Pulpen', $items[0]->name);
        $this->assertSame('Buku', $items[1]->name);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'gpt-test'
            && $request['response_format']['type'] === 'json_object');
    }

    public function test_it_limits_the_number_of_items(): void
    {
        $this->fakeContent(json_encode(['items' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']]]));

        $this->assertCount(2, (new OpenAiStockGeneratorService())->generate('x', 2));
    }

    public function test_invalid_json_returns_empty_list(): void
    {
        $this->fakeContent('ini bukan json');

        $this->assertSame([], (new OpenAiStockGeneratorService())->generate('x'));
    }

    public function test_missing_api_key_throws(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiStockGeneratorService())->generate('x');
    }

    public function test_http_failure_throws(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiStockGeneratorService())->generate('x');
    }
}
