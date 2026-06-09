# Installation

## Requirements

- PHP 8.1 or higher
- Composer
- A HTML to PDF API key (free tier available at [htmltopdfapi.co](https://htmltopdfapi.co))

## Install via Composer

```bash
composer require htmltopdfapi/php-sdk
```

## Laravel

The service provider and facade are auto-discovered. Just set your API key in `.env`:

```bash
HTML_TO_PDF_API_KEY=sk_xxxxxxxxxxxxxx
HTML_TO_PDF_BASE_URL=https://platform.htmltopdfapi.co/api/v1   # optional, default shown
HTML_TO_PDF_TIMEOUT=60                                    # optional, default shown
```

Optionally publish the config file if you want to override programmatically:

```bash
php artisan vendor:publish --tag=html-to-pdf-config
```

You can now use the `HtmlToPdf` facade anywhere in your app:

```php
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf;

$pdf = HtmlToPdf::html('<h1>Hi</h1>')->generate();
```

## Plain PHP

```php
require 'vendor/autoload.php';

use HtmlToPdfApi\HtmlToPdf;

$sdk = new HtmlToPdf([
    'api_key'  => getenv('HTML_TO_PDF_API_KEY'),
    'base_url' => 'https://platform.htmltopdfapi.co/api/v1',
    'timeout'  => 60,
]);

$pdf = $sdk->html('<h1>Hi</h1>')->generate();
```

## Verify install

```php
$pdf = HtmlToPdf::html('<h1>SDK works</h1>')->generate();
file_put_contents('test.pdf', $pdf->bytes());
echo "Wrote " . $pdf->size() . " bytes\n";
```

If you see a valid PDF written to disk, you're set.

## Troubleshooting

**`HtmlToPdf API key is required`** — `HTML_TO_PDF_API_KEY` isn't set, or your config returns null. Check `.env`, then `php artisan config:clear` if you're caching config.

**`AuthenticationException: Invalid API credentials`** — your key is set but the API rejects it. Verify in the dashboard that the key is active and not revoked.

**`ConnectionException: Could not reach the HTML to PDF API`** — DNS or network issue. If running against a local API (`base_url` pointing to a `.test` domain), confirm Herd/Valet has it serving.
