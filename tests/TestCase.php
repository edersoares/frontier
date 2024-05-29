<?php

declare(strict_types=1);

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
}
