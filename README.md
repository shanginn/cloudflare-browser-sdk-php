# Cloudflare Browser Rendering PHP SDK

A strongly-typed, modern PHP SDK for the Cloudflare Browser Rendering REST API. Built with `amphp/http-client` for performance and `crell/serde` for robust object serialization.

## Features

*   **Full Coverage**: Supports `/content`, `/screenshot`, `/pdf`, `/scrape`, `/json`, `/snapshot`, `/links`, and `/markdown` endpoints.
*   **Strong Typing**: Uses Request DTOs and specific Response objects.
*   **AI Extraction**: Seamless integration with `spiral/json-schema-generator` to extract structured data from webpages into PHP objects.
*   **Custom Exceptions**: Granular error handling for API errors.

## Installation

```bash
composer require shanginn/cloudflare-browser
```

## Documentation

📚 **[View the full Wiki Documentation](../../wiki)** for detailed guides on all endpoints.

### Quick Links

- [Getting Started & REST API Overview](../../wiki/REST-API)
- [Fetching HTML Content](../../wiki/content-endpoint)
- [Capturing Screenshots](../../wiki/screenshot-endpoint)
- [Generating PDFs](../../wiki/pdf-endpoint)
- [Taking Snapshots](../../wiki/snapshot-endpoint)
- [Scraping Elements](../../wiki/scrape-endpoint)
- [AI Structured Data Extraction](../../wiki/json-endpoint)
- [Retrieving Links](../../wiki/links-endpoint)
- [Extracting Markdown](../../wiki/markdown-endpoint)

## Basic Usage

### Setup

```php
use Shanginn\CloudflareBrowser\CloudflareClient;
use Shanginn\CloudflareBrowser\CloudflareBrowser;

$accountId = getenv('CLOUDFLARE_ACCOUNT_ID');
$apiToken = getenv('CLOUDFLARE_API_TOKEN');

$client = new CloudflareClient($accountId, $apiToken);
$browser = new CloudflareBrowser($client);
```

### Take a Screenshot

```php
use Shanginn\CloudflareBrowser\Requests\ScreenshotRequest;

$pngData = $browser->screenshot(new ScreenshotRequest(
    url: 'https://example.com',
    screenshotOptions: ['fullPage' => true]
));

file_put_contents('screenshot.png', $pngData);
```

See the [Screenshot Documentation](../../wiki/screenshot-endpoint) for more examples including:
- Custom HTML screenshots
- Authenticated pages
- Full-page captures
- Element-specific screenshots
- High-resolution captures

### Scrape Elements

```php
use Shanginn\CloudflareBrowser\Requests\ScrapeRequest;

$results = $browser->scrape(new ScrapeRequest(
    url: 'https://news.ycombinator.com',
    elements: [
        ['selector' => '.titleline > a']
    ]
));

foreach ($results as $group) {
    foreach ($group->results as $element) {
        echo "Found: {$element->text} ({$element->attributes['href'] ?? ''})\n";
    }
}
```

See the [Scraping Documentation](../../wiki/scrape-endpoint) for more details.

## Advanced Usage

### AI Structured Data Extraction

Define your target data structure using a PHP class and attributes. The SDK will generate the JSON schema and map the AI response back to your object.

**1. Define Schema:**

```php
use Spiral\JsonSchemaGenerator\Attribute\Field;

class ProductSchema 
{
    public function __construct(
        #[Field(title: 'Product Title', description: 'The main name of the item')]
        public string $title,

        #[Field(title: 'Price', description: 'Current price')]
        public float $price,
    ) {}
}
```

**2. Extract:**

```php
use Shanginn\CloudflareBrowser\Requests\JsonRequest;

/** @var ProductSchema $product */
$product = $browser->json(
    new JsonRequest(
        url: 'https://example-shop.com/item/123', 
        prompt: 'Extract the main product details'
    ),
    ProductSchema::class
);

echo "Product: {$product->title} - \${$product->price}\n";
```

See the [JSON Endpoint Documentation](../../wiki/json-endpoint) for more examples including array extraction and custom AI models.

### Generating PDFs

```php
use Shanginn\CloudflareBrowser\Requests\PdfRequest;
use Shanginn\CloudflareBrowser\Requests\Common\Viewport;

$pdfData = $browser->pdf(new PdfRequest(
    url: 'https://example.com',
    viewport: new Viewport(width: 1200, height: 800)
));

file_put_contents('page.pdf', $pdfData);
```

See the [PDF Documentation](../../wiki/pdf-endpoint) for more examples including:
- Custom headers and footers
- Page format options (A4, A5, Letter, etc.)
- Blocking images/resources
- Dynamic placeholders (page numbers, dates, titles)

### Fetch HTML Content

```php
$html = $browser->content('https://example.com');
echo $html;
```

See the [Content Documentation](../../wiki/content-endpoint) for handling JavaScript-heavy pages and blocking resources.

### Extract Markdown

```php
$markdown = $browser->markdown('https://example.com');
echo $markdown;
```

See the [Markdown Documentation](../../wiki/markdown-endpoint) for more details.

### Get All Links

```php
$links = $browser->links('https://example.com');

// Or get only internal links
$internalLinks = $browser->links(
    url: 'https://example.com',
    excludeExternal: true
);
```

See the [Links Documentation](../../wiki/links-endpoint) for more details.

### Take a Snapshot

```php
use Shanginn\CloudflareBrowser\Requests\SnapshotRequest;

$snapshot = $browser->snapshot(new SnapshotRequest(
    url: 'https://example.com'
));

echo "Title: " . $snapshot->title . "\n";
echo "Content: " . $snapshot->content . "\n";
```

See the [Snapshot Documentation](../../wiki/snapshot-endpoint) for more examples.

## Authentication

Before you begin, make sure you [create a custom API Token](https://developers.cloudflare.com/fundamentals/api/get-started/create-token/) with the following permissions:

* `Browser Rendering - Edit`

Set these environment variables:
- `CLOUDFLARE_ACCOUNT_ID` - Your Cloudflare Account ID
- `CLOUDFLARE_API_TOKEN` - Your Cloudflare API Token

## Monitoring Usage

You can monitor Browser Rendering usage in two ways:

* In the Cloudflare dashboard, go to the **Browser Rendering** page to view aggregate metrics. [Go to **Browser Rendering**](https://dash.cloudflare.com/?to=/:account/workers/browser-rendering)
* `X-Browser-Ms-Used` header: Returned in every REST API response, reporting browser time used for that request (in milliseconds).

## Troubleshooting

If you have questions or encounter an error, see the [Browser Rendering FAQ and troubleshooting guide](https://developers.cloudflare.com/browser-rendering/faq/).

## License

MIT
