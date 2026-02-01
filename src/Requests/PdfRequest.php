<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Requests;

use Crell\Serde\Attributes as Serde;
use Crell\Serde\Renaming\Cases;
use Shanginn\CloudflareBrowser\Enums\PdfFormat;

#[Serde\ClassSettings(renameWith: Cases::snake_case, omitNullFields: true)]
class PdfRequest extends BaseRequest
{
    /**
     * @param array{displayHeaderFooter?: bool, headerTemplate?: string, footerTemplate?: string, printBackground?: bool, landscape?: bool, pageRanges?: string, width?: string, height?: string, margin?: array{top?: string, bottom?: string, left?: string, right?: string}, preferCSSPageSize?: bool}|null $pdfOptions
     */
    public function __construct(
        ?string $url = null,
        ?string $html = null,
        public ?PdfFormat $format = null,
        public ?array $pdfOptions = null,
        ...$args
    ) {
        parent::__construct($url, $html, ...$args);
        
        // Don't send empty arrays in the request
        if ($pdfOptions === []) {
            $this->pdfOptions = null;
        }
    }
}
