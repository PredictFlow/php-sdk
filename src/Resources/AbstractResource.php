<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Resources;

use PredictFlow\Sdk\Http\HttpClient;

abstract class AbstractResource
{
    public function __construct(protected readonly HttpClient $http)
    {
    }
}
