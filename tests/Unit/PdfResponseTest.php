<?php

namespace HtmlToPdfApi\Tests\Unit;

use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use HtmlToPdfApi\PdfResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class PdfResponseTest extends TestCase
{
    private const SAMPLE_PDF_BYTES = "%PDF-1.7\n1 0 obj << /Type /Page >> endobj\n%%EOF\n";

    public function test_bytes_returns_raw_pdf(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $this->assertSame(self::SAMPLE_PDF_BYTES, $response->bytes());
    }

    public function test_size_returns_byte_length(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $this->assertSame(strlen(self::SAMPLE_PDF_BYTES), $response->size());
    }

    public function test_base64_round_trips(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $this->assertSame(self::SAMPLE_PDF_BYTES, base64_decode($response->base64()));
    }

    public function test_filename_parses_content_disposition(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES, [
            'Content-Disposition' => 'inline; filename="invoice-001.pdf"',
        ]);
        $this->assertSame('invoice-001.pdf', $response->filename());
    }

    public function test_filename_handles_unquoted_form(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES, [
            'Content-Disposition' => 'attachment; filename=report.pdf',
        ]);
        $this->assertSame('report.pdf', $response->filename());
    }

    public function test_filename_returns_null_when_header_missing(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $this->assertNull($response->filename());
    }

    public function test_save_as_writes_to_disk(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sdk-test-').'.pdf';
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);

        $this->assertTrue($response->saveAs($path));
        $this->assertFileExists($path);
        $this->assertSame(self::SAMPLE_PDF_BYTES, file_get_contents($path));

        unlink($path);
    }

    public function test_save_as_throws_when_path_not_writable(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);

        $this->expectException(HtmlToPdfException::class);
        $response->saveAs('/this/path/does/not/exist/file.pdf');
    }

    public function test_stream_returns_readable_resource(): void
    {
        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $stream = $response->stream();

        $this->assertIsResource($stream);
        $this->assertSame(self::SAMPLE_PDF_BYTES, stream_get_contents($stream));

        fclose($stream);
    }

    public function test_to_response_requires_laravel(): void
    {
        // Skip when Laravel's response class is autoloadable (Testbench in dev
        // pulls it in). The plain-PHP guard is exercised by other test envs.
        if (class_exists(Response::class)) {
            $this->markTestSkipped('Laravel is loaded in this test environment.');
        }

        $response = new PdfResponse(self::SAMPLE_PDF_BYTES);
        $this->expectException(HtmlToPdfException::class);
        $response->toResponse();
    }
}
