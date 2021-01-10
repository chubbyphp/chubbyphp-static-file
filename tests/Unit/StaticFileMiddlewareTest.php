<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Unit\StaticFile;

use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\StaticFile\StaticFileMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
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
    use MockByCallsTrait;

    public function testIsNotReadable(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.xml';

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
        ]);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class);

        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockByCalls(RequestHandlerInterface::class, [
            Call::create('handle')->with($request)->willReturn($response),
        ]);

        /** @var ResponseFactoryInterface|MockObject $responseFactory */
        $responseFactory = $this->getMockByCalls(ResponseFactoryInterface::class);

        /** @var StreamFactoryInterface|MockObject $streamFactory */
        $streamFactory = $this->getMockByCalls(StreamFactoryInterface::class);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testInvalidHashAlgorythm(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid or not supported hash algorithm: "unknown"');

        $publicDirectory = sys_get_temp_dir();
        $hashAlgorithm = 'unknown';

        /** @var ResponseFactoryInterface|MockObject $responseFactory */
        $responseFactory = $this->getMockByCalls(ResponseFactoryInterface::class);

        /** @var StreamFactoryInterface|MockObject $streamFactory */
        $streamFactory = $this->getMockByCalls(StreamFactoryInterface::class);

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

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
            Call::create('getHeaderLine')->with('If-None-Match')->willReturn($hash),
        ]);

        if (null !== $contentType) {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('Content-Type', $contentType)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
            ]);
        } else {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
            ]);
        }

        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockByCalls(RequestHandlerInterface::class);

        /** @var ResponseFactoryInterface|MockObject $responseFactory */
        $responseFactory = $this->getMockByCalls(ResponseFactoryInterface::class, [
            Call::create('createResponse')->with(304, '')->willReturn($response),
        ]);

        /** @var StreamFactoryInterface|MockObject $streamFactory */
        $streamFactory = $this->getMockByCalls(StreamFactoryInterface::class);

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

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
            Call::create('getHeaderLine')->with('If-None-Match')->willReturn($hash),
        ]);

        if (null !== $contentType) {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('Content-Type', $contentType)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
            ]);
        } else {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
            ]);
        }

        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockByCalls(RequestHandlerInterface::class);

        /** @var ResponseFactoryInterface|MockObject $responseFactory */
        $responseFactory = $this->getMockByCalls(ResponseFactoryInterface::class, [
            Call::create('createResponse')->with(304, '')->willReturn($response),
        ]);

        /** @var StreamFactoryInterface|MockObject $streamFactory */
        $streamFactory = $this->getMockByCalls(StreamFactoryInterface::class);

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

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
            Call::create('getHeaderLine')->with('If-None-Match')->willReturn(''),
        ]);

        /** @var StreamInterface|MockObject $responseBody */
        $responseBody = $this->getMockByCalls(StreamInterface::class);

        if (null !== $contentType) {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('Content-Type', $contentType)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
                Call::create('withBody')->with($responseBody)->willReturnSelf(),
            ]);
        } else {
            /** @var ResponseInterface|MockObject $response */
            $response = $this->getMockByCalls(ResponseInterface::class, [
                Call::create('withHeader')->with('Content-Length', $contentLength)->willReturnSelf(),
                Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
                Call::create('withBody')->with($responseBody)->willReturnSelf(),
            ]);
        }

        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockByCalls(RequestHandlerInterface::class);

        /** @var ResponseFactoryInterface|MockObject $responseFactory */
        $responseFactory = $this->getMockByCalls(ResponseFactoryInterface::class, [
            Call::create('createResponse')->with(200, '')->willReturn($response),
        ]);

        /** @var StreamFactoryInterface|MockObject $streamFactory */
        $streamFactory = $this->getMockByCalls(StreamFactoryInterface::class, [
            Call::create('createStreamFromFile')->with($filename, 'r')->willReturn($responseBody),
        ]);

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory, $hashAlgorithm);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function provideFiles(): array
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
