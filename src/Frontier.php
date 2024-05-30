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
            'proxy' => self::proxy($config),
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

    private static function proxy(array $config): void
    {
        $host = $config['host'] ?? '';
        $rules = $config['rules'] ?? [];

        foreach ($rules as $rule) {
            $segments = explode('::', $rule);

            $url = $host;
            $uri = $segments[0];
            $replaces = [];
            $proxyAll = true;

            foreach ($segments as $segment) {
                if ($segment === 'exact') {
                    $proxyAll = false;
                }

                if (str_starts_with($segment, 'replace(') && str_ends_with($segment, ')')) {
                    $replace = substr($segment, 8, -1);

                    [$search, $replace] = explode(',', $replace . ',');

                    if (empty($replace)) {
                        $replace = $url . $search;
                    }

                    $replaces[$search] = $replace;
                }
            }

            if ($proxyAll) {
                $url = $url . $uri;
                $uri .= '/{uri?}';
            }

            $uri = str_replace('//', '/', $uri);

            Route::get($uri, FrontendProxyController::class)
                ->where('uri', '.*')
                ->setDefaults([
                    'uri' => $uri,
                    'config' => [
                        'url' => $url,
                        'replaces' => $replaces,
                    ],
                ]);
        }
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
