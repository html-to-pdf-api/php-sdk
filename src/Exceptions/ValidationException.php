<?php

namespace HtmlToPdfApi\Exceptions;

/**
 * 422 — request rejected because one or more fields failed server-side
 * validation. Inspect ->errors() to map each field to its messages.
 */
class ValidationException extends HtmlToPdfException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param  array<string, string[]>  $errors  Field-name => messages map, as returned by the API.
     */
    public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Convenience: the first message for the named field, or null.
     */
    public function errorFor(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
