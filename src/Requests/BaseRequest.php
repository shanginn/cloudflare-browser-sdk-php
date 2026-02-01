<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;
use Shanginn\CloudflareBrowser\Requests\Common\GotoOptions;
use Shanginn\CloudflareBrowser\Requests\Common\Viewport;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
abstract class BaseRequest
{
    public function __construct(
        public ?string $url = null,
        public ?string $html = null,
        public ?GotoOptions $gotoOptions = null,
        public ?Viewport $viewport = null,
        public ?array $cookies = null,
        public ?string $userAgent = null,
        public ?array $rejectResourceTypes = null,
    ) {}
}
