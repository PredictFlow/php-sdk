<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use PredictFlow\Sdk\Exceptions\AuthenticationException;
use PredictFlow\Sdk\Exceptions\ExceptionFactory;
use PredictFlow\Sdk\Exceptions\InternalServerException;
use PredictFlow\Sdk\Exceptions\NotFoundException;
use PredictFlow\Sdk\Exceptions\RateLimitException;
use PredictFlow\Sdk\Exceptions\ValidationException;

final class ExceptionFactoryTest extends TestCase
{
    public function testMaps401ToAuthenticationException(): void
    {
        $exception = ExceptionFactory::fromResponse(401, ['detail' => 'Invalid or expired API key']);
        self::assertInstanceOf(AuthenticationException::class, $exception);
        self::assertSame('Invalid or expired API key', $exception->getMessage());
        self::assertSame(401, $exception->status);
    }

    public function testMaps404ToNotFoundException(): void
    {
        $exception = ExceptionFactory::fromResponse(404, ['detail' => 'Store not found']);
        self::assertInstanceOf(NotFoundException::class, $exception);
    }

    /**
     * 422s return `detail` as a list of {msg, loc, type} objects, not a
     * string - this is the exact bug pattern that has bitten the main
     * PredictFlow frontend before; the fix must hold here too.
     */
    public function testUnwrapsPydanticArrayDetailIntoReadableMessage(): void
    {
        $detail = [
            ['loc' => ['body', 'horizon_days'], 'msg' => 'Value error, horizon_days must be positive', 'type' => 'value_error'],
        ];
        $exception = ExceptionFactory::fromResponse(422, ['detail' => $detail]);

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame('horizon_days must be positive', $exception->getMessage());
        self::assertSame($detail, $exception->details);
    }

    public function testJoinsMultipleValidationMessages(): void
    {
        $exception = ExceptionFactory::fromResponse(422, [
            'detail' => [['msg' => 'field a is required'], ['msg' => 'field b must be positive']],
        ]);
        self::assertSame('field a is required field b must be positive', $exception->getMessage());
    }

    public function testFallsBackToGenericMessageWhenBodyHasNoDetail(): void
    {
        $exception = ExceptionFactory::fromResponse(500, []);
        self::assertInstanceOf(InternalServerException::class, $exception);
        self::assertSame('API request failed with status 500', $exception->getMessage());
    }

    public function testRateLimitExceptionCarriesRetryAfterSeconds(): void
    {
        $exception = ExceptionFactory::fromResponse(429, ['detail' => 'Rate limit exceeded'], retryAfterHeader: '30');
        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertSame(30.0, $exception->retryAfterSeconds);
    }

    public function testUnparseableRetryAfterIsIgnoredNotRaised(): void
    {
        $exception = ExceptionFactory::fromResponse(429, ['detail' => 'Rate limit exceeded'], retryAfterHeader: 'not-a-number');
        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertNull($exception->retryAfterSeconds);
    }
}
