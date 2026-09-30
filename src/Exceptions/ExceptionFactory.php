<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Exceptions;

/**
 * Maps an HTTP status code and decoded response body to the appropriate
 * PredictFlowException subclass.
 */
final class ExceptionFactory
{
    private function __construct()
    {
    }

    public static function fromResponse(
        int $status,
        mixed $body,
        ?string $requestId = null,
        ?string $retryAfterHeader = null,
    ): PredictFlowException {
        $message = "API request failed with status {$status}";
        $errorCode = null;
        $details = null;

        if (is_array($body)) {
            $unwrapped = self::unwrapDetail($body['detail'] ?? null);
            if ($unwrapped !== null) {
                $message = $unwrapped;
            } elseif (isset($body['message']) && is_string($body['message'])) {
                $message = $body['message'];
            }
            if (array_key_exists('detail', $body)) {
                $details = $body['detail'];
            }
            if (isset($body['code']) && is_string($body['code'])) {
                $errorCode = $body['code'];
            }
        }

        if ($status === 429) {
            $retryAfterSeconds = null;
            if ($retryAfterHeader !== null && is_numeric($retryAfterHeader)) {
                $retryAfterSeconds = (float) $retryAfterHeader;
            }

            return new RateLimitException($message, $status, $errorCode, $requestId, $details, $retryAfterSeconds);
        }

        $exceptionClass = match (true) {
            $status === 401, $status === 403 => AuthenticationException::class,
            $status === 404 => NotFoundException::class,
            $status === 400, $status === 422 => ValidationException::class,
            $status === 500, $status === 502, $status === 503, $status === 504 => InternalServerException::class,
            default => PredictFlowException::class,
        };

        return new $exceptionClass($message, $status, $errorCode, $requestId, $details);
    }

    /**
     * FastAPI/Pydantic validation failures (422s) return `detail` as a
     * list of {msg, loc, type} objects, not a string - unwrapping it here
     * means a ValidationException's message is always the real
     * field-level reason instead of a generic "request failed with
     * status 422". Pydantic prefixes a model-level ValueError's message
     * with "Value error, " - stripped here as an implementation detail.
     */
    private static function unwrapDetail(mixed $detail): ?string
    {
        if (is_string($detail)) {
            return $detail;
        }

        if (is_array($detail)) {
            $messages = [];
            foreach ($detail as $item) {
                if (is_array($item) && isset($item['msg']) && is_string($item['msg'])) {
                    $msg = $item['msg'];
                    if (str_starts_with($msg, 'Value error, ')) {
                        $msg = substr($msg, strlen('Value error, '));
                    }
                    $messages[] = $msg;
                }
            }
            if ($messages !== []) {
                return implode(' ', $messages);
            }
        }

        return null;
    }
}
