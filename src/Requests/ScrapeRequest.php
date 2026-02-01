<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
class ScrapeRequest extends BaseRequest
{
    /**
     * @param array<array{selector: string}> $elements
     */
    public function __construct(
        public array $elements,
        ?string $url = null,
        ?string $html = null,
        ...$args
    ) {
        parent::__construct($url, $html, ...$args);
    }
}
