<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PredictFlow\Sdk\Config;
use PredictFlow\Sdk\Exceptions\ConnectionException;
use PredictFlow\Sdk\Exceptions\ExceptionFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The HTTP layer: retries, and typed error mapping, over whatever PSR-18
 * client is discovered or injected. POST/PATCH aren't guaranteed
 * idempotent - retrying one whose response was lost after the server
 * already processed it (a sync trigger, a CSV import, a forecast run)
 * risks duplicate side effects, with no idempotency-key mechanism to
 * protect against it. GET/PUT/DELETE are safe to retry by default; a
 * caller who knows a specific POST/PATCH call is safe can still opt in
 * by passing maxRetries explicitly on that call.
 */
final class HttpClient
{
    private const IDEMPOTENT_METHODS = ['GET', 'PUT', 'DELETE'];
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];
    private const SDK_USER_AGENT = 'predictflow-php';

    private readonly ClientInterface $client;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $jsonBody
     * @param array<int, array{field: string, filename: string, content: string, contentType: string}>|null $files
     * @param array<string, string>|null $headers
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $jsonBody = null,
        ?array $files = null,
        ?array $headers = null,
        ?int $maxRetries = null,
    ): mixed {
        $response = $this->send($method, $path, $query, $jsonBody, $files, $headers, $maxRetries);
        return $this->decodeJsonOrRaise($response);
    }

    /**
     * Like request(), but against an absolute URL instead of one joined
     * to config->baseUrl - only needed for /health, which lives at the
     * bare origin rather than under the /api/v1 prefix.
     */
    public function requestAbsolute(string $method, string $absoluteUrl): mixed
    {
        $response = $this->send($method, $absoluteUrl, null, null, null, null, 0, absoluteUrl: true);
        return $this->decodeJsonOrRaise($response);
    }

    /** @param array<string, mixed>|null $query */
    public function requestFile(string $method, string $path, ?array $query = null): FileDownload
    {
        $response = $this->send($method, $path, $query, null, null, null, 0);
        if ($response->getStatusCode() >= 400) {
            $this->raiseFromResponse($response);
        }

        $disposition = $response->getHeaderLine('content-disposition');
        $filename = null;
        if ($disposition !== '' && preg_match('/filename="?([^";]+)"?/', $disposition, $matches) === 1) {
            $filename = $matches[1];
        }

        return new FileDownload(
            content: (string) $response->getBody(),
            contentType: $response->getHeaderLine('content-type') ?: 'application/octet-stream',
            filename: $filename,
        );
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $jsonBody
     * @param array<int, array{field: string, filename: string, content: string, contentType: string}>|null $files
     * @param array<string, string>|null $headers
     */
    private function send(
        string $method,
        string $path,
        ?array $query,
        ?array $jsonBody,
        ?array $files,
        ?array $headers,
        ?int $maxRetries,
        bool $absoluteUrl = false,
    ): ResponseInterface {
        $url = $absoluteUrl ? $path : $this->config->baseUrl . $path;
        $cleanedQuery = $this->cleanParams($query ?? []);
        if ($cleanedQuery !== []) {
            $url .= '?' . http_build_query($cleanedQuery);
        }

        $retries = $maxRetries ?? (in_array($method, self::IDEMPOTENT_METHODS, true) ? $this->config->maxRetries : 0);

        $request = $this->requestFactory->createRequest($method, $url);
        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('User-Agent', self::SDK_USER_AGENT);
        foreach ($this->config->defaultHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        foreach ($headers ?? [] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($this->config->apiKey !== '') {
            $request = $request->withHeader('Authorization', "Bearer {$this->config->apiKey}");
        }

        if ($files !== null && $files !== []) {
            $builder = new MultipartBuilder();
            foreach ($files as $file) {
                $builder->addFile($file['field'], $file['filename'], $file['content'], $file['contentType']);
            }
            $request = $request->withHeader('Content-Type', $builder->contentType());
            $request = $request->withBody($this->streamFactory->createStream($builder->build()));
        } elseif ($jsonBody !== null) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withBody($this->streamFactory->createStream((string) json_encode($jsonBody)));
        }

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = $this->client->sendRequest($request);
            } catch (ClientExceptionInterface $exception) {
                if ($attempt <= $retries) {
                    usleep((int) ($this->backoffSeconds($attempt, null) * 1_000_000));
                    continue;
                }
                throw new ConnectionException($exception->getMessage(), previous: $exception);
            }

            if (in_array($response->getStatusCode(), self::RETRYABLE_STATUSES, true) && $attempt <= $retries) {
                $retryAfter = $response->getHeaderLine('retry-after') ?: null;
                usleep((int) ($this->backoffSeconds($attempt, $retryAfter) * 1_000_000));
                continue;
            }

            return $response;
        }
    }

    private function decodeJsonOrRaise(ResponseInterface $response): mixed
    {
        if ($response->getStatusCode() >= 400) {
            $this->raiseFromResponse($response);
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            return null;
        }

        $contentType = $response->getHeaderLine('content-type');
        if (!str_contains($contentType, 'application/json')) {
            return $body;
        }

        return json_decode($body, associative: true);
    }

    private function raiseFromResponse(ResponseInterface $response): never
    {
        $body = null;
        $rawBody = (string) $response->getBody();
        if ($rawBody !== '' && str_contains($response->getHeaderLine('content-type'), 'application/json')) {
            $body = json_decode($rawBody, associative: true);
        }

        throw ExceptionFactory::fromResponse(
            $response->getStatusCode(),
            $body,
            $response->getHeaderLine('x-request-id') ?: null,
            $response->getHeaderLine('retry-after') ?: null,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function cleanParams(array $params): array
    {
        return array_filter($params, static fn (mixed $value): bool => $value !== null);
    }

    private function backoffSeconds(int $attempt, ?string $retryAfterHeader): float
    {
        if ($retryAfterHeader !== null && is_numeric($retryAfterHeader)) {
            $parsed = (float) $retryAfterHeader;
            if ($parsed > 0) {
                return $parsed;
            }
        }

        $base = min(0.2 * (2 ** ($attempt - 1)), 4.0);
        return $base + (random_int(0, 100) / 1000);
    }
}
