<?php

namespace HtmlToPdfApi\Laravel;

use HtmlToPdfApi\HtmlToPdf;
use Illuminate\Support\ServiceProvider;

class HtmlToPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/html-to-pdf.php',
            'html-to-pdf'
        );

        $this->app->singleton(HtmlToPdf::class, function ($app) {
            return new HtmlToPdf([
                'api_key' => $app['config']->get('html-to-pdf.api_key'),
                'base_url' => $app['config']->get('html-to-pdf.base_url'),
                'timeout' => $app['config']->get('html-to-pdf.timeout'),
            ]);
        });

        $this->app->alias(HtmlToPdf::class, 'html-to-pdf');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/html-to-pdf.php' => config_path('html-to-pdf.php'),
            ], 'html-to-pdf-config');
        }
    }
}
