<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Shanginn\CloudflareBrowser\CloudflareClient;
use Shanginn\CloudflareBrowser\CloudflareBrowser;
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
use Shanginn\CloudflareBrowser\Exceptions\CloudflareRateLimitException;
use Spiral\JsonSchemaGenerator\Attribute\Field;
use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

$accountId = $_ENV['CLOUDFLARE_ACCOUNT_ID'] ?? getenv('CLOUDFLARE_ACCOUNT_ID');
$apiToken = $_ENV['CLOUDFLARE_API_TOKEN'] ?? getenv('CLOUDFLARE_API_TOKEN');

if (empty($accountId) || empty($apiToken)) {
    echo "Error: Please set CLOUDFLARE_ACCOUNT_ID and CLOUDFLARE_API_TOKEN environment variables.\n";
    echo "Copy .env.example to .env and fill in your credentials.\n";
    exit(1);
}

echo "========================================\n";
echo "Cloudflare Browser Rendering PHP SDK\n";
echo "Real API Test Script\n";
echo "========================================\n\n";

// Initialize client
$client = new CloudflareClient($accountId, $apiToken);
$browser = new CloudflareBrowser($client);

$testResults = [];

/**
 * Execute a test with automatic rate limit handling.
 * If rate limited, waits for the specified duration and retries once.
 */
