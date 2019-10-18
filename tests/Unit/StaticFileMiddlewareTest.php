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

    public function testIfMatch(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.xml';
        $filename = $publicDirectory.$requestTarget;

        file_put_contents($filename, '<?xml version="1.0" encoding="UTF-8"?><test></test>');

        $hash = hash_file('sha256', $filename);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
            Call::create('getHeaderLine')->with('If-None-Match')->willReturn($hash),
        ]);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class, [
            Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
        ]);

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

    public function testIfNotMatch(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.xml';
        $filename = $publicDirectory.$requestTarget;

        file_put_contents($filename, '<?xml version="1.0" encoding="UTF-8"?><test></test>');

        $hash = hash_file('sha256', $filename);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getRequestTarget')->with()->willReturn($requestTarget),
            Call::create('getHeaderLine')->with('If-None-Match')->willReturn(''),
        ]);

        /** @var StreamInterface|MockObject $responseBody */
        $responseBody = $this->getMockByCalls(StreamInterface::class);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class, [
            Call::create('withHeader')->with('Content-Length', '51')->willReturnSelf(),
            Call::create('withHeader')->with('Content-Type', 'application/xml')->willReturnSelf(),
            Call::create('withHeader')->with('ETag', $hash)->willReturnSelf(),
            Call::create('withBody')->with($responseBody)->willReturnSelf(),
        ]);

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

        $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

        self::assertSame($response, $middleware->process($request, $handler));
    }
}
