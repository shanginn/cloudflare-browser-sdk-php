<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests\Common;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
final class ScriptTag
{
    public function __construct(
        public string $url,
        public ?string $type = null
    ) {}
}
