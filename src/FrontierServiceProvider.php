<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\ServiceProvider;

class FrontierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/frontier.php', 'frontier');

        $this->allowedFrontends();
        PreventRequestForgery::except(['/api/*']);
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

    protected function allowedFrontends(): void
    {
        $this->app->booted(function () {
            $kernel = $this->app->make(HttpKernel::class);

            if (method_exists($kernel, 'addToMiddlewarePriorityBefore')) {
                $kernel->prependMiddleware(AllowedFrontendsMiddleware::class);

                if ($kernel->hasMiddleware(HandleCors::class)) {
                    $kernel->addToMiddlewarePriorityBefore(HandleCors::class, AllowedFrontendsMiddleware::class);
                }
            }

            if (method_exists($kernel, 'prependMiddlewareToGroup')) {
                $kernel->prependMiddlewareToGroup('api', AcceptJsonMiddleware::class);
            }
        });
    }
}
