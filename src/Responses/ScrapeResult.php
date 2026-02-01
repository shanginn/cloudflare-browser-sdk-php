<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Responses;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case)]
final class ScrapeResult
{
    public function __construct(
        public string $selector,
        #[Serde\SequenceField(arrayType: ElementResult::class)]
        public array $results
    ) {}
}
