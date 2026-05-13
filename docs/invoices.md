# Recipe: invoice PDFs

The most common use-case for this SDK is generating an invoice PDF from a Blade or HTML template. This recipe shows the canonical patterns.

## Laravel

### Render and stream inline

```php
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf;

Route::get('/invoices/{invoice}.pdf', function (Invoice $invoice, Request $request) {
    $html = view('invoices.pdf', ['invoice' => $invoice])->render();

    return HtmlToPdf::html($html)
        ->paperSize('a4')
        ->margins(top: 25, right: 20, bottom: 30, left: 20)
        ->footer(
            '<div style="font-size:9px;width:100%;text-align:center;color:#666">'
            . '<span class="title"></span> · Page <span class="pageNumber"></span>'
            . ' of <span class="totalPages"></span></div>'
        )
        ->waitUntil('networkidle0') // wait for web fonts and images
        ->filename("invoice-{$invoice->number}.pdf")
        ->generate()
        ->toResponse($request);
});
```

### Save to disk and email

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

### Render in a queued job (no special wiring required)

```php
class GenerateInvoicePdfJob implements ShouldQueue
{
    public function __construct(public Invoice $invoice) {}

    public function handle(): void
    {
        $html = view('invoices.pdf', ['invoice' => $this->invoice])->render();

        HtmlToPdf::html($html)
            ->paperSize('a4')
            ->margin(20)
            ->footer($this->footer())
            ->generate()
            ->saveAs(storage_path("app/invoices/{$this->invoice->number}.pdf"));
    }

    private function footer(): string
    {
        return '<div style="font-size:9px;width:100%;text-align:center">Acme Co. · '
            . 'Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>';
    }
}
```

## Plain PHP

```php
$invoiceHtml = file_get_contents(__DIR__ . "/templates/invoice-{$invoice->id}.html");

$sdk->html($invoiceHtml)
    ->paperSize('a4')
    ->margin(20)
    ->footer('<div style="font-size:9px;width:100%;text-align:center">Page <span class="pageNumber"></span></div>')
    ->generate()
    ->saveAs("/var/invoices/{$invoice->id}.pdf");
```

## Page numbering tips

The reserved CSS classes (`.pageNumber`, `.totalPages`, `.date`, `.title`, `.url`) only work **inside** the header/footer HTML. Putting them in your body content does nothing.

```html
<!-- ✅ Works in ->footer(...) -->
<div>Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>

<!-- ❌ Does NOT work in your main HTML body -->
<body>
  <footer>Page <span class="pageNumber"></span></footer>
</body>
```

Use Chromium's footer area (`->footer(...)`), not your body, when you need real page numbers.

## Sizing the body to leave room for header/footer

When you provide `->header(...)` or `->footer(...)`, Chromium reserves vertical space at the top/bottom of every page for it. Your `margin_top` / `margin_bottom` should accommodate that — 25mm is a reasonable starting point for a footer with one line of small text.

Too little margin → footer overlaps content. Too much → wasted space.
