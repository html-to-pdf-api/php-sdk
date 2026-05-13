# Error handling

Every SDK exception extends `HtmlToPdfApi\Exceptions\HtmlToPdfException`. Catch the most specific subclass you can react to; let the rest bubble up.

## Exception hierarchy

```
HtmlToPdfException                base — catch this for "anything"
├── ConnectionException           DNS / TCP / TLS / timeout — usually retryable
├── AuthenticationException       401 — API key bad; do NOT retry blindly
├── ValidationException           422 — has ->errors() field-name → messages map
├── UsageLimitException           429 — plan quota; wait or upgrade
└── ServerException               5xx — usually retryable with backoff
```

## Pattern: surface validation errors

```php
use HtmlToPdfApi\Exceptions\ValidationException;

try {
    $pdf = HtmlToPdf::html($html)
        ->margins(top: $userInput['margin'])
        ->generate();
} catch (ValidationException $e) {
    // The API rejected one or more fields.
    return response()->json([
        'errors' => $e->errors(),     // ['margin_top' => ['must be between 0 and 250 mm']]
    ], 422);
}
```

The `errors()` method returns the same field-name → string-array map as Laravel's own validator, so it slots into existing form-error flows.

## Pattern: retry transient failures

```php
use HtmlToPdfApi\Exceptions\{ConnectionException, ServerException};

$attempts = 0;
retry:
try {
    return HtmlToPdf::html($html)->generate();
} catch (ConnectionException | ServerException $e) {
    if (++$attempts >= 3) {
        throw $e;
    }
    usleep(($attempts ** 2) * 500_000); // 0.5s, 2s
    goto retry;
}
```

(Or use Laravel's `retry()` helper.)

## Pattern: alert on auth + quota

```php
use HtmlToPdfApi\Exceptions\{AuthenticationException, UsageLimitException};

try {
    $pdf = HtmlToPdf::html($html)->generate();
} catch (AuthenticationException $e) {
    // Operator needs to rotate the key. Page someone.
    Bugsnag::notifyError('htmltopdf-auth', $e->getMessage());
    throw $e;
} catch (UsageLimitException $e) {
    // Customer hit their plan quota.
    return view('errors.quota-exceeded');
}
```

## Pattern: catch everything

When you don't need to differentiate:

```php
use HtmlToPdfApi\Exceptions\HtmlToPdfException;

try {
    $pdf = HtmlToPdf::html($html)->generate();
} catch (HtmlToPdfException $e) {
    Log::error('PDF generation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Could not generate PDF'], 500);
}
```

## Client-side validation

Many obvious mistakes fail with `InvalidArgumentException` **before** the HTTP round-trip:

```php
HtmlToPdf::html('...')->paperSize('a99');       // InvalidArgumentException
HtmlToPdf::html('...')->deviceScaleFactor(4);    // InvalidArgumentException
HtmlToPdf::html('...')->margins(top: 500);       // InvalidArgumentException
HtmlToPdf::html('...')->waitForSelector('<x>');  // InvalidArgumentException
```

These are PHP standard `\InvalidArgumentException`s — not subclasses of `HtmlToPdfException` — because they fire before any API call has been attempted. Catch them separately if you want to distinguish "your code is wrong" from "the API is sad".
