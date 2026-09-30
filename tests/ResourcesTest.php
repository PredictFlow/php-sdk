<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PredictFlow\Sdk\PredictFlow;

/**
 * Spot-checks that resource methods build the right method/path/params -
 * one per resource, not exhaustive coverage of every method (that's what
 * the live end-to-end verification against the running backend during
 * development was for; these guard against regressions in that verified
 * mapping).
 */
final class ResourcesTest extends TestCase
{
    private function client(MockPsr18Client $mock): PredictFlow
    {
        $factory = new HttpFactory();

        return new PredictFlow(
            apiKey: 'pk_live_test',
            baseUrl: 'https://predictflow.test/api/v1',
            httpClient: $mock,
            requestFactory: $factory,
            streamFactory: $factory,
        );
    }

    private function jsonResponse(int $status = 200, string $body = '{}'): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }

    public function testStoresSyncSendsFullAsQueryParam(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->stores->sync('store_1', full: true);

        $request = $mock->requests()[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://predictflow.test/api/v1/stores/store_1/sync?full=true', (string) $request->getUri());
    }

    public function testProductsForecastSendsOptionsAsQueryNotBody(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->products->forecast('prod_1', horizonDays: 14, model: 'xgboost');

        $request = $mock->requests()[0];
        self::assertSame('POST', $request->getMethod());
        self::assertStringContainsString('horizon_days=14', (string) $request->getUri());
        self::assertStringContainsString('model=xgboost', (string) $request->getUri());
        self::assertSame('', (string) $request->getBody());
    }

    public function testProductsUpdateCostSendsBodyNotQuery(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->products->updateCost('prod_1', cogs: 5.5);

        $request = $mock->requests()[0];
        self::assertSame('PATCH', $request->getMethod());
        self::assertStringNotContainsString('cogs', (string) $request->getUri());
        self::assertStringContainsString('"cogs":5.5', (string) $request->getBody());
    }

    public function testPredictionsStockoutPathAndParams(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->predictions->stockout('pred_1', currentStock: 42);

        self::assertSame(
            'https://predictflow.test/api/v1/predictions/pred_1/stockout?current_stock=42',
            (string) $mock->requests()[0]->getUri(),
        );
    }

    public function testPricingCrossElasticityPath(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->pricing->crossElasticity('prod_1', 'prod_2', lookbackDays: 60);

        $uri = (string) $mock->requests()[0]->getUri();
        self::assertStringContainsString('/pricing/product/prod_1/cross-elasticity/prod_2', $uri);
        self::assertStringContainsString('lookback_days=60', $uri);
    }

    public function testScenariosCreateSendsJsonBody(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->scenarios->create('store_1', name: 'Holiday markdown', priceChangePct: -10.0);

        $request = $mock->requests()[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://predictflow.test/api/v1/stores/store_1/scenarios', (string) $request->getUri());
        self::assertStringContainsString('"name":"Holiday markdown"', (string) $request->getBody());
    }

    public function testScenariosCompareSendsIdsAsQueryParams(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->scenarios->compare('store_1', 'scen_a', 'scen_b');

        $uri = (string) $mock->requests()[0]->getUri();
        self::assertStringContainsString('scenario_a_id=scen_a', $uri);
        self::assertStringContainsString('scenario_b_id=scen_b', $uri);
    }

    public function testAlertsCreateRuleBody(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->alerts->createRule('store_1', metricType: 'low_stock', threshold: 10.0);

        $request = $mock->requests()[0];
        self::assertSame('https://predictflow.test/api/v1/stores/store_1/alert-rules', (string) $request->getUri());
        self::assertStringContainsString('"metric_type":"low_stock"', (string) $request->getBody());
    }

    public function testExportsReturnsFileDownloadNotJson(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse(new Response(
            200,
            ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="products.csv"'],
            "sku,name\nSKU-1,Widget\n",
        ));

        $result = $this->client($mock)->exports->products('store_1', format: 'csv');

        self::assertSame("sku,name\nSKU-1,Widget\n", $result->content);
        self::assertSame('products.csv', $result->filename);
        self::assertSame('text/csv', $result->contentType);
    }

    public function testStoresImportProductsCsvSendsMultipartFile(): void
    {
        $mock = new MockPsr18Client();
        $mock->queueResponse($this->jsonResponse());

        $this->client($mock)->stores->importProductsCsv('store_1', "sku,name\nSKU-1,Widget\n", filename: 'my-products.csv');

        $body = (string) $mock->requests()[0]->getBody();
        self::assertStringContainsString('filename="my-products.csv"', $body);
        self::assertStringContainsString('SKU-1,Widget', $body);
    }
}
