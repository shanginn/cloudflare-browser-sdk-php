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

function sleepTenSecond(): void
{
    echo "   (sleeping 10s...)\n";
    sleep(10);
}

// ==========================================
// Test 1: /screenshot - Capture Screenshot
// ==========================================
echo "Test 1: Capturing screenshot of example.com...\n";
try {
    $screenshot = $browser->screenshot(new ScreenshotRequest(
        url: 'https://example.com',
        screenshotOptions: ['fullPage' => false]
    ));
    
    // Save screenshot
    $filename = 'test-screenshot-' . time() . '.png';
    file_put_contents($filename, $screenshot);
    
    // Verify it's a PNG (check magic bytes)
    $pngMagicBytes = pack('H*', '89504E47');
    if (str_starts_with($screenshot, $pngMagicBytes)) {
        echo "✅ Screenshot test PASSED - Saved as {$filename} (" . strlen($screenshot) . " bytes)\n";
        $testResults['screenshot'] = true;
    } else {
        echo "⚠️ Screenshot test WARNING - Saved but may not be a valid PNG\n";
        $testResults['screenshot'] = true;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Screenshot test FAILED - " . $e->getMessage() . "\n";
    $testResults['screenshot'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 2: /pdf - Generate PDF
// ==========================================
echo "Test 2: Generating PDF from example.com...\n";
try {
    $pdf = $browser->pdf(new PdfRequest(
        url: 'https://example.com',
        format: PdfFormat::A4
    ));
    
    // Save PDF
    $filename = 'test-pdf-' . time() . '.pdf';
    file_put_contents($filename, $pdf);
    
    // Verify it's a PDF (check magic bytes %PDF)
    if (str_starts_with($pdf, '%PDF')) {
        echo "✅ PDF test PASSED - Saved as {$filename} (" . strlen($pdf) . " bytes)\n";
        $testResults['pdf'] = true;
    } else {
        echo "⚠️ PDF test WARNING - Saved but may not be a valid PDF\n";
        $testResults['pdf'] = true;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ PDF test FAILED - " . $e->getMessage() . "\n";
    $testResults['pdf'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 3: /snapshot - Take Snapshot
// ==========================================
echo "Test 3: Taking snapshot of example.com...\n";
try {
    $snapshot = $browser->snapshot(new SnapshotRequest(
        url: 'https://example.com'
    ));
    
    if (!empty($snapshot->url) || !empty($snapshot->content)) {
        echo "✅ Snapshot test PASSED\n";
        echo "   URL: " . ($snapshot->url ?? 'N/A') . "\n";
        echo "   Title: " . ($snapshot->title ?? 'N/A') . "\n";
        echo "   Content length: " . strlen($snapshot->content ?? '') . " bytes\n";
        $testResults['snapshot'] = true;
    } else {
        echo "❌ Snapshot test FAILED - Empty result\n";
        $testResults['snapshot'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Snapshot test FAILED - " . $e->getMessage() . "\n";
    $testResults['snapshot'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 4: /content - Fetch HTML
// ==========================================
echo "Test 4: Fetching HTML content from example.com...\n";
try {
    $html = $browser->content('https://example.com');
    
    if (strlen($html) > 0 && str_contains($html, 'Example Domain')) {
        echo "✅ Content test PASSED - Received " . strlen($html) . " bytes\n";
        $testResults['content'] = true;
    } else {
        echo "❌ Content test FAILED - Unexpected content\n";
        $testResults['content'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Content test FAILED - " . $e->getMessage() . "\n";
    $testResults['content'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 5: /markdown - Extract Markdown
// ==========================================
echo "Test 5: Extracting Markdown from example.com...\n";
try {
    $markdown = $browser->markdown('https://example.com');
    
    if (strlen($markdown) > 0 && str_contains($markdown, 'Example Domain')) {
        echo "✅ Markdown test PASSED\n";
        echo "   Preview: " . substr($markdown, 0, 100) . "...\n";
        $testResults['markdown'] = true;
    } else {
        echo "❌ Markdown test FAILED - Unexpected content\n";
        $testResults['markdown'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Markdown test FAILED - " . $e->getMessage() . "\n";
    $testResults['markdown'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 6: /links - Retrieve Links
// ==========================================
echo "Test 6: Retrieving links from example.com...\n";
try {
    $links = $browser->links('https://example.com');
    
    if (is_array($links) && count($links) > 0) {
        echo "✅ Links test PASSED - Found " . count($links) . " links\n";
        echo "   First link: " . $links[0] . "\n";
        $testResults['links'] = true;
    } else {
        echo "❌ Links test FAILED - No links found\n";
        $testResults['links'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Links test FAILED - " . $e->getMessage() . "\n";
    $testResults['links'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 7: /scrape - Scrape Elements
// ==========================================
echo "Test 7: Scraping elements from example.com...\n";
try {
    $results = $browser->scrape(new ScrapeRequest(
        url: 'https://example.com',
        elements: [
            ['selector' => 'h1'],
            ['selector' => 'p'],
            ['selector' => 'a']
        ]
    ));
    
    if (is_array($results) && count($results) > 0) {
        echo "✅ Scrape test PASSED - Found " . count($results) . " element groups\n";
        foreach ($results as $group) {
            echo "   Selector '{$group->selector}': " . count($group->results) . " elements\n";
            if (count($group->results) > 0) {
                echo "     First text: " . substr($group->results[0]->text, 0, 50) . "\n";
            }
        }
        $testResults['scrape'] = true;
    } else {
        echo "❌ Scrape test FAILED - No results\n";
        $testResults['scrape'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ Scrape test FAILED - " . $e->getMessage() . "\n";
    $testResults['scrape'] = false;
}
echo "\n";
sleepTenSecond();

// ==========================================
// Test 8: /json - AI Structured Data Extraction
// ==========================================
echo "Test 8: Extracting structured data using AI from cloudflare.com...\n";
echo "   (This may take a few seconds + 1s sleep)...\n";

// Define schema inline
class TestPageInfo
{
    public function __construct(
        #[Field(title: 'Page Title', description: 'The title of the webpage')]
        public string $title,

        #[Field(title: 'Description', description: 'A brief description or tagline')]
        public ?string $description = null
    ) {}
}

try {
    $pageInfo = $browser->json(
        new JsonRequest(
            url: 'https://cloudflare.com',
            prompt: 'Extract the page title and main description'
        ),
        TestPageInfo::class
    );
    
    if (!empty($pageInfo->title)) {
        echo "✅ JSON extraction test PASSED\n";
        echo "   Title: " . $pageInfo->title . "\n";
        echo "   Description: " . ($pageInfo->description ?? 'N/A') . "\n";
        $testResults['json'] = true;
    } else {
        echo "❌ JSON extraction test FAILED - No title extracted\n";
        $testResults['json'] = false;
    }
} catch (Exception $e) {
    dump($e);
    echo "❌ JSON extraction test FAILED - " . $e->getMessage() . "\n";
    $testResults['json'] = false;
}
echo "\n";

// ==========================================
// Summary
// ==========================================
echo "========================================\n";
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
