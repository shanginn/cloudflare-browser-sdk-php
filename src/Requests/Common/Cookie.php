<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests\Common;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
final class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public ?string $domain = null,
        public ?string $path = null,
        public ?bool $httpOnly = null,
        public ?bool $secure = null,
        public ?string $sameSite = null,
        public ?float $expires = null
    ) {}
}
