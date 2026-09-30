<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** What-if scenario planning: price/demand shift simulations and side-by-side comparisons. */
final class ScenariosResource extends AbstractResource
{
    /** @return array<string, mixed> */
    public function create(
        string $storeId,
        string $name,
        ?string $description = null,
        float $priceChangePct = 0.0,
        float $demandShiftPct = 0.0,
    ): array {
        return $this->http->request('POST', "/stores/{$storeId}/scenarios", jsonBody: [
            'name' => $name,
            'description' => $description,
            'price_change_pct' => $priceChangePct,
            'demand_shift_pct' => $demandShiftPct,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $storeId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}/scenarios");
    }

    /** @return array<string, mixed> */
    public function compare(string $storeId, string $scenarioAId, string $scenarioBId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}/scenarios/compare", query: [
            'scenario_a_id' => $scenarioAId,
            'scenario_b_id' => $scenarioBId,
        ]);
    }

    /** @return array<string, mixed> */
    public function get(string $storeId, string $scenarioId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}/scenarios/{$scenarioId}");
    }

    /**
     * @param array<string, mixed> $fields may include name, description, price_change_pct, demand_shift_pct
     * @return array<string, mixed>
     */
    public function update(string $scenarioId, array $fields): array
    {
        return $this->http->request('PATCH', "/scenarios/{$scenarioId}", jsonBody: $fields);
    }

    public function delete(string $scenarioId): void
    {
        $this->http->request('DELETE', "/scenarios/{$scenarioId}");
    }

    /** @return array<string, mixed> */
    public function execute(string $scenarioId): array
    {
        return $this->http->request('POST', "/scenarios/{$scenarioId}/execute");
    }

    /** @return array<string, mixed> */
    public function clone(string $scenarioId, ?string $name = null): array
    {
        return $this->http->request('POST', "/scenarios/{$scenarioId}/clone", jsonBody: ['name' => $name]);
    }
}
