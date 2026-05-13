<?php

namespace HtmlToPdfApi\Laravel\Facades;

use HtmlToPdfApi\HtmlToPdf as HtmlToPdfClient;
use HtmlToPdfApi\PdfBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PdfBuilder html(string $html)
 * @method static PdfBuilder url(string $url)
 * @method static PdfBuilder builder()
 * @method static \HtmlToPdfApi\Client client()
 *
 * @see HtmlToPdfClient
 */
class HtmlToPdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HtmlToPdfClient::class;
    }
}
