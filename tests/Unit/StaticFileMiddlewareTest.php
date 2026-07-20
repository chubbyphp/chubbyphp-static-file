<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Unit\StaticFile;

use Chubbyphp\Mock\MockMethod\WithReturn;
use Chubbyphp\Mock\MockMethod\WithReturnSelf;
use Chubbyphp\Mock\MockObjectBuilder;
use Chubbyphp\StaticFile\StaticFileMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testPathTraversalIsNotServed(): void
    {
        $baseDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $publicDirectory = $baseDirectory.'/public';
        $secretFilename = $baseDirectory.'/secret.outside';
        $requestTarget = '/../secret.outside';
        $secret = 'must not be served';

        mkdir($publicDirectory, 0o777, true);
        file_put_contents($secretFilename, $secret);

        try {
            $builder = new MockObjectBuilder();

            /** @var ServerRequestInterface $request */
            $request = $builder->create(ServerRequestInterface::class, [
                new WithReturn('getRequestTarget', [], $requestTarget),
            ]);

            /** @var ResponseInterface $handlerResponse */
            $handlerResponse = $builder->create(ResponseInterface::class, []);

            $handler = new class($handlerResponse) implements RequestHandlerInterface {
                public function __construct(private ResponseInterface $response) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->response;
                }
            };

            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $builder->create(ResponseFactoryInterface::class, []);

            /** @var StreamFactoryInterface $streamFactory */
            $streamFactory = $builder->create(StreamFactoryInterface::class, []);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($handlerResponse, $middleware->process($request, $handler));
        } finally {
            unlink($secretFilename);
            rmdir($publicDirectory);
            rmdir($baseDirectory);
        }
    }

    public function testPathTraversalToDirectoryWithSamePrefixIsNotServed(): void
    {
        $baseDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $publicDirectory = $baseDirectory.'/public';
        $privateDirectory = $baseDirectory.'/public-private';
        $secretFilename = $privateDirectory.'/secret.outside';

        mkdir($publicDirectory, 0o777, true);
        mkdir($privateDirectory, 0o777, true);
        file_put_contents($secretFilename, 'must not be served');

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn('/../public-private/secret.outside');
            $request->method('getMethod')->willReturn('GET');

            $handlerResponse = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($handlerResponse);

            $staticFileResponse = self::createStub(ResponseInterface::class);
            $staticFileResponse->method('withHeader')->willReturnSelf();
            $staticFileResponse->method('withBody')->willReturnSelf();

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $responseFactory->method('createResponse')->willReturn($staticFileResponse);

            $responseBody = self::createStub(StreamInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);
            $streamFactory->method('createStreamFromFile')->willReturn($responseBody);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($handlerResponse, $middleware->process($request, $handler));
        } finally {
            unlink($secretFilename);
            rmdir($privateDirectory);
            rmdir($publicDirectory);
            rmdir($baseDirectory);
        }
    }

    public function testFileIsNotAValidPublicDirectory(): void
    {
        $publicDirectory = tempnam(sys_get_temp_dir(), 'chubbyphp-static-file-');

        try {
            $builder = new MockObjectBuilder();

            /** @var ServerRequestInterface $request */
            $request = $builder->create(ServerRequestInterface::class, []);

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
        } finally {
            unlink($publicDirectory);
        }
    }

    public function testDirectoryWithinPublicDirectoryIsNotServed(): void
    {
        $publicDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $directory = $publicDirectory.'/assets';

        mkdir($directory, 0o777, true);

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn('/assets');
            $request->method('getMethod')->willReturn('GET');

            $response = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($response);

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($response, $middleware->process($request, $handler));
        } finally {
            rmdir($directory);
            rmdir($publicDirectory);
        }
    }

    public function testFifoWithinPublicDirectoryIsNotServed(): void
    {
        $publicDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $fifo = $publicDirectory.'/asset.fifo';

        mkdir($publicDirectory, 0o777, true);

        if (\function_exists('posix_mkfifo')) {
            posix_mkfifo($fifo, 0o666);
        } else {
            @exec('mkfifo '.escapeshellarg($fifo).' 2>/dev/null');
        }

        if (!file_exists($fifo)) {
            rmdir($publicDirectory);

            self::markTestSkipped('unable to create a fifo');
        }

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn('/asset.fifo');
            $request->method('getMethod')->willReturn('GET');

            $response = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($response);

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($response, $middleware->process($request, $handler));
        } finally {
            unlink($fifo);
            rmdir($publicDirectory);
        }
    }

    public function testRelativeRequestTargetIsNotServed(): void
    {
        $publicDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $filename = $publicDirectory.'/asset.outside';

        mkdir($publicDirectory, 0o777, true);
        file_put_contents($filename, 'asset');

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn('asset.outside');
            $request->method('getMethod')->willReturn('GET');

            $handlerResponse = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($handlerResponse);

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($handlerResponse, $middleware->process($request, $handler));
        } finally {
            unlink($filename);
            rmdir($publicDirectory);
        }
    }

    public function testRequestTargetQueryStringIsNotPartOfFilename(): void
    {
        $publicDirectory = sys_get_temp_dir().'/'.uniqid('chubbyphp-static-file-', true);
        $filename = $publicDirectory.'/asset.outside';

        mkdir($publicDirectory, 0o777, true);
        file_put_contents($filename, 'asset');

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn('/asset.outside?version=1');
            $request->method('getMethod')->willReturn('GET');

            $handlerResponse = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($handlerResponse);

            $staticFileResponse = self::createStub(ResponseInterface::class);
            $staticFileResponse->method('withHeader')->willReturnSelf();
            $staticFileResponse->method('withBody')->willReturnSelf();

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $responseFactory->method('createResponse')->willReturn($staticFileResponse);

            $responseBody = self::createStub(StreamInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);
            $streamFactory->method('createStreamFromFile')->willReturn($responseBody);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($staticFileResponse, $middleware->process($request, $handler));
        } finally {
            unlink($filename);
            rmdir($publicDirectory);
        }
    }

    public function testFileIsServedWhenPublicDirectoryIsFilesystemRoot(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'chubbyphp-static-file-');

        file_put_contents($filename, 'asset');

        try {
            $request = self::createStub(ServerRequestInterface::class);
            $request->method('getRequestTarget')->willReturn($filename);
            $request->method('getMethod')->willReturn('GET');

            $handlerResponse = self::createStub(ResponseInterface::class);
            $handler = self::createStub(RequestHandlerInterface::class);
            $handler->method('handle')->willReturn($handlerResponse);

            $staticFileResponse = self::createStub(ResponseInterface::class);
            $staticFileResponse->method('withHeader')->willReturnSelf();
            $staticFileResponse->method('withBody')->willReturnSelf();

            $responseFactory = self::createStub(ResponseFactoryInterface::class);
            $responseFactory->method('createResponse')->willReturn($staticFileResponse);

            $responseBody = self::createStub(StreamInterface::class);
            $streamFactory = self::createStub(StreamFactoryInterface::class);
            $streamFactory->method('createStreamFromFile')->willReturn($responseBody);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, \DIRECTORY_SEPARATOR);

            self::assertSame($staticFileResponse, $middleware->process($request, $handler));
        } finally {
            unlink($filename);
        }
    }

    public function testInvalidHashAlgorithm(): void
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

    #[DataProvider('provideFiles')]
    public function testIfMatch(string $body, string $contentLength, ?string $contentType, string $extension): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';

        file_put_contents($filename, $body);

        $hash = '"'.hash_file($hashAlgorithm, $filename).'"';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getMethod', [], 'GET'),
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

    #[DataProvider('provideFiles')]
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

        $hash = '"'.hash_file('md5', $filename).'"';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getMethod', [], 'GET'),
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

    #[DataProvider('provideIfNoneMatchRecognizesWildcardWeakAndListedEtagsCases')]
    public function testIfNoneMatchRecognizesWildcardWeakAndListedEtags(string $type): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.outside';
        $filename = $publicDirectory.$requestTarget;
        $body = 'asset';

        file_put_contents($filename, $body);

        try {
            $etag = '"'.hash_file('md5', $filename).'"';
            $ifNoneMatch = match ($type) {
                'wildcard' => '*',
                'weak' => 'W/'.$etag,
                'list' => '"other", W/'.$etag,
            };

            $builder = new MockObjectBuilder();

            /** @var ServerRequestInterface $request */
            $request = $builder->create(ServerRequestInterface::class, [
                new WithReturn('getRequestTarget', [], $requestTarget),
                new WithReturn('getMethod', [], 'GET'),
                new WithReturn('getHeaderLine', ['If-None-Match'], $ifNoneMatch),
            ]);

            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', (string) \strlen($body)]),
                new WithReturnSelf('withHeader', ['ETag', $etag]),
            ]);

            /** @var RequestHandlerInterface $handler */
            $handler = $builder->create(RequestHandlerInterface::class, []);

            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $builder->create(ResponseFactoryInterface::class, [
                new WithReturn('createResponse', [304, ''], $response),
            ]);

            /** @var StreamFactoryInterface $streamFactory */
            $streamFactory = $builder->create(StreamFactoryInterface::class, []);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory, 'md5', []);

            self::assertSame($response, $middleware->process($request, $handler));
        } finally {
            unlink($filename);
        }
    }

    public static function provideIfNoneMatchRecognizesWildcardWeakAndListedEtagsCases(): iterable
    {
        return [
            ['wildcard'],
            ['weak'],
            ['list'],
        ];
    }

    #[DataProvider('provideFiles')]
    public function testIfNoneMatch(string $body, string $contentLength, ?string $contentType, string $extension): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';

        file_put_contents($filename, $body);

        $hash = '"'.hash_file($hashAlgorithm, $filename).'"';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getMethod', [], 'GET'),
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

    #[DataProvider('provideFiles')]
    public function testIfNoneMatchWithDifferentEtag(
        string $body,
        string $contentLength,
        ?string $contentType,
        string $extension
    ): void {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';

        file_put_contents($filename, $body);

        $hash = '"'.hash_file($hashAlgorithm, $filename).'"';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getMethod', [], 'GET'),
            new WithReturn('getHeaderLine', ['If-None-Match'], $hash.'-different'),
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

    #[DataProvider('provideFiles')]
    public function testUsesCustomMimetypes(
        string $body,
        string $contentLength,
        ?string $contentType,
        string $extension
    ): void {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.'.$extension;
        $filename = $publicDirectory.$requestTarget;
        $hashAlgorithm = 'adler32';
        $customContentType = 'application/custom-'.$extension;
        $mimetypes = null !== $contentType ? [$extension => $customContentType] : [];

        file_put_contents($filename, $body);

        $hash = '"'.hash_file($hashAlgorithm, $filename).'"';

        $builder = new MockObjectBuilder();

        /** @var ServerRequestInterface $request */
        $request = $builder->create(ServerRequestInterface::class, [
            new WithReturn('getRequestTarget', [], $requestTarget),
            new WithReturn('getMethod', [], 'GET'),
            new WithReturn('getHeaderLine', ['If-None-Match'], $hash),
        ]);

        if (null !== $contentType) {
            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', $contentLength]),
                new WithReturnSelf('withHeader', ['Content-Type', $customContentType]),
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

        $middleware = new StaticFileMiddleware(
            $responseFactory,
            $streamFactory,
            $publicDirectory,
            $hashAlgorithm,
            $mimetypes
        );

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

    public function testHeadRequestIsServedWithoutBody(): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.json';
        $filename = $publicDirectory.$requestTarget;
        $body = '{"key":"value"}';

        file_put_contents($filename, $body);

        try {
            $hash = '"'.hash_file('md5', $filename).'"';

            $builder = new MockObjectBuilder();

            /** @var ServerRequestInterface $request */
            $request = $builder->create(ServerRequestInterface::class, [
                new WithReturn('getRequestTarget', [], $requestTarget),
                new WithReturn('getMethod', [], 'HEAD'),
                new WithReturn('getHeaderLine', ['If-None-Match'], ''),
            ]);

            /** @var ResponseInterface $response */
            $response = $builder->create(ResponseInterface::class, [
                new WithReturnSelf('withHeader', ['Content-Length', (string) \strlen($body)]),
                new WithReturnSelf('withHeader', ['Content-Type', 'application/json']),
                new WithReturnSelf('withHeader', ['ETag', $hash]),
            ]);

            /** @var RequestHandlerInterface $handler */
            $handler = $builder->create(RequestHandlerInterface::class, []);

            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $builder->create(ResponseFactoryInterface::class, [
                new WithReturn('createResponse', [200, ''], $response),
            ]);

            /** @var StreamFactoryInterface $streamFactory */
            $streamFactory = $builder->create(StreamFactoryInterface::class, []);

            $middleware = new StaticFileMiddleware($responseFactory, $streamFactory, $publicDirectory);

            self::assertSame($response, $middleware->process($request, $handler));
        } finally {
            unlink($filename);
        }
    }

    #[DataProvider('provideNotAllowedMethodIsDelegatedToHandlerCases')]
    public function testNotAllowedMethodIsDelegatedToHandler(string $method): void
    {
        $publicDirectory = sys_get_temp_dir();
        $requestTarget = '/'.uniqid().uniqid().'.json';
        $filename = $publicDirectory.$requestTarget;

        file_put_contents($filename, '{"key":"value"}');

        try {
            $builder = new MockObjectBuilder();

            /** @var ServerRequestInterface $request */
            $request = $builder->create(ServerRequestInterface::class, [
                new WithReturn('getRequestTarget', [], $requestTarget),
                new WithReturn('getMethod', [], $method),
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
        } finally {
            unlink($filename);
        }
    }

    public static function provideNotAllowedMethodIsDelegatedToHandlerCases(): iterable
    {
        return [
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['OPTIONS'],
        ];
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
}
