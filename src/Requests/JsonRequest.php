<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
class JsonRequest extends BaseRequest
{
    public ?array $responseFormat = null;

    public function __construct(
        public ?string $prompt = null,
        ?string $url = null,
        ?string $html = null,
        ...$args
    ) {
        parent::__construct($url, $html, ...$args);
    }
}
