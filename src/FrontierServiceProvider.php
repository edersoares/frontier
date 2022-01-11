<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FrontierServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/frontier.php', 'frontier');

        foreach ($this->app['config']->get('frontier') as $config) {
            $this->frontend($config);

            foreach ($config['views'] as $namespace => $path) {
                $this->loadViewsFrom($path, $namespace);
            }

            foreach ($config['publishes'] as $groups => $paths) {
                $this->publishes($paths, $groups);
            }
        }
    }

    private function frontend($config)
    {
        return Route::get($config['endpoint'] . '/{uri?}', FrontendController::class)
            ->middleware($config['middleware'] ?? [])
            ->where('uri', '.*')
            ->setDefaults([
                'uri' => '',
                'config' => $config,
            ]);
    }
}
