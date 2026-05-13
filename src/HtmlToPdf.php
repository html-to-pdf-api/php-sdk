<?php

namespace HtmlToPdfApi;

/**
 * SDK entry point. Construct once per app boot; call ->html() / ->url()
 * to start a builder chain.
 *
 *   $sdk = new HtmlToPdf(['api_key' => 'sk_xxx']);
 *   $pdf = $sdk->html('<h1>Hi</h1>')->margins(top: 25)->generate();
 *
 * In a Laravel app you usually access it via the HtmlToPdf facade instead.
 */
class HtmlToPdf
{
    /**
     * SDK version. Bump on every release; surfaced in the User-Agent header
     * so the API can correlate issues with specific SDK versions.
     */
    public const VERSION = '0.1.0';

    private Client $client;

    /**
     * @param  array{api_key?: string|null, base_url?: string|null, timeout?: int|null}|Config  $config
     */
    public function __construct(array|Config $config, ?Client $client = null)
    {
        $resolved = $config instanceof Config ? $config : new Config($config);
        $this->client = $client ?? new Client($resolved);
    }

    public function html(string $html): PdfBuilder
    {
        return $this->builder()->html($html);
    }

    public function url(string $url): PdfBuilder
    {
        return $this->builder()->url($url);
    }

    /**
     * For advanced use — start an empty builder and configure both source
     * and other fields yourself.
     */
    public function builder(): PdfBuilder
    {
        return new PdfBuilder($this->client);
    }

    public function client(): Client
    {
        return $this->client;
    }
}
