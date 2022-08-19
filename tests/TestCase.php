<?php

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\FrontierServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            FrontierServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'frontier' => [
                'http' => [
                    'type' => 'http',
                    'endpoint' => 'http',
                    'url' => 'http://localhost',
                    'headers' => [],
                    'middleware' => [],
                    'replaces' => [],
                ],
                'view' => [
                    'type' => 'view',
                    'endpoint' => 'view',
                    'view' => 'frontier::index',
                    'views' => [
                        'frontier' => __DIR__ . '/../resources/html',
                    ],
                    'publishes' => [],
                    'middleware' => [],
                    'replaces' => [],
                ],
            ]
        ]);
    }
}
