<?php

declare(strict_types=1);

namespace Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Shanginn\CloudflareBrowser\CloudflareBrowser;
use Shanginn\CloudflareBrowser\CloudflareClientInterface;
use Shanginn\CloudflareBrowser\Requests\JsonRequest;
use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;
use Shanginn\CloudflareBrowser\Requests\PdfRequest;
use Shanginn\CloudflareBrowser\Requests\ScrapeRequest;
use Shanginn\CloudflareBrowser\Requests\SnapshotRequest;
use Shanginn\CloudflareBrowser\Requests\Common\Viewport;
use Shanginn\CloudflareBrowser\Enums\PdfFormat;
use Shanginn\CloudflareBrowser\Enums\ImageFormat;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareException;

class CloudflareBrowserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $mockClient;
    private CloudflareBrowser $browser;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(CloudflareClientInterface::class);
        $this->browser = new CloudflareBrowser($this->mockClient);
    }

    public function testScreenshotSuccess(): void
    {
        $fakePng = "fake-binary-data";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' && $data['url'] === 'https://google.com';
            })
            ->andReturn($fakePng);

        $result = $this->browser->screenshot(new ScreenshotRequest(url: 'https://google.com'));
        $this->assertEquals($fakePng, $result);
    }

    public function testScreenshotWithOptions(): void
    {
        $fakePng = "fake-binary-data";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['url'] === 'https://example.com'
                    && $data['screenshotOptions']['fullPage'] === true
                    && $data['format'] === 'webp';
            })
            ->andReturn($fakePng);

        $result = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com',
            screenshotOptions: ['fullPage' => true],
            format: ImageFormat::WEBP
        ));
        $this->assertEquals($fakePng, $result);
    }

    public function testPdfSuccess(): void
    {
        $fakePdf = "fake-pdf-data";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'pdf' 
                    && $data['url'] === 'https://example.com'
                    && $data['pdfOptions']['format'] === 'a4';
            })
            ->andReturn($fakePdf);

        $result = $this->browser->pdf(new PdfRequest(
            url: 'https://example.com',
            format: PdfFormat::A4
        ));
        $this->assertEquals($fakePdf, $result);
    }

    public function testContentSuccess(): void
    {
        $fakeHtml = "<html><body>Hello World</body></html>";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('content', json_encode(['url' => 'https://example.com']))
            ->andReturn($fakeHtml);

        $result = $this->browser->content('https://example.com');
        $this->assertEquals($fakeHtml, $result);
    }

    public function testMarkdownSuccess(): void
    {
        $response = json_encode(['success' => true, 'result' => '# Hello World']);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('markdown', json_encode(['url' => 'https://example.com']))
            ->andReturn($response);

        $result = $this->browser->markdown('https://example.com');
        $this->assertEquals('# Hello World', $result);
    }

    public function testSnapshotSuccess(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                'url' => 'https://example.com',
                'title' => 'Example Page',
                'content' => 'Page content',
                'html' => '<html>content</html>',
                'text' => 'Page content'
            ]
        ]);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'snapshot' && $data['url'] === 'https://example.com';
            })
            ->andReturn($response);

        $result = $this->browser->snapshot(new SnapshotRequest(url: 'https://example.com'));
        
        $this->assertEquals('https://example.com', $result->url);
        $this->assertEquals('Example Page', $result->title);
        $this->assertEquals('Page content', $result->content);
    }

    public function testSnapshotInvalidResponseThrowsException(): void
    {
        $response = json_encode(['success' => false]);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($response);

        $this->expectException(CloudflareException::class);
        $this->expectExceptionMessage('Invalid API response structure');

        $this->browser->snapshot(new SnapshotRequest(url: 'https://example.com'));
    }

    public function testScrapeSuccess(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => [
                [
                    'selector' => '.title',
                    'results' => [
                        [
                            'text' => 'Title 1',
                            'html' => '<div class="title">Title 1</div>',
                            'width' => 100.0,
                            'height' => 50.0,
                            'top' => 10.0,
                            'left' => 20.0,
                            'attributes' => ['class' => 'title']
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
                    && $data['url'] === 'https://example.com'
                    && isset($data['elements']);
            })
            ->andReturn($response);

        $results = $this->browser->scrape(new ScrapeRequest(
            url: 'https://example.com',
            elements: [['selector' => '.title']]
        ));
        
        $this->assertCount(1, $results);
        $this->assertEquals('.title', $results[0]->selector);
        $this->assertCount(1, $results[0]->results);
        $this->assertEquals('Title 1', $results[0]->results[0]->text);
    }

    public function testLinksSuccess(): void
    {
        $response = json_encode([
            'success' => true,
            'result' => ['https://example.com/page1', 'https://example.com/page2']
        ]);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'links' 
                    && $data['url'] === 'https://example.com'
                    && $data['excludeExternalLinks'] === true;
            })
            ->andReturn($response);

        $results = $this->browser->links('https://example.com', excludeExternal: true);
        
        $this->assertCount(2, $results);
        $this->assertEquals('https://example.com/page1', $results[0]);
    }

    public function testJsonExtractionWithSchema(): void
    {
        $mockResponse = json_encode([
            'success' => true,
            'result' => [
                'name' => 'Test Product',
                'price' => 99.99
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                // Assert Schema was generated and injected
                return $endpoint === 'json' 
                    && isset($data['response_format']['schema']['properties']['name']);
            })
            ->andReturn($mockResponse);

        $request = new JsonRequest(url: 'https://shop.com', prompt: 'Extract product');
        
        /** @var SampleJsonSchema $result */
        $result = $this->browser->json($request, SampleJsonSchema::class);

        $this->assertInstanceOf(SampleJsonSchema::class, $result);
        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals(99.99, $result->price);
    }

    public function testJsonExtractionPreservesPrompt(): void
    {
        $mockResponse = json_encode([
            'success' => true,
            'result' => [
                'name' => 'Test Product',
                'price' => 99.99
            ]
        ]);

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'json' 
                    && $data['prompt'] === 'Extract product details'
                    && $data['url'] === 'https://shop.com';
            })
            ->andReturn($mockResponse);

        $request = new JsonRequest(
            url: 'https://shop.com', 
            prompt: 'Extract product details'
        );
        
        $result = $this->browser->json($request, SampleJsonSchema::class);

        $this->assertInstanceOf(SampleJsonSchema::class, $result);
    }

    public function testRequestWithViewport(): void
    {
        $fakePng = "fake-binary-data";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->withArgs(function($endpoint, $body) {
                $data = json_decode($body, true);
                return $endpoint === 'screenshot' 
                    && $data['viewport']['width'] === 1200
                    && $data['viewport']['height'] === 800
                    && $data['viewport']['device_scale_factor'] === 2.0;
            })
            ->andReturn($fakePng);

        $result = $this->browser->screenshot(new ScreenshotRequest(
            url: 'https://example.com',
            viewport: new Viewport(width: 1200, height: 800, deviceScaleFactor: 2.0)
        ));
        $this->assertEquals($fakePng, $result);
    }
}
