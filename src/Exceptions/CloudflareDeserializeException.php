<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Exceptions;

class CloudflareDeserializeException extends CloudflareException
{
    public function __construct(
        public mixed $data,
        public string $targetClass,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Failed to deserialize data into %s: %s',
            $targetClass,
            $previous?->getMessage() ?? 'Unknown error'
        );
        parent::__construct($message, 0, $previous);
    }
}
