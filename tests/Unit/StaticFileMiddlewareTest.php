<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Unit\StaticFile;

use Chubbyphp\Mock\MockMethod\WithReturn;
use Chubbyphp\Mock\MockMethod\WithReturnSelf;
use Chubbyphp\Mock\MockObjectBuilder;
use Chubbyphp\StaticFile\StaticFileMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \Chubbyphp\StaticFile\StaticFileMiddleware
 *
 * @internal
 */
final class StaticFileMiddlewareTest extends TestCase
{
    public function testIsNotReadable(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.xml';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
        ]);

        /** @var ResponseInterface $response */
        $response = $builder->create(ResponseInterface::class, []);

        /** @var RequestHandlerInterface $handler */
        $handler = $builder->create(RequestHandlerInterface::class, [
            new WithReturn('handle', [$request], $response),
        ]);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, []);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, []);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testInvalidHashAlgorythm(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid or not supported hash algorithm: "unknown"');

        $publicDirectory = sys_get_temp_dir();
        $hashAlgorithm = 'unknown';

        $builder = new MockObjectBuilder();

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, []);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, []);

        new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory, $hashAlgorithm);
    }

    /**
     * @dataProvider provideFiles
     */
    public function testIfMatch(string $body, string $contentLength, ?string $contentType, string $extension): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';

        file_put_contents($filename, $body);

        $hash = hash_file($hashAlgorithm, $filename);

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getHeaderLine', ['If-None-Match'], $hash),
        ]);

        if (null !== $contentType) {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['Content-Type', $contentType]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
            ]);
        } else {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
            ]);
        }

        /** @var RequestHandlerInterface $handler */
        $handler = $builder->create(RequestHandlerInterface::class, []);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, [
            new WithReturn('createResponse', [304, ''], $response),
        ]);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, []);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory, $hashAlgorithm);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    /**
     * @dataProvider provideFiles
     */
    public function testIfMatchWithDefaultHashAlgorithm(
        string $body,
        string $contentLength,
        ?string $contentType,
        string $extension
    ): void {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;

        file_put_contents($filename, $body);

        $hash = hash_file('md5', $filename);

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getHeaderLine', ['If-None-Match'], $hash),
        ]);

        if (null !== $contentType) {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['Content-Type', $contentType]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
            ]);
        } else {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
            ]);
        }

        /** @var RequestHandlerInterface $handler */
        $handler = $builder->create(RequestHandlerInterface::class, []);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, [
            new WithReturn('createResponse', [304, ''], $response),
        ]);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, []);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    /**
     * @dataProvider provideFiles
     */
    public function testIfNoneMatch(string $body, string $contentLength, ?string $contentType, string $extension): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';

        file_put_contents($filename, $body);

        $hash = hash_file($hashAlgorithm, $filename);

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getHeaderLine', ['If-None-Match'], ''),
        ]);

        /** @var StreamInterface $responseBody */
        $responseBody = $builder->create(StreamInterface::class, []);

        if (null !== $contentType) {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['Content-Type', $contentType]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
                new WithReturnSelf('withBody', [$responseBody]),
            ]);
        } else {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
                new WithReturnSelf('withBody', [$responseBody]),
            ]);
        }

        /** @var RequestHandlerInterface $handler */
        $handler = $builder->create(RequestHandlerInterface::class, []);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, [
            new WithReturn('createResponse', [200, ''], $response),
        ]);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, [
            new WithReturn('createStreamFromFile', [$filename, 'r'], $responseBody),
        ]);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory, $hashAlgorithm);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testBaseDirectory(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
        ]);

        /** @var ResponseInterface $response */
        $response = $builder->create(ResponseInterface::class, []);

        /** @var RequestHandlerInterface $handler */
        $handler = $builder->create(RequestHandlerInterface::class, [
            new WithReturn('handle', [$request], $response),
        ]);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $builder->create(ResponseFactoryInterface::class, []);

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $builder->create(StreamFactoryInterface::class, []);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public static function provideFiles(): iterable
    {
        return [
            [
                'body' => '{"key":"value"}',
                'contentLength' => '15',
                'contentType' => 'application/json',
                'extension' => 'json',
            ],
            [
                'body' => '<?xml version="1.0" encoding="UTF-8"?><json:string name="key">value</json:string>',
                'contentLength' => '81',
                'contentType' => 'application/jsonx+xml',
                'extension' => 'jsonx',
            ],
            [
                'body' => '<?xml version="1.0" encoding="UTF-8"?><key>value</key>',
                'contentLength' => '54',
                'contentType' => 'application/xml',
                'extension' => 'xml',
            ],
            [
                'body' => 'key: value',
                'contentLength' => '10',
                'contentType' => 'application/x-yaml',
                'extension' => 'yml',
            ],
            [
                'body' => 'abcdefgh',
                'contentLength' => '8',
                'contentType' => null,
                'extension' => 'xxxxxxxxx',
            ],
        ];
    }
}
