<?php

namespace HtmlToPdfApi\Tests\Feature;

use HtmlToPdfApi\HtmlToPdf;
use HtmlToPdfApi\Laravel\Facades\HtmlToPdf as HtmlToPdfFacade;
use HtmlToPdfApi\PdfBuilder;
use HtmlToPdfApi\Tests\TestCase;
use Illuminate\Support\ServiceProvider;

class LaravelIntegrationTest extends TestCase
{
    public function test_service_provider_registers_singleton(): void
    {
        $first = app(HtmlToPdf::class);
        $second = app(HtmlToPdf::class);

        $this->assertInstanceOf(HtmlToPdf::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_facade_resolves_and_returns_builder(): void
    {
        $builder = HtmlToPdfFacade::html('<h1>Hi</h1>');
        $this->assertInstanceOf(PdfBuilder::class, $builder);
        $this->assertSame('<h1>Hi</h1>', $builder->toPayload()['html']);
    }

    public function test_config_is_published_and_merged(): void
    {
        $this->assertSame('sk_test_orchestra', config('html-to-pdf.api_key'));
        $this->assertNotEmpty(config('html-to-pdf.base_url'));
        $this->assertIsInt(config('html-to-pdf.timeout'));
    }

    public function test_missing_api_key_throws_helpful_error_on_resolve(): void
    {
        config(['html-to-pdf.api_key' => null]);
        // Clear the singleton so it's re-constructed with the null api_key.
        app()->forgetInstance(HtmlToPdf::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTML_TO_PDF_API_KEY');
        app(HtmlToPdf::class);
    }

    public function test_alias_html_to_pdf_resolves_same_singleton(): void
    {
        $byClass = app(HtmlToPdf::class);
        $byAlias = app('html-to-pdf');

        $this->assertSame($byClass, $byAlias);
    }

    public function test_publishes_config_tag_exists(): void
    {
        $tags = ServiceProvider::publishableGroups();
        $this->assertContains('html-to-pdf-config', $tags);
    }
}
