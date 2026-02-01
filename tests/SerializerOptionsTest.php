<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Shanginn\CloudflareBrowser\CloudflareSerializer;
use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;
use Shanginn\CloudflareBrowser\Requests\PdfRequest;
use Shanginn\CloudflareBrowser\Enums\PdfFormat;

/**
 * Tests that verify specific API field naming requirements.
 * Cloudflare API expects camelCase for certain fields like screenshotOptions and pdfOptions.
 */
class SerializerOptionsTest extends TestCase
{
    private CloudflareSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new CloudflareSerializer();
    }

    public function testScreenshotOptionsUsesCamelCase(): void
    {
        $request = new ScreenshotRequest(
            url: 'https://example.com',
            screenshotOptions: [
                'fullPage' => true,
                'omitBackground' => false
            ]
        );

        $json = $this->serializer->serialize($request);
        $data = json_decode($json, true);

        // The API expects camelCase "screenshotOptions", not snake_case "screenshot_options"
        $this->assertArrayHasKey('screenshotOptions', $data);
        $this->assertArrayNotHasKey('screenshot_options', $data);
        $this->assertEquals(['fullPage' => true, 'omitBackground' => false], $data['screenshotOptions']);
    }

    public function testScreenshotOptionsWithNestedArray(): void
    {
        $request = new ScreenshotRequest(
            url: 'https://example.com',
            screenshotOptions: [
                'clip' => [
                    'x' => 0,
                    'y' => 0,
                    'width' => 800,
                    'height' => 600
                ]
            ]
        );

        $json = $this->serializer->serialize($request);
        
        // Verify the raw JSON contains camelCase
        $this->assertStringContainsString('"screenshotOptions":', $json);
        $this->assertStringNotContainsString('"screenshot_options":', $json);
    }

    public function testPdfOptionsUsesCamelCase(): void
    {
        $request = new PdfRequest(
            url: 'https://example.com',
            format: PdfFormat::A4,
            pdfOptions: [
                'printBackground' => true,
                'displayHeaderFooter' => false
            ]
        );

        $json = $this->serializer->serialize($request);
        $data = json_decode($json, true);

        // The API expects camelCase "pdfOptions", not snake_case "pdf_options"
        $this->assertArrayHasKey('pdfOptions', $data);
        $this->assertArrayNotHasKey('pdf_options', $data);
        // Format should be moved inside pdfOptions
        $this->assertArrayHasKey('format', $data['pdfOptions']);
        $this->assertEquals('a4', $data['pdfOptions']['format']);
        $this->assertArrayNotHasKey('format', $data);
    }

    public function testPdfOptionsWithMargins(): void
    {
        $request = new PdfRequest(
            url: 'https://example.com',
            pdfOptions: [
                'margin' => [
                    'top' => '100px',
                    'bottom' => '50px'
                ],
                'headerTemplate' => '<div>Header</div>'
            ]
        );

        $json = $this->serializer->serialize($request);
        
        // Verify the raw JSON contains camelCase
        $this->assertStringContainsString('"pdfOptions":', $json);
        $this->assertStringNotContainsString('"pdf_options":', $json);
    }

    public function testOtherFieldsUseSnakeCase(): void
    {
        $request = new ScreenshotRequest(
            url: 'https://example.com',
            screenshotOptions: ['fullPage' => true],
            selector: '#test'
        );

        $json = $this->serializer->serialize($request);
        $data = json_decode($json, true);

        // Other fields should still use snake_case
        $this->assertArrayHasKey('screenshotOptions', $data); // camelCase
        $this->assertArrayHasKey('selector', $data); // already snake_case
        $this->assertArrayHasKey('url', $data); // already snake_case
    }

    public function testScreenshotRequestWithoutOptions(): void
    {
        $request = new ScreenshotRequest(
            url: 'https://example.com'
        );

        $json = $this->serializer->serialize($request);
        
        // Should not contain screenshotOptions when null
        $this->assertStringNotContainsString('screenshotOptions', $json);
        $this->assertStringNotContainsString('screenshot_options', $json);
    }

    public function testPdfRequestWithoutOptions(): void
    {
        $request = new PdfRequest(
            url: 'https://example.com'
        );

        $json = $this->serializer->serialize($request);
        
        // Should not contain pdfOptions when null
        $this->assertStringNotContainsString('pdfOptions', $json);
        $this->assertStringNotContainsString('pdf_options', $json);
    }
}
