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

## License

MIT
