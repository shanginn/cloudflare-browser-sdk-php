<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
class SnapshotRequest extends BaseRequest
{
    public function __construct(
        ?string $url = null,
        ?string $html = null,
        ...$args
    ) {
        parent::__construct($url, $html, ...$args);
    }
}
