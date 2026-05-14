# HTML to PDF API — PHP / Laravel SDK

[![CI](https://github.com/html-to-pdf-api/php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/html-to-pdf-api/php-sdk/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](composer.json)

Official PHP SDK for the [HTML to PDF API](https://htmltopdfapi.co). One package — works in any PHP 8.1+ project and ships with first-class Laravel integration (service provider, facade, publishable config).

```php
HtmlToPdf::html('<h1>Invoice #001</h1>...')
    ->paperSize('a4')
    ->margins(top: 25, bottom: 25)
    ->footer('<div class="text-center">Page <span class="pageNumber"></span></div>')
    ->generate()
    ->saveAs(storage_path('invoices/001.pdf'));
```

---

## Why this SDK

- **Fluent builder, one method per API field** — `->margins(...)`, `->waitForSelector(...)`, `->retina()` — discoverable in any IDE
- **Typed exceptions** — `ValidationException::errors()` maps the API's per-field errors so they slot into existing form-error flows
- **Client-side validation** — obvious typos fail with `\InvalidArgumentException` *before* the HTTP round-trip
- **Laravel auto-discovery** — `composer require` and you're done; facade, service provider, and config are wired automatically
- **Zero hidden state** — no globals, no static configuration; everything is injected through the constructor

---

## Compatibility

| | Supported |
|---|---|
| **PHP** | 8.1, 8.2, 8.3, 8.4 |
| **Laravel** | 8.x, 9.x, 10.x, 11.x, 12.x, 13.x |
| **Required extensions** | `ext-curl`, `ext-json` (both ship with PHP by default) |

The full Laravel × PHP test matrix lives in [`.github/workflows/ci.yml`](.github/workflows/ci.yml) — every combination below runs on every PR:

| Laravel | PHP versions tested |
|---|---|
| 8.x  | 8.1 |
| 9.x  | 8.1 |
| 10.x | 8.1, 8.2 |
| 11.x | 8.2, 8.3 |
| 12.x | 8.2, 8.3, 8.4 |
| 13.x | 8.3, 8.4 |

> **Laravel < 8 (i.e. 6.x / 7.x):** not tested. The SDK has no Laravel-version-specific code and only touches stable `ServiceProvider` / `Facade` APIs, so it *may* work — but you'd also need PHP 8.1+ (which Laravel 6/7 don't officially support), and we don't run CI against it. Use at your own risk.

The SDK does **not** require Laravel — `composer require htmltopdfapi/php-sdk` in a vanilla PHP project works fine.

---

## Install

> **Note**: this package isn't on Packagist yet. Install from GitHub via a VCS repository in your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/html-to-pdf-api/php-sdk"
    }
  ],
  "require": {
    "htmltopdfapi/php-sdk": "dev-main"
  }
}
```

Then:

```bash
composer update htmltopdfapi/php-sdk
```

Once published to Packagist this becomes the standard one-liner:

```bash
composer require htmltopdfapi/php-sdk
```

### Set your API key

Create one in the [dashboard](https://htmltopdfapi.co) and add to your `.env` (Laravel) or environment (plain PHP):

```bash
HTML_TO_PDF_API_KEY=sk_live_xxxxxxxxxxxxxxxx
```

---

## Configuration reference

| Env var | Config key | Default | Notes |
|---|---|---|---|
| `HTML_TO_PDF_API_KEY` | `api_key` | — | **Required.** Bearer token for the API. |
| `HTML_TO_PDF_BASE_URL` | `base_url` | `https://api.htmltopdfapi.co/api/v1` | Override for staging / local dev. |
| `HTML_TO_PDF_TIMEOUT` | `timeout` | `60` | Seconds before the HTTP client gives up on a single render. |

### Laravel

The service provider auto-discovers; nothing else to do. Optionally publish the config to customise programmatically:

```bash
php artisan vendor:publish --tag=html-to-pdf-config
```

### Plain PHP

```php
use HtmlToPdfApi\HtmlToPdf;

$sdk = new HtmlToPdf([
    'api_key'  => getenv('HTML_TO_PDF_API_KEY'),
    'base_url' => 'https://api.htmltopdfapi.co/api/v1', // optional
    'timeout'  => 60,                                    // optional
]);
```

---

## Quick start

### Laravel — Blade view to inline PDF response

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

### Plain PHP — URL to file on disk

```php
$sdk->url('https://example.com/dashboard')
    ->landscape()
    ->waitUntil('networkidle0')
    ->retina()
    ->generate()
    ->saveAs('/tmp/dashboard.pdf');
```

### Save to disk and attach to an email (Laravel)

```php
$path = storage_path("app/invoices/{$invoice->number}.pdf");

HtmlToPdf::html(view('invoices.pdf', compact('invoice'))->render())
    ->paperSize('a4')
    ->margin(20)
    ->generate()
    ->saveAs($path);

Mail::to($invoice->customer->email)
    ->send(new InvoiceEmail($invoice, $path));
```

More recipes: [`examples/`](examples/) + [`docs/`](docs/).

