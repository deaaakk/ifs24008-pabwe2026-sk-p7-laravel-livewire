<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Repositories\StockRepository;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceHttps(false);

        parent::tearDown();
    }

    public function test_bindings_are_registered(): void
    {
        $this->assertInstanceOf(StockRepository::class, $this->app->make(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, $this->app->make(AiClientInterface::class));
    }

    public function test_policy_is_registered(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('1000-per-second'));
    }

    public function test_force_https_when_enabled(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/login'));
    }

    public function test_https_not_forced_by_default(): void
    {
        config(['app.force_https' => false]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', url('/login'));
    }
}
