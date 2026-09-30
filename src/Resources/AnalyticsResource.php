<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** Sales KPIs, revenue trends, day-of-week seasonality, and product growth. */
final class AnalyticsResource extends AbstractResource
{
    /** @return array<string, mixed> */
    public function overview(int $months = 12): array
    {
        return $this->http->request('GET', '/analytics', query: ['months' => $months]);
    }

    /** @return array<string, mixed> */
    public function kpis(int $days = 30, ?string $storeId = null): array
    {
        return $this->http->request('GET', '/analytics/kpis', query: ['days' => $days, 'store_id' => $storeId]);
    }

    /** @return array<string, mixed> */
    public function dayOfWeek(int $days = 30, ?string $storeId = null): array
    {
        return $this->http->request('GET', '/analytics/day-of-week', query: ['days' => $days, 'store_id' => $storeId]);
    }

    /** @return array<string, mixed> */
    public function trend(int $days = 30, ?string $storeId = null, string $interval = 'auto'): array
    {
        return $this->http->request('GET', '/analytics/trend', query: [
            'days' => $days,
            'store_id' => $storeId,
            'interval' => $interval,
        ]);
    }

    /** @return array<string, mixed> */
    public function productGrowth(int $days = 30, ?string $storeId = null, int $limit = 5): array
    {
        return $this->http->request('GET', '/analytics/product-growth', query: [
            'days' => $days,
            'store_id' => $storeId,
            'limit' => $limit,
        ]);
    }

    /** @return array<string, mixed> */
    public function projections(int $days = 30, ?string $storeId = null): array
    {
        return $this->http->request('GET', '/analytics/projections', query: ['days' => $days, 'store_id' => $storeId]);
    }
}
