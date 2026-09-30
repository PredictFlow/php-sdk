<?php

declare(strict_types=1);

namespace PredictFlow\Sdk\Http;

/**
 * A binary export response - content plus the filename/content-type the
 * server suggested, so callers can write it to disk without having to
 * parse Content-Disposition themselves.
 */
final class FileDownload
{
    public function __construct(
        public readonly string $content,
        public readonly string $contentType,
        public readonly ?string $filename,
    ) {
    }

    public function saveTo(string $path): void
    {
        file_put_contents($path, $this->content);
    }
}
