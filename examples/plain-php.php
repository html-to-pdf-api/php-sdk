<?php

/**
 * Plain PHP example — no framework required.
 *
 *   composer require htmltopdfapi/php-sdk
 *   HTML_TO_PDF_API_KEY=sk_xxx php examples/plain-php.php
 */

require __DIR__.'/../vendor/autoload.php';

use HtmlToPdfApi\Exceptions\AuthenticationException;
use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use HtmlToPdfApi\Exceptions\UsageLimitException;
use HtmlToPdfApi\Exceptions\ValidationException;
use HtmlToPdfApi\HtmlToPdf;

$sdk = new HtmlToPdf([
    'api_key' => getenv('HTML_TO_PDF_API_KEY'),
    // 'base_url' => 'http://html-to-pdf-api.test/api/v1', // for local dev
    // 'timeout'  => 60,
]);

// ── Example 1: render HTML to PDF and save to disk ────────────────────
try {
    $sdk->html('<h1>Hello PHP</h1><p>Generated via the SDK.</p>')
        ->paperSize('a4')
        ->margins(top: 25, bottom: 25)
        ->footer('<div style="font-size:9px;width:100%;text-align:center">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>')
        ->generate()
        ->saveAs(__DIR__.'/output-hello.pdf');

    echo "Saved output-hello.pdf\n";
} catch (ValidationException $e) {
    echo "Validation failed:\n";
    foreach ($e->errors() as $field => $messages) {
        echo "  - {$field}: ".implode(' | ', $messages)."\n";
    }
    exit(1);
} catch (AuthenticationException $e) {
    fwrite(STDERR, "Bad API key: {$e->getMessage()}\n");
    exit(1);
} catch (UsageLimitException $e) {
    fwrite(STDERR, "Plan limit reached: {$e->getMessage()}\n");
    exit(1);
} catch (HtmlToPdfException $e) {
    fwrite(STDERR, "PDF generation failed: {$e->getMessage()}\n");
    exit(1);
}

// ── Example 2: render a URL with a wait condition ─────────────────────
$bytes = $sdk->url('https://example.com')
    ->landscape()
    ->waitUntil('networkidle0')
    ->retina()
    ->generate()
    ->bytes();

file_put_contents(__DIR__.'/output-url.pdf', $bytes);
echo 'Saved output-url.pdf ('.strlen($bytes)." bytes)\n";

// ── Example 3: invoice with custom paper dimensions ───────────────────
$invoiceHtml = <<<'HTML'
<!doctype html><html><head>
<style>body{font-family:sans-serif;padding:20px}h1{color:#333}table{width:100%;border-collapse:collapse}td{padding:4px;border-bottom:1px solid #eee}</style>
</head><body>
  <h1>INVOICE #ACME-001</h1>
  <p>Acme Co. · billing@acme.test</p>
  <table>
    <tr><td>Pro plan (monthly)</td><td style="text-align:right">$29.00</td></tr>
    <tr><td><strong>Total</strong></td><td style="text-align:right"><strong>$29.00</strong></td></tr>
  </table>
</body></html>
HTML;

$sdk->html($invoiceHtml)
    ->paperSize(210, 297) // custom mm dimensions (A4-equivalent)
    ->margin(20)
    ->filename('invoice-acme-001.pdf')
    ->footer('<div style="font-size:8px;color:#888;width:100%;text-align:center">Generated on <span class="date"></span></div>')
    ->generate()
    ->saveAs(__DIR__.'/output-invoice.pdf');

echo "Saved output-invoice.pdf\n";
