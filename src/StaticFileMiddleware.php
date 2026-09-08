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
    private readonly string $hashAlgorithm;

    /**
     * @var array<string, string>
     */
    private readonly array $mimetypes;

    /**
     * @param null|array<string, string> $mimetypes
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $publicDirectory,
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
        $filename = $this->resolveFilename($request);
        $response = null !== $filename ? $this->createFileResponse($request, $filename) : null;

        return $response ?? $handler->handle($request);
    }

    private function resolveFilename(ServerRequestInterface $request): ?string
    {
        $publicDirectory = realpath($this->publicDirectory);
        if (false === $publicDirectory || !is_dir($publicDirectory)) {
            return null;
        }

        $requestTarget = $request->getRequestTarget();
        $requestPath = substr($requestTarget, 0, strcspn($requestTarget, '?'));
        $filename = realpath($publicDirectory.$requestPath);

        if (
            false === $filename
            || !str_starts_with($filename, rtrim($publicDirectory, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR)
            || !is_file($filename)
            || !is_readable($filename)
        ) {
            return null;
        }

        return $filename;
    }

    private function createFileResponse(ServerRequestInterface $request, string $filename): ?ResponseInterface
    {
        $method = $request->getMethod();
        if (!\in_array($method, ['GET', 'HEAD'], true)) {
            return null;
        }

        $fileSize = @filesize($filename);
        $hash = @hash_file($this->hashAlgorithm, $filename);

        if (!\is_int($fileSize) || !\is_string($hash)) {
            return null;
        }

        $etag = '"'.$hash.'"';

        return $this->matchesIfNoneMatch($request->getHeaderLine('If-None-Match'), $etag)
            ? $this->createResponse(304, $filename, $fileSize, $etag)
            : $this->createBodyResponse($method, $filename, $fileSize, $etag);
    }

    private function createBodyResponse(string $method, string $filename, int $fileSize, string $etag): ?ResponseInterface
    {
        $response = $this->createResponse(200, $filename, $fileSize, $etag);
        if ('HEAD' === $method) {
            return $response;
        }

        try {
            return $response->withBody($this->streamFactory->createStreamFromFile($filename));
        } catch (\RuntimeException) {
            return null;
        }
    }

    private function createResponse(int $code, string $filename, int $fileSize, string $etag): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);
        $response = $response->withHeader('Content-Length', (string) $fileSize);
        $response = $this->addContentType($response, $filename);
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

        return $response->withHeader('ETag', $etag);
    }

    private function matchesIfNoneMatch(string $header, string $etag): bool
    {
        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);
            if ('*' === $candidate) {
                return true;
            }

            if (str_starts_with($candidate, 'W/')) {
                $candidate = substr($candidate, 2);
            }

            if ($etag === $candidate) {
                return true;
            }
        }

        return false;
    }

    private function addContentType(ResponseInterface $response, string $filename): ResponseInterface
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $response->withHeader('Content-Type', $this->mimetypes[$extension] ?? 'application/octet-stream');
    }
}
