<?php

declare(strict_types=1);

namespace Chubbyphp\StaticFile;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StaticFileMiddleware implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var string
     */
    private $publicDirectory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        string $publicDirectory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->publicDirectory = $publicDirectory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $filename = $this->publicDirectory.$request->getRequestTarget();

        if (!is_readable($filename)) {
            return $handler->handle($request);
        }

        $hash = hash_file('sha256', $filename);

        if ($request->getHeaderLine('If-None-Match') === $hash) {
            return $this->responseFactory->createResponse(304)
                ->withHeader('ETag', $hash)
            ;
        }

        return $this->createResponse($filename, $hash);
    }

    private function createResponse(string $filename, string $hash): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200);

        if ($contentLength = filesize($filename)) {
            $response = $response->withHeader('Content-Length', (string) $contentLength);
        }

        if ($contentType = mime_content_type($filename)) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        $response = $response->withHeader('ETag', $hash);

        return $response->withBody($this->streamFactory->createStreamFromFile($filename));
    }
}
