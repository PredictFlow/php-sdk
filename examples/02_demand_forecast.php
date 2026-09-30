<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PredictFlow\Sdk\PredictFlow;

$client = new PredictFlow(apiKey: getenv('PREDICTFLOW_API_KEY') ?: null);

$productId = getenv('PREDICTFLOW_EXAMPLE_PRODUCT_ID') ?: '';

$prediction = $client->products->forecast($productId, horizonDays: 30, model: 'prophet');
echo "Prediction {$prediction['id']} generated at {$prediction['generated_at']}" . PHP_EOL;

$stockout = $client->predictions->stockout($prediction['id'], currentStock: 40);
printf(
    "Probability of stockout within %d days: %.1f%%\n",
    $stockout['horizon_days'],
    $stockout['total_stockout_probability'] * 100,
);
if ($stockout['expected_days_until_stockout'] !== null) {
    printf("Expected days until stockout: %.1f\n", $stockout['expected_days_until_stockout']);
}

$recommendation = $client->predictions->inventoryRecommendation($prediction['id'], currentStock: 40, leadTimeDays: 7);
$detail = $recommendation['recommendation'];
$action = $recommendation['action'];
echo "Reorder point: {$detail['reorder_point']} units" . PHP_EOL;
echo "Recommended order quantity: {$detail['reorder_quantity']} units" . PHP_EOL;
echo 'Should reorder now: ' . ($action['should_reorder_now'] ? 'yes' : 'no') . " (urgency: {$action['urgency']})" . PHP_EOL;
