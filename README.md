# PredictFlow PHP SDK

[![Packagist](https://img.shields.io/packagist/v/predictflow/sdk.svg)](https://packagist.org/packages/predictflow/sdk)
[![PHP](https://img.shields.io/packagist/php-v/predictflow/sdk.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

The official PHP SDK for [PredictFlow](https://predictflow.co) — demand forecasting, inventory intelligence, and pricing optimization for Shopify and WooCommerce stores.

---

## Installation

```bash
composer require predictflow/sdk
```

### Bring your own HTTP client

This SDK is built against [PSR-18](https://www.php-fig.org/psr/psr-18/) (`ClientInterface`) and [PSR-17](https://www.php-fig.org/psr/psr-17/) (request/stream factories) rather than depending on Guzzle directly. That matters most inside a WordPress/WooCommerce plugin, where you often want to reuse whatever HTTP stack is already there instead of pulling in a second one.

If you already have a PSR-18 client installed (Guzzle, Symfony HttpClient, etc.), it's discovered automatically via [`php-http/discovery`](https://github.com/php-http/discovery) - you don't need to configure anything. If not, install one:

```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

Or pass your own explicitly (see [`examples/04_custom_http_client.php`](examples/04_custom_http_client.php)):

```php
new PredictFlow\Sdk\PredictFlow(
    apiKey: 'pk_live_...',
    httpClient: $yourPsr18Client,
    requestFactory: $yourPsr17Factory,
    streamFactory: $yourPsr17Factory,
);
```

## Quickstart

```php
use PredictFlow\Sdk\PredictFlow;

$client = new PredictFlow(apiKey: 'pk_live_...'); // or set PREDICTFLOW_API_KEY

$stores = $client->stores->list();
$forecast = $client->products->forecast(productId: 'prod_123', horizonDays: 30);
echo $forecast['forecast_data'];
```

## Resources

| Resource | Covers |
|---|---|
| `$client->stores` | Connect/manage stores, trigger syncs, import CSV catalogs and orders |
| `$client->products` | Catalog, inventory health, ABC analysis, forecasting, cost/margin, competitor prices |
| `$client->predictions` | Simulation, stockout risk, and inventory policy for an existing forecast |
| `$client->analytics` | KPIs, revenue trends, day-of-week seasonality, product growth, projections |
| `$client->pricing` | Elasticity, optimization, simulation, cross-elasticity, batch repricing |
| `$client->scenarios` | What-if scenario planning and side-by-side comparisons |
| `$client->alerts` | Alert rules, templates, and evaluation history |
| `$client->exports` | CSV/XLSX/PDF exports — returns a `FileDownload` (raw bytes + filename), not JSON |

See [`examples/`](examples/) for runnable scripts covering each of these.

## Error handling

```php
use PredictFlow\Sdk\Exceptions\{
    AuthenticationException,
    NotFoundException,
    ValidationException,
    RateLimitException,
    PredictFlowException,
};

try {
    $forecast = $client->products->forecast('prod_missing');
} catch (AuthenticationException $e) {
    echo 'Check your API key';
} catch (NotFoundException $e) {
    echo 'Product not found';
} catch (RateLimitException $e) {
    echo "Rate limited, retry after {$e->retryAfterSeconds}s";
} catch (ValidationException $e) {
    echo "Invalid request: {$e->getMessage()}";
} catch (PredictFlowException $e) {
    echo "API error [{$e->status}]: {$e->getMessage()}";
}
```

Every non-2xx response raises a specific `PredictFlowException` subclass, never a bare PSR-18 exception. One deliberate gap: there's no separate `TimeoutException`. PSR-18 doesn't standardize a portable way to distinguish "timed out" from other transport failures across different client implementations, so both surface as `ConnectionException` - shipping a `TimeoutException` that would never actually fire felt worse than not having one.

## Client options

```php
$client = new PredictFlow(
    apiKey: 'pk_live_...',              // or PREDICTFLOW_API_KEY env var
    baseUrl: 'https://predictflow.co/api/v1', // default
    timeoutSeconds: 30.0,
    maxRetries: 2,                       // applies to GET/PUT/DELETE by default
    defaultHeaders: ['X-Custom-App' => 'my-integration/1.0'],
);
```

Every resource method also accepts a per-call `maxRetries` where relevant - e.g. pass `maxRetries: 1` to opt a specific `POST` call into retries if you know that particular endpoint is safe to retry (see [Retries](#retries) below).

## Retries

`GET`/`PUT`/`DELETE` retry automatically on `429`/5xx and transport-level failures, with backoff. `POST`/`PATCH` don't, by default - they aren't guaranteed idempotent, and retrying one whose response was lost after the server already processed it (a sync trigger, a CSV import, a forecast run) risks duplicate side effects. Pass `maxRetries` explicitly on a specific call if you know it's safe.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) — please don't open a public issue for a vulnerability.

## License

MIT © [PredictFlow](https://predictflow.co)
