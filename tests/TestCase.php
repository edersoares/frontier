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
}
