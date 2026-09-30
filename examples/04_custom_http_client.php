<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PredictFlow\Sdk\PredictFlow;

/**
 * By default, PredictFlow discovers whatever PSR-18 client is already
 * installed (via php-http/discovery) - useful in a plain PHP project. But
 * inside a WordPress/WooCommerce plugin, you often want to route requests
 * through WordPress's own HTTP layer instead of pulling in a second HTTP
 * stack, or you may already have a specifically-configured client (custom
 * timeouts, a proxy, request logging) elsewhere in your app. Pass it in
 * explicitly and the SDK uses that instead of discovering one.
 */
$customClient = new Client([
    'timeout' => 15,
    'headers' => ['X-App-Name' => 'my-woocommerce-plugin/1.0'],
]);
$factory = new HttpFactory();

$client = new PredictFlow(
    apiKey: getenv('PREDICTFLOW_API_KEY') ?: null,
    httpClient: $customClient,
    requestFactory: $factory,
    streamFactory: $factory,
);

$stores = $client->stores->list();
echo 'Connected stores (via custom HTTP client): ' . count($stores) . PHP_EOL;
