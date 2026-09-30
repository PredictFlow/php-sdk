<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Exceptions;

use Throwable;

/** 429 Too Many Requests. */
class RateLimitException extends PredictFlowException
{
    public function __construct(
        string $message,
        ?int $status = null,
        ?string $errorCode = null,
        ?string $requestId = null,
        mixed $details = null,
        public readonly ?float $retryAfterSeconds = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $errorCode, $requestId, $details, $previous);
    }
}
