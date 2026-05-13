<?php

namespace HtmlToPdfApi\Tests;

use HtmlToPdfApi\Laravel\HtmlToPdfServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [HtmlToPdfServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('html-to-pdf.api_key', 'sk_test_orchestra');
    }
}
