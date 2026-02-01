<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Exceptions;

class CloudflareRateLimitException extends CloudflareApiErrorException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        public readonly ?int $retryAfter = null
    )
    {
        parent::__construct($message, 429);
    }
}
