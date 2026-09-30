<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** Price elasticity, optimization, simulation, cross-elasticity, and batch repricing. */
final class PricingResource extends AbstractResource
{
    /** @return array<string, mixed> */
    public function elasticity(string $productId): array
    {
        return $this->http->request('GET', "/pricing/product/{$productId}/elasticity");
    }

    /** @return array<string, mixed> */
    public function optimize(
        string $productId,
        string $strategy = 'maximize_revenue',
        ?float $competitorPrice = null,
        ?float $costOverride = null,
    ): array {
        return $this->http->request('POST', "/pricing/product/{$productId}/optimize", query: [
            'strategy' => $strategy,
            'competitor_price' => $competitorPrice,
            'cost_override' => $costOverride,
        ]);
    }

    /** @return array<string, mixed> */
    public function simulate(string $productId, float $newPrice, ?float $costOverride = null): array
    {
        return $this->http->request('POST', "/pricing/product/{$productId}/simulate", query: [
            'new_price' => $newPrice,
            'cost_override' => $costOverride,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function batchOptimize(string $storeId, string $strategy = 'maximize_revenue', int $limit = 50): array
    {
        return $this->http->request('POST', "/pricing/store/{$storeId}/batch-optimize", query: [
            'strategy' => $strategy,
            'limit' => $limit,
        ]);
    }

    /** @return array<string, mixed> */
    public function crossElasticity(string $productId, string $relatedProductId, int $lookbackDays = 180): array
    {
        return $this->http->request(
            'GET',
            "/pricing/product/{$productId}/cross-elasticity/{$relatedProductId}",
            query: ['lookback_days' => $lookbackDays],
        );
    }
}
