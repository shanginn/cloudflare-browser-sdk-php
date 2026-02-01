<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser;

interface CloudflareClientInterface
{
    /**
     * Sends a request to the Cloudflare API.
     *
     * @param string $endpoint The relative endpoint (e.g., 'screenshot', 'pdf')
     * @param string $jsonBody The JSON serialized body
     * @return string The raw response body (JSON string or binary data)
     */
    public function post(string $endpoint, string $jsonBody): string;
}
