<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ExplicitActionMetadata;
use Crell\HttpTools\Fakes\FakeContainer;
use Crell\HttpTools\Fakes\TracingMiddleware;
use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AdditionalMiddlewareTest extends TestCase
{
    public static function middlewareExamples(): \Generator
    {
        yield 'one extra middleware' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(
                action: fn(string $a, int $b) => $a . $b,
                actionDef: new ExplicitActionMetadata(additionalMiddleware: [TracingMiddleware::class])
            ),
        ];

        yield 'two extra middleware' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(
                action: fn(string $a, int $b) => $a . $b,
                actionDef: new ExplicitActionMetadata(additionalMiddleware: [TracingMiddleware::class])
            ),
        ];

        yield 'Extra from container' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(
                action: fn(string $a, int $b) => $a . $b,
                actionDef: new ExplicitActionMetadata(additionalMiddleware: [TracingMiddleware::class])
            ),
            'container' => new FakeContainer([TracingMiddleware::class => new TracingMiddleware()]),
        ];
    }

    #[Test, DataProvider('middlewareExamples')]
    public function additionalMiddleware(
        string $uri,
        RouteResult $routeResult,
        ?ContainerInterface $container = null,
    ): void
    {
        $middleware = new AdditionalMiddlewareMiddleware($container);

        $request = new ServerRequest('GET', $uri)
            ->withAttribute(RouteResult::class, $routeResult)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        self::assertEquals(TracingMiddleware::class, $fakeNext->request->getAttribute(TracingMiddleware::class));
        self::assertEquals('from next', $response->getBody()->getContents());
    }
}
