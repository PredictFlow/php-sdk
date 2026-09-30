<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PredictFlow\Sdk\Config;
use PredictFlow\Sdk\Exceptions\NotFoundException;
use PredictFlow\Sdk\Exceptions\RateLimitException;
use PredictFlow\Sdk\Exceptions\ValidationException;
use PredictFlow\Sdk\Http\HttpClient;

final class HttpClientTest extends TestCase
{
    private function client(MockPsr18Client $mock, int $maxRetries = 2): HttpClient
    {
        $config = Config::resolve(apiKey: 'pk_live_test', baseUrl: 'https://predictflow.test/api/v1', maxRetries: $maxRetries);
        $factory = new HttpFactory();

        return new HttpClient($config, $mock, $factory, $factory);
    }

    public function testAuthorizationAndAcceptHeadersAreSent(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"id":"store_1"}'));

        $this->client($mock)->request('GET', '/stores/store_1');

        $request = $mock->requests()[0];
        self::assertSame('Bearer pk_live_test', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testGetRetriesOn500AndSucceeds(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(500, ['Content-Type' => 'application/json'], '{"detail":"Temporary failure"}'));
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"recovered":true}'));

        $result = $this->client($mock)->request('GET', '/health');

        self::assertSame(['recovered' => true], $result);
        self::assertSame(2, $mock->requestCount());
    }

    /**
     * POST isn't guaranteed idempotent - retrying one whose response was
     * lost after the server already processed it risks duplicate side
     * effects, so it must not retry unless the caller opts in per-call.
     */
    public function testPostDoesNotAutoRetryOn500(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(500, ['Content-Type' => 'application/json'], '{"detail":"Temporary failure"}'));

        $this->expectException(\PredictFlow\Sdk\Exceptions\InternalServerException::class);
        try {
            $this->client($mock)->request('POST', '/predictions/pred_1/evaluate');
        } finally {
            self::assertSame(1, $mock->requestCount());
        }
    }

    public function testPostRetriesWhenMaxRetriesExplicitlyPassed(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(500, ['Content-Type' => 'application/json'], '{"detail":"Temporary failure"}'));
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"recovered":true}'));

        $result = $this->client($mock)->request('POST', '/predictions/pred_1/evaluate', maxRetries: 1);

        self::assertSame(['recovered' => true], $result);
        self::assertSame(2, $mock->requestCount());
    }

    public function test404RaisesNotFoundException(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(404, ['Content-Type' => 'application/json'], '{"detail":"Store not found"}'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Store not found');
        $this->client($mock)->request('GET', '/stores/missing');
    }

    public function test422WithArrayDetailRaisesReadableValidationException(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(
            422,
            ['Content-Type' => 'application/json'],
            '{"detail":[{"msg":"Value error, horizon_days must be positive"}]}',
        ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('horizon_days must be positive');
        $this->client($mock)->request('POST', '/products/prod_1/forecast', maxRetries: 0);
    }

    public function test429CarriesRetryAfterAndIsRetried(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(
            429,
            ['Content-Type' => 'application/json', 'Retry-After' => '0'],
            '{"detail":"Rate limited"}',
        ));
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'));

        $result = $this->client($mock)->request('GET', '/products');

        self::assertSame(['ok' => true], $result);
    }

    public function testMaxRetriesExhaustedRaisesRateLimitException(): void
    {
        $mock = new MockPsr18Client();
        for ($i = 0; $i < 2; $i++) {
            $mock->queueResponse(new Response(
                429,
                ['Content-Type' => 'application/json', 'Retry-After' => '0'],
                '{"detail":"Rate limited"}',
            ));
        }

        $this->expectException(RateLimitException::class);
        try {
            $this->client($mock, maxRetries: 1)->request('GET', '/products');
        } finally {
            self::assertSame(2, $mock->requestCount()); // 1 original + 1 retry
        }
    }

    public function testDeleteReturnsNullOn204(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(204));

        $result = $this->client($mock)->request('DELETE', '/stores/store_1');

        self::assertNull($result);
    }

    public function testNetworkExceptionRetriesOnGet(): void
    {
        $mock = new MockPsr18Client();
        $factory = new HttpFactory();
        $request = $factory->createRequest('GET', 'https://predictflow.test/api/v1/health');
        $mock->queueException(new MockNetworkException($request));
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"recovered":true}'));

        $result = $this->client($mock)->request('GET', '/health');

        self::assertSame(['recovered' => true], $result);
    }

    public function testQueryParamsWithNullValuesAreDropped(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'));

        $this->client($mock)->request('GET', '/analytics/kpis', query: ['days' => 30, 'store_id' => null]);

        $uri = (string) $mock->requests()[0]->getUri();
        self::assertStringNotContainsString('store_id', $uri);
        self::assertStringContainsString('days=30', $uri);
    }
}
