<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests\Common;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;
use Shanginn\CloudflareBrowser\Enums\WaitUntil;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
final class GotoOptions
{
    public function __construct(
        public ?WaitUntil $waitUntil = null,
        public ?int $timeout = null,
        public ?string $referer = null
    ) {}
}
