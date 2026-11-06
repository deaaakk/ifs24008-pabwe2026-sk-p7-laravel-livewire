<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function requestFor(?User $user, mixed $param): Request
    {
        $request = Request::create('/stocks/1');
        $route = (new Route('GET', 'stocks/{stock}', fn () => null))->bind($request);
        $route->setParameter('stock', $param);

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function pass(Request $request): Response
    {
        return (new EnsureStockOwnership())->handle($request, fn () => new Response('ok'));
    }

    public function test_passes_when_route_has_no_stock_parameter(): void
    {
        $request = Request::create('/stocks');
        $request->setUserResolver(fn () => User::factory()->create());

        $this->assertSame('ok', $this->pass($request)->getContent());
    }

    public function test_owner_passes_with_bound_model(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create();

        $this->assertSame('ok', $this->pass($this->requestFor($user, $stock))->getContent());
    }

    public function test_owner_passes_with_raw_id(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->for($user)->create();

        $this->assertSame('ok', $this->pass($this->requestFor($user, (string) $stock->id))->getContent());
    }

    public function test_missing_stock_returns_404(): void
    {
        try {
            $this->pass($this->requestFor(User::factory()->create(), '999999'));
            $this->fail('Seharusnya melempar HttpException');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_foreign_stock_returns_403(): void
    {
        try {
            $this->pass($this->requestFor(User::factory()->create(), Stock::factory()->create()));
            $this->fail('Seharusnya melempar HttpException');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_guest_returns_403(): void
    {
        $this->expectException(HttpException::class);

        $this->pass($this->requestFor(null, Stock::factory()->create()));
    }
}
