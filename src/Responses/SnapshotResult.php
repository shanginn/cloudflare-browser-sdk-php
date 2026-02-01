<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Responses;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case)]
final class SnapshotResult
{
    public function __construct(
        public string $url,
        public string $title,
        public string $content,
        public ?string $html = null,
        public ?string $text = null
    ) {}
}
