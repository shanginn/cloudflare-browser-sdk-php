<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Exceptions;

class CloudflareApiErrorException extends CloudflareException
{
    public function __construct(string $message, public int $statusCode = 0)
    {
        parent::__construct("Cloudflare API Error: {$message}", $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
