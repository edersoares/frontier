<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class Frontier
{
    public static function add(array $config): void
    {
        match ($config['type']) {
            'http' => self::http($config),
            'view' => self::view($config),
        };
    }

    public static function addFromConfig(string $key): void
    {
        self::add(config($key, []));
    }

    private static function http(array $config): void
    {
        self::frontend($config);
    }

    private static function view(array $config): void
    {
        self::frontend($config);
    }

    private static function frontend($config): void
    {
        $controller = match ($config['type']) {
            'http' => FrontendHttpController::class,
            'view' => FrontendViewController::class,
            default => throw new InvalidArgumentException('Unknown controller type'),
        };

        Route::get($config['endpoint'] . '/{uri?}', $controller)
            ->middleware($config['middleware'] ?? [])
            ->where('uri', '.*')
            ->setDefaults([
                'uri' => '',
                'config' => $config,
            ]);
    }
}
