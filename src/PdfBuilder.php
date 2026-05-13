<?php

namespace HtmlToPdfApi;

/**
 * Fluent builder for `POST /api/v1/pdf/generate`. One method per API field,
 * plus a handful of syntactic-sugar shortcuts (margin/marginX/landscape/retina).
 *
 * Builder methods perform cheap client-side validation so obvious typos
 * fail with InvalidArgumentException before the HTTP round-trip.
 */
class PdfBuilder
{
    private const VALID_PAPER_SIZES = ['a3', 'a4', 'a5', 'letter', 'legal'];

    private const VALID_ORIENTATIONS = ['portrait', 'landscape'];

    private const VALID_WAIT_UNTIL = ['load', 'domcontentloaded', 'networkidle0', 'networkidle2'];

    private const VALID_CSS_MEDIA = ['screen', 'print'];

    /** @var array<string, mixed> */
    private array $payload = [];

    public function __construct(private readonly Client $client) {}

    // ── Source ──────────────────────────────────────────────────────────

    public function html(string $html): static
    {
        unset($this->payload['url']);
        $this->payload['html'] = $html;

        return $this;
    }

    public function url(string $url): static
    {
        unset($this->payload['html']);
        $this->payload['url'] = $url;

        return $this;
    }

    // ── Output ──────────────────────────────────────────────────────────

    public function filename(string $name): static
    {
        $this->payload['filename'] = $name;

        return $this;
    }

    public function download(bool $download = true): static
    {
        $this->payload['download'] = $download;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->payload['download'] = ! $inline;

        return $this;
    }

    // ── Paper ───────────────────────────────────────────────────────────

    /**
     * One-arg form: a named size (`a4`, `letter`, …).
     * Two-arg form: custom dimensions in millimetres.
     */
    public function paperSize(string|int $sizeOrWidth, ?int $height = null): static
    {
        if ($height !== null) {
            // Custom dimensions
            $this->assertPositiveInt('paper_width', $sizeOrWidth);
            $this->assertPositiveInt('paper_height', $height);
            unset($this->payload['paper_size']);
            $this->payload['paper_width'] = (int) $sizeOrWidth;
            $this->payload['paper_height'] = $height;

            return $this;
        }

        if (! is_string($sizeOrWidth) || ! in_array($sizeOrWidth, self::VALID_PAPER_SIZES, true)) {
            throw new \InvalidArgumentException(
                "Invalid paper size '{$sizeOrWidth}'. Use one of: ".implode(', ', self::VALID_PAPER_SIZES)
                .', or pass two integers for custom width/height in mm.'
            );
        }

        unset($this->payload['paper_width'], $this->payload['paper_height']);
        $this->payload['paper_size'] = $sizeOrWidth;

        return $this;
    }

    public function orientation(string $orientation): static
    {
        if (! in_array($orientation, self::VALID_ORIENTATIONS, true)) {
            throw new \InvalidArgumentException("Invalid orientation '{$orientation}'. Use 'portrait' or 'landscape'.");
        }

        $this->payload['orientation'] = $orientation;

        return $this;
    }

    public function portrait(): static
    {
        return $this->orientation('portrait');
    }

    public function landscape(): static
    {
        return $this->orientation('landscape');
    }

    // ── Margins ─────────────────────────────────────────────────────────

    public function margins(?int $top = null, ?int $right = null, ?int $bottom = null, ?int $left = null): static
    {
        if ($top !== null) {
            $this->payload['margin_top'] = $this->assertMargin('margin_top', $top);
        }
        if ($right !== null) {
            $this->payload['margin_right'] = $this->assertMargin('margin_right', $right);
        }
        if ($bottom !== null) {
            $this->payload['margin_bottom'] = $this->assertMargin('margin_bottom', $bottom);
        }
        if ($left !== null) {
            $this->payload['margin_left'] = $this->assertMargin('margin_left', $left);
        }

        return $this;
    }

    public function margin(int $all): static
    {
        return $this->margins($all, $all, $all, $all);
    }

    public function marginX(int $leftAndRight): static
    {
        return $this->margins(right: $leftAndRight, left: $leftAndRight);
    }

    public function marginY(int $topAndBottom): static
    {
        return $this->margins(top: $topAndBottom, bottom: $topAndBottom);
    }

    // ── Header / footer ────────────────────────────────────────────────

