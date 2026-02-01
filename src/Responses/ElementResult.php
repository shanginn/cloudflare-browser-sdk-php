<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Responses;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case)]
final class ElementResult
{
    public function __construct(
        public string $text,
        public string $html,
        public float $width,
        public float $height,
        public float $top,
        public float $left,
        public array $attributes = []
    ) {}
}
