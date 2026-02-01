<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;
use Shanginn\CloudflareBrowser\Enums\ImageFormat;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
class ScreenshotRequest extends BaseRequest
{
    /**
     * @param array{omitBackground?: bool, fullPage?: bool, clip?: array}|null $screenshotOptions
     */
    public function __construct(
        ?string $url = null,
        ?string $html = null,
        #[SerializedName('screenshotOptions')]
        public ?array $screenshotOptions = null,
        public ?string $selector = null,
        public ?ImageFormat $format = null,
        // ... passthrough base args
        ...$args
    ) {
        parent::__construct($url, $html, ...$args);
    }
}
