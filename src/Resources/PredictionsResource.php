<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/**
 * Monte Carlo simulation, stockout risk, and inventory policy for an
 * existing prediction. Forecast generation itself lives on
 * ProductsResource (forecast/forecastStore) since that's where the
 * backend puts it - a prediction is created there, then these methods
 * operate on its id.
 */
final class PredictionsResource extends AbstractResource
{
    /**
     * Backtests a prediction against real sales that have since happened.
     *
     * @return array<string, mixed>
     */
    public function evaluate(string $predictionId): array
    {
        return $this->http->request('POST', "/predictions/{$predictionId}/evaluate");
    }

    /** @return array<string, mixed> */
    public function simulate(string $predictionId, int $nSimulations = 5000, string $distribution = 'negative_binomial'): array
    {
        return $this->http->request('POST', "/predictions/{$predictionId}/simulate", query: [
            'n_simulations' => $nSimulations,
            'distribution' => $distribution,
        ]);
    }

    /** @return array<string, mixed> */
    public function stockout(string $predictionId, ?int $currentStock = null): array
    {
        return $this->http->request('POST', "/predictions/{$predictionId}/stockout", query: [
            'current_stock' => $currentStock,
        ]);
    }

    /** @return array<string, mixed> */
    public function inventoryRecommendation(
        string $predictionId,
        ?int $currentStock = null,
        int $leadTimeDays = 7,
        float $targetServiceLevel = 0.95,
    ): array {
        return $this->http->request('POST', "/predictions/{$predictionId}/inventory-recommendation", query: [
            'current_stock' => $currentStock,
            'lead_time_days' => $leadTimeDays,
            'target_service_level' => $targetServiceLevel,
        ]);
    }

    /** @return array<string, mixed> */
    public function optimizePolicy(
        string $predictionId,
        ?int $currentStock = null,
        int $leadTimeDays = 7,
        float $targetServiceLevel = 0.95,
        int $nSimulations = 1000,
    ): array {
        return $this->http->request('POST', "/predictions/{$predictionId}/optimize-policy", query: [
            'current_stock' => $currentStock,
            'lead_time_days' => $leadTimeDays,
            'target_service_level' => $targetServiceLevel,
            'n_simulations' => $nSimulations,
        ]);
    }
}
