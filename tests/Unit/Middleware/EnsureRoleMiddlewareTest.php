<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    private function requestFor(?User $user): Request
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_allows_user_with_matching_role(): void
    {
        $response = (new EnsureRole())->handle(
            $this->requestFor(User::factory()->make(['role' => 'admin'])),
            fn () => new Response('ok'),
            'admin',
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_allows_any_of_multiple_roles(): void
    {
        $response = (new EnsureRole())->handle(
            $this->requestFor(User::factory()->make(['role' => 'member'])),
            fn () => new Response('ok'),
            'admin',
            'member',
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_rejects_wrong_role(): void
    {
        $this->expectException(HttpException::class);

        (new EnsureRole())->handle(
            $this->requestFor(User::factory()->make(['role' => 'member'])),
            fn () => new Response('ok'),
            'admin',
        );
    }

    public function test_rejects_guest(): void
    {
        $this->expectException(HttpException::class);

        (new EnsureRole())->handle($this->requestFor(null), fn () => new Response('ok'), 'admin');
    }
}
