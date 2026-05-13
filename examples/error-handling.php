<?php

/**
 * Comprehensive error-handling pattern. Catch the most specific
 * exception you can react to; HtmlToPdfException covers the rest.
 */

require __DIR__.'/../vendor/autoload.php';

use HtmlToPdfApi\Exceptions\AuthenticationException;
use HtmlToPdfApi\Exceptions\ConnectionException;
use HtmlToPdfApi\Exceptions\HtmlToPdfException;
use HtmlToPdfApi\Exceptions\ServerException;
use HtmlToPdfApi\Exceptions\UsageLimitException;
use HtmlToPdfApi\Exceptions\ValidationException;
use HtmlToPdfApi\HtmlToPdf;

$sdk = new HtmlToPdf(['api_key' => getenv('HTML_TO_PDF_API_KEY')]);

try {
    $pdf = $sdk->html('<h1>Test</h1>')
        ->margins(top: 999)             // intentionally out of range
        ->generate();
} catch (ValidationException $e) {
    // The API rejected one or more fields. Inspect ->errors() to surface
    // them in your UI or logs.
    echo "Bad request:\n";
    foreach ($e->errors() as $field => $messages) {
        echo "  {$field}: ".$messages[0]."\n";
    }
} catch (AuthenticationException $e) {
    // 401 — API key missing, malformed, or revoked. Don't retry.
    error_log("HtmlToPdf auth failed: {$e->getMessage()}");
} catch (UsageLimitException $e) {
    // 429 — plan quota exhausted. Wait for reset or upgrade.
    error_log("HtmlToPdf quota reached: {$e->getMessage()}");
} catch (ConnectionException $e) {
    // DNS/TCP/TLS failure — retrying with backoff is usually safe.
    error_log("HtmlToPdf unreachable: {$e->getMessage()}");
} catch (ServerException $e) {
    // 5xx — try once more, then give up.
    error_log("HtmlToPdf server error: {$e->getMessage()}");
} catch (HtmlToPdfException $e) {
    // Catch-all (and parent class of all of the above).
    error_log("HtmlToPdf failed: {$e->getMessage()}");
}
