<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** Product catalog, inventory levels, ABC analysis, forecasting, cost/margin, and competitor pricing. */
final class ProductsResource extends AbstractResource
{
    /** @return array<string, mixed> */
    public function list(
        ?string $storeId = null,
        ?string $search = null,
        int $page = 1,
        int $pageSize = 20,
        string $sortBy = 'name',
        string $sortOrder = 'asc',
        int $threshold = 10,
        int $overstockThreshold = 100,
    ): array {
        return $this->http->request('GET', '/products', query: [
            'store_id' => $storeId,
            'search' => $search,
            'page' => $page,
            'page_size' => $pageSize,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'threshold' => $threshold,
            'overstock_threshold' => $overstockThreshold,
        ]);
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return $this->http->request('GET', '/products/summary');
    }

    /** @return array<string, mixed> */
    public function get(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}");
    }

    /** @return array<int, array<string, mixed>> */
    public function topSellers(string $storeId, int $days = 30, int $limit = 10): array
    {
        return $this->http->request('GET', "/products/store/{$storeId}/top-sellers", query: [
            'days' => $days,
            'limit' => $limit,
        ]);
    }

    /** @return array<string, mixed> */
    public function abcAnalysis(string $storeId, int $days = 90): array
    {
        return $this->http->request('GET', "/products/store/{$storeId}/abc-analysis", query: ['days' => $days]);
    }

    /** @return array<string, mixed> */
    public function lowStock(string $storeId, int $threshold = 10): array
    {
        return $this->http->request('GET', "/products/store/{$storeId}/low-stock", query: ['threshold' => $threshold]);
    }

    /** @return array<string, mixed> */
    public function inventoryHealth(string $storeId, int $threshold = 10, int $overstockThreshold = 100): array
    {
        return $this->http->request('GET', "/products/store/{$storeId}/inventory-health", query: [
            'threshold' => $threshold,
            'overstock_threshold' => $overstockThreshold,
        ]);
    }

    /** @return array<string, mixed> */
    public function accuracySummary(string $storeId): array
    {
        return $this->http->request('GET', "/products/store/{$storeId}/accuracy-summary");
    }

    /** @return array<string, mixed> */
    public function reorderAlerts(
        string $storeId,
        ?string $search = null,
        ?string $urgency = null,
        int $leadTimeDays = 7,
        float $targetServiceLevel = 0.95,
        string $sortBy = 'urgency',
    ): array {
        return $this->http->request('GET', "/products/store/{$storeId}/reorder-alerts", query: [
            'search' => $search,
            'urgency' => $urgency,
            'lead_time_days' => $leadTimeDays,
            'target_service_level' => $targetServiceLevel,
            'sort_by' => $sortBy,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function inventoryHistory(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}/inventory-history");
    }

    /** @return array<string, mixed> */
    public function forecast(string $productId, int $horizonDays = 30, string $model = 'prophet'): array
    {
        return $this->http->request('POST', "/products/{$productId}/forecast", query: [
            'horizon_days' => $horizonDays,
            'model' => $model,
        ]);
    }

    /**
     * Forecasts every product in the store in the background - returns
     * immediately with a batch status.
     *
     * @return array<string, mixed>
     */
    public function forecastStore(string $storeId, int $horizonDays = 30, string $model = 'prophet'): array
    {
        return $this->http->request('POST', "/products/store/{$storeId}/forecast", query: [
            'horizon_days' => $horizonDays,
            'model' => $model,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function forecastHistory(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}/forecast-history");
    }

    /** @return array<string, mixed> */
    public function updateCost(string $productId, ?float $cogs = null, ?float $shippingCost = null, ?float $otherFees = null): array
    {
        return $this->http->request('PATCH', "/products/{$productId}/cost", jsonBody: [
            'cogs' => $cogs,
            'shipping_cost' => $shippingCost,
            'other_fees' => $otherFees,
        ]);
    }

    /** @return array<string, mixed> */
    public function margin(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}/margin");
    }

    /** @return array<string, mixed> */
    public function addCompetitorPrice(
        string $productId,
        string $competitorName,
        float $price,
        ?string $url = null,
        ?string $observedAt = null,
    ): array {
        return $this->http->request('POST', "/products/{$productId}/competitor-prices", jsonBody: [
            'competitor_name' => $competitorName,
            'price' => $price,
            'url' => $url,
            'observed_at' => $observedAt,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listCompetitorPrices(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}/competitor-prices");
    }

    public function deleteCompetitorPrice(string $productId, string $competitorPriceId): void
    {
        $this->http->request('DELETE', "/products/{$productId}/competitor-prices/{$competitorPriceId}");
    }

    /** @return array<string, mixed> */
    public function repriceSuggestion(string $productId): array
    {
        return $this->http->request('GET', "/products/{$productId}/competitor-prices/reprice-suggestion");
    }
}
