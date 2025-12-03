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
    private string $hashAlgorithm;

    /**
     * @var array<string, string>
     */
    private array $mimetypes;

    /**
     * @param null|array<string, string> $mimetypes
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private string $publicDirectory,
        string $hashAlgorithm = 'md5',
        ?array $mimetypes = null,
    ) {
        if (!\in_array($hashAlgorithm, hash_algos(), true)) {
            throw new \LogicException(\sprintf('Invalid or not supported hash algorithm: "%s"', $hashAlgorithm));
        }
        $this->hashAlgorithm = $hashAlgorithm;
        $this->mimetypes = $mimetypes ?? require __DIR__.'/mimetypes.php';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $filename = $this->publicDirectory.$request->getRequestTarget();

        if (!is_readable($filename) || is_dir($filename)) {
            return $handler->handle($request);
        }

        $hash = hash_file($this->hashAlgorithm, $filename);

        if ($request->getHeaderLine('If-None-Match') === $hash) {
            return $this->createResponse(304, $filename, $hash);
        }

        return $this->createResponse(200, $filename, $hash)
            ->withBody($this->streamFactory->createStreamFromFile($filename))
        ;
    }

    private function createResponse(int $code, string $filename, string $hash): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);
        $response = $response->withHeader('Content-Length', (string) filesize($filename));
        $response = $this->addContentType($response, $filename);

        return $response->withHeader('ETag', $hash);
    }

    private function addContentType(ResponseInterface $response, string $filename): ResponseInterface
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (isset($this->mimetypes[$extension])) {
            return $response->withHeader('Content-Type', $this->mimetypes[$extension]);
        }

        return $response;
    }
}
