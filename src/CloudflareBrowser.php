<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser;

use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;
use Shanginn\CloudflareBrowser\Requests\PdfRequest;
use Shanginn\CloudflareBrowser\Requests\ScrapeRequest;
use Shanginn\CloudflareBrowser\Requests\JsonRequest;
use Shanginn\CloudflareBrowser\Requests\SnapshotRequest;
use Shanginn\CloudflareBrowser\Responses\SnapshotResult;
use Shanginn\CloudflareBrowser\Responses\ScrapeResult;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareException;

class CloudflareBrowser
{
    private CloudflareSerializer $serializer;

    public function __construct(
        private readonly CloudflareClientInterface $client
    ) {
        $this->serializer = new CloudflareSerializer();
    }

    /**
     * @return string Binary image data
     */
    public function screenshot(ScreenshotRequest $request): string
    {
        return $this->client->post('screenshot', $this->serializer->serialize($request));
    }

    /**
     * @return string Binary PDF data
     */
    public function pdf(PdfRequest $request): string
    {
        return $this->client->post('pdf', $this->serializer->serialize($request));
    }

    /**
     * @return string HTML content
     */
    public function content(string $url): string
    {
        // Simple wrapper for content endpoint
        return $this->client->post('content', json_encode(['url' => $url]));
    }
    
    /**
     * @return string Markdown content
     */
    public function markdown(string $url): string
    {
        // Simple wrapper for markdown endpoint
        $response = $this->client->post('markdown', json_encode(['url' => $url]));
        
        // Markdown endpoint returns JSON wrapper
        $data = json_decode($response, true);
        return $data['result'] ?? '';
    }

    public function snapshot(SnapshotRequest $request): SnapshotResult
    {
        $json = $this->client->post('snapshot', $this->serializer->serialize($request));
        // Unwrap logic: { success: true, result: { ... } }
        $data = json_decode($json, true);
        if (!isset($data['result'])) {
            throw new CloudflareException('Invalid API response structure');
        }
        
        // Re-serialize the 'result' part to deserialize into object
        return $this->serializer->deserialize(json_encode($data['result']), SnapshotResult::class);
    }

    /**
     * @return ScrapeResult[]
     */
    public function scrape(ScrapeRequest $request): array
    {
        $json = $this->client->post('scrape', $this->serializer->serialize($request));
        $data = json_decode($json, true);
        
        // Result is an array of objects
        $results = [];
        foreach ($data['result'] ?? [] as $item) {
             $results[] = $this->serializer->deserialize(json_encode($item), ScrapeResult::class);
        }
        
        return $results;
    }

    /**
     * @return string[]
     */
    public function links(string $url, bool $excludeExternal = false): array
    {
        $json = $this->client->post('links', json_encode([
            'url' => $url,
            'excludeExternalLinks' => $excludeExternal
        ]));
        $data = json_decode($json, true);
        
        return $data['result'] ?? [];
    }

    /**
     * Extracts structured data using AI based on a class schema.
     * 
     * @template T
     * @param JsonRequest $request
     * @param class-string<T> $schemaClass
     * @return T
     */
    public function json(JsonRequest $request, string $schemaClass): object
    {
        // Generate schema from the provided class
        $jsonSchema = $this->serializer->generateSchema($schemaClass);
        
        // Inject schema into request
        $request->responseFormat = [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => $jsonSchema['properties'] ?? [],
                'required' => $jsonSchema['required'] ?? [],
            ]
        ];

        $json = $this->client->post('json', $this->serializer->serialize($request));
        $data = json_decode($json, true);

        // Deserialize result back to PHP object
        return $this->serializer->deserialize(json_encode($data['result']), $schemaClass);
    }
}
