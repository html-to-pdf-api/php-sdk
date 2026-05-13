<?php

namespace HtmlToPdfApi\Tests\Unit;

use HtmlToPdfApi\Client;
use HtmlToPdfApi\Config;
use HtmlToPdfApi\PdfBuilder;
use PHPUnit\Framework\TestCase;

class PdfBuilderTest extends TestCase
{
    private function builder(): PdfBuilder
    {
        $client = new Client(new Config(['api_key' => 'sk_test']));

        return new PdfBuilder($client);
    }

    // ── Source ──────────────────────────────────────────────────────────

    public function test_html_sets_html_field(): void
    {
        $payload = $this->builder()->html('<h1>Hi</h1>')->toPayload();
        $this->assertSame('<h1>Hi</h1>', $payload['html']);
    }

    public function test_url_sets_url_field(): void
    {
        $payload = $this->builder()->url('https://example.com')->toPayload();
        $this->assertSame('https://example.com', $payload['url']);
    }

    public function test_html_and_url_are_mutually_exclusive(): void
    {
        $payload = $this->builder()->html('<h1>x</h1>')->url('https://example.com')->toPayload();
        $this->assertArrayNotHasKey('html', $payload);
        $this->assertSame('https://example.com', $payload['url']);
    }

    // ── Output ──────────────────────────────────────────────────────────

    public function test_filename_sets_filename(): void
    {
        $payload = $this->builder()->filename('invoice.pdf')->toPayload();
        $this->assertSame('invoice.pdf', $payload['filename']);
    }

    public function test_download_and_inline_are_complementary(): void
    {
        $this->assertTrue($this->builder()->download()->toPayload()['download']);
        $this->assertFalse($this->builder()->inline()->toPayload()['download']);
    }

    // ── Paper ───────────────────────────────────────────────────────────

    public function test_paper_size_named(): void
    {
        $payload = $this->builder()->paperSize('letter')->toPayload();
        $this->assertSame('letter', $payload['paper_size']);
    }

    public function test_paper_size_custom_dimensions(): void
    {
        $payload = $this->builder()->paperSize(210, 297)->toPayload();
        $this->assertSame(210, $payload['paper_width']);
        $this->assertSame(297, $payload['paper_height']);
        $this->assertArrayNotHasKey('paper_size', $payload);
    }

    public function test_paper_size_named_clears_custom_dimensions(): void
    {
        $payload = $this->builder()->paperSize(200, 280)->paperSize('a4')->toPayload();
        $this->assertSame('a4', $payload['paper_size']);
        $this->assertArrayNotHasKey('paper_width', $payload);
        $this->assertArrayNotHasKey('paper_height', $payload);
    }

