<?php

namespace HtmlToPdfApi;

/**
 * Immutable configuration value object passed to the Client.
 *
 * Validation happens up front so misconfiguration surfaces at boot time,
 * not on the first API call.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.htmltopdfapi.co/api/v1';

    public const DEFAULT_TIMEOUT_SECONDS = 60;

    public readonly string $apiKey;

    public readonly string $baseUrl;

    public readonly int $timeout;

    /**
     * @param  array{api_key?: string|null, base_url?: string|null, timeout?: int|null}  $config
     */
    public function __construct(array $config)
    {
        $apiKey = $config['api_key'] ?? null;
        if (empty($apiKey) || ! is_string($apiKey)) {
            throw new \InvalidArgumentException(
                'HtmlToPdf API key is required. Pass it as `api_key` or set HTML_TO_PDF_API_KEY in your environment.'
            );
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($config['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = (int) ($config['timeout'] ?? self::DEFAULT_TIMEOUT_SECONDS);
    }
}
