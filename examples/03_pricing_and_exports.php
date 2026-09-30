<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PredictFlow\Sdk\PredictFlow;

$client = new PredictFlow(apiKey: getenv('PREDICTFLOW_API_KEY') ?: null);

$productId = getenv('PREDICTFLOW_EXAMPLE_PRODUCT_ID') ?: '';
$storeId = getenv('PREDICTFLOW_EXAMPLE_STORE_ID') ?: '';

$optimized = $client->pricing->optimize($productId, strategy: 'maximize_profit');
echo "Current price: \${$optimized['current_price']}" . PHP_EOL;
printf("Recommended price: \$%s (%+.1f%%)\n", $optimized['recommended_price'], $optimized['price_change_pct']);
echo "Expected profit change: {$optimized['expected_profit_change_pct']}%" . PHP_EOL;

$simulated = $client->pricing->simulate($productId, newPrice: $optimized['recommended_price'] - 1);
printf(
    "If priced at \$%s instead: %+.1f%% revenue\n",
    $simulated['new_price'],
    $simulated['expected_revenue_change_pct'],
);

$deadStock = $client->exports->deadStock($storeId, format: 'csv');
$outPath = $deadStock->filename ?? 'dead-stock-export.csv';
$deadStock->saveTo($outPath);
echo "Saved {$outPath} (" . strlen($deadStock->content) . ' bytes)' . PHP_EOL;
