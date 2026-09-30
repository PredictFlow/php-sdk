<?php

declare(strict_types=1);

namespace PredictFlow\Sdk;

use PredictFlow\Sdk\Http\HttpClient;
use PredictFlow\Sdk\Resources\AlertsResource;
use PredictFlow\Sdk\Resources\AnalyticsResource;
use PredictFlow\Sdk\Resources\ExportsResource;
use PredictFlow\Sdk\Resources\PredictionsResource;
use PredictFlow\Sdk\Resources\PricingResource;
use PredictFlow\Sdk\Resources\ProductsResource;
use PredictFlow\Sdk\Resources\ScenariosResource;
use PredictFlow\Sdk\Resources\StoresResource;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The unified entry point for the PredictFlow API.
 *
 * Example:
 *   $client = new PredictFlow(apiKey: 'pk_live_...');
 *   $forecast = $client->products->forecast('prod_123', horizonDays: 30);
 */
final class PredictFlow
{
    public readonly Config $config;
    public readonly StoresResource $stores;
    public readonly ProductsResource $products;
    public readonly PredictionsResource $predictions;
    public readonly AnalyticsResource $analytics;
    public readonly PricingResource $pricing;
    public readonly ScenariosResource $scenarios;
    public readonly AlertsResource $alerts;
    public readonly ExportsResource $exports;

    private readonly HttpClient $http;

    /** @param array<string, string>|null $defaultHeaders */
    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?float $timeoutSeconds = null,
        ?int $maxRetries = null,
        ?array $defaultHeaders = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->config = Config::resolve($apiKey, $baseUrl, $timeoutSeconds, $maxRetries, $defaultHeaders);
        $this->http = new HttpClient($this->config, $httpClient, $requestFactory, $streamFactory);

        $this->stores = new StoresResource($this->http);
        $this->products = new ProductsResource($this->http);
        $this->predictions = new PredictionsResource($this->http);
        $this->analytics = new AnalyticsResource($this->http);
        $this->pricing = new PricingResource($this->http);
        $this->scenarios = new ScenariosResource($this->http);
        $this->alerts = new AlertsResource($this->http);
        $this->exports = new ExportsResource($this->http);
    }

    /**
     * Checks API connectivity. /health lives at the bare origin, not
     * under /api/v1, so this falls back to the root URL if the
     * configured base_url includes the /api/v1 prefix (the default).
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        try {
            return $this->http->request('GET', '/health');
        } catch (\Throwable) {
            $parts = parse_url($this->config->baseUrl);
            $root = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
            if (isset($parts['port'])) {
                $root .= ':' . $parts['port'];
            }

            return $this->http->requestAbsolute('GET', $root . '/health');
        }
    }
}
