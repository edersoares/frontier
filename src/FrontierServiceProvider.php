<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Support\ServiceProvider;

class FrontierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/frontier.php', 'frontier');
    }

    public function boot(): void
    {
        foreach (config('frontier') as $config) {
            foreach ($config['views'] ?? [] as $namespace => $path) {
                $this->loadViewsFrom($path, $namespace);
            }

            foreach ($config['publishes'] ?? [] as $groups => $paths) {
                $this->publishes($paths, $groups);
            }
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/frontier.php');
    }
}
