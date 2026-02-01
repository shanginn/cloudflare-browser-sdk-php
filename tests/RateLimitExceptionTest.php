<?php

declare(strict_types=1);

namespace Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Shanginn\CloudflareBrowser\CloudflareBrowser;
use Shanginn\CloudflareBrowser\CloudflareClientInterface;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareRateLimitException;
use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;

class RateLimitExceptionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $mockClient;
    private CloudflareBrowser $browser;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(CloudflareClientInterface::class);
        $this->browser = new CloudflareBrowser($this->mockClient);
    }

    public function testRateLimitExceptionIsThrown(): void
    {
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new CloudflareRateLimitException('Rate limit exceeded', 60));

        $this->expectException(CloudflareRateLimitException::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        $this->expectExceptionCode(429);

        $this->browser->screenshot(new ScreenshotRequest(url: 'https://example.com'));
    }

    public function testRateLimitExceptionHasRetryAfter(): void
    {
        $exception = new CloudflareRateLimitException('Rate limit exceeded', 60);
        
        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertEquals(60, $exception->retryAfter);
        $this->assertStringContainsString('Rate limit', $exception->getMessage());
    }

    public function testRateLimitExceptionExtendsApiErrorException(): void
    {
        $exception = new CloudflareRateLimitException('Rate limit exceeded');
        
        $this->assertInstanceOf(
            \Shanginn\CloudflareBrowser\Exceptions\CloudflareApiErrorException::class,
            $exception
        );
        $this->assertInstanceOf(
            \Shanginn\CloudflareBrowser\Exceptions\CloudflareException::class,
            $exception
        );
    }
}
