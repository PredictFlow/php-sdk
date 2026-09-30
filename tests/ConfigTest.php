<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use PredictFlow\Sdk\Config;

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('PREDICTFLOW_API_KEY');
    }

    public function testDefaults(): void
    {
        $config = Config::resolve();
        self::assertSame(Config::DEFAULT_BASE_URL, $config->baseUrl);
        self::assertSame(Config::DEFAULT_TIMEOUT_SECONDS, $config->timeoutSeconds);
        self::assertSame(Config::DEFAULT_MAX_RETRIES, $config->maxRetries);
        self::assertSame('', $config->apiKey);
    }

    public function testExplicitApiKeyWinsOverEnv(): void
    {
        putenv('PREDICTFLOW_API_KEY=pk_live_from_env');
        $config = Config::resolve('pk_live_explicit');
        self::assertSame('pk_live_explicit', $config->apiKey);
    }

    public function testFallsBackToEnvVar(): void
    {
        putenv('PREDICTFLOW_API_KEY=pk_live_from_env');
        $config = Config::resolve();
        self::assertSame('pk_live_from_env', $config->apiKey);
    }

    public function testTrailingSlashStrippedFromBaseUrl(): void
    {
        $config = Config::resolve(baseUrl: 'https://predictflow.co/api/v1/');
        self::assertSame('https://predictflow.co/api/v1', $config->baseUrl);
    }
}
