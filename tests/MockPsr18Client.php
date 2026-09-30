<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Tests;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A minimal PSR-18 test double: queue responses (or exceptions) up front,
 * they're returned in order as requests come in. Records every request
 * sent so tests can assert on method/URL/headers/body.
 */
final class MockPsr18Client implements ClientInterface
{
    /** @var list<ResponseInterface|ClientExceptionInterface> */
    private array $queue = [];

    /** @var list<RequestInterface> */
    private array $requests = [];

    public function queueResponse(ResponseInterface $response): void
    {
        $this->queue[] = $response;
    }

    public function queueException(ClientExceptionInterface $exception): void
    {
        $this->queue[] = $exception;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new \RuntimeException('MockPsr18Client: no more queued responses');
        }

        $next = array_shift($this->queue);
        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    /** @return list<RequestInterface> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }
}
