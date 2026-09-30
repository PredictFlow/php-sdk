<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PredictFlow\Sdk\PredictFlow;

$client = new PredictFlow(apiKey: getenv('PREDICTFLOW_API_KEY') ?: null);

$stores = $client->stores->list();
echo 'Connected stores: ' . count($stores) . PHP_EOL;

if ($stores === []) {
    exit;
}

$store = $stores[0];
echo "Using store: {$store['name']} ({$store['id']})" . PHP_EOL;

$health = $client->products->inventoryHealth($store['id']);
printf("Healthy: %d (%.1f%%)\n", $health['healthy'], $health['healthy_pct']);
printf("Low stock: %d (%.1f%%)\n", $health['low_stock'], $health['low_stock_pct']);
printf("Out of stock: %d (%.1f%%)\n", $health['out_of_stock'], $health['out_of_stock_pct']);

$lowStock = $client->products->lowStock($store['id']);
echo "Products below the low-stock threshold ({$lowStock['threshold']}): " . count($lowStock['items']) . PHP_EOL;
