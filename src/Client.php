<?php

namespace HtmlToPdfApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use HtmlToPdfApi\Exceptions\AuthenticationException;
use HtmlToPdfApi\Exceptions\ConnectionException;
use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use HtmlToPdfApi\Exceptions\ServerException;
use HtmlToPdfApi\Exceptions\UsageLimitException;
use HtmlToPdfApi\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin HTTP client wrapper. All Guzzle quirks (RequestException semantics,
 * connect-vs-request errors, status-code branching) are absorbed here so
 * callers only deal with the typed SDK exceptions.
 */
class Client
{
    private GuzzleClient $http;

    public function __construct(
        public readonly Config $config,
        ?HandlerStack $handlerStack = null,
    ) {
        $this->http = new GuzzleClient([
            'base_uri' => $this->config->baseUrl.'/',
            'timeout' => $this->config->timeout,
            'http_errors' => false, // we map status codes ourselves
            'headers' => [
                'Authorization' => 'Bearer '.$this->config->apiKey,
                'Accept' => 'application/pdf, application/json',
                'User-Agent' => 'htmltopdfapi-php-sdk/0.1',
            ],
            'handler' => $handlerStack,
        ]);
    }

    /**
     * POST /pdf/generate. Returns a PdfResponse on success, throws on failure.
     *
     * @param  array<string, mixed>  $payload
     */
    public function generatePdf(array $payload): PdfResponse
    {
        try {
            $response = $this->http->post('pdf/generate', [
                'json' => $payload,
            ]);
        } catch (ConnectException $e) {
            throw new ConnectionException(
                'Could not reach the HTML to PDF API: '.$e->getMessage(),
                0,
                $e,
            );
        } catch (RequestException $e) {
            // Other request-time failures (TLS, etc.) without a response.
            if (! $e->hasResponse()) {
                throw new ConnectionException(
                    'HTTP request failed: '.$e->getMessage(),
                    0,
                    $e,
                );
            }
            $response = $e->getResponse();
        }

        return $this->parseResponse($response);
    }

    private function parseResponse(ResponseInterface $response): PdfResponse
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status >= 200 && $status < 300) {
            return new PdfResponse($body, $this->extractHeaders($response));
        }

        $decoded = $this->decodeJson($body);
        $message = $decoded['message'] ?? ($decoded['error'] ?? "HTTP {$status} from HTML to PDF API");

        throw match (true) {
            $status === 401 => new AuthenticationException($message),
            $status === 422 => new ValidationException($message, $decoded['errors'] ?? []),
            $status === 429 => new UsageLimitException($message),
            $status >= 500 => new ServerException($message),
            default => new HtmlToPdfException("Unexpected {$status} response from HTML to PDF API: {$message}"),
        };
    }

    /**
     * @return array<string, string>
     */
    private function extractHeaders(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