---

## Cookbook

| Task | Recipe |
|---|---|
| Install + first PDF | [`docs/installation.md`](docs/installation.md) |
| Invoice with page numbering | [`docs/invoices.md`](docs/invoices.md) |
| Handle each error type | [`docs/error-handling.md`](docs/error-handling.md) |
| Vanilla PHP end-to-end | [`examples/plain-php.php`](examples/plain-php.php) |
| Laravel controller pattern | [`examples/laravel-controller.php`](examples/laravel-controller.php) |
| Laravel route closures | [`examples/laravel-route.php`](examples/laravel-route.php) |

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

Every method returns `$this` for chaining and validates input client-side.

| Group | Methods |
|---|---|
| **Source** | `html(string)`, `url(string)` (mutually exclusive) |
| **Output** | `filename(string)`, `download(bool=true)`, `inline(bool=true)` |
| **Paper** | `paperSize(string)` for `a4`/`a3`/`a5`/`letter`/`legal`; `paperSize(int $w, int $h)` for custom mm dimensions |
| **Orientation** | `portrait()`, `landscape()`, `orientation(string)` |
| **Margins** | `margins(top:, right:, bottom:, left:)`, `margin(int $all)`, `marginX(int)`, `marginY(int)` |
| **Header / footer** | `header(string $html)`, `footer(string $html)`, `withoutHeaderFooter()` |
| **Viewport** | `viewport(int $w, int $h)`, `deviceScaleFactor(1\|2\|3)`, `retina()` |
| **Wait conditions** | `delay(int $ms)`, `timeout(int $s)`, `waitUntil('load'\|'domcontentloaded'\|'networkidle0'\|'networkidle2')`, `waitForSelector(string)` |
| **Page styling** | `cssMedia('screen'\|'print')`, `hideBackground()`, `transparentBackground()`, `printBackground(bool)` |
| **Execute** | `generate(): PdfResponse` |

**Reserved CSS classes** inside `header(...)` / `footer(...)` are substituted by Chromium at render time:

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
$pdf->bytes();              // string   — raw PDF bytes
$pdf->base64();             // string   — base64-encoded (inline embed)
$pdf->size();               // int      — byte length
$pdf->filename();           // string   — parsed from Content-Disposition, or null
$pdf->saveAs(string $path); // bool     — writes to disk; throws on failure
$pdf->stream();             // resource — caller must fclose()
$pdf->headers();            // array    — raw response headers
$pdf->toResponse($request); // Illuminate\Http\Response (Laravel only)
```

---

## Error handling

Every SDK exception extends `HtmlToPdfApi\Exceptions\HtmlToPdfException`:

```
HtmlToPdfException
├── ConnectionException       network / DNS / TLS / timeout — usually retryable
├── AuthenticationException   401 — bad / missing API key
├── ValidationException       422 — exposes ->errors(): array<string, string[]>
├── UsageLimitException       429 — plan quota
└── ServerException           5xx — usually retryable with backoff
```

```php
use HtmlToPdfApi\Exceptions\ValidationException;

try {
    $pdf = HtmlToPdf::html('...')->margins(top: 999)->generate();
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $messages) {
        report("[{$field}] " . $messages[0]);
    }
}
```

Full pattern reference: [`docs/error-handling.md`](docs/error-handling.md).

---

## Testing

### Running the SDK's own tests

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/pint --test
```

### Faking the SDK in consumer tests

A built-in `HtmlToPdf::fake()` helper is planned for 0.2. Until then, mock the `Client` and bind it through the container:

```php
$client = Mockery::mock(\HtmlToPdfApi\Client::class);
$client->shouldReceive('generatePdf')->andReturn(new \HtmlToPdfApi\PdfResponse('%PDF-fake'));

$this->app->instance(\HtmlToPdfApi\HtmlToPdf::class, new \HtmlToPdfApi\HtmlToPdf(
    new \HtmlToPdfApi\Config(['api_key' => 'sk_test']),
    $client,
));
```

---

## Versioning

This SDK follows [Semantic Versioning](https://semver.org/). The public surface — builder method signatures, exception classes, `PdfResponse` methods — won't break in patch or minor releases. Pre-1.0 versions may introduce changes; pin to `^0.1` to be safe.

The SDK version is exposed at `\HtmlToPdfApi\HtmlToPdf::VERSION` and is sent in the User-Agent header on every API call.

---

## Roadmap

Things planned for upcoming releases (in rough order):

- [ ] Screenshot endpoint binding (PNG / JPEG / WebP) — when the API ships it
- [ ] `HtmlToPdf::fake()` test helper
- [ ] Rendered-HTML endpoint binding
- [ ] Async generation + webhook receiver helper
- [ ] PSR-3 logger integration
- [ ] PHPStan / Larastan static analysis

---

## Contributing

Bug reports and PRs welcome. Before sending a PR:

```bash
./vendor/bin/phpunit
./vendor/bin/pint
```

Both should pass.

---

## License

MIT. See [LICENSE](LICENSE).
