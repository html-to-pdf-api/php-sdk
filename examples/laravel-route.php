<?php

/**
 * Laravel example — single-line routes that produce a PDF inline.
 *
 * Drop snippets into routes/web.php or routes/api.php.
 */

use App\Models\Invoice;
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf;
use Illuminate\Support\Facades\Route;

// Simplest possible: render an HTML string.
Route::get('/hello.pdf', function () {
    return HtmlToPdf::html('<h1>Hello from Laravel</h1>')
        ->generate()
        ->toResponse(request());
});

// Render a Blade view.
Route::get('/reports/{year}.pdf', function (string $year) {
    $html = view('reports.annual', ['year' => $year])->render();

    return HtmlToPdf::html($html)
        ->landscape()
        ->margins(top: 15, bottom: 15)
        ->footer('<div style="text-align:center;font-size:9px">Page <span class="pageNumber"></span></div>')
        ->generate()
        ->toResponse(request());
});

// Snapshot a public URL (e.g., a dashboard) as a PDF.
Route::get('/snapshot.pdf', function () {
    return HtmlToPdf::url('https://example.com/dashboard')
        ->waitUntil('networkidle0')
        ->waitForSelector('#chart svg')
        ->viewport(1440, 900)
        ->retina()
        ->generate()
        ->toResponse(request());
});

// Save to disk and return JSON with the path.
Route::post('/invoices/{id}/render', function (int $id) {
    $invoice = Invoice::findOrFail($id);

    $path = storage_path("app/invoices/{$invoice->number}.pdf");

    HtmlToPdf::html(view('invoices.pdf', compact('invoice'))->render())
        ->paperSize('a4')
        ->margin(20)
        ->generate()
        ->saveAs($path);

    return response()->json(['path' => $path]);
});
