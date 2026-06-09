<?php

namespace HtmlToPdfApi\Tests\Feature;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use HtmlToPdfApi\Client;
use HtmlToPdfApi\Config;
use HtmlToPdfApi\Exceptions\AuthenticationException;
use HtmlToPdfApi\Exceptions\ConnectionException;
use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use HtmlToPdfApi\Exceptions\ServerException;
use HtmlToPdfApi\Exceptions\UsageLimitException;
use HtmlToPdfApi\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private MockHandler $mock;

    /** @var Request[] */
    private array $sentRequests = [];

    private Client $client;

    protected function setUp(): void
    {
        $this->mock = new MockHandler;
        $this->sentRequests = [];

        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->sentRequests));

        $this->client = new Client(
            new Config(['api_key' => 'sk_test_xyz']),
            $stack,
        );
    }

    public function test_posts_to_pdf_generate_with_payload_as_json(): void
    {
        $this->mock->append(new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-bytes'));

        $this->client->generatePdf(['html' => '<h1>Hi</h1>', 'paper_size' => 'a4']);

        $this->assertCount(1, $this->sentRequests);
        $request = $this->sentRequests[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/pdf/generate', (string) $request->getUri());
        $this->assertSame(
            ['html' => '<h1>Hi</h1>', 'paper_size' => 'a4'],
            json_decode((string) $request->getBody(), true),
        );
    }

    public function test_sends_bearer_token_authorization_header(): void
    {
        $this->mock->append(new Response(200, [], '%PDF'));
        $this->client->generatePdf(['html' => '<h1>Hi</h1>']);

        $auth = $this->sentRequests[0]['request']->getHeaderLine('Authorization');
        $this->assertSame('Bearer sk_test_xyz', $auth);
    }

    public function test_returns_pdf_response_on_2xx(): void
    {
        $this->mock->append(new Response(
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="x.pdf"'],
            '%PDF-content',
        ));

        $pdf = $this->client->generatePdf(['html' => '<h1>Hi</h1>']);

        $this->assertSame('%PDF-content', $pdf->bytes());
        $this->assertSame('x.pdf', $pdf->filename());
    }

    public function test_401_throws_authentication_exception(): void
    {
        $this->mock->append(new Response(401, [], json_encode(['message' => 'Invalid API credentials.'])));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API credentials.');
        $this->client->generatePdf(['html' => 'x']);
    }

    public function test_422_throws_validation_exception_with_errors_map(): void
    {
        $body = json_encode([
            'status' => 'error',
            'message' => 'The given data was invalid.',
            'errors' => [
                'margin_top' => ['margin_top must be between 0 and 250 mm.'],
                'paper_size' => ['Paper size must be one of: a4, a3, …'],
            ],
        ]);
        $this->mock->append(new Response(422, [], $body));

        try {
            $this->client->generatePdf(['html' => 'x', 'margin_top' => 999]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('The given data was invalid.', $e->getMessage());
            $this->assertSame(
                ['margin_top must be between 0 and 250 mm.'],
                $e->errors()['margin_top'],
            );
            $this->assertSame(
                'margin_top must be between 0 and 250 mm.',
                $e->errorFor('margin_top'),
            );
        }
    }

    public function test_429_throws_usage_limit_exception(): void
    {
        $this->mock->append(new Response(429, [], json_encode(['message' => 'Quota exceeded.'])));

        $this->expectException(UsageLimitException::class);
        $this->expectExceptionMessage('Quota exceeded.');
        $this->client->generatePdf(['html' => 'x']);
    }

    public function test_5xx_throws_server_exception(): void
    {
        $this->mock->append(new Response(500, [], json_encode(['message' => 'Server boom'])));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server boom');
        $this->client->generatePdf(['html' => 'x']);
    }

    public function test_unexpected_status_throws_base_exception(): void
    {
        $this->mock->append(new Response(418, [], json_encode(['message' => "I'm a teapot"])));

        $this->expectException(HtmlToPdfException::class);
        $this->expectExceptionMessage("I'm a teapot");
        $this->client->generatePdf(['html' => 'x']);
    }

    public function test_connection_error_throws_connection_exception(): void
    {
        $this->mock->append(new ConnectException(
            'Connection refused',
            new Request('POST', 'https://platform.htmltopdfapi.co/api/v1/pdf/generate'),
        ));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection refused');
        $this->client->generatePdf(['html' => 'x']);
    }

    public function test_empty_error_body_still_throws_with_default_message(): void
    {
        $this->mock->append(new Response(503, [], ''));

        try {
            $this->client->generatePdf(['html' => 'x']);
            $this->fail('Expected ServerException.');
        } catch (ServerException $e) {
            $this->assertStringContainsString('HTTP 503', $e->getMessage());
        }
    }
}
