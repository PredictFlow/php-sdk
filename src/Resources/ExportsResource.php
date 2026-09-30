<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

use PredictFlow\Sdk\Http\FileDownload;

/**
 * CSV/XLSX/PDF exports of products, sales, inventory, forecasts, reorder,
 * dead stock, and the dashboard. Every method returns a FileDownload (raw
 * bytes + suggested filename) rather than trying to JSON-decode a
 * spreadsheet or PDF.
 */
final class ExportsResource extends AbstractResource
{
    public function allStoresProducts(string $format = 'xlsx'): FileDownload
    {
        return $this->http->requestFile('GET', '/exports/products', query: ['format' => $format]);
    }

    public function products(string $storeId, string $format = 'csv'): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/products", query: ['format' => $format]);
    }

    public function sales(string $storeId, string $format = 'csv', int $days = 90): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/sales", query: [
            'format' => $format,
            'days' => $days,
        ]);
    }

    public function inventory(string $storeId, string $format = 'csv', int $days = 90): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/inventory", query: [
            'format' => $format,
            'days' => $days,
        ]);
    }

    public function forecasts(string $storeId, string $format = 'csv', ?string $productId = null): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/forecasts", query: [
            'format' => $format,
            'product_id' => $productId,
        ]);
    }

    public function reorder(string $storeId, string $format = 'csv'): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/reorder", query: ['format' => $format]);
    }

    public function deadStock(string $storeId, string $format = 'csv'): FileDownload
    {
        return $this->http->requestFile('GET', "/exports/stores/{$storeId}/dead-stock", query: ['format' => $format]);
    }

    public function dashboard(string $format = 'csv', int $days = 30): FileDownload
    {
        return $this->http->requestFile('GET', '/exports/dashboard', query: ['format' => $format, 'days' => $days]);
    }
}
