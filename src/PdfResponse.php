<?php

namespace HtmlToPdfApi;

use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use Illuminate\Http\Response;

/**
 * Wraps a successful PDF generation response: the raw bytes plus the
 * headers the API returned. Exposes ergonomic helpers (saveAs, base64,
 * toResponse) so consumers don't have to think about the underlying HTTP.
 */
final class PdfResponse
{
    /**
     * @param  array<string, string|string[]>  $headers
     */
    public function __construct(
        private readonly string $bytes,
        private readonly array $headers = [],
    ) {}

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function base64(): string
    {
        return base64_encode($this->bytes);
    }

    public function size(): int
    {
        return strlen($this->bytes);
    }

    /**
     * Suggested filename parsed from Content-Disposition. Returns null when
     * the server didn't supply one.
     */
    public function filename(): ?string
    {
        $disposition = $this->headerLine('Content-Disposition');
        if ($disposition === null) {
            return null;
        }

        if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }

    /**
     * Write the PDF bytes to disk. Throws if the path isn't writable.
     */
    public function saveAs(string $path): bool
    {
        $bytesWritten = @file_put_contents($path, $this->bytes);

        if ($bytesWritten === false) {
            throw new HtmlToPdfException("Failed to write PDF to {$path}.");
        }

        return true;
    }

    /**
     * Return the PDF as a stream resource (useful for chunked uploads).
     *
     * @return resource
     */
    public function stream()
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->bytes);
        rewind($stream);

        return $stream;
    }

    /**
     * Build a Laravel HTTP response carrying the PDF inline. Only works
     * when the Illuminate\Http\Response class is available (i.e. inside
     * a Laravel app); the SDK doesn't require Laravel as a hard dependency.
     */
    public function toResponse(mixed $request = null): mixed
    {
        if (! class_exists(Response::class)) {
            throw new HtmlToPdfException(
                'toResponse() requires Laravel. Use bytes(), saveAs(), or stream() in plain PHP contexts.'
            );
        }

        $filename = $this->filename() ?? 'document.pdf';

        return new Response($this->bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) $this->size(),
            'Content-Disposition' => $this->headerLine('Content-Disposition')
                ?? "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array<string, string|string[]>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    private function headerLine(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }
}
