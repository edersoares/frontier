<?php

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\FrontierServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FrontierServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'frontier' => [
                'http' => [
                    'type' => 'http',
                    'endpoint' => 'http',
                    'view' => 'http://localhost',
                    'cache' => false,
                    'proxy' => [
                        'proxy-uri'
                    ],
                ],
                'http-with-cache' => [
                    'type' => 'http',
                    'endpoint' => 'http-with-cache',
                    'view' => 'http://localhost',
                    'cache' => true,
                ],
                'view' => [
                    'type' => 'view',
                    'endpoint' => 'view',
                    'view' => 'frontier::index',
                    'views' => [
                        'frontier' => __DIR__ . '/../resources/html',
                    ],
                ],
            ],
        ]);
    }
}
