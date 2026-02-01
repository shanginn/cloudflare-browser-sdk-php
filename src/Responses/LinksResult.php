<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Responses;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case)]
final class LinksResult
{
    /**
     * @param string[] $links
     */
    public function __construct(
        #[Serde\SequenceField(arrayType: 'string')]
        public array $links
    ) {}
}