    public function test_paper_size_rejects_unknown_named_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->paperSize('a99');
    }

    public function test_orientation_shortcuts(): void
    {
        $this->assertSame('portrait', $this->builder()->portrait()->toPayload()['orientation']);
        $this->assertSame('landscape', $this->builder()->landscape()->toPayload()['orientation']);
    }

    public function test_orientation_rejects_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->orientation('sideways');
    }

    // ── Margins ─────────────────────────────────────────────────────────

    public function test_margins_named_arguments(): void
    {
        $payload = $this->builder()->margins(top: 5, right: 10, bottom: 15, left: 20)->toPayload();
        $this->assertSame(5, $payload['margin_top']);
        $this->assertSame(10, $payload['margin_right']);
        $this->assertSame(15, $payload['margin_bottom']);
        $this->assertSame(20, $payload['margin_left']);
    }

    public function test_margin_all_sides(): void
    {
        $payload = $this->builder()->margin(25)->toPayload();
        $this->assertSame(25, $payload['margin_top']);
        $this->assertSame(25, $payload['margin_right']);
        $this->assertSame(25, $payload['margin_bottom']);
        $this->assertSame(25, $payload['margin_left']);
    }

    public function test_margin_x_and_y(): void
    {
        $payload = $this->builder()->marginX(10)->marginY(30)->toPayload();
        $this->assertSame(30, $payload['margin_top']);
        $this->assertSame(10, $payload['margin_right']);
        $this->assertSame(30, $payload['margin_bottom']);
        $this->assertSame(10, $payload['margin_left']);
    }

    public function test_margin_out_of_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->margin(500);
    }

    public function test_margin_negative_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->margins(top: -1);
    }

    // ── Header / footer ────────────────────────────────────────────────

    public function test_header_sets_header_html_and_enables_chrome(): void
    {
        $payload = $this->builder()->header('<div>H</div>')->toPayload();
        $this->assertSame('<div>H</div>', $payload['header_html']);
        $this->assertTrue($payload['show_header_footer']);
    }

    public function test_footer_sets_footer_html_and_enables_chrome(): void
    {
        $payload = $this->builder()->footer('<div>F</div>')->toPayload();
        $this->assertSame('<div>F</div>', $payload['footer_html']);
        $this->assertTrue($payload['show_header_footer']);
    }

    public function test_without_header_footer_disables_chrome(): void
    {
        $payload = $this->builder()->header('<div>H</div>')->withoutHeaderFooter()->toPayload();
        $this->assertFalse($payload['show_header_footer']);
    }

    // ── Viewport ────────────────────────────────────────────────────────

    public function test_viewport_sets_dimensions(): void
    {
        $payload = $this->builder()->viewport(1280, 800)->toPayload();
        $this->assertSame(1280, $payload['viewport_width']);
        $this->assertSame(800, $payload['viewport_height']);
    }

    public function test_device_scale_factor_accepts_1_2_3(): void
    {
        $this->assertSame(1, $this->builder()->deviceScaleFactor(1)->toPayload()['device_scale_factor']);
        $this->assertSame(2, $this->builder()->deviceScaleFactor(2)->toPayload()['device_scale_factor']);
        $this->assertSame(3, $this->builder()->deviceScaleFactor(3)->toPayload()['device_scale_factor']);
    }

    public function test_device_scale_factor_rejects_other_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->deviceScaleFactor(4);
    }

    public function test_retina_shortcut_sets_scale_2(): void
    {
        $this->assertSame(2, $this->builder()->retina()->toPayload()['device_scale_factor']);
    }

    // ── Wait conditions ─────────────────────────────────────────────────

    public function test_delay_within_range(): void
    {
        $this->assertSame(500, $this->builder()->delay(500)->toPayload()['delay']);
    }

    public function test_delay_out_of_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->delay(99999);
    }

    public function test_timeout_within_range(): void
    {
        $this->assertSame(30, $this->builder()->timeout(30)->toPayload()['timeout']);
    }

    public function test_timeout_out_of_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->timeout(0);
    }

    public function test_wait_until_accepts_known_values(): void
    {
        foreach (['load', 'domcontentloaded', 'networkidle0', 'networkidle2'] as $event) {
            $this->assertSame($event, $this->builder()->waitUntil($event)->toPayload()['wait_until']);
        }
    }

    public function test_wait_until_rejects_unknown_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->waitUntil('nonsense');
    }

    public function test_wait_for_selector_accepts_simple_css(): void
    {
        $payload = $this->builder()->waitForSelector('#chart svg')->toPayload();
        $this->assertSame('#chart svg', $payload['wait_for_selector']);
    }

    public function test_wait_for_selector_rejects_unsafe_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->waitForSelector('<script>alert(1)</script>');
    }

    public function test_wait_for_selector_length_capped_at_256(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->waitForSelector(str_repeat('a', 257));
    }

    // ── Page styling ────────────────────────────────────────────────────

    public function test_css_media_screen_or_print(): void
    {
        $this->assertSame('screen', $this->builder()->cssMedia('screen')->toPayload()['css_media_type']);
        $this->assertSame('print', $this->builder()->cssMedia('print')->toPayload()['css_media_type']);
    }

    public function test_css_media_rejects_other_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->cssMedia('projection');
    }

    public function test_background_toggles(): void
    {
        $payload = $this->builder()
            ->hideBackground()
            ->transparentBackground()
            ->printBackground(false)
            ->toPayload();
        $this->assertTrue($payload['hide_background']);
        $this->assertTrue($payload['transparent_background']);
        $this->assertFalse($payload['print_background']);
    }

    // ── Execute guard ───────────────────────────────────────────────────

    public function test_generate_requires_html_or_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('->html(...) or ->url(...)');
        $this->builder()->generate();
    }

    public function test_method_chaining_returns_self(): void
    {
        $b = $this->builder();
        $this->assertSame($b, $b->html('<h1>x</h1>'));
        $this->assertSame($b, $b->landscape());
        $this->assertSame($b, $b->margin(20));
    }
}
