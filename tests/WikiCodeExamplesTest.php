<?php

declare(strict_types=1);

namespace Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Shanginn\CloudflareBrowser\CloudflareBrowser;
use Shanginn\CloudflareBrowser\CloudflareClientInterface;
use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;
use Shanginn\CloudflareBrowser\Requests\PdfRequest;
use Shanginn\CloudflareBrowser\Requests\ScrapeRequest;
use Shanginn\CloudflareBrowser\Requests\JsonRequest;
use Shanginn\CloudflareBrowser\Requests\SnapshotRequest;
use Shanginn\CloudflareBrowser\Requests\Common\Viewport;
use Shanginn\CloudflareBrowser\Requests\Common\GotoOptions;
use Shanginn\CloudflareBrowser\Requests\Common\Cookie;
use Shanginn\CloudflareBrowser\Enums\PdfFormat;
use Shanginn\CloudflareBrowser\Enums\ImageFormat;
use Shanginn\CloudflareBrowser\Enums\WaitUntil;
use Spiral\JsonSchemaGenerator\Attribute\Field;

/**
 * Tests for wiki code examples to ensure they work correctly.
 * These tests verify that all code examples from the wiki documentation
 * execute without errors and produce expected results.
 */
class WikiCodeExamplesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $mockClient;
    private CloudflareBrowser $browser;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(CloudflareClientInterface::class);
        $this->browser = new CloudflareBrowser($this->mockClient);
    }

    // ==========================================
    // REST API Overview Tests
    // ==========================================

    public function testQuickStartExample(): void
    {
        // This test verifies the quick start example works
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('content', json_encode(['url' => 'https://example.com']))
            ->andReturn('<html><body>Example</body></html>');

        // Simulating the quick start code
        $html = $this->browser->content('https://example.com');
        $this->assertStringContainsString('Example', $html);
    }

    // ==========================================
    // /content Endpoint Tests
    // ==========================================

    public function testContentFetchHtmlExample(): void
    {
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('content', json_encode(['url' => 'https://developers.cloudflare.com/']))
            ->andReturn('<html><head></head><body>Cloudflare Docs</body></html>');

        $html = $this->browser->content('https://developers.cloudflare.com/');
        $this->assertStringContainsString('Cloudflare Docs', $html);
    }

    public function testContentWithGotoOptionsExample(): void
    {
        // Test GotoOptions creation (from wiki example)
        $gotoOptions = new GotoOptions(
            waitUntil: WaitUntil::NETWORK_IDLE,
            timeout: 30000
        );

        $this->assertInstanceOf(GotoOptions::class, $gotoOptions);
        $this->assertEquals(WaitUntil::NETWORK_IDLE, $gotoOptions->waitUntil);
        $this->assertEquals(30000, $gotoOptions->timeout);

        // Note: The content() method doesn't support gotoOptions directly.
        // For advanced options, users need to use the underlying client.
        // This test verifies the GotoOptions class works correctly.
        $this->assertTrue(true);
    }

    // ==========================================
    // /screenshot Endpoint Tests
    // ==========================================

    public function testScreenshotFromHtmlExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['html'] === 'Hello World!'
                    && $data['screenshot_options']['omitBackground'] === true;
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            html: 'Hello World!',
            screenshotOptions: [
                'omitBackground' => true
            ]
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotFromUrlExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['url'] === 'https://example.com';
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com'
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotAuthenticatedPageExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['url'] === 'https://example.com/protected-page'
                    && $data['cookies'][0]['name'] === 'session_id'
                    && $data['cookies'][0]['value'] === 'your-session-cookie-value'
                    && $data['cookies'][0]['domain'] === 'example.com'
                    && $data['cookies'][0]['path'] === '/';
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com/protected-page',
            cookies: [
                new Cookie(
                    name: 'session_id',
                    value: 'your-session-cookie-value',
                    domain: 'example.com',
                    path: '/'
                )
            ]
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotFullPageExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['url'] === 'https://cloudflare.com/'
                    && $data['screenshot_options']['fullPage'] === true
                    && $data['viewport']['width'] === 1280
                    && $data['viewport']['height'] === 720
                    && $data['goto_options']['wait_until'] === 'networkidle'
                    && $data['goto_options']['timeout'] === 45000;
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://cloudflare.com/',
            screenshotOptions: [
                'fullPage' => true
            ],
            viewport: new Viewport(
                width: 1280,
                height: 720
            ),
            gotoOptions: new GotoOptions(
                waitUntil: WaitUntil::NETWORK_IDLE,
                timeout: 45000
            )
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotHighResolutionExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['viewport']['width'] === 3600
                    && $data['viewport']['height'] === 2400
                    && $data['viewport']['device_scale_factor'] === 2.0;
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://cloudflare.com/',
            viewport: new Viewport(
                width: 3600,
                height: 2400,
                deviceScaleFactor: 2
            )
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotElementSelectorExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['selector'] === '#example_element_name'
                    && $data['viewport']['width'] === 1200
                    && $data['viewport']['height'] === 1600;
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com',
            selector: '#example_element_name',
            viewport: new Viewport(
                width: 1200,
                height: 1600
            )
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    public function testScreenshotJavaScriptHeavyPagesExample(): void
    {
        $fakePng = "fake-png-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['goto_options']['wait_until'] === 'networkidle';
            })
            ->andReturn($fakePng);

        $screenshot = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com',
            gotoOptions: new GotoOptions(
                waitUntil: WaitUntil::NETWORK_IDLE
            )
        ));

        $this->assertEquals($fakePng, $screenshot);
    }

    // ==========================================
    // /pdf Endpoint Tests
    // ==========================================

    public function testPdfFromUrlExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['url'] === 'https://example.com/';
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            url: 'https://example.com/'
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    public function testPdfFromHtmlExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['html'] === '<html><body>Advanced Snapshot</body></html>';
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            html: '<html><body>Advanced Snapshot</body></html>'
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    public function testPdfWithViewportExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['viewport']['width'] === 1200
                    && $data['viewport']['height'] === 800
                    && $data['goto_options']['wait_until'] === 'networkidle'
                    && $data['goto_options']['timeout'] === 45000;
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            url: 'https://example.com/',
            viewport: new Viewport(
                width: 1200,
                height: 800
            ),
            gotoOptions: new GotoOptions(
                waitUntil: WaitUntil::NETWORK_IDLE,
                timeout: 45000
            )
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    public function testPdfBlockImagesExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['url'] === 'https://cloudflare.com/'
                    && $data['reject_resource_types'] === ['image'];
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            url: 'https://cloudflare.com/',
            rejectResourceTypes: ['image']
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    public function testPdfWithHeaderFooterExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['format'] === 'A5'
                    && $data['pdf_options']['displayHeaderFooter'] === true
                    && $data['pdf_options']['headerTemplate'] === '<div style="font-size: 10px; text-align: center; width: 100%; padding: 5px;"><span>Brand Name</span></div>'
                    && $data['pdf_options']['margin']['top'] === '70px'
                    && $data['pdf_options']['margin']['bottom'] === '70px';
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            url: 'https://example.com',
            format: PdfFormat::A5,
            pdfOptions: [
                'displayHeaderFooter' => true,
                'headerTemplate' => '<div style="font-size: 10px; text-align: center; width: 100%; padding: 5px;"><span>Brand Name</span></div>',
                'footerTemplate' => '<div style="color: lightgray; border-top: solid lightgray 1px; font-size: 10px; padding-top: 5px; text-align: center; width: 100%;"><span>This is a test message</span> - <span class="pageNumber"></span></div>',
                'margin' => [
                    'top' => '70px',
                    'bottom' => '70px'
                ]
            ]
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    public function testPdfDynamicPlaceholdersExample(): void
    {
        $fakePdf = "fake-pdf-binary-data";

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['format'] === 'A4'
                    && $data['pdf_options']['displayHeaderFooter'] === true
                    && str_contains($data['pdf_options']['headerTemplate'], 'class="date"')
                    && str_contains($data['pdf_options']['footerTemplate'], 'class="pageNumber"');
            })
            ->andReturn($fakePdf);

        $pdf = $this->browser->pdf(new PdfRequest(
            url: 'https://news.ycombinator.com',
            format: PdfFormat::A4,
            pdfOptions: [
                'landscape' => false,
                'printBackground' => true,
                'preferCSSPageSize' => true,
                'displayHeaderFooter' => true,
                'scale' => 1.0,
                'headerTemplate' => '<div style="width: 100%; font-size: 10px; padding: 10px; text-align: center;"><div style="border-bottom: 1px solid #ddd;"><span style="color: #666;">Company Name</span> | <span class="date"></span> | <span class="title"></span></div></div>',
                'footerTemplate' => '<div style="width: 100%; font-size: 10px; padding: 10px; text-align: center;"><div style="border-top: 1px solid #ddd;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div></div>',
                'margin' => [
                    'top' => '100px',
                    'bottom' => '80px',
                    'right' => '30px',
                    'left' => '30px'
                ]
            ]
        ));

        $this->assertEquals($fakePdf, $pdf);
    }

    // ==========================================
    // /snapshot Endpoint Tests
    // ==========================================

    public function testSnapshotFromUrlExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'url' => 'https://example.com/',
                'title' => 'Example Page',
                'content' => 'Example content with screenshot',
                'html' => '<html><body>Example</body></html>',
                'text' => 'Example'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'snapshot' 
                    && $data['url'] === 'https://example.com/';
            })
            ->andReturn($response);

        $snapshot = $this->browser->snapshot(new SnapshotRequest(
            url: 'https://example.com/'
        ));

        $this->assertEquals('https://example.com/', $snapshot->url);
        $this->assertEquals('Example Page', $snapshot->title);
        $this->assertEquals('Example content with screenshot', $snapshot->content);
    }

    public function testSnapshotFromHtmlExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'url' => '',
                'title' => '',
                'content' => '<html><body>Advanced Snapshot</body></html>',
                'html' => '<html><body>Advanced Snapshot</body></html>',
                'text' => 'Advanced Snapshot'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'snapshot' 
                    && $data['html'] === '<html><body>Advanced Snapshot</body></html>'
                    && $data['viewport']['width'] === 1200
                    && $data['viewport']['height'] === 800
                    && $data['goto_options']['wait_until'] === 'domcontentloaded'
                    && $data['goto_options']['timeout'] === 30000;
            })
            ->andReturn($response);

        $snapshot = $this->browser->snapshot(new SnapshotRequest(
            html: '<html><body>Advanced Snapshot</body></html>',
            viewport: new Viewport(
                width: 1200,
                height: 800
            ),
            gotoOptions: new GotoOptions(
                waitUntil: WaitUntil::DOM_CONTENT_LOADED,
                timeout: 30000
            )
        ));

        $this->assertStringContainsString('Advanced Snapshot', $snapshot->content);
    }

    public function testSnapshotHighResolutionExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'url' => 'https://cloudflare.com/',
                'title' => 'Cloudflare',
                'content' => 'Content',
                'html' => '<html></html>',
                'text' => 'Cloudflare'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'snapshot' 
                    && $data['viewport']['width'] === 3600
                    && $data['viewport']['height'] === 2400
                    && $data['viewport']['device_scale_factor'] === 2.0;
            })
            ->andReturn($response);

        $snapshot = $this->browser->snapshot(new SnapshotRequest(
            url: 'https://cloudflare.com/',
            viewport: new Viewport(
                width: 3600,
                height: 2400,
                deviceScaleFactor: 2
            )
        ));

        $this->assertEquals('https://cloudflare.com/', $snapshot->url);
    }

    // ==========================================
    // /scrape Endpoint Tests
    // ==========================================

    public function testScrapeHeadingsAndLinksExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                [
                    'selector' => 'h1',
                    'results' => [
                        [
                            'text' => 'Example Domain',
                            'html' => 'Example Domain',
                            'width' => 600,
                            'height' => 39,
                            'top' => 133.4375,
                            'left' => 100,
                            'attributes' => []
                        ]
                    ]
                ],
                [
                    'selector' => 'a',
                    'results' => [
                        [
                            'text' => 'More information...',
                            'html' => 'More information...',
                            'width' => 142,
                            'height' => 20,
                            'top' => 249.875,
                            'left' => 100,
                            'attributes' => [
                                ['name' => 'href', 'value' => 'https://www.iana.org/domains/example']
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'scrape' 
                    && $data['url'] === 'https://example.com/'
                    && $data['elements'][0]['selector'] === 'h1'
                    && $data['elements'][1]['selector'] === 'a';
            })
            ->andReturn($response);

        $results = $this->browser->scrape(new ScrapeRequest(
            url: 'https://example.com/',
            elements: [
                ['selector' => 'h1'],
                ['selector' => 'a']
            ]
        ));

        $this->assertCount(2, $results);
        $this->assertEquals('h1', $results[0]->selector);
        $this->assertEquals('Example Domain', $results[0]->results[0]->text);
        $this->assertEquals('a', $results[1]->selector);
        $this->assertEquals('More information...', $results[1]->results[0]->text);
    }

    public function testScrapeJavaScriptHeavyPagesExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                [
                    'selector' => '.dynamic-content',
                    'results' => [
                        [
                            'text' => 'Dynamic Content',
                            'html' => '<div class="dynamic-content">Dynamic Content</div>',
                            'width' => 100,
                            'height' => 50,
                            'top' => 10,
                            'left' => 10,
                            'attributes' => ['class' => 'dynamic-content']
                        ]
                    ]
                ]
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($response);

        $results = $this->browser->scrape(new ScrapeRequest(
            url: 'https://example.com',
            elements: [
                ['selector' => '.dynamic-content']
            ],
            gotoOptions: new GotoOptions(
                waitUntil: WaitUntil::NETWORK_IDLE
            )
        ));

        $this->assertCount(1, $results);
        $this->assertEquals('.dynamic-content', $results[0]->selector);
    }

    // ==========================================
    // /json Endpoint Tests
    // ==========================================

    public function testJsonWithSchemaExample(): void
    {
        $mockResponse = json_encode([
            'success' => true,
            'result' => [
                'name' => 'Workers AI',
                'link' => 'https://developers.cloudflare.com/workers-ai/'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'json' 
                    && $data['url'] === 'https://developers.cloudflare.com/'
                    && $data['prompt'] === 'Get me the list of AI products'
                    && isset($data['response_format']['schema']['properties']['name']);
            })
            ->andReturn($mockResponse);

        $result = $this->browser->json(
            new JsonRequest(
                url: 'https://developers.cloudflare.com/',
                prompt: 'Get me the list of AI products'
            ),
            WikiProductSchema::class
        );

        $this->assertEquals('Workers AI', $result->name);
    }

    public function testJsonJavaScriptHeavyPagesExample(): void
    {
        $mockResponse = json_encode([
            'success' => true,
            'result' => [
                'title' => 'Example Page'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'json' 
                    && $data['goto_options']['wait_until'] === 'networkidle';
            })
            ->andReturn($mockResponse);

        $result = $this->browser->json(
            new JsonRequest(
                url: 'https://example.com',
                prompt: 'Extract the page title',
                gotoOptions: new GotoOptions(
                    waitUntil: WaitUntil::NETWORK_IDLE
                )
            ),
            WikiPageDataSchema::class
        );

        $this->assertEquals('Example Page', $result->title);
    }

    // ==========================================
    // /links Endpoint Tests
    // ==========================================

    public function testLinksGetAllExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'https://developers.cloudflare.com/',
                'https://developers.cloudflare.com/products/',
                'https://developers.cloudflare.com/api/'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('links', json_encode(['url' => 'https://developers.cloudflare.com/', 'excludeExternalLinks' => false]))
            ->andReturn($response);

        $links = $this->browser->links('https://developers.cloudflare.com/');

        $this->assertCount(3, $links);
        $this->assertEquals('https://developers.cloudflare.com/', $links[0]);
    }

    public function testLinksExcludeExternalExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'https://developers.cloudflare.com/',
                'https://developers.cloudflare.com/products/'
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('links', json_encode([
                'url' => 'https://developers.cloudflare.com/',
                'excludeExternalLinks' => true
            ]))
            ->andReturn($response);

        $links = $this->browser->links(
            url: 'https://developers.cloudflare.com/',
            excludeExternal: true
        );

        $this->assertCount(2, $links);
    }

    // ==========================================
    // /markdown Endpoint Tests
    // ==========================================

    public function testMarkdownFromUrlExample(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => "# Example Domain\n\nThis domain is for use in illustrative examples in documents.\n[More information...](https://www.iana.org/domains/example)"
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('markdown', json_encode(['url' => 'https://example.com']))
            ->andReturn($response);

        $markdown = $this->browser->markdown('https://example.com');

        $this->assertStringContainsString('# Example Domain', $markdown);
        $this->assertStringContainsString('[More information...]', $markdown);
    }
}

// Schema classes for JSON endpoint tests
class WikiProductSchema
{
    public function __construct(
        #[Field(title: 'Product Name')]
        public string $name,

        #[Field(title: 'Product Link')]
        public ?string $link = null
    ) {}
}

class WikiPageDataSchema
{
    public function __construct(
        #[Field(title: 'Title')]
        public string $title
    ) {}
}
