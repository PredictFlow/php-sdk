<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

/** Store management, connection testing, and catalog/order syncing. */
final class StoresResource extends AbstractResource
{
    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->http->request('GET', '/stores');
    }

    /** @return array<string, mixed> */
    public function create(string $platform, string $name, string $storeUrl = ''): array
    {
        return $this->http->request('POST', '/stores', jsonBody: [
            'platform' => $platform,
            'name' => $name,
            'store_url' => $storeUrl,
        ]);
    }

    /** @return array<string, mixed> */
    public function get(string $storeId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}");
    }

    /**
     * @param array<string, mixed> $fields may include name, store_url, status, currency
     * @return array<string, mixed>
     */
    public function update(string $storeId, array $fields): array
    {
        return $this->http->request('PATCH', "/stores/{$storeId}", jsonBody: $fields);
    }

    public function delete(string $storeId): void
    {
        $this->http->request('DELETE', "/stores/{$storeId}");
    }

    /**
     * Only WooCommerce stores support setting credentials this way today.
     *
     * @return array<string, mixed>
     */
    public function setWooCommerceCredentials(string $storeId, string $consumerKey, string $consumerSecret): array
    {
        return $this->http->request('PUT', "/stores/{$storeId}/credentials", jsonBody: [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ]);
    }

    /** @return array<string, mixed> */
    public function testConnection(string $storeId): array
    {
        return $this->http->request('POST', "/stores/{$storeId}/test-connection");
    }

    /** @return array<string, mixed> */
    public function sync(string $storeId, bool $full = false): array
    {
        return $this->http->request('POST', "/stores/{$storeId}/sync", query: ['full' => $full ? 'true' : 'false']);
    }

    /** @return array<int, array<string, mixed>> */
    public function syncHistory(string $storeId): array
    {
        return $this->http->request('GET', "/stores/{$storeId}/sync-history");
    }

    /** @return array<string, mixed> */
    public function importProductsCsv(string $storeId, string $csvContent, string $filename = 'products.csv'): array
    {
        return $this->http->request('POST', "/stores/{$storeId}/import/products", files: [
            ['field' => 'file', 'filename' => $filename, 'content' => $csvContent, 'contentType' => 'text/csv'],
        ]);
    }

    /** @return array<string, mixed> */
    public function importOrdersCsv(string $storeId, string $csvContent, string $filename = 'orders.csv'): array
    {
        return $this->http->request('POST', "/stores/{$storeId}/import/orders", files: [
            ['field' => 'file', 'filename' => $filename, 'content' => $csvContent, 'contentType' => 'text/csv'],
        ]);
    }
}
