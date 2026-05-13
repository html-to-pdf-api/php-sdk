# HTML to PDF API — PHP / Laravel SDK

Official PHP SDK for the [HTML to PDF API](https://htmltopdfapi.co). Works in any PHP 8.1+ project and ships with a Laravel service provider, facade, and config.

```php
HtmlToPdf::html('<h1>Invoice #001</h1>...')
    ->paperSize('a4')
    ->margins(top: 25, bottom: 25)
    ->footer('<div class="text-center">Page <span class="pageNumber"></span></div>')
    ->generate()
    ->saveAs(storage_path('invoices/001.pdf'));
```

---

## Install

```bash
composer require htmltopdfapi/php-sdk
```

Set your API key (created in the [dashboard](https://htmltopdfapi.co)) — either via environment variable (recommended) or passed inline:

```bash
HTML_TO_PDF_API_KEY=sk_xxx
```

### Laravel (auto-discovered)

No manual setup needed. The service provider is registered automatically. Optional config publish:

```bash
php artisan vendor:publish --tag=html-to-pdf-config
```

This drops `config/html-to-pdf.php`:

```php
return [
    'api_key'  => env('HTML_TO_PDF_API_KEY'),
    'base_url' => env('HTML_TO_PDF_BASE_URL', 'https://api.htmltopdfapi.co/api/v1'),
    'timeout'  => env('HTML_TO_PDF_TIMEOUT', 60),
];
```

### Plain PHP

```php
use HtmlToPdfApi\HtmlToPdf;

$sdk = new HtmlToPdf([
    'api_key' => getenv('HTML_TO_PDF_API_KEY'),
]);
```

---

## Quick start

### Laravel — Blade view → PDF response

```php
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf;

Route::get('/invoices/{invoice}.pdf', function (Invoice $invoice, Request $request) {
    $html = view('invoices.pdf', compact('invoice'))->render();

    return HtmlToPdf::html($html)
        ->paperSize('a4')
        ->margin(20)
        ->footer('<div style="text-align:center;font-size:9px">Page <span class="pageNumber"></span></div>')
        ->generate()
        ->toResponse($request);
});
```

### Plain PHP — URL → file on disk

```php
$sdk->url('https://example.com/dashboard')
    ->landscape()
    ->waitUntil('networkidle0')
    ->retina()
    ->generate()
    ->saveAs('/tmp/dashboard.pdf');
```

More: see [`examples/`](examples/).

---

## API reference

### Entry point

```php
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf; // Laravel
// or
use HtmlToPdfApi\HtmlToPdf;                 // Plain PHP
$sdk = new HtmlToPdf(['api_key' => '...']);
```

### Builder

All methods return `$this` for chaining and validate input client-side.

| Group | Methods |
|---|---|
| **Source** | `html(string)`, `url(string)` (mutually exclusive) |
| **Output** | `filename(string)`, `download(bool=true)`, `inline(bool=true)` |
| **Paper** | `paperSize(string)` for `a4`/`a3`/`a5`/`letter`/`legal`; `paperSize(int $w, int $h)` for custom mm |
| **Orientation** | `portrait()`, `landscape()`, `orientation(string)` |
| **Margins** | `margins(top:, right:, bottom:, left:)`, `margin(int $all)`, `marginX(int)`, `marginY(int)` |
| **Header / footer** | `header(string $html)`, `footer(string $html)`, `withoutHeaderFooter()` |
| **Viewport** | `viewport(int $w, int $h)`, `deviceScaleFactor(1\|2\|3)`, `retina()` |
| **Wait conditions** | `delay(int $ms)`, `timeout(int $s)`, `waitUntil('load'\|'domcontentloaded'\|'networkidle0'\|'networkidle2')`, `waitForSelector(string)` |
| **Page styling** | `cssMedia('screen'\|'print')`, `hideBackground()`, `transparentBackground()`, `printBackground(bool)` |
| **Execute** | `generate(): PdfResponse` |

**Reserved CSS classes** inside `header(...)` / `footer(...)` are substituted by Chromium:

| Class | Renders |
|---|---|
| `.date` | Date of render |
| `.title` | Document title |
| `.url` | Page URL |
| `.pageNumber` | Current page number |
| `.totalPages` | Total page count |

### `PdfResponse`

Returned by `generate()`:

```php
$pdf->bytes();              // string — raw PDF bytes
$pdf->base64();             // string — base64-encoded (for inline embedding)
$pdf->size();               // int    — byte length
$pdf->filename();           // string — parsed from Content-Disposition
$pdf->saveAs(string $path); // bool   — writes to disk; throws on failure
$pdf->stream();             // resource
$pdf->toResponse($request); // Illuminate\Http\Response (Laravel only)
```

---

## Error handling

Every SDK exception extends `HtmlToPdfApi\Exceptions\HtmlToPdfException`:

```
HtmlToPdfException
├── ConnectionException       network / DNS / timeout
├── AuthenticationException   401 — bad / missing API key
├── ValidationException       422 — exposes ->errors(): array<string, string[]>
├── UsageLimitException       429 — plan quota
└── ServerException           5xx
```

```php
try {
    $pdf = HtmlToPdf::html('...')->margins(top: 999)->generate();
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $messages) {
        report("[$field] " . $messages[0]);
    }
}
```

Full example: [`examples/error-handling.php`](examples/error-handling.php).

---

## Testing

Run the SDK's own test suite:

```bash
composer install
./vendor/bin/phpunit
```

### Faking the SDK in consumer tests

This SDK doesn't ship a `HtmlToPdf::fake()` helper yet (planned for 0.2). For now, mock the underlying `Client` in your tests:

```php
$client = Mockery::mock(\HtmlToPdfApi\Client::class);
$client->shouldReceive('generatePdf')->andReturn(new \HtmlToPdfApi\PdfResponse('%PDF-fake'));

$this->app->instance(\HtmlToPdfApi\HtmlToPdf::class, new \HtmlToPdfApi\HtmlToPdf(
    new \HtmlToPdfApi\Config(['api_key' => 'sk_test']),
    $client,
));
```

---

## License

MIT. See [LICENSE](LICENSE).
