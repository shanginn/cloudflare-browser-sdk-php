<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareApiErrorException;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareException;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareRateLimitException;

final readonly class CloudflareClient implements CloudflareClientInterface
{
    private HttpClient $client;
    private string $baseUrl;

    public function __construct(
        private string $accountId,
        private string $apiToken,
    ) {
        $this->client = HttpClientBuilder::buildDefault();
        $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/browser-rendering";
    }

    public function post(string $endpoint, string $jsonBody): string
    {
        $url = "{$this->baseUrl}/{$endpoint}";

        $request = new Request($url, 'POST');
        $request->setBody($jsonBody);
        $request->setHeader('Authorization', "Bearer {$this->apiToken}");
        $request->setHeader('Content-Type', 'application/json');
        // Extended timeout for rendering tasks
        $request->setTransferTimeout(60); 
        $request->setInactivityTimeout(60);

        try {
            $response = $this->client->request($request);
            $body = $response->getBody()->buffer();
            $status = $response->getStatus();

            if ($status >= 400) {
                // Try to parse error message if JSON
                $data = json_decode($body, true);
                $msg = $data['errors'][0]['message'] ?? "HTTP $status error";
                
                // Handle rate limit (429) specifically
                if ($status === 429) {
                    $retryAfter = (int) ($response->getHeader('Retry-After') ?? 0);
                    throw new CloudflareRateLimitException($msg, $retryAfter);
                }
                
                throw new CloudflareApiErrorException($msg, $status);
            }

            return $body;

        } catch (\Throwable $e) {
            if ($e instanceof CloudflareApiErrorException) {
                throw $e;
            }
            throw new CloudflareException("Request failed: " . $e->getMessage(), 0, $e);
        }
    }
}
