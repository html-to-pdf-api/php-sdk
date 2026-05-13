<?php

/**
 * Laravel example — invoice controller using the HtmlToPdf facade.
 *
 *   # Install:
 *   composer require htmltopdfapi/php-sdk
 *
 *   # Publish config (optional — defaults work):
 *   php artisan vendor:publish --tag=html-to-pdf-config
 *
 *   # In .env:
 *   HTML_TO_PDF_API_KEY=sk_xxx
 *
 * Drop this in app/Http/Controllers/InvoiceController.php.
 */

namespace App\Http\Controllers;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;
use HtmlToPdfApi\Exceptions\ValidationException;
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * GET /invoices/{invoice}/pdf
     *
     * Renders the invoice's Blade view to HTML, ships it to the API,
     * and streams the PDF back to the browser inline.
     */
    public function show(Request $request, Invoice $invoice)
    {
        $html = view('invoices.pdf', ['invoice' => $invoice])->render();

        return HtmlToPdf::html($html)
            ->paperSize('a4')
            ->margins(top: 25, right: 20, bottom: 25, left: 20)
            ->footer(
                '<div style="font-size:9px;width:100%;text-align:center;color:#666">'
                .'<span class="title"></span> · Page <span class="pageNumber"></span>'
                .' of <span class="totalPages"></span></div>'
            )
            ->waitUntil('networkidle0')   // wait for fonts / images
            ->filename("invoice-{$invoice->number}.pdf")
            ->generate()
            ->toResponse($request);       // returns Illuminate\Http\Response
    }

    /**
     * POST /invoices/{invoice}/email
     *
     * Renders the PDF, writes it to disk, attaches to an email.
     */
    public function email(Invoice $invoice)
    {
        $html = view('invoices.pdf', ['invoice' => $invoice])->render();
        $path = storage_path("app/invoices/{$invoice->number}.pdf");

        try {
            HtmlToPdf::html($html)
                ->paperSize('a4')
                ->margin(20)
                ->footer('<div class="text-center">Page <span class="pageNumber"></span></div>')
                ->generate()
                ->saveAs($path);
        } catch (ValidationException $e) {
            // The API rejected our payload — log the per-field errors, return 422.
            return response()->json(['errors' => $e->errors()], 422);
        }

        \Mail::to($invoice->customer->email)
            ->send(new InvoiceEmail($invoice, $path));

        return response()->noContent();
    }
}
