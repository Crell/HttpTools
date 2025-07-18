<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class NormalizeArgumentTypesMiddlewareTest extends TestCase
{
    public static function typeMappingExamples(): \Generator
    {
        yield 'int to int' => [
            'type' => 'int',
            'value' => 5,
            'expectedValue' => 5,
        ];
        yield 'float to float' => [
            'type' => 'float',
            'value' => 3.14,
            'expectedValue' => 3.14,
        ];
        yield 'string to string' => [
            'type' => 'string',
            'value' => 'hello',
            'expectedValue' => 'hello',
        ];
        yield 'numeric string to string' => [
            'type' => 'string',
            'value' => '5',
            'expectedValue' => '5',
        ];
        yield 'int to float' => [
            'type' => 'float',
            'value' => 5,
            'expectedValue' => 5.0,
        ];
        yield 'numeric string to int' => [
            'type' => 'int',
            'value' => '3',
            'expectedValue' => 3,
        ];
        yield 'numeric string to float' => [
            'type' => 'float',
            'value' => '3.14',
            'expectedValue' => 3.14,
        ];
        yield 'string to object (ignored)' => [
            'type' => NormalizeArgumentTypesMiddleware::class, // Doesn't matter, just need a class name.
            'value' => 'hello',
            'expectedValue' => 'hello',
        ];
        yield 'int to object (ignored)' => [
            'type' => NormalizeArgumentTypesMiddleware::class, // Doesn't matter, just need a class name.
            'value' => 5,
            'expectedValue' => 5,
        ];
    }

    #[Test, DataProvider('typeMappingExamples')]
    #[TestDox('We can normalize from $_dataName')]
    public function typeMapping(string $type, mixed $value, mixed $expectedValue): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $middleware = new NormalizeArgumentTypesMiddleware($responseBuilder);

        $result = new RouteSuccess(
            action: fn(string $a) => $a,
            arguments: ['a' => $value],
            parameters: ['a' => $type],
        );
        $request = new ServerRequest('GET', '/foo/bar')
            ->withAttribute(RouteResult::class, $result)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertSame(['a' => $expectedValue], $updatedResult->arguments);
    }
}