function runTest(
    string $testName,
    CloudflareBrowser $browser,
    callable $testFn
): array {
    $maxRetries = 2;
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        $attempt++;
        
        try {
            return ['success' => true, 'result' => $testFn()];
        } catch (CloudflareRateLimitException $e) {
            $retryAfter = $e->retryAfter ?: 10;
            echo "   ⚠️  Rate limited! Waiting {$retryAfter} seconds...\n";
            sleep($retryAfter);
            
            if ($attempt >= $maxRetries) {
                echo "❌ {$testName} FAILED - Rate limit exceeded after {$maxRetries} attempts\n";
                return ['success' => false, 'error' => $e->getMessage()];
            }
            
            echo "   Retrying...\n";
        } catch (Exception $e) {
            dump($e);
            echo "❌ {$testName} FAILED - " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    return ['success' => false, 'error' => 'Max retries exceeded'];
}

/**
 * Sleep between tests to avoid rate limits.
 */
function sleepBetweenTests(): void
{
    echo "   (sleeping 1s between tests...)\n";
    sleep(1);
}

// ==========================================
// Test 1: /screenshot - Capture Screenshot
// ==========================================
echo "Test 1: Capturing screenshot of example.com...\n";
$result = runTest('Screenshot', $browser, function() use ($browser) {
    $screenshot = $browser->screenshot(new ScreenshotRequest(
        url: 'https://example.com',
        screenshotOptions: ['fullPage' => false]
    ));
    
    // Save screenshot
    $filename = 'test-screenshot-' . time() . '.png';
    file_put_contents($filename, $screenshot);
    
    // Verify it's a PNG (check magic bytes)
    $pngMagicBytes = pack('H*', '89504E47');
    if (!str_starts_with($screenshot, $pngMagicBytes)) {
        throw new Exception('Not a valid PNG file');
    }
    
    echo "✅ Screenshot test PASSED - Saved as {$filename} (" . strlen($screenshot) . " bytes)\n";
    return true;
});
$testResults['screenshot'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 2: /pdf - Generate PDF
// ==========================================
echo "Test 2: Generating PDF from example.com...\n";
$result = runTest('PDF', $browser, function() use ($browser) {
    $pdf = $browser->pdf(new PdfRequest(
        url: 'https://example.com',
        format: PdfFormat::A4
    ));
    
    // Save PDF
    $filename = 'test-pdf-' . time() . '.pdf';
    file_put_contents($filename, $pdf);
    
    // Verify it's a PDF (check magic bytes %PDF)
    if (!str_starts_with($pdf, '%PDF')) {
        throw new Exception('Not a valid PDF file');
    }
    
    echo "✅ PDF test PASSED - Saved as {$filename} (" . strlen($pdf) . " bytes)\n";
    return true;
});
$testResults['pdf'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 3: /snapshot - Take Snapshot
// ==========================================
echo "Test 3: Taking snapshot of example.com...\n";
$result = runTest('Snapshot', $browser, function() use ($browser) {
    $snapshot = $browser->snapshot(new SnapshotRequest(
        url: 'https://example.com'
    ));
    
    if (empty($snapshot->url) && empty($snapshot->content)) {
        throw new Exception('Empty result');
    }
    
    echo "✅ Snapshot test PASSED\n";
    echo "   URL: " . ($snapshot->url ?: 'N/A') . "\n";
    echo "   Title: " . ($snapshot->title ?: 'N/A') . "\n";
    echo "   Content length: " . strlen($snapshot->content) . " bytes\n";
    return true;
});
$testResults['snapshot'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 4: /content - Fetch HTML
// ==========================================
echo "Test 4: Fetching HTML content from example.com...\n";
$result = runTest('Content', $browser, function() use ($browser) {
    $html = $browser->content('https://example.com');
    
    if (strlen($html) === 0 || !str_contains($html, 'Example Domain')) {
        throw new Exception('Unexpected content');
    }
    
    echo "✅ Content test PASSED - Received " . strlen($html) . " bytes\n";
    return true;
});
$testResults['content'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 5: /markdown - Extract Markdown
// ==========================================
echo "Test 5: Extracting Markdown from example.com...\n";
$result = runTest('Markdown', $browser, function() use ($browser) {
    $markdown = $browser->markdown('https://example.com');
    
    if (strlen($markdown) === 0 || !str_contains($markdown, 'Example Domain')) {
        throw new Exception('Unexpected content');
    }
    
    echo "✅ Markdown test PASSED\n";
    echo "   Preview: " . substr($markdown, 0, 100) . "...\n";
    return true;
});
$testResults['markdown'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 6: /links - Retrieve Links
// ==========================================
echo "Test 6: Retrieving links from example.com...\n";
$result = runTest('Links', $browser, function() use ($browser) {
    $links = $browser->links('https://example.com');
    
    if (!is_array($links) || count($links) === 0) {
        throw new Exception('No links found');
    }
    
    echo "✅ Links test PASSED - Found " . count($links) . " links\n";
    echo "   First link: " . $links[0] . "\n";
    return true;
});
$testResults['links'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 7: /scrape - Scrape Elements
// ==========================================
echo "Test 7: Scraping elements from example.com...\n";
$result = runTest('Scrape', $browser, function() use ($browser) {
    $results = $browser->scrape(new ScrapeRequest(
        url: 'https://example.com',
        elements: [
            ['selector' => 'h1'],
            ['selector' => 'p'],
            ['selector' => 'a']
        ]
    ));
    
    if (!is_array($results) || count($results) === 0) {
        throw new Exception('No results');
    }
    
    echo "✅ Scrape test PASSED - Found " . count($results) . " element groups\n";
    foreach ($results as $group) {
        echo "   Selector '{$group->selector}': " . count($group->results) . " elements\n";
        if (count($group->results) > 0) {
            echo "     First text: " . substr($group->results[0]->text, 0, 50) . "\n";
        }
    }
    return true;
});
$testResults['scrape'] = $result['success'];
sleepBetweenTests();

// ==========================================
// Test 8: /json - AI Structured Data Extraction
// ==========================================
echo "Test 8: Extracting structured data using AI from cloudflare.com...\n";
echo "   (This may take a few seconds...)\n";

class TestPageInfo
{
    public function __construct(
        #[Field(title: 'Page Title', description: 'The title of the webpage')]
        public string $title,

        #[Field(title: 'Description', description: 'A brief description or tagline')]
        public ?string $description = null
    ) {}
}

$result = runTest('JSON', $browser, function() use ($browser) {
    $pageInfo = $browser->json(
        new JsonRequest(
            url: 'https://cloudflare.com',
            prompt: 'Extract the page title and main description'
        ),
        TestPageInfo::class
    );
    
    if (empty($pageInfo->title)) {
        throw new Exception('No title extracted');
    }
    
    echo "✅ JSON extraction test PASSED\n";
    echo "   Title: " . $pageInfo->title . "\n";
    echo "   Description: " . ($pageInfo->description ?: 'N/A') . "\n";
    return true;
});
$testResults['json'] = $result['success'];

// ==========================================
// Summary
// ==========================================
echo "\n========================================\n";
echo "Test Summary\n";
echo "========================================\n";

$passed = 0;
$failed = 0;

foreach ($testResults as $test => $result) {
    $status = $result ? '✅ PASSED' : '❌ FAILED';
    echo "{$test}: {$status}\n";
    if ($result) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";
echo "Total: " . ($passed + $failed) . " tests\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
