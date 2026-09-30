<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Exceptions;

use Throwable;

/**
 * Base class for all exceptions originating from the PredictFlow SDK.
 */
class PredictFlowException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $requestId = null,
        public readonly mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
