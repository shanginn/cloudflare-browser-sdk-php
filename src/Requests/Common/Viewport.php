<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests\Common;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
final class Viewport
{
    public function __construct(
        public int $width = 1920,
        public int $height = 1080,
        public ?float $deviceScaleFactor = null,
        public ?bool $isMobile = null,
        public ?bool $hasTouch = null,
        public ?bool $isLandscape = null
    ) {}
}
