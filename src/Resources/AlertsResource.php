<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** Inventory alert rules, templates, and evaluation history. */
final class AlertsResource extends AbstractResource
{
    /** @return array<int, array<string, mixed>> */
    public function listTemplates(): array
    {
        return $this->http->request('GET', '/alert-templates');
    }

    /**
     * @param array<int, string> $productIds
     * @return array<string, mixed>
     */
    public function createRule(
        string $storeId,
        string $metricType,
        ?string $name = null,
        ?float $threshold = null,
        array $productIds = [],
        bool $isActive = true,
    ): array {
        return $this->http->request('POST', "/stores/{$storeId}/alert-rules", jsonBody: [
            'metric_type' => $metricType,
            'name' => $name,
            'threshold' => $threshold,
            'product_ids' => $productIds,
            'is_active' => $isActive,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listRules(string $storeId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}/alert-rules");
    }

    /** @return array<string, mixed> */
    public function getRule(string $ruleId): array
    {
        return $this->http->request('GET', "/alert-rules/{$ruleId}");
    }

    /**
     * @param array<string, mixed> $fields may include name, threshold, is_active
     * @return array<string, mixed>
     */
    public function updateRule(string $ruleId, array $fields): array
    {
        return $this->http->request('PATCH', "/alert-rules/{$ruleId}", jsonBody: $fields);
    }

    public function deleteRule(string $ruleId): void
    {
        $this->http->request('DELETE', "/alert-rules/{$ruleId}");
    }

    /** @return array<int, array<string, mixed>> */
    public function ruleHistory(string $ruleId): array
    {
        return $this->http->request('GET', "/alert-rules/{$ruleId}/history");
    }
}
