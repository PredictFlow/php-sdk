<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Http;

/**
 * Builds a raw multipart/form-data body as a plain string, using nothing
 * but PHP built-ins - deliberately not GuzzleHttp\Psr7\MultipartStream,
 * since this SDK only depends on PSR-18/17 interfaces, not a specific
 * HTTP client implementation (see README's "Bring your own HTTP client"
 * section - the whole point is working inside WooCommerce/WordPress
 * environments that may already have a different PSR-18 client
 * installed).
 */
final class MultipartBuilder
{
    private readonly string $boundary;

    /** @var list<string> */
    private array $parts = [];

    public function __construct()
    {
        $this->boundary = '----PredictFlowSDK' . bin2hex(random_bytes(16));
    }

    public function addFile(string $fieldName, string $filename, string $content, string $contentType): void
    {
        $this->parts[] = "--{$this->boundary}\r\n"
            . "Content-Disposition: form-data; name=\"{$fieldName}\"; filename=\"{$filename}\"\r\n"
            . "Content-Type: {$contentType}\r\n\r\n"
            . $content . "\r\n";
    }

    public function contentType(): string
    {
        return "multipart/form-data; boundary={$this->boundary}";
    }

    public function build(): string
    {
        return implode('', $this->parts) . "--{$this->boundary}--\r\n";
    }
}
