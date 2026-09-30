<?php

declare(strict_types=1);

namespace PredictFlow\Sdk;

/**
 * Resolved, immutable client configuration.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://predictflow.co/api/v1';
    public const DEFAULT_TIMEOUT_SECONDS = 30.0;
    public const DEFAULT_MAX_RETRIES = 2;

    /** @param array<string, string> $defaultHeaders */
    private function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl,
        public readonly float $timeoutSeconds,
        public readonly int $maxRetries,
        public readonly array $defaultHeaders,
    ) {
    }

    /**
     * Resolves user-provided options into a complete configuration.
     *
     * apiKey falls back to the PREDICTFLOW_API_KEY environment variable if
     * not passed explicitly - matches the JS/Python SDKs' same fallback so
     * a consumer moving between languages doesn't have to relearn this.
     *
     * @param array<string, string>|null $defaultHeaders
     */
    public static function resolve(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?float $timeoutSeconds = null,
        ?int $maxRetries = null,
        ?array $defaultHeaders = null,
    ): self {
        $resolvedKey = $apiKey ?? (getenv('PREDICTFLOW_API_KEY') ?: '');
        $resolvedBaseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');

        return new self(
            apiKey: $resolvedKey,
            baseUrl: $resolvedBaseUrl,
            timeoutSeconds: $timeoutSeconds ?? self::DEFAULT_TIMEOUT_SECONDS,
            maxRetries: $maxRetries ?? self::DEFAULT_MAX_RETRIES,
            defaultHeaders: $defaultHeaders ?? [],
        );
    }
}
