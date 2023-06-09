<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class FrontierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/frontier.php', 'frontier');

        foreach ($this->app['config']->get('frontier') as $config) {
            $this->frontend($config);

            foreach ($config['views'] ?? [] as $namespace => $path) {
                $this->loadViewsFrom($path, $namespace);
            }

            foreach ($config['publishes'] ?? [] as $groups => $paths) {
                $this->publishes($paths, $groups);
            }
        }
    }

    private function frontend($config): void
    {
        Route::get($config['endpoint'] . '/{uri?}', $this->getControllerFromType($config['type']))
            ->middleware($config['middleware'] ?? [])
            ->where('uri', '.*')
            ->setDefaults([
                'uri' => '',
                'config' => $config,
            ]);

        foreach ($config['proxy'] ?? [] as $uri) {
            Route::get($uri, $this->getControllerFromType('proxy'))
                ->setDefaults([
                    'uri' => $uri,
                    'config' => $config,
                ]);
        }
    }

    private function getControllerFromType(string $type): string
    {
        return match ($type) {
            'http' => FrontendHttpController::class,
            'proxy' => FrontendProxyController::class,
            'view' => FrontendViewController::class,
            default => throw new InvalidArgumentException('Unknown controller type'),
        };
    }
}