    public function header(string $html): static
    {
        $this->payload['header_html'] = $html;
        $this->payload['show_header_footer'] = true;

        return $this;
    }

    public function footer(string $html): static
    {
        $this->payload['footer_html'] = $html;
        $this->payload['show_header_footer'] = true;

        return $this;
    }

    public function withoutHeaderFooter(): static
    {
        $this->payload['show_header_footer'] = false;

        return $this;
    }

    // ── Viewport ────────────────────────────────────────────────────────

    public function viewport(int $width, int $height): static
    {
        $this->assertPositiveInt('viewport_width', $width);
        $this->assertPositiveInt('viewport_height', $height);
        $this->payload['viewport_width'] = $width;
        $this->payload['viewport_height'] = $height;

        return $this;
    }

    public function deviceScaleFactor(int $factor): static
    {
        if (! in_array($factor, [1, 2, 3], true)) {
            throw new \InvalidArgumentException("device_scale_factor must be 1, 2, or 3 (got {$factor}).");
        }

        $this->payload['device_scale_factor'] = $factor;

        return $this;
    }

    public function retina(): static
    {
        return $this->deviceScaleFactor(2);
    }

    // ── Wait conditions ─────────────────────────────────────────────────

    public function delay(int $milliseconds): static
    {
        if ($milliseconds < 0 || $milliseconds > 30000) {
            throw new \InvalidArgumentException("delay must be between 0 and 30000 ms (got {$milliseconds}).");
        }

        $this->payload['delay'] = $milliseconds;

        return $this;
    }

    public function timeout(int $seconds): static
    {
        if ($seconds < 1 || $seconds > 120) {
            throw new \InvalidArgumentException("timeout must be between 1 and 120 seconds (got {$seconds}).");
        }

        $this->payload['timeout'] = $seconds;

        return $this;
    }

    public function waitUntil(string $event): static
    {
        if (! in_array($event, self::VALID_WAIT_UNTIL, true)) {
            throw new \InvalidArgumentException(
                "Invalid wait_until '{$event}'. Use one of: ".implode(', ', self::VALID_WAIT_UNTIL)
            );
        }

        $this->payload['wait_until'] = $event;

        return $this;
    }

    public function waitForSelector(string $selector): static
    {
        if (preg_match('/[<>"\r\n]/', $selector)) {
            throw new \InvalidArgumentException(
                'wait_for_selector must be a CSS selector (no angle brackets, quotes, or newlines).'
            );
        }
        if (strlen($selector) > 256) {
            throw new \InvalidArgumentException('wait_for_selector cannot exceed 256 characters.');
        }

        $this->payload['wait_for_selector'] = $selector;

        return $this;
    }

    // ── Page styling ────────────────────────────────────────────────────

    public function cssMedia(string $type): static
    {
        if (! in_array($type, self::VALID_CSS_MEDIA, true)) {
            throw new \InvalidArgumentException("css_media_type must be 'screen' or 'print' (got '{$type}').");
        }

        $this->payload['css_media_type'] = $type;

        return $this;
    }

    public function hideBackground(bool $hide = true): static
    {
        $this->payload['hide_background'] = $hide;

        return $this;
    }

    public function transparentBackground(bool $transparent = true): static
    {
        $this->payload['transparent_background'] = $transparent;

        return $this;
    }

    public function printBackground(bool $print): static
    {
        $this->payload['print_background'] = $print;

        return $this;
    }

    // ── Execute ─────────────────────────────────────────────────────────

    public function generate(): PdfResponse
    {
        if (! isset($this->payload['html']) && ! isset($this->payload['url'])) {
            throw new \InvalidArgumentException(
                'Call ->html(...) or ->url(...) before ->generate().'
            );
        }

        return $this->client->generatePdf($this->payload);
    }

    /**
     * Inspect the JSON payload that would be sent. Useful for tests and
     * debugging; not normally needed in production code.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->payload;
    }

    // ── Internal helpers ────────────────────────────────────────────────

    private function assertMargin(string $field, int $value): int
    {
        if ($value < 0 || $value > 250) {
            throw new \InvalidArgumentException("{$field} must be between 0 and 250 mm (got {$value}).");
        }

        return $value;
    }

    private function assertPositiveInt(string $field, int|string $value): void
    {
        if (! is_int($value) || $value < 1) {
            throw new \InvalidArgumentException("{$field} must be a positive integer (got {$value}).");
        }
    }
}
